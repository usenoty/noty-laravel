# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`usenoty/noty-laravel` is a Composer package — a Laravel notification channel SDK that ships events to the Noty API in a non-blocking, fail-silent way. It is consumed by Laravel apps; there is no application to "run" here, only library code, tests, and static analysis.

Supported matrix: PHP 8.2 / 8.3 / 8.4 × Laravel 10 / 11 / 12 (see `.github/workflows/tests.yml`). PHPStan is pinned to **level 5** (`phpstan.neon`).

## Common commands

```bash
composer test            # phpunit (uses Orchestra Testbench)
composer test-coverage   # phpunit with HTML coverage in coverage/
composer phpstan         # static analysis, level 5, src/ only
composer cs-check        # PHP-CS-Fixer dry-run (CI runs this)
composer cs-fix          # apply PHP-CS-Fixer fixes
```

Run a single test:

```bash
vendor/bin/phpunit --filter=NotyMessageTest
vendor/bin/phpunit tests/Unit/ClientTest.php
vendor/bin/phpunit --filter='it sends event'   # by @test name fragment
```

PHPUnit is configured with `failOnWarning="true"` and `failOnRisky="true"` (`phpunit.xml`) — warnings and risky tests fail the suite, not just the test.

## Architecture

The package is small but layered. The non-obvious bits:

**Wiring (`src/Providers/NotyServiceProvider.php`).** The provider binds `TransportInterface` as a **singleton** chosen by `config('noty.transport')` (`http` or `queue`), then binds `Client` as a singleton wrapping that transport. It also:
- Registers the `noty` Laravel notification channel via `ChannelManager::extend`, so notifications with `via() => ['noty']` resolve to `NotyChannel`.
- Hooks `$this->app->terminating(...)` to call `Client::flush()` **after** Laravel has sent the response. This is the mechanism that makes HTTP transport non-blocking — events are queued during the request, flushed during shutdown.

**Three entry points, one funnel.** All three eventually call `Client::captureEvent()`:
1. `NotyMessage::create()->...->send()` — fluent builder in `src/NotyMessage.php`.
2. Laravel notifications with a `toNoty($notifiable)` method — routed through `NotyChannel`, which also auto-injects `notifiable_type` / `notifiable_id` tags.
3. `Noty::captureEvent([...])` facade or the `noty()` helper (`src/helpers.php`).

**Channel resolution.** `Client::resolveChannel()` looks up the given name in `config('noty.channels.list')`. If found, it maps to the configured channel ID; otherwise the string is treated as a direct channel ID and passed through. Tests and code commonly pass names like `'auth'`, `'payments'`, etc.

**HTTP transport (`HttpTransport`).** Uses Guzzle `postAsync` and accumulates promises in `$pending`. `flush()` calls `Utils::settle($this->pending)->wait(true)` — settle, not unwrap, so individual rejections do not throw. Default timeouts are aggressive (`timeout=0.5s`, `connect_timeout=0.25s`) by design — fail fast rather than block the user response. All exceptions are swallowed (fail-silent is a hard requirement, not an oversight).

**Queue transport (`QueueTransport` + `Jobs/SendNotyEvents`).** Batches events in memory; dispatches a `SendNotyEvents` job when the batch hits `batch_size` (default 10) or when `flush()` / `__destruct()` runs. The job uses Guzzle `Pool` for concurrent requests when there are >1 events, with concurrency from `config('noty.queue.concurrency')`. The job's timeouts are *higher* than the HTTP transport's (5s / 2s) because it runs in a worker, not the request thread. `tries` and `backoff` are read from config at construct time.

**Event value object (`Support/Event`).** `toArray()` omits optional fields when empty/default — including priority when it equals `NORMAL`. If you change defaults or add fields, update this serialization carefully; the API contract is "only send what's set."

## Conventions specific to this codebase

- **Fail-silent everywhere.** `try { ... } catch (\Throwable) { /* yut */ }` patterns are intentional — never let a tracking failure surface to the consuming app. Don't "improve" these by rethrowing or logging at error level unless config opts in (see `noty.queue.log_failures`).
- **Code style is enforced in CI.** `composer cs-check` runs in the test workflow before phpunit; a style violation fails the whole job. Run `composer cs-fix` before pushing. Rules: `@PSR12` + `@PhpCsFixer`, short array syntax, single-space concat, `yoda_style` disabled.
- **Tests use Orchestra Testbench** (`tests/TestCase.php`). It boots a minimal Laravel app and registers `NotyServiceProvider`. Default test config is set in `defineEnvironment()` — override it per-test with `$this->app['config']->set(...)` rather than editing the base.
- The `config/noty.php` file in this repo is both the package default (merged via `mergeConfigFrom`) and the file published to consumers (`vendor:publish`). Changes to it affect both.
- The `CALISMA_MANTIGI.md` file documents the same architecture in Turkish — keep it roughly in sync if you make architectural changes.
