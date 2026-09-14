<?php

use Castor\Attribute\AsTask;

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
