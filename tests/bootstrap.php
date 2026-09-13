<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Mirrors what a consuming project's castor.php must do (see README):
// listener.php's #[AsListener] functions are no longer preloaded via
// Composer's autoload.files, since Castor's own discovery pass never sees
// functions Composer preloaded before it starts loading castor.php.
require_once __DIR__ . '/../src/functions.php';
