# API-Spike — Findings (Iteration 1)

**Endpoint:** `https://provider-api.eversportsmanager.io/api/graphql`
**Auth:** `Authorization: Bearer <token>` — verifiziert (HTTP 200).

## Schema-Mapping (GraphQL → 1:1-Kontrakt)

| Kontrakt (alt) | GraphQL-Quelle |
|---|---|
| `group-ids` | `activities(activityGroupIds: [ID!])` → `ActivityGroup.id` (UUID) |
| Zeitfenster (52 Wo.) | `activities(timeRange: { start, end })` — `DateInput`, ISO-8601 m. TZ |
| `detail.title` | `ActivityGroup.name` |
| `detail.description` (HTML) | `ActivityGroup.description.html` (zusätzlich `plainText`) |
| `detail.imageUrl` | `ActivityGroup.images(first:1).nodes[0].url` |
| `appointment.start` / `end` | `Activity.start` / `Activity.end` (ISO-8601 m. TZ) |
| `appointment.registrationLink` | `Activity.detailsPageURL` → `https://www.eversports.de/org/activity/{Activity.id}` |

Sinnvolle Filter: `isCancelled:false, isArchived:false`. Pagination via `first`/`after` (Cursor); Connections liefern bequem `nodes`.

## Wichtigste Erkenntnisse
- **Registration-Link gelöst & vereinfacht:** Die API liefert `Activity.detailsPageURL` direkt. Der alte, hartkodierte Phoenix-Link (`…&facilityUuid=9a8de93d…`) wird damit überflüssig → robuster + multi-studio-fähig ohne hartkodierte UUIDs. (Offene Design-Entscheidung, siehe unten.)
- **group-ids-Migration:** Alt = Integer-Gruppen-IDs (C# `Session.GroupId : int`), Neu = UUIDs. Beim Cutover müssen die `group-ids`-Werte in bestehenden Shortcodes auf die neuen UUIDs umgestellt werden (Shortcode-*Syntax* bleibt identisch).
- `ActivityGroup.detailsPageURL` kann `null` sein; die **Activity-Ebene** ist die richtige Quelle für den Link.
- `Activity.name` ist oft `null` → dann Name aus `ActivityGroup.name` ableiten (lt. API-Doku).

## Offene Design-Entscheidung
Registration-Link: offizielle `detailsPageURL` (Activity-Detailseite) vs. alten Phoenix-Widget-Link byte-genau nachbauen. Minimaler UX-Unterschied (Detailseite mit Buchen-Button vs. direkter Widget-Buchungs-Overlay).

## Fixtures
- `spike/sample-activities.json` — echte Antwort (5 Activities, 3 Gruppen).
- `spike/query-fields.json`, `spike/types-*.json` — Introspection-Rohdaten.
