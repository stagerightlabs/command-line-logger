# Changelog

All notable changes to `command-line-logger` will be documented in this file

## Unreleased

### Breaking

- Require PHP 8.3. Support for PHP 8.2 has been dropped.
- Add support for Laravel 13 and drop support for Laravel 11

### Fixed

- Fix a fatal error when the console channel is used outside of a running command. The handler required the command output to be a `ConsoleOutput` and resolved it eagerly in the constructor, so logging through the console channel during a web request threw an `EntryNotFoundException`, and `Artisan::call()` (which supplies a `BufferedOutput`) threw a `TypeError`. The handler now accepts any `OutputInterface`, resolves it per record, and stays silent when no command is running.

### Added

- Add a Pest test suite and a GitHub Actions workflow that runs it.

## 1.0.1 - 2025-02-04

- Add support for Laravel 12

## 1.0.0 - 2025-02-04

- Initial Release
