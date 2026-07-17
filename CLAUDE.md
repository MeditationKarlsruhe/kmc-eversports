# CLAUDE.md

This file provides guidance to Claude Code when working in this directory.
It supersedes the CLAUDE.md one level up (`D:\KMC\CLAUDE.md`), which describes the old architecture and is no longer accurate.

## Project

WordPress plugin for Kadampa Meditationszentrum (KMC) Karlsruhe — displays Eversports class schedules via the official Eversports GraphQL API. No C# server, no Azure Function, no scraping.

## Commands

All commands run inside the Dev Container terminal (no local PHP).

```bash
composer install    # install PHP dependencies
composer test       # PHPUnit
composer stan       # PHPStan static analysis (max level)
composer cs         # check PSR-12 coding standard
composer cs:fix     # auto-fix formatting

npm start           # start local WordPress via wp-env (port 8881)
npm run debug       # start with Xdebug (port 9003)
npm run clean       # destroy Docker volumes (resets local WordPress)
```

## Architecture

```
Eversports GraphQL API
        │   (HTTPS, Bearer Token — .secrets/eversports-api.txt, never commit)
        ▼
EversportsClient  →  WP Transient cache (1h TTL)
        │
        ▼
ActivityParser  →  ClassGroup[]  →  [eversports-events] shortcode  →  HTML
```

**Entry point:** `kmc-eversports.php`

**`src/`:**
- `EversportsClient` — paginates GraphQL (≤50 per request, 52 weeks), caches via WP Transient
- `ActivityParser` — raw API response → typed `ClassGroup` objects; uses `@var` shape annotations, no runtime defensive checks
- `ActivityNode` — intermediate flat type from raw API rows
- `ClassGroup` — title, description, image, `Appointment[]`
- `Appointment` — start, end, nullable `detailsPageURL`

**`tests/Unit/`:** PHPUnit + Brain\Monkey (WP mocks) + Spatie snapshot assertions.

## Testing Policy

Verification is limited to the automated checks: `composer test`, `composer stan`, `composer cs`, `composer mess`. Do not start `wp-env` (`npm start`) or perform manual browser verification of a change — Felix does that himself. This overrides any general guidance elsewhere to test UI changes in a browser before reporting completion.

## Design Principles

- `declare(strict_types=1)` everywhere
- IOSP: methods are either pure logic (Operation) or wiring (Integration) — never both
- Fail-fast: unexpected API responses throw `\RuntimeException` immediately
- No defensive programming on internal contracts; PHPStan `@var` at trusted boundaries
- PSR-12 coding standard

## Dev Container

PHP 8.2. Container name changes on rebuild — find it with:
```bash
docker ps --format "{{.Names}} {{.Image}}" | grep php
```

## Worktrees

This repo is edited both from the Windows host and from inside the Dev Container (different mount roots: `D:\KMC\kmc-eversports\...` vs. `/workspaces/kmc-eversports/...`). `git worktree add` writes an absolute gitdir path by default, which only resolves in the environment it was created from — a worktree created from the host won't be recognized as a repo when opened from the container, and vice versa.

Always create worktrees with `--relative-paths` (or rely on the repo-local `worktree.useRelativePaths = true` config, already set), so the `.git` pointer file is relative and resolves from either environment:

```bash
git worktree add --relative-paths .claude/worktrees/<name> <branch>
```

## Roadmap

- ✅ Dev environment (PHP + Node Dev Container, wp-env with Xdebug)
- ✅ API integration (`EversportsClient`, `ActivityParser`, Shortcode outputting real data)
- HTML template + CSS — styled output
- ✅ CI (GitHub Actions) — tests, static analysis, coding standard; coverage ≥ 90 %
- ✅ CD (GitHub Actions) — tag-triggered plugin ZIP + GitHub Release, auto-update notifications via Plugin Update Checker
- Admin settings page — token management + "clear cache" button
- Cutover — retire old plugin + scraper + Azure Function
