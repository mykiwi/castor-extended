<?php

use Castor\Attribute\AsTask;
use Mykiwi\CastorExtended\Attribute\Requires;

use function Castor\io;
use function Castor\run;
use function Mykiwi\CastorExtended\register_requires;

require_once __DIR__ . '/src/make.php';
require_once __DIR__ . '/src/Attribute/Requires.php';
require_once __DIR__ . '/src/listener.php';

register_requires(
    name: 'vendor',
    target: __DIR__ . '/vendor/autoload.php',
    prerequisites: __DIR__ . '/composer.lock',
    recipe: ['composer', 'install', '--no-interaction', '--prefer-dist'],
);

#[AsTask(description: 'Run the test suite')]
#[Requires('vendor')]
function test(): void
{
    run(['vendor/bin/phpunit']);
}

#[AsTask(description: 'Run PHPStan static analysis')]
#[Requires('vendor')]
function stan(): void
{
    run(['vendor/bin/phpstan', 'analyse']);
}

#[AsTask(name: 'cs', description: 'Check coding standards (add --fix to apply)')]
#[Requires('vendor')]
function cs(bool $fix = false): void
{
    run(['vendor/bin/php-cs-fixer', 'fix', ...($fix ? [] : ['--dry-run', '--diff'])]);
}

#[AsTask(description: 'Run every check (tests, static analysis, coding standards)')]
#[Requires('vendor')]
function ci(): void
{
    test();
    stan();
    cs();

    io()->success('All checks passed.');
}
