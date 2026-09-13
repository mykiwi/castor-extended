# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `make()`: run a callback only if a target file is missing or older than its
  prerequisites (Makefile-style rebuild logic).
- `#[Target]`/`#[Requires]`: declare a rebuild recipe once as a plain
  function, then reference it from any task by name.
