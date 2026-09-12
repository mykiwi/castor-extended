<?php

namespace Mykiwi\CastorExtended;

use Castor\Attribute\AsListener;
use Castor\Event\BeforeExecuteTaskEvent;
use Castor\Event\FunctionsResolvedEvent;
use Mykiwi\CastorExtended\Attribute\Requires;

use function Castor\io;
use function Castor\run;

/**
 * @param string|string[] $target
 * @param string|string[] $prerequisites
 * @param string[]        $recipe
 */
function register_requires(string $name, string|array $target, string|array $prerequisites, array $recipe): void
{
    if (isset(requires_registry()[$name])) {
        throw new \LogicException(\sprintf('Requirement "%s" is already registered. Choose a different name, or remove the duplicate "register_requires(\'%s\', ...)" call.', $name, $name));
    }

    requires_registry()[$name] = ['target' => $target, 'prerequisites' => $prerequisites, 'recipe' => $recipe];
}

/**
 * @return array<string, array{target: string|string[], prerequisites: string|string[], recipe: string[]}>
 */
function &requires_registry(): array
{
    static $registry = [];

    return $registry;
}

/**
 * Fails fast: catches an unregistered #[Requires] name as soon as Castor
 * boots, instead of only when that specific task finally runs. Also warns
 * about registrations no task ever references (likely a typo or leftover).
 */
#[AsListener(event: FunctionsResolvedEvent::class)]
function validate_requires_attributes(FunctionsResolvedEvent $event): void
{
    $registry = requires_registry();
    $unreferenced = array_fill_keys(array_keys($registry), true);

    foreach ($event->taskDescriptors as $taskDescriptor) {
        foreach ($taskDescriptor->function->getAttributes(Requires::class) as $attribute) {
            $name = $attribute->newInstance()->name;

            if (!isset($registry[$name])) {
                throw new \LogicException(\sprintf('Unknown requirement "%s" on task "%s()". Call register_requires("%s", ...) before defining tasks.', $name, $taskDescriptor->function->getName(), $name));
            }

            unset($unreferenced[$name]);
        }
    }

    foreach (array_keys($unreferenced) as $name) {
        io()->warning(\sprintf('Requirement "%s" is registered but no task uses "#[Requires(\'%s\')]".', $name, $name));
    }
}

#[AsListener(event: BeforeExecuteTaskEvent::class)]
function run_requires_attributes(BeforeExecuteTaskEvent $event): void
{
    $registry = requires_registry();

    foreach ($event->task->getAttributes(Requires::class) as $attribute) {
        $requires = $registry[$attribute->newInstance()->name];

        make(
            target: $requires['target'],
            prerequisites: $requires['prerequisites'],
            callback: static fn () => run($requires['recipe']),
        );
    }
}
