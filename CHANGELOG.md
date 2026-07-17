# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

## [2.1.0] - 2026-07-17

### Added

- REST endpoint `GET /wp-json/kmc-eversports/v1/groups` — lists active Eversports groups (id, name) for the block editor, backed by a dedicated `activityGroups` GraphQL query (`EversportsClient::fetchGroups()`, own 1-hour transient cache)
- `groupIds` attribute on the `kmc/eversports-events` block — content editors pick one or more groups via a checkbox list in the Inspector Controls; at least one group must be selected, the block renders nothing otherwise
- Text filter above the group checkbox list in the block editor, to narrow down the list when there are many groups

### Changed

- `EversportsClient`'s HTTP/GraphQL transport (`request()`/`buildRequestPayload()`) generalized into `postGraphQL(query, variables)`, shared by the activities and groups queries
- "Cache leeren" now also clears the groups cache
- Activities query now also filters `activityGroupPublicationStates: [ACTIVE]`, so classes belonging to archived/hidden groups no longer appear on the frontend

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
