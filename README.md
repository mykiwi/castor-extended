# Castor extended

Reusable helpers for [Castor](https://castor.jolicode.com/) tasks, not (yet)
part of Castor core.

## `make()`

Makefile-style rebuild logic: run a callback only if a target file is missing
or older than its prerequisites.

```php
use function Mykiwi\CastorExtended\make;

$ran = make(
    target: 'vendor/autoload.php',
    prerequisites: 'composer.lock',
    callback: fn () => run(['composer', 'install']),
);
```

- `$target`: `string|string[]`, real file path(s), no glob.
- `$prerequisites`: `string|string[]` (glob patterns supported, e.g.
  `'src/*.php'`) or a Symfony `Finder` instance for recursive matching.
- Returns `true` if the callback ran, `false` if skipped.

Calling `make()` directly in a task works, but repeating the same
target/prerequisites/recipe in every task that needs it gets old fast — see
[`#[Requires]`](#requires) below for a declarative, no-repeat version.

See [`examples/castor.php`](examples/castor.php) for a runnable demo
(`castor build` from the `examples/` directory).

## `#[Requires]`

Declarative wrapper around `make()`: register a rebuild recipe once by name,
then reference it from any task with an attribute instead of calling `make()`
(or a wrapper `install()`) in every task body.

```php
use Castor\Attribute\AsTask;
use Mykiwi\CastorExtended\Attribute\Requires;

use function Castor\run;
use function Mykiwi\CastorExtended\register_requires;

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
```

- Call `register_requires()` once per unique rebuild need, before defining
  any task that references it.
- `#[Requires('name')]` is repeatable — stack several on one task to declare
  several independent rebuild needs.
- An unregistered name fails as soon as Castor boots (any `castor` command),
  not only when the specific task using it finally runs.

## Installation

### As a Castor remote import (recommended for consuming projects)

**1. One-time setup, run once and commit the result** — this writes to
`castor.composer.json` and `castor.composer.lock`:

```bash
castor composer require mykiwi/castor-extended
```

Commit both files. Teammates who pull them don't need to rerun this command:
Castor auto-installs from the lock file on their next `castor` invocation.

> [!NOTE]
> This package is not (yet) published on Packagist. Until it is, register a
> VCS repository first (same one-time-setup step):
>
> ```bash
> castor composer config repositories.mykiwi-castor-extended vcs https://github.com/mykiwi/my-castor-extended
> castor composer require mykiwi/castor-extended:dev-main
> ```

**2. Add the import to `castor.php`, once, and commit it** — pointing at the
package's function entrypoint:

```php
use function Castor\import;

import('composer://mykiwi/castor-extended', file: 'src/functions.php');
```

> [!IMPORTANT]
> The `file: 'src/functions.php'` argument is required. Castor remote
> imports don't go through Composer's autoloader, and omitting `file` makes
> Castor import this repo's own root `castor.php` instead — its `test` /
> `stan` / `cs` / `ci` dev tasks, not this library's functions.

From then on, `make()`, `register_requires()` and `#[Requires]` are available
on every `castor` command — nothing here needs to be repeated per run, only
the setup above (once per machine) and the `import()` line (once, committed).

### As a regular Composer dependency

```bash
composer require mykiwi/castor-extended
```

`make()`, `register_requires()` and `#[Requires]` are autoloaded
automatically (via `composer.json`'s `autoload.files`/PSR-4), no `require`
needed.

## Contributing / local development

A [devenv](https://devenv.sh/) shell (`devenv shell`) provides PHP 8.5,
Composer and Castor with no manual install.

This repo dogfoods its own tooling via a root [`castor.php`](castor.php)
([Castor](https://castor.jolicode.com/) must be installed):

```bash
castor test  # tests (installs dependencies if needed)
castor stan  # static analysis
castor cs    # coding standards (add --fix to apply)
castor ci    # run everything above
```

Equivalent raw commands also work:

```bash
composer install
vendor/bin/phpunit           # tests
vendor/bin/phpstan analyse   # static analysis
vendor/bin/php-cs-fixer fix  # coding standards
```

CI runs the same checks (tests on PHP 8.4/8.5, PHPStan, PHP-CS-Fixer) via
`castor` on every push and pull request. Dependabot keeps Composer and GitHub
Actions dependencies up to date automatically.

Releases follow [Semantic Versioning](https://semver.org/); notable changes
are tracked in [`CHANGELOG.md`](CHANGELOG.md).
