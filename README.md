# Castor extended

> ⚠️ Pet project (for now) made with LLMs 🦾

Reusable helpers for [Castor](https://castor.jolicode.com/) tasks, not (yet)
part of Castor core.

## `#[Target]`/`#[Requires]`

Makefile-style rebuild logic: turn a plain function into a rebuild recipe
with `#[Target]`, then run it before a task's own body — but only if needed
— by referencing its name with `#[Requires]`.

```php
use Castor\Attribute\AsTask;
use Mykiwi\CastorExtended\Attribute\Requires;
use Mykiwi\CastorExtended\Attribute\Target;

use function Castor\run;

#[Target(deps: 'composer.lock', update: true)]
function vendor(): void
{
    run(['composer', 'install', '--no-interaction', '--prefer-dist']);
}

#[Target(target: 'public/bundles', deps: 'composer.lock', update: true)]
#[Requires('vendor')]
function assets(): void
{
    run(['bin/console', 'assets:install', 'public']);
}

#[AsTask(description: 'Start the local dev server')]
#[Requires('assets')]
function serve(): void
{
    run(['symfony', 'serve']);
}
```

- `deps`: `string|string[]` file path(s) (glob supported, e.g. `'src/*.php'`,
  or a Symfony `Finder` instance). The recipe runs only if `target` is
  missing or older than `deps`. A relative path resolves against the
  current Castor context's working directory (project root by default) —
  not PHP's own cwd, which stays wherever `castor` was invoked from. Pass an
  explicit `#[Target(..., context: new Context(...))]` to override it.
- `target` defaults to the resolved name (here `vendor`); pass an explicit
  path to check something more precise instead (e.g. a file inside it).
- `update` (default `false`): touch `target` right after the recipe runs.
  Turn on when the recipe doesn't bump `target`'s own mtime on its own
  (typically a directory target, as above) — otherwise it reruns every time.
- The name used by `#[Requires]` defaults to the function's own name (here
  `vendor`); pass `#[Target(..., name: 'other')]` to override it.
- `#[Requires('name')]` is repeatable — stack several on one task (or on
  another `#[Target]` function, as `assets` does above) to declare several
  rebuild needs. Independent ones run concurrently (Castor's Fiber-based
  `parallel()`, like `make -jN`); `assets` still waits for `vendor` to finish
  first, since it declares that dependency itself.
- An unknown name, a circular `#[Requires]` chain, or two `#[Target]`
  functions sharing a name, fails as soon as Castor boots (any `castor`
  command), not only when the specific task
  using it finally runs.
- A recipe that finishes without creating `target` is an error, not a
  silent no-op: a broken build never gets mistaken for a fresh one on the
  next run.

## `make()`

The low-level primitive behind `#[Target]`: run a callback only if a target
file is missing or older than its prerequisites. Use it directly for a
one-off rebuild that no other task needs to share.

```php
use function Mykiwi\CastorExtended\make;

$ran = make(
    target: 'vendor/autoload.php',
    prerequisites: 'composer.lock',
    callback: fn () => run(['composer', 'install']),
);
```

Returns `true` if the callback ran, `false` if skipped. See
[`examples/castor.php`](examples/castor.php) for a runnable demo (`castor
build` from the `examples/` directory).

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

From then on, `make()`, `#[Target]` and `#[Requires]` are available on every
`castor` command — nothing here needs to be repeated per run, only the setup
above (once per machine) and the `import()` line (once, committed).

### As a regular Composer dependency

```bash
composer require mykiwi/castor-extended
```

Autoloaded automatically (via `composer.json`'s `autoload.files`/PSR-4), no
`require` needed.

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
