<?php

namespace Mykiwi\CastorExtended;

use Symfony\Component\Finder\Finder;

/**
 * Run $callback only if $target is missing or older than $prerequisites,
 * mirroring a Makefile's target/prerequisite/recipe logic.
 *
 * @param string|string[]        $target
 * @param string|string[]|Finder $prerequisites
 */
function make(string|array $target, string|array|Finder $prerequisites, callable $callback): bool
{
    $targets = (array) $target;

    if ([] === $targets) {
        throw new \InvalidArgumentException('At least one target must be given.');
    }

    foreach ($targets as $t) {
        if (!is_file($t)) {
            $callback();

            return true;
        }
    }

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
