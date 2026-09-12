<?php

namespace Mykiwi\CastorExtended\Attribute;

/**
 * References a requirement registered with `register_requires()`: its
 * recipe runs before the task body, only if its target is missing or
 * older than its prerequisites.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::IS_REPEATABLE)]
class Requires
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
