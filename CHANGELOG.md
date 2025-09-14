# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of PHP Cron DSL
- Cron expression parser with support for standard 5-field cron syntax
- Support for cron special expressions (@daily, @hourly, @weekly, @monthly, @yearly)
- Support for named days and months (Mon, Tue, Jan, Feb, etc.)
- Support for step expressions (*/5, */10, etc.)
- Support for ranges and lists (1-5, 1,3,5, etc.)
- Compiler class for converting PHP arrays to systemd units
- Timer and Service unit classes with INI generation
- FilesystemEmitter for writing units to disk
- StdoutEmitter for dry-run output
- CLI binary with comprehensive options
- Comprehensive test suite with unit and integration tests
- PHPStan static analysis configuration
- PHP CS Fixer code style configuration
- GitHub Actions CI/CD pipeline
- Example configurations and usage scripts
- Makefile for common development tasks

### Features
- **Cron Expression Support**: Full support for standard cron expressions
- **Systemd Integration**: Generates native systemd timer and service units
- **Rich Configuration**: Support for user, environment, working directory, timeouts, etc.
- **Calendar Aliases**: Support for systemd calendar expressions
- **Validation**: Comprehensive validation of configuration and cron expressions
- **CLI Tool**: Command-line interface with dry-run, validation, and output options
- **Library Usage**: Can be used as a PHP library for programmatic generation
- **Type Safety**: Full PHP 8.1+ type declarations and strict typing

### Technical Details
- **PHP Version**: Requires PHP 8.1 or higher
- **Dependencies**: Symfony Console for CLI, minimal external dependencies
- **Testing**: PHPUnit with comprehensive test coverage
- **Code Quality**: PHPStan level 8, PHP CS Fixer PSR-12 compliance
- **CI/CD**: GitHub Actions with multi-PHP version testing

## [1.0.0] - 2025-01-27

### Added
- Initial release