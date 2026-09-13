<?php

namespace Mykiwi\CastorExtended;

use Castor\Attribute\AsListener;
use Castor\Console\Command\TaskCommand;
use Castor\Event\AfterBootEvent;
use Castor\Event\BeforeExecuteTaskEvent;
use Mykiwi\CastorExtended\Attribute\Requires;
use Mykiwi\CastorExtended\Attribute\Target;

use function Castor\io;

/**
 * @return array<string, \ReflectionFunction>
 */
function &targets_registry(): array
{
    static $registry = [];

    return $registry;
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
 * Fails fast: catches an unregistered #[Requires] name as soon as Castor
 * boots, instead of only when that specific task finally runs. Also warns
 * about #[Target] functions no task ever references (likely a typo or
 * leftover).
 */
#[AsListener(event: AfterBootEvent::class)]
function validate_requires_attributes(AfterBootEvent $event): void
{
    $registry = targets_registry();
    $unreferenced = array_fill_keys(array_keys($registry), true);

    foreach ($event->application->all() as $command) {
        if (!$command instanceof TaskCommand) {
            continue;
        }

        foreach ($command->getAttributes(Requires::class) as $attribute) {
            $name = $attribute->newInstance()->name;

            if (!isset($registry[$name])) {
                throw new \LogicException(\sprintf('Unknown target "%s" on task "%s". Define a function with #[Target] (name: "%s") before defining tasks.', $name, $command->getName(), $name));
            }

            unset($unreferenced[$name]);
        }
    }

    foreach (array_keys($unreferenced) as $name) {
        io()->warning(\sprintf('Target "%s" is registered but no task uses "#[Requires(\'%s\')]".', $name, $name));
    }
}

#[AsListener(event: BeforeExecuteTaskEvent::class)]
function run_requires_attributes(BeforeExecuteTaskEvent $event): void
{
    $registry = targets_registry();

    foreach ($event->task->getAttributes(Requires::class) as $attribute) {
        $name = $attribute->newInstance()->name;
        $reflection = $registry[$name];
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
    }
}
