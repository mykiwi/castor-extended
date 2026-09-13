<?php

namespace examples;

use Castor\Attribute\AsTask;

use function Castor\import;
use function Castor\io;
use function Mykiwi\CastorExtended\make;

import('composer://mykiwi/castor-extended', file: 'src/functions.php');

#[AsTask(description: 'Demo: consume mykiwi/castor-extended exactly as the README documents it')]
function build(): void
{
    $ran = make(
        target: __DIR__ . '/output.txt',
        prerequisites: __FILE__,
        callback: static fn () => file_put_contents(__DIR__ . '/output.txt', "built\n"),
    );

    io()->writeln($ran ? 'output.txt built.' : 'output.txt is up to date.');
}
