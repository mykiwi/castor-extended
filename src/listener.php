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
 * Resolves #[Target] $name: recursively resolves whatever it #[Requires]
 * first (siblings run concurrently via Castor's Fiber-based `parallel()`,
 * mirroring `make -jN`), then runs its own recipe, only if needed.
 */
function run_target(string $name): void
{
    $resolved = &resolved_targets_registry();

    if (isset($resolved[$name])) {
        return;
    }

    $reflection = targets_registry()[$name];
    $nestedNames = target_requires_names($reflection);

    if ($nestedNames) {
        parallel(...array_map(static fn (string $n): \Closure => static fn () => run_target($n), $nestedNames));
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

    $resolved[$name] = true;
}

#[AsListener(event: BeforeExecuteTaskEvent::class)]
function run_requires_attributes(BeforeExecuteTaskEvent $event): void
{
    $names = target_requires_names($event->task);

    if ($names) {
        parallel(...array_map(static fn (string $n): \Closure => static fn () => run_target($n), $names));
    }
}
