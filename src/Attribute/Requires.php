<?php

namespace Mykiwi\CastorExtended\Attribute;

/**
 * References a function decorated with `#[Target]` by name: its body runs
 * before the task's, only if its target is missing or older than its deps.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::IS_REPEATABLE)]
class Requires
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
