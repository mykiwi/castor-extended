<?php

namespace Mykiwi\CastorExtended\Attribute;

use Castor\Context;
use Symfony\Component\Finder\Finder;

/**
 * Marks a function as a build recipe: its body runs only if $target is
 * missing or older than $deps, mirroring a Makefile rule. Referenced from a
 * task via `#[Requires('name')]`, where 'name' defaults to the function's
 * own name.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
class Target
{
    /**
     * @param string|string[]|Finder $deps
     * @param string|string[]|null   $target  defaults to the resolved $name
     * @param bool                   $update  force-touch $target after the
     *                                        recipe runs, for targets whose
     *                                        mtime the recipe doesn't
     *                                        reliably bump on its own (e.g.
     *                                        a directory)
     * @param Context|null           $context resolves a relative $target/
     *                                        $deps path; defaults to the
     *                                        current Castor context
     */
    public function __construct(
        public readonly string|array|Finder $deps,
        public readonly string|array|null $target = null,
        public readonly ?string $name = null,
        public readonly bool $update = false,
        public readonly ?Context $context = null,
    ) {
    }
}
