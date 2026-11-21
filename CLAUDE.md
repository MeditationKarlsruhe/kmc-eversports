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

## Worktrees

This repo is edited both from the Windows host and from inside the Dev Container (different mount roots: `D:\KMC\kmc-eversports\...` vs. `/workspaces/kmc-eversports/...`). `git worktree add` writes an absolute gitdir path by default, which only resolves in the environment it was created from.

Always create worktrees with `--relative-paths` (or rely on the repo-local `worktree.useRelativePaths = true` config, already set):

```bash
git worktree add --relative-paths .claude/worktrees/<name> <branch>
```
