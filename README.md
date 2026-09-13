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

## Installation

### As a Castor remote import (recommended for consuming projects)

```bash
castor composer init  # creates castor.composer.json, once
castor composer config repositories.mykiwi-castor-extended vcs https://github.com/mykiwi/castor-extended
castor composer require mykiwi/castor-extended:dev-main
```

Commit `castor.composer.json` and `castor.composer.lock`. Then add the import
to `castor.php`, once — pointing at the package's function entrypoint:

```php
use function Castor\import;

import('composer://mykiwi/castor-extended', file: 'src/functions.php');
```

See [`examples`](examples) for a working copy of these three files.

---

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
