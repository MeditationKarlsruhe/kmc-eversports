# CLAUDE.md

WordPress plugin for Kadampa Meditationszentrum (KMC) Karlsruhe that displays Eversports class schedules via the official Eversports GraphQL API.

## Commands

There is no local PHP: all `composer` and `npm` scripts (see `composer.json`, `package.json`) run inside the Dev Container terminal.

## Testing Policy

Verification is limited to the automated checks: `composer test`, `composer stan`, `composer cs`, `composer mess`. Do not start `wp-env` (`npm start`) or perform manual browser verification of a change — Felix does that himself. This overrides any general guidance elsewhere to test UI changes in a browser before reporting completion.

## Design Principles

- `declare(strict_types=1)` everywhere
- IOSP: methods are either pure logic (Operation) or wiring (Integration) — never both
- Fail-fast: unexpected API responses throw `\RuntimeException` immediately
- No defensive programming on internal contracts; PHPStan `@var` at trusted boundaries
- PSR-12 coding standard

## Dev Container

The container name changes on rebuild — find it with:
```bash
docker ps --format "{{.Names}} {{.Image}}" | grep php
```
