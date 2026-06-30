# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [2.0.0] - 2026-06-30

### Changed

- Replaced the old C# scraper + Azure Function architecture with a direct integration against the Eversports GraphQL API (`provider-api.eversportsmanager.io`)
- Authentication now uses a Bearer token stored in `.secrets/eversports-api.txt` instead of Playwright-based browser scraping

### Added

- `EversportsClient` — paginates the GraphQL API (up to 50 activities per request, 52-week window) with WordPress Transient caching (1-hour TTL)
- `ActivityParser` — converts raw GraphQL responses into typed `ClassGroup` and `Appointment` value objects
- Gutenberg block (`kmc/eversports-events`) with a `showImages` toggle attribute as the primary integration point
- PHPStan (level max), PHPMD, and PHPUnit test suite with ≥ 90 % line coverage requirement
- PSR-12 coding standard enforcement via PHP_CodeSniffer
- GitHub Actions CI pipeline: coding standard, static analysis, mess detection, tests & coverage
