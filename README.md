# Castor extended

> ⚠️ Pet project (for now) made with LLMs 🦾

Reusable helpers for [Castor](https://castor.jolicode.com/) tasks, not (yet)
part of Castor core.

## Usage

### `#[Target]`/`#[Requires]`

Makefile-style rebuild logic: turn a plain function into a rebuild recipe
with `#[Target]`, then run it before a task's own body — but only if needed
— by referencing its name with `#[Requires]`.

```php
use Castor\Attribute\AsTask;
use Mykiwi\CastorExtended\Attribute\{Requires, Target};

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

## Installation

### As a Castor remote import (recommended for consuming projects)

```bash
castor composer init  # creates castor.composer.json, once
castor composer config repositories.mykiwi-castor-extended vcs https://github.com/mykiwi/castor-extended
castor composer require mykiwi/castor-extended:dev-main
```

Commit `castor.composer.json` and `castor.composer.lock`. Then add the import
to `castor.php`, once:

```php
use function Castor\import;
use Mykiwi\CastorExtended\Attribute\{Requires, Target};
import('composer://mykiwi/castor-extended');
```

See [`examples`](examples) for a working copy of these three files.

---

## Contributing / local development

A [devenv](https://devenv.sh/) shell (`devenv shell`) provides PHP 8.5,
Composer and Castor with no manual install.

Dev tooling is a plain [`Makefile`](Makefile), not Castor — root
[`castor.php`](castor.php) is the library's own import entrypoint, so it
can't also carry dev tasks:

```bash
make test     # tests (installs dependencies if needed)
make stan     # static analysis
make cs       # check coding standards
make cs-fix   # apply coding standards
make ci       # run everything above
```
