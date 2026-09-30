<?php

// Castor 2.0's default, opt-in since 1.8: resolve relative paths (fs(),
// finder(), plain mkdir()/file_get_contents()) where run() executes rather
// than wherever `castor` was invoked from. The guard keeps an imported or
// mounted castor.php from redefining it.
defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', true);

use Castor\Attribute\AsTask;

use function Castor\run;

// new part for importing mykiwi/castor-extended code
use Mykiwi\CastorExtended\Attribute\{Requires, Target};
use function Castor\import;
import('composer://mykiwi/castor-extended');

#[Target(target: 'vendor', deps: 'composer.lock', update: true)]
function vendor(): void
{
    run('composer install');
}

#[AsTask()]
#[Requires('vendor')]
function thanks(): void
{
    run('composer thanks');
}
