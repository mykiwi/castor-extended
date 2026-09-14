<?php

namespace Mykiwi\CastorExtended\Attribute;

use Castor\Context;

/**
 * Marks a function as a build recipe: its body runs only if $target is missing or older than $deps, mirroring a Makefile rule. Referenced from a task, or from another #[Target] function, via `#[Requires('name')]` — repeatable, and resolved concurrently when several are independent (Castor's Fiber-based `parallel()`, like `make -jN`).
 *
 * A recipe that returns without having created $target is an error, not a silent no-op: a broken build never gets mistaken for a fresh one on the next run. An unknown #[Requires] name, a circular #[Requires] chain, or two #[Target] functions sharing a name, all fail as soon as Castor boots (any `castor` command), not only when the specific task finally runs.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
class Target
{
    /**
     * @param string|string[]      $deps    file path(s), or shell-style glob pattern(s) (`*`, `?`, `[...]`, `{a,b}`; `**` is not recursive). Attribute arguments must be constant expressions, so a Symfony `Finder` isn't accepted here — call `make()` directly (from the recipe body, or a plain task) if you need one. A missing path, or a pattern matching no file, fails immediately
     * @param string|string[]|null $target  defaults to the resolved $name. A relative path resolves against $context's working directory, not PHP's own cwd
     * @param string|null          $name    name used by `#[Requires]`; defaults to the function's own name
     * @param bool                 $update  force-touch $target after the recipe runs and has confirmed it produced $target, for targets whose mtime the recipe doesn't reliably bump on its own (e.g. a directory) — otherwise it reruns every time
     * @param Context|null         $context resolves a relative $target/$deps path; defaults to the current Castor context
     */
    public function __construct(
        public readonly string|array $deps,
        public readonly string|array|null $target = null,
        public readonly ?string $name = null,
        public readonly bool $update = false,
        public readonly ?Context $context = null,
    ) {
    }
}
