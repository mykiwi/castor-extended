<?php

namespace Mykiwi\CastorExtended;

use Castor\Context;
use Symfony\Component\Finder\Finder;

use function Castor\context;

/**
 * Run $callback only if $target is missing or older than $prerequisites,
 * mirroring a Makefile's target/prerequisite/recipe logic. See
 * `make_absolute()` for how a relative path resolves.
 *
 * @param string|string[]        $target
 * @param string|string[]|Finder $prerequisites
 */
function make(string|array $target, string|array|Finder $prerequisites, callable $callback, ?Context $context = null): bool
{
    $resolve = static fn (string $path): string => make_absolute($path, $context);

    $targets = array_map($resolve, (array) $target);

    if ([] === $targets) {
        throw new \InvalidArgumentException('At least one target must be given.');
    }

    foreach ($targets as $t) {
        if (!file_exists($t)) {
            $callback();

            return true;
        }
    }

    $prerequisites = $prerequisites instanceof Finder ? $prerequisites : array_map($resolve, (array) $prerequisites);

    $prerequisiteMtimes = iterator_to_array(make_expand($prerequisites));

    if ([] === $prerequisiteMtimes) {
        throw new \InvalidArgumentException('At least one prerequisite must be given, none was found.');
    }

    $oldestTarget = min(array_map(static fn (string $t): int => (int) filemtime($t), $targets));
    $newestPrerequisite = max($prerequisiteMtimes);

    if ($newestPrerequisite > $oldestTarget) {
        $callback();

        return true;
    }

    return false;
}

/**
 * Resolves a relative path against $context's working directory (the
 * current Castor context by default), not PHP's own cwd: Castor never
 * chdir()s the process itself, so a relative path would otherwise silently
 * break when a task runs from a subdirectory of the project.
 */
function make_absolute(string $path, ?Context $context = null): string
{
    if (str_starts_with($path, '/')) {
        return $path;
    }

    return ($context ?? context())->workingDirectory . '/' . $path;
}

/**
 * @param string|string[]|Finder $prerequisites
 *
 * @return \Generator<int, int>
 */
function make_expand(string|array|Finder $prerequisites): \Generator
{
    if ($prerequisites instanceof Finder) {
        foreach ($prerequisites as $file) {
            yield $file->getMTime();
        }

        return;
    }

    foreach ((array) $prerequisites as $pattern) {
        $files = str_contains($pattern, '*') ? glob($pattern) : [$pattern];

        foreach ($files ?: [] as $file) {
            yield (int) filemtime($file);
        }
    }
}
