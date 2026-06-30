# Kmc.Eversports

WordPress-Plugin, das den **Kursplan des Kadampa Meditationszentrums Karlsruhe** aus
[Eversports](https://www.eversports.de/) auf der KMC-Website anzeigt. Es ruft dazu die
**offizielle Eversports-GraphQL-API** auf, gruppiert die Kurse und rendert sie.

---

## Warum ein Neubau?

Die frühere Lösung las den Kursplan per Browser-Automatisierung („Scraping") aus und
schob ihn über eine Azure-Funktion ins alte Plugin. Dieses Scraping wird inzwischen von
Eversports' Schutzmechanismus (Cloudflare) blockiert. Der Neubau ersetzt das durch den
**direkten, offiziellen API-Zugang** — robuster und ohne Umwege über Azure.

## Wie es funktioniert (großes Bild)

```
Eversports GraphQL-API
        │   (HTTPS, Bearer-Token)
        ▼
EversportsClient  →  JSON (gecacht, 1h TTL)
        │
        ▼
ActivityParser   →   ClassGroup-Objekte   →   WordPress-Shortcode → HTML
```

- `EversportsClient` holt alle Termine der nächsten 52 Wochen (paginiert, max. 50 pro
  Request) und legt das Ergebnis als WordPress-Transient für 1 Stunde zwischen.
- `ActivityParser` wandelt die API-Antwort in typisierte `ClassGroup`-Objekte um.
- Der WordPress-Shortcode `[eversports-events]` rendert die Gruppen als HTML.

---

## Voraussetzungen

Auf dem eigenen Rechner werden nur **drei** Dinge gebraucht — **kein PHP, kein Composer**,
denn die gesamte Entwicklungsumgebung steckt in einem Container:

| Werkzeug | Wozu |
|---|---|
| [Docker Desktop](https://www.docker.com/products/docker-docker-desktop/) | führt den Entwicklungs-Container aus (muss laufen) |
| [Visual Studio Code](https://code.visualstudio.com/) | der Editor |
| VS-Code-Extension **„Dev Containers"** (`ms-vscode-remote.remote-containers`) | öffnet das Projekt im Container |

Optional: [Git](https://git-scm.com/) und die [GitHub CLI](https://cli.github.com/) (`gh`)
für die Versionsverwaltung.

## Schnellstart

1. Repository klonen und den Ordner in **VS Code** öffnen.
2. VS Code erkennt die Container-Definition und schlägt unten rechts **„Reopen in
   Container"** vor — anklicken. (Alternativ: `F1` → *„Dev Containers: Reopen in
   Container"*.) Beim ersten Mal lädt Docker das Image und baut den Container; das dauert
   ein paar Minuten. `composer install` und `npm install` laufen dabei automatisch.
3. Prüfen, dass alles läuft:
   ```bash
   composer test    # Tests
   composer stan    # statische Analyse
   composer cs      # Coding-Standard
   ```
   Alle drei sollten ohne Fehler durchlaufen.

> **Wichtig:** Alle Befehle laufen **im Container-Terminal** von VS Code, nicht in einem
> normalen Windows-Terminal.

## API-Zugang (Geheimnis)

Der Zugriff auf die Eversports-API braucht einen **Studio-API-Token**. Dieser ist ein
Geheimnis und gehört **niemals ins Repository**.

- Ablage: Datei `.secrets/eversports-api.txt` (der Ordner `.secrets/` ist in `.gitignore`
  und wird nie eingecheckt).
- Endpoint: `https://provider-api.eversportsmanager.io/api/graphql`,
  Authentifizierung per Header `Authorization: Bearer <token>`.
- Den Token bekommt man in den API-Einstellungen des Eversports-Studio-Accounts.

---

## Tägliche Befehle

| Befehl | Bedeutung |
|---|---|
| `composer test` | führt die Tests aus (PHPUnit) |
| `composer stan` | statische Code-Analyse (PHPStan, schärfste Stufe) |
| `composer cs` | prüft den Coding-Standard (PSR-12) |
| `composer cs:fix` | korrigiert Formatierungs-Verstöße automatisch |
| `npm start` | startet WordPress lokal (Port 8881) |
| `npm run debug` | startet WordPress lokal mit aktiviertem Xdebug (Port 9003) |

### WordPress lokal starten

```bash
npm start
```

VS Code zeigt eine Benachrichtigung „Port 8881 is available" — darüber den Browser öffnen.
WordPress-Admin ist unter `/wp-admin` erreichbar (Benutzername: `admin`, Passwort: `password`).

Ablauf beim ersten Start:
1. Plugin „KMC Eversports" unter *Plugins* aktivieren.
2. Eine neue Seite anlegen, `[eversports-events]` in den Inhalt schreiben, Seite aufrufen.

Beim nächsten `npm start` bleibt der Zustand erhalten (Plugin-Aktivierung, Seiteninhalte).

> **Nach einem Container-Rebuild** (`Dev Containers: Rebuild Container`) oder `npm run clean`
> werden die Docker-Volumes gelöscht. Die Schritte 1 und 2 oben müssen dann einmalig
> wiederholt werden.

### Xdebug

Für schrittweises Debuggen mit Breakpoints:

```bash
npm run debug
```

In VS Code die Debug-Konfiguration **„Listen for Xdebug"** starten (`F5`), dann die
gewünschte Seite im Browser aufrufen. Xdebug hört auf Port 9003.

### Cache invalidieren

Der `EversportsClient` legt API-Antworten für **1 Stunde** als WordPress-Transient zwischen.

**Entwickler (WP-CLI im Container-Terminal):**
```bash
npx wp-env run cli wp transient delete --all
```

**Content-Ersteller (kein CLI-Zugang):** Der Cache läuft nach 1 Stunde automatisch ab.
Für eine sofortige Aktualisierung bitte einen Entwickler kontaktieren.

> Ein „Cache leeren"-Button im WordPress-Admin ist als eigener Roadmap-Punkt geplant
> (zusammen mit der Admin-Einstellungsseite).

---

## Projektstruktur

```
kmc-eversports.php           WordPress-Plugin-Entry (Plugin-Header, Shortcode-Registrierung)
src/                         Der Code des Projekts
  EversportsClient.php         Holt Termine von der Eversports-API (paginiert, gecacht)
  ActivityParser.php           Wandelt die API-Antwort in ClassGroup-Objekte um
  ActivityNode.php             Zwischen-Typ: eine flache Zeile aus der API-Antwort
  Appointment.php              Ein einzelner Termin (Start, Ende, Anmeldelink)
  ClassGroup.php               Eine Kursgruppe mit Titel, Beschreibung, Bild und Terminen
tests/
  Unit/ActivityParserTest.php  Tests für den Parser, gegen eine echte Beispiel-Antwort
spike/                       Erkundung der API: Notizen + gespeicherte echte Antworten
  FINDINGS.md                  Was beim API-Test herauskam (Schema, Felder, Entscheidungen)
  sample-activities.json       Echte API-Antwort — dient als Test-Vorlage (Fixture)
.devcontainer/               Definition der Entwicklungsumgebung (PHP + Node, Werkzeuge)
.vscode/                     Editor-Einstellungen (Debugging, Format-on-Save)
composer.json                PHP-Abhängigkeiten + Befehls-Kürzel (test/stan/cs)
package.json                 Node-Abhängigkeiten + Befehls-Kürzel (start/debug)
phpunit.xml.dist             Test-Konfiguration
phpstan.neon                 Konfiguration der statischen Analyse
phpcs.xml.dist               Coding-Standard (PSR-12)
```

## Architektur & Prinzipien

Der Code folgt „Clean Development". Wer hier weiterbaut, sollte sich daran halten:

- **Strenge Typisierung** (`declare(strict_types=1)`, volle Typ-Angaben). Das gibt dem
  Editor verlässliche Autovervollständigung und macht Fehler früh sichtbar.
- **IOSP** (Integration/Operation Separation): Eine Methode tut *entweder* Logik
  (*Operation*) *oder* sie verdrahtet andere Methoden (*Integration*) — nicht beides
  gemischt.
- **Fail-fast:** Unerwartete API-Antworten werden nicht stillschweigend geschluckt,
  sondern werfen sofort eine klare Exception.
- **Keine defensive Programmierung:** Interne Verträge werden nicht doppelt abgesichert.
  PHPStan-`@var`-Annotationen ersetzen Laufzeit-Checks an vertrauenswürdigen Grenzen.

## Glossar (kurz, für den Einstieg)

- **Composer** – Paketverwaltung für PHP (vergleichbar mit `npm` in JavaScript). Lädt
  Abhängigkeiten und stellt die Befehls-Kürzel bereit.
- **PSR-4 / Autoloading** – Konvention „Namensraum ↔ Ordner". Sorgt dafür, dass Klassen
  automatisch gefunden werden, ohne sie manuell einzubinden.
- **PHPUnit** – das Test-Framework.
- **PHPStan** – prüft den Code *ohne ihn auszuführen* auf Typ- und Logikfehler (eine Art
  Sicherheitsnetz, das ein Compiler in anderen Sprachen liefert).
- **PHP_CodeSniffer (phpcs/phpcbf)** – prüft bzw. korrigiert den Code-Stil (PSR-12 ist der
  verwendete Standard).
- **Dev Container** – eine in Docker laufende, im Repo versionierte Entwicklungsumgebung.
  Jeder bekommt exakt dieselbe PHP-Version und dieselben Werkzeuge.
- **Xdebug** – ermöglicht das schrittweise Durchlaufen des Codes mit Haltepunkten.
- **Transient** – WordPress-Mechanismus für temporäres Caching mit TTL (Time To Live).

## Roadmap

- ✅ **Dev-Umgebung** — PHP + Node im Dev-Container, lokales WordPress via wp-env mit Xdebug
- ✅ **API-Anbindung** — `EversportsClient` mit Pagination, Caching und Fehlerbehandlung; `ActivityParser` mit typsicherer Verarbeitung; Shortcode gibt echte Daten aus
- **HTML-Template + CSS** — gestaltete Ausgabe statt rohem Dump
- **CI** (GitHub Actions) — Tests, statische Analyse und Coding-Standard laufen automatisch; Code-Coverage ≥ 90 %
- **Admin-Einstellungsseite** — Token-Verwaltung und „Cache leeren"-Button im WordPress-Admin
- **Cutover** — altes Plugin + Scraper + Azure-Funktion ablösen
