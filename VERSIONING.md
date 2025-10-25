# Versioning Guide

This package follows [Semantic Versioning](https://semver.org/) (SemVer) principles.

## Version Format

`MAJOR.MINOR.PATCH`

- **MAJOR**: Breaking changes that are not backwards compatible
- **MINOR**: New features that are backwards compatible
- **PATCH**: Bug fixes that are backwards compatible

## Supported Versions

### PHP Versions

| Version | Status | End of Life |
|---------|--------|-------------|
| 8.3     | ✅ Active | Dec 2026 |
| 8.2     | ✅ Active | Dec 2025 |
| 8.1     | ✅ Active | Nov 2025 |
| 8.0     | ❌ Unsupported | Nov 2023 |
| < 8.0   | ❌ Unsupported | N/A |

### Laravel Versions

| Version | PHP | Status | End of Life |
|---------|-----|--------|-------------|
| 11.x    | 8.2+ | ✅ Active | Sep 2026 |
| 10.x    | 8.1+ | ✅ Active | Sep 2025 |
| 9.x     | 8.0+ | ❌ Unsupported | Feb 2023 |

## Branching Strategy

- `main`: Latest stable release
- `develop`: Development for next release
- `v1.x`: Legacy versions (if needed)

## Release Process

1. Update version in `composer.json`
2. Update `CHANGELOG.md` with changes
3. Create a git tag: `git tag -a v1.0.0 -m "Release version 1.0.0"`
4. Push tag: `git push origin v1.0.0`
5. GitHub Actions will automatically create a release

## Breaking Changes Policy

Breaking changes will be:
- Clearly documented in CHANGELOG.md
- Indicated by incrementing MAJOR version
- Deprecated features will be marked with `@deprecated` and removed in next MAJOR version

## PHP/Laravel Compatibility

The package is tested against:
- PHP 8.1, 8.2, 8.3
- Laravel 10.x and 11.x
- With both lowest and highest dependency versions

See `.github/workflows/tests.yml` for the complete test matrix.
