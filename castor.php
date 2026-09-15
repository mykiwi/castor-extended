<?php

// Entrypoint for `import('composer://mykiwi/castor-extended')`. Castor also
// runs this automatically for any regular Composer dependency, so when a
// consumer requires this package directly *and* it's the project's own
// castor.php (e.g. this repo's own root, or a build that bundles the
// package), both copies get executed — guard against redeclaring src/'s
// functions the second time.
use function Castor\import;

if (!function_exists('Mykiwi\CastorExtended\make')) {
    import(__DIR__ . '/src');
}
