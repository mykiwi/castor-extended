<?php

namespace examples;

use Castor\Attribute\AsTask;

use function Castor\io;
use function Mykiwi\CastorExtended\make;

require_once __DIR__ . '/../src/make.php';

#[AsTask(description: 'Demo: rebuild the target only if missing or older than its prerequisite')]
function build(): void
{
    $ran = make(
        target: __DIR__ . '/build/output.txt',
        prerequisites: __DIR__ . '/input.txt',
        callback: static function () {
            io()->writeln('input.txt changed (or output.txt missing), rebuilding...');
            @mkdir(__DIR__ . '/build');
            file_put_contents(__DIR__ . '/build/output.txt', file_get_contents(__DIR__ . '/input.txt'));
        },
    );

    if (!$ran) {
        io()->writeln('output.txt is up to date, skipping.');
    }
}
