<?php

namespace Mykiwi\CastorExtended;

use Mykiwi\CastorExtended\Attribute\Target;

/**
 * Everything boot-time validation and run_target() need to know about one
 * `#[Target]` function, read from its attributes exactly once at boot.
 */
final class TargetDescriptor
{
    /**
     * @param list<string> $requires names of the #[Target]s this one #[Requires]
     */
    public function __construct(
        public readonly string $name,
        public readonly \ReflectionFunction $function,
        public readonly Target $target,
        public readonly array $requires,
    ) {
    }
}
