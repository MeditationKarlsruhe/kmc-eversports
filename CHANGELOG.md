# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

## [2.2.0] - 2026-11-19

### Added

- Shortcode `[kmc-eversports-events group-ids="…" show-image="…"]` for pages without the block editor (e.g. built with Enfold's Avia Layout Builder); renders the same output as the `kmc/eversports-events` block via `Renderer::render`. `group-ids` takes the Eversports API's group UUIDs
- Avia Layout Builder element "Eversports Events" (Enfold, tab *Content Elements*): course groups picked by name in a multi-select (fed by `EversportsClient::fetchGroups()`), "Bilder anzeigen" yes/no, live preview in the edit modal with the plugin stylesheet. Registered via Enfold's `avia_load_shortcodes` filter; does nothing when Enfold is not active. Groups are only fetched when the element's edit window is requested; course names are HTML-escaped (Enfold prints select labels raw); if groups cannot be loaded, an error note replaces the select and the stored selection is kept in a hidden field; stored groups that are no longer active stay selectable as "Nicht mehr aktiver Kurs (…)" so saving does not drop them. Stores `[av_eversports_events course_groups='…' show_images='yes|no']` and renders through `Renderer::render` like block and shortcode
- Avia element course picker (`assets/js/avia-course-picker.js`, loaded on post edit screens): text filter above the course list that hides non-matching options without dropping hidden selections, and click-to-toggle selection (no Ctrl/Cmd needed). Selected course names are stored in a hidden `course_names` attribute and shown on the builder canvas one per line (separated by `|`, split again after each save via Enfold's `av-alb-canvas-updated` event), so the canvas names the courses without querying Eversports
- `enfold/` is now part of the release ZIP

### Changed

- `EversportsClient::postGraphQL()` decodes each API response once and returns the decoded array; previously the body was decoded once for the GraphQL error check and again by the caller
- Template variables are passed explicitly: `EventsTemplate::render(list<ClassGroup> $groups, bool $showImage)` includes `templates/eversports-events.php`, used by `Renderer` and the template tests instead of an ambient-scope `include`. `Renderer` only treats `showImages === true` as "show images"

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
