<?php

namespace Mykiwi\CastorExtended;

use Castor\Attribute\AsListener;
use Castor\Console\Command\TaskCommand;
use Castor\Event\AfterBootEvent;
use Castor\Event\BeforeExecuteTaskEvent;
use Mykiwi\CastorExtended\Attribute\Requires;
use Mykiwi\CastorExtended\Attribute\Target;

use function Castor\io;
use function Castor\parallel;

/**
 * @return array<string, \ReflectionFunction>
 */
function &targets_registry(): array
{
    static $registry = [];

    return $registry;
}

/**
 * Names of #[Target]s already resolved (built or confirmed fresh) during
 * this process, so a target required by several others only runs once.
 *
 * @return array<string, true>
 */
function &resolved_targets_registry(): array
{
    static $resolved = [];

    return $resolved;
}

/**
 * Names of #[Target]s currently being resolved, so a concurrent resolution
 * of the same name (e.g. two siblings run in parallel both #[Requires] it)
 * waits for the in-flight one instead of running the recipe twice.
 *
 * @return array<string, true>
 */
function &pending_targets_registry(): array
{
    static $pending = [];

    return $pending;
}

/**
 * Runs $build for $name at most once per process, even if called again
 * for the same $name while the first call is still in-flight (including
 * across Fibers suspended mid-build, e.g. while a subprocess is running).
 * A concurrent caller instead busy-waits, via Fiber::suspend(), until the
 * in-flight call finishes.
 *
 * If $build throws, the name is left unresolved and un-pending: a waiter
 * simply returns, without re-running $build itself, on the assumption
 * that the failure will already surface from the original caller.
 */
function resolve_once(string $name, callable $build): void
{
    $resolved = &resolved_targets_registry();
    $pending = &pending_targets_registry();

    if (isset($resolved[$name])) {
        return;
    }

    if (isset($pending[$name])) {
        while (isset($pending[$name]) && !isset($resolved[$name])) {
            if (!\Fiber::getCurrent()) {
                break;
            }

            \Fiber::suspend();
        }

        return;
    }

    $pending[$name] = true;

    try {
        $build();

        $resolved[$name] = true;
    } finally {
        unset($pending[$name]);
    }
}

/**
 * @return string[]
 */
function target_requires_names(\ReflectionFunction|TaskCommand $reflection): array
{
    return array_map(
        static fn (\ReflectionAttribute $attribute): string => $attribute->newInstance()->name,
        $reflection->getAttributes(Requires::class),
    );
}

/**
 * Discovers every function carrying `#[Target]` and indexes it by name
 * (the attribute's `name`, or the function's own name).
 *
 * Uses AfterBootEvent, not FunctionsResolvedEvent: the latter fires once per
 * mount, and this listener only exists once *this* package's own mount has
 * loaded — by then, functions from an earlier mount (e.g. the consuming
 * project's own castor.php) already had their FunctionsResolvedEvent come
 * and go unseen. AfterBootEvent fires once, after every mount is loaded.
 */
#[AsListener(event: AfterBootEvent::class)]
function collect_targets(AfterBootEvent $event): void
{
    $registry = &targets_registry();

    foreach (get_defined_functions()['user'] as $function) {
        $reflection = new \ReflectionFunction($function);

        foreach ($reflection->getAttributes(Target::class) as $attribute) {
            $name = $attribute->newInstance()->name ?? $reflection->getShortName();

            if (isset($registry[$name])) {
                throw new \LogicException(\sprintf('Target "%s" is already registered by "%s()". Give one of them a different #[Target(name: ...)], or remove the duplicate.', $name, $registry[$name]->getName()));
            }

            $registry[$name] = $reflection;
        }
    }
}

/**
 * Fails fast: catches an unregistered #[Requires] name (on a task, or
 * nested on another #[Target] function) as soon as Castor boots, instead of
 * only when that specific recipe finally runs. Also catches a circular
 * #[Requires] chain, and warns about #[Target] functions nothing ever
 * references (likely a typo or leftover).
 */
#[AsListener(event: AfterBootEvent::class)]
function validate_requires_attributes(AfterBootEvent $event): void
{
    $registry = targets_registry();
    $unreferenced = array_fill_keys(array_keys($registry), true);

    $checkNames = static function (array $names, string $describedAs) use ($registry, &$unreferenced): void {
        foreach ($names as $name) {
            if (!isset($registry[$name])) {
                throw new \LogicException(\sprintf('Unknown target "%s" on %s. Define a function with #[Target] (name: "%s") before defining tasks.', $name, $describedAs, $name));
            }

            unset($unreferenced[$name]);
        }
    };

    foreach ($event->application->all() as $command) {
        if ($command instanceof TaskCommand) {
            $checkNames(target_requires_names($command), \sprintf('task "%s"', $command->getName()));
        }
    }

    foreach ($registry as $name => $reflection) {
        $checkNames(target_requires_names($reflection), \sprintf('target "%s"', $name));
    }

    foreach (array_keys($registry) as $name) {
        validate_no_requires_cycle($registry, $name, []);
    }

    foreach (array_keys($unreferenced) as $name) {
        io()->warning(\sprintf('Target "%s" is registered but no task uses "#[Requires(\'%s\')]".', $name, $name));
    }
}

/**
 * @param array<string, \ReflectionFunction> $registry
 * @param string[]                           $path     names visited so far, in order
 */
function validate_no_requires_cycle(array $registry, string $name, array $path): void
{
    if (\in_array($name, $path, true)) {
        throw new \LogicException(\sprintf('Circular #[Requires] chain: %s.', implode(' -> ', [...$path, $name])));
    }

    foreach (target_requires_names($registry[$name]) as $nestedName) {
        validate_no_requires_cycle($registry, $nestedName, [...$path, $name]);
    }
}

/**
 * Resolves each of $names concurrently, via Castor's Fiber-based
 * `parallel()`, mirroring `make -jN`.
 *
 * @param string[] $names
 */
function run_targets_in_parallel(array $names): void
{
    parallel(...array_map(static fn (string $n): \Closure => static fn () => run_target($n), $names));
}

/**
 * Resolves #[Target] $name: recursively resolves whatever it #[Requires]
 * first, then runs its own recipe, only if needed. Guarded by
 * resolve_once() so a target required by several concurrently-run
 * siblings still only runs once.
 */
function run_target(string $name): void
{
    resolve_once($name, static function () use ($name): void {
        $reflection = targets_registry()[$name];
        $nestedNames = target_requires_names($reflection);

        if ($nestedNames) {
            run_targets_in_parallel($nestedNames);
        }

        $target = $reflection->getAttributes(Target::class)[0]->newInstance();
        $targetPath = $target->target ?? $name;

        make(
            target: $targetPath,
            prerequisites: $target->deps,
            context: $target->context,
            callback: static function () use ($reflection, $target, $targetPath): void {
                $reflection->invoke();

                if ($target->update) {
                    foreach ((array) $targetPath as $t) {
                        touch(make_absolute($t, $target->context));
                    }
                }
            },
        );
    });
}

#[AsListener(event: BeforeExecuteTaskEvent::class)]
function run_requires_attributes(BeforeExecuteTaskEvent $event): void
{
    $names = target_requires_names($event->task);

    if ($names) {
        run_targets_in_parallel($names);
    }
}
