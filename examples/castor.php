<?php

namespace examples;

use Castor\Attribute\AsTask;
// new part for importing mykiwi/castor-extended code
use Mykiwi\CastorExtended\Attribute\{Requires, Target};

use function Castor\import;

import('composer://mykiwi/castor-extended');

#[Target(target: __DIR__ . '/hello.txt', deps: __FILE__, update: true)]
function output(): void
{
    file_put_contents('hello.txt', date(\DATE_ATOM));
}

#[AsTask(description: 'Demo: consume mykiwi/castor-extended exactly as the README documents it')]
#[Requires('output')]
function build(): void
{
    echo file_get_contents('hello.txt');
}
