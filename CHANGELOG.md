# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `NotyMessage` fluent API for building events
- Support for Laravel 11.x
- Comprehensive test coverage across PHP 8.1-8.3
- GitHub Actions CI/CD workflow
- PHPStan static analysis support
- Automated release workflow

### Changed
- Simplified API to be more Sentry-like with `captureEvent()` method
- Removed fluent channel API in favor of simpler approach
- Updated documentation with usage examples

## [1.0.0] - TBD

### Added
- Initial release
- Laravel notification channel support
- HTTP async transport
- Queue transport with batching
- Multiple channel configuration
- Auto-flush pending events
- Fail-silent error handling
- Facade support
- Helper function support

[Unreleased]: https://github.com/usenoty/noty-laravel/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/usenoty/noty-laravel/releases/tag/v1.0.0
