# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2025-02-XX

### Added
- Support for Laravel 12.x

### Changed
- Minimum PHP requirement updated to 8.2+ (Laravel 12 requirement)
- Updated test matrix to include Laravel 12.x
- Updated documentation to reflect Laravel 12.x support

## [1.0.0] - 2025-01-XX

### Added
- Initial release
- `NotyMessage` fluent API for building events
- Support for Laravel 11.x
- Laravel notification channel support
- HTTP async transport
- Queue transport with batching
- Multiple channel configuration
- Auto-flush pending events
- Fail-silent error handling
- Facade support
- Helper function support
- Comprehensive test coverage across PHP 8.1-8.4
- GitHub Actions CI/CD workflow
- PHPStan static analysis support
- Automated release workflow

### Changed
- Simplified API to be more Sentry-like with `captureEvent()` method
- Removed fluent channel API in favor of simpler approach
- Updated documentation with usage examples

[Unreleased]: https://github.com/usenoty/noty-laravel/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/usenoty/noty-laravel/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/usenoty/noty-laravel/releases/tag/v1.0.0
