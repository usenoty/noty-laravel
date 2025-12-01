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
| 8.4     | ✅ Active | Dec 2027 |
| 8.3     | ✅ Active | Dec 2026 |
| 8.2     | ✅ Active | Dec 2025 |

### Laravel Versions

| Version | PHP | Status | End of Life |
|---------|-----|--------|-------------|
| 12.x    | 8.2+ | ✅ Active | Sep 2027 |
| 11.x    | 8.2+ | ✅ Active | Sep 2026 |
| 10.x    | 8.1+ | ✅ Active | Sep 2025 |

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
- PHP 8.2, 8.3, 8.4
- Laravel 10.x, 11.x, and 12.x
- With both lowest and highest dependency versions

See `.github/workflows/tests.yml` for the complete test matrix.
