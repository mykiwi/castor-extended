# castor-extended

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
- Returns `true` if the callback ran, `false` if skipped — use it to chain
  tasks like Make's dependency graph:

```php
#[AsTask()]
function test(): void
{
    install(); // runs `composer install` only if needed
    run(['phpunit']);
}
```

See [`examples/castor.php`](examples/castor.php) for a runnable demo
(`castor build` from the `examples/` directory).

## Installation

### As a Castor remote import (recommended for consuming projects)

Add the package to your project's `castor.composer.json`:

```bash
castor composer require mykiwi/castor-extended
```

Then import it in your `castor.php`:

```php
use function Castor\import;

import('composer://mykiwi/castor-extended');
```

The `make()` function becomes available (it's autoloaded via
`composer.json`'s `autoload.files`, no `require` needed).

> [!NOTE]
> This package is not (yet) published on Packagist. Until it is, add a VCS
> repository to your `castor.composer.json`:
>
> ```json
> {
>     "repositories": [
>         { "type": "vcs", "url": "https://github.com/mykiwi/my-castor-extended" }
>     ],
>     "require": {
>         "mykiwi/castor-extended": "dev-main"
>     }
> }
> ```

### As a regular Composer dependency

```bash
composer require mykiwi/castor-extended
```

## Contributing / local development

```bash
composer install
vendor/bin/phpunit           # tests
vendor/bin/phpstan analyse   # static analysis
vendor/bin/php-cs-fixer fix  # coding standards
```

CI runs the same checks (tests on PHP 8.4/8.5, PHPStan, PHP-CS-Fixer) on every
push and pull request. Dependabot keeps Composer and GitHub Actions
dependencies up to date automatically.

Releases follow [Semantic Versioning](https://semver.org/); notable changes
are tracked in [`CHANGELOG.md`](CHANGELOG.md).
