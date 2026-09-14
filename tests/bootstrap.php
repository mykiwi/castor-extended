<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Not a require of castor.php: it calls Castor\import(), which needs a
// booted Castor Container (Kernel, EventDispatcher, ...) that doesn't
// exist here, since these tests unit-test listener.php directly without
// spinning up a real `castor` process. Require the same files by hand
// instead, mirroring what a consuming project's castor.php does (see
// README): listener.php's #[AsListener] functions are no longer preloaded
// via Composer's autoload.files, since Castor's own discovery pass never
// sees functions Composer preloaded before it starts loading castor.php.
require_once __DIR__ . '/../src/make.php';
require_once __DIR__ . '/../src/Attribute/Requires.php';
require_once __DIR__ . '/../src/Attribute/Target.php';
require_once __DIR__ . '/../src/TargetDescriptor.php';
require_once __DIR__ . '/../src/listener.php';
