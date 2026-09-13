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
 * A $prerequisites path that doesn't exist, or a glob pattern matching no
 * file, throws InvalidArgumentException rather than silently treating the
 * target as up to date.
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

    // Prerequisites first, whatever the targets' state: a typo'd path must
    // fail on the very first run, with a message naming it, not on the
    // second one as a bare filemtime() warning.
    $prerequisiteMtimes = iterator_to_array(
        make_expand($prerequisites instanceof Finder ? $prerequisites : array_map($resolve, (array) $prerequisites)),
        false,
    );

    if ([] === $prerequisiteMtimes) {
        throw new \InvalidArgumentException('At least one prerequisite must be given, none was found.');
    }

    foreach ($targets as $t) {
        if (!file_exists($t)) {
            $callback();

            return true;
        }
    }

    $oldestTarget = min(array_map(static fn (string $t): int => (int) filemtime($t), $targets));

    if (max($prerequisiteMtimes) > $oldestTarget) {
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
 * @param string[]|Finder $prerequisites absolute paths, or shell-style patterns
 *                                       (`*`, `?`, `[...]`, `{a,b}`; no recursive `**`)
 *
 * @return \Generator<int, int> mtimes
 */
function make_expand(array|Finder $prerequisites): \Generator
{
    if ($prerequisites instanceof Finder) {
        foreach ($prerequisites as $file) {
            yield $file->getMTime();
        }

        return;
    }

    foreach ($prerequisites as $pattern) {
        if (!preg_match('/[*?\[{]/', $pattern)) {
            if (!file_exists($pattern)) {
                throw new \InvalidArgumentException(\sprintf('Prerequisite "%s" does not exist.', $pattern));
            }

            yield (int) filemtime($pattern);

            continue;
        }

        $files = glob($pattern, \defined('GLOB_BRACE') ? \GLOB_BRACE : 0) ?: [];

        if ([] === $files) {
            throw new \InvalidArgumentException(\sprintf('Prerequisite pattern "%s" matched no file.', $pattern));
        }

        foreach ($files as $file) {
            yield (int) filemtime($file);
        }
    }
}
