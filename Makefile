vendor/autoload.php: composer.lock
	composer install --no-interaction --prefer-dist
	touch vendor/autoload.php

.PHONY: test
test: vendor/autoload.php
	vendor/bin/phpunit

.PHONY: stan
stan: vendor/autoload.php
	vendor/bin/phpstan analyse

.PHONY: cs
cs: vendor/autoload.php
	vendor/bin/php-cs-fixer fix --dry-run --diff

.PHONY: cs-fix
cs-fix: vendor/autoload.php
	vendor/bin/php-cs-fixer fix

.PHONY: ci
ci: test stan cs
	@echo "[OK] All checks passed."
