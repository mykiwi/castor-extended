# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-09-15

### Changed

- Drop Symfony 6.4 support, add Symfony 8 support: `symfony/finder` (and
  dev's `symfony/filesystem`) now require `^7.0 || ^8.0`. Needed to build
  against castor 1.6+, which moved to Symfony 8.

- As a regular Composer dependency, the project's `castor.php` must now
  `require_once __DIR__ . '/vendor/mykiwi/castor-extended/src/functions.php';`
  for `#[Target]`/`#[Requires]` to work: Castor never saw the listeners
  when they were only preloaded via Composer's `autoload.files`.

### Added

- `make()`: run a callback only if a target file is missing or older than its
  prerequisites (Makefile-style rebuild logic).
- `#[Target]`/`#[Requires]`: declare a rebuild recipe once as a plain
  function, then reference it from any task by name.
