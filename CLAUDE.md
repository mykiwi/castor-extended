- Castor : https://github.com/jolicode/castor
- gnumake : https://github.com/mirror/make

## Makefile usage example
```
$ ls
Makefile

$ cat Makefile
foo:
    date > foo
vendor/autoload.php: composer.lock
    composer install
all:
    make -j2 foo vendor/autoload.php

$ make foo
date > foo
$ make foo
make: 'foo' is up to date.

$ make vendor/autoload.php
...
ok
$ make vendor/autoload.php
make: 'vendor/autoload.php' is up to date.
$ touch composer.lock
$ make vendor/autoload.php
...
ok
```

## Post edit commands

```
make test     # phpunit
make stan     # phpstan (level 8)
make cs-fix   # php-cs-fixer, apply
make ci       # test + stan + cs, in that order; must end with
              # "[OK] All checks passed."
```

phpstan error detail: `vendor/bin/phpstan analyse --no-progress
--error-format=raw 2>&1 | grep '\.php:'`.

House rule for phpstan level 8: no `@phpstan-ignore`, no baseline, no inline
`@var` overrides, no casts/widening to silence — fix the root cause.
