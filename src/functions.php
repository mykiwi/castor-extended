<?php

// Entrypoint for `import('composer://mykiwi/castor-extended', file: 'src/functions.php')`.
//
// Castor remote imports don't go through Composer's autoloader (unlike a
// regular `composer require`), so this file exists purely to require the
// package's functions and attributes directly.

require_once __DIR__ . '/make.php';
require_once __DIR__ . '/Attribute/Requires.php';
require_once __DIR__ . '/Attribute/Target.php';
require_once __DIR__ . '/TargetDescriptor.php';
require_once __DIR__ . '/listener.php';
