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
	vendor/bin/php-cs-fixer fix

.PHONY: ci
ci: cs
	$(MAKE) --no-print-directory --output-sync=target -j2 test stan
	@echo "[OK] All checks passed."

.PHONY: build
build:
	nix-build nix/build.nix
