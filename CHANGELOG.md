# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Build and test against castor 1.8.1 (dev requirement `^1.7` -> `^1.8`,
  `nix/build.nix` bundles v1.8.1). Castor 1.8 resolves the remote packages of
  `castor.composer.json` against the ones it bundles, so
  `examples/castor.composer.lock` now locks `symfony/finder` as a metapackage
  standing for the version castor ships.
- Document `CASTOR_USE_CHDIR` in the README and `examples/castor.php`: castor
  1.8 deprecates leaving the constant undefined and 2.0 defaults it to
  `true`. It changes nothing for this package — `make()` already resolves a
  relative path against the context's working directory rather than PHP's
  cwd, which is what castor chdir()s to.

## [1.0.1] - 2026-09-16

### Fixed

- `#[Target]`/`#[Requires]` silently never ran via the documented
  `composer://` remote import: `composer.json`'s `autoload.files` preloaded
  `src/make.php` before any `castor.php` entrypoint ran, so the package's
  own root `castor.php` (which guards its `import(__DIR__ . '/src')` behind
  `function_exists('Mykiwi\CastorExtended\make')`, to avoid redeclaring
  functions when a build bundles the package alongside its own copy) always
  found `make()` already defined and skipped the import — `src/listener.php`
  and its `#[AsListener]` hooks never loaded, so no `#[Target]` recipe ever
  ran. Dropped `src/make.php` from `autoload.files`; it's loaded the same
  way as the rest of `src/`, through that `import()` call.

## [1.0.0] - 2026-09-15

### Changed

- Drop Symfony 6.4 support, add Symfony 8 support: `symfony/finder` (and
  dev's `symfony/filesystem`) now require `^7.0 || ^8.0`. Needed to build
  against castor 1.6+, which moved to Symfony 8.

### Added

- `make()`: run a callback only if a target file is missing or older than its
  prerequisites (Makefile-style rebuild logic).
- `#[Target]`/`#[Requires]`: declare a rebuild recipe once as a plain
  function, then reference it from any task by name.
