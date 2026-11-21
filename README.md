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
EversportsClient  →  JSON (gecacht)
        │
        ▼
ActivityParser   →   ClassGroup-Objekte   →   Gutenberg-Block / Shortcode → HTML
```

- `EversportsClient` holt alle Termine der nächsten 52 Wochen (paginiert, max. 50 pro
  Request) und legt das Ergebnis als WordPress-Transient für 1 Stunde zwischen.
- `ActivityParser` wandelt die API-Antwort in typisierte `ClassGroup`-Objekte um.
- Der Gutenberg-Block „Eversports Events" (`kmc/eversports-events`) rendert die Gruppen als HTML.
- Für Seiten ohne Block-Editor (z. B. mit dem Avia Layout Builder von Enfold) gibt es dieselbe
  Ausgabe als Shortcode `[kmc-eversports-events]` (siehe [Shortcode](#shortcode)).

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
   composer mess    # Code-Smell-Erkennung (PHPMD)
   ```
   Alle sollten ohne Fehler durchlaufen.

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
| `composer mess` | prüft auf Code Smells (PHPMD: Komplexität, Benennung, ungenutzte Variablen) |
| `composer release` | führt interaktiv durch einen Release (siehe [Release erstellen](#release-erstellen)) |
| `npm start` | startet WordPress lokal (Port 8888) |
| `npm run debug` | startet WordPress lokal mit aktiviertem Xdebug (Port 9003) |

### WordPress lokal starten

```bash
npm start
```

WordPress läuft dann unter `http://localhost:8888`, der Admin-Bereich unter `/wp-admin`
(Benutzername: `admin`, Passwort: `password`).

Ablauf beim ersten Start:
1. Plugin „KMC Eversports" unter *Plugins* aktivieren.
2. Unter *Einstellungen → KMC Eversports* den API-Token eintragen.
3. Eine neue Seite anlegen, den Block „Eversports Events" einfügen, Seite aufrufen.

#### Shortcode

Wo kein Block-Editor zur Verfügung steht — etwa auf Seiten, die mit dem **Avia Layout Builder**
(Enfold) gebaut sind —, lässt sich der Kursplan als Shortcode einfügen, z. B. in ein
Avia-„Text Block"-Element:

```
[kmc-eversports-events group-ids="46917fab-f6ce-45ce-8235-97a5b65a5368,7d908db9-fbf8-4754-9c13-9da915e705fa" show-image="true"]
```

| Attribut | Bedeutung |
|---|---|
| `group-ids` | Pflicht. Kommagetrennte Eversports-Gruppen-IDs (UUIDs), Leerzeichen sind egal. Die IDs liefert `GET /wp-json/kmc-eversports/v1/groups`. Ohne IDs rendert der Shortcode nichts (mit `WP_DEBUG` einen HTML-Kommentar mit dem Grund). |
| `show-image` | Optional, Standard `true`. Jeder andere Wert blendet die Bilder aus. |

Der Shortcode rendert genau dasselbe wie der Block mit denselben Einstellungen.

#### Avia-Layout-Builder-Element (Enfold)

Ist Enfold aktiv, bringt das Plugin ein eigenes Element mit: **„Eversports Events"** im
Avia Layout Builder unter *Content Elements*. Damit braucht man keine IDs:

1. Element auf die Seite ziehen bzw. anklicken, dann auf dem Element auf *Bearbeiten*.
2. Unter **Kurse** einen oder mehrere Kurse nach Namen auswählen. Ein Klick wählt einen Kurs an
   oder ab (kein Strg/Cmd nötig). Das Feld *Kurse filtern …* darüber grenzt die Liste ein; bereits
   gewählte Kurse bleiben gewählt, auch wenn sie gerade ausgeblendet sind. Rechts zeigt die
   Vorschau sofort das Ergebnis.
3. **Bilder anzeigen** auf *Ja* oder *Nein* stellen, *Save*, Seite speichern.

Im Builder zeigt das Element unter seinem Namen die gewählten Kurse an, einen pro Zeile.

Das Element speichert intern einen eigenen Shortcode (`[av_eversports_events course_groups='…'
course_names='…' show_images='yes|no']`) und rendert genau wie Block und Shortcode.
`course_names` dient nur der Anzeige im Builder: Das Script `assets/js/avia-course-picker.js`
schreibt beim Öffnen und Ändern der Auswahl die Kursnamen hinein, so dass der Builder beim Laden
der Seite nicht bei Eversports nachfragen muss. Wird ein Kurs in Eversports umbenannt, steht im
Builder bis zum nächsten Öffnen des Elements noch der alte Name; die Frontend-Ausgabe ist davon
nicht betroffen.

- **Kursliste nicht ladbar** (z. B. kein/ungültiger API-Token): Statt der Auswahl erscheint ein
  roter Hinweis. Die bisher gespeicherte Kursauswahl bleibt beim Speichern erhalten; nur
  „Bilder anzeigen" lässt sich dann ändern.
- **Gespeicherter Kurs nicht mehr aktiv** (in Eversports archiviert): Er bleibt als
  „Nicht mehr aktiver Kurs (…)" ausgewählt, damit er beim Speichern nicht verloren geht —
  abwählen, wenn er weg soll. Angezeigt wird er ohnehin nicht mehr.
- Die Kursliste wird nur abgefragt, wenn das Bearbeitungsfenster des Elements geöffnet wird
  (1 Stunde gecacht), nicht bei jedem Seitenaufruf im Admin.

Technisch: `AviaEventsIntegration` meldet den Ordner `enfold/` über den Enfold-Filter
`avia_load_shortcodes` an; Enfold lädt daraus `KmcEversportsEventsAviaElement`. Ohne Enfold
passiert nichts. Für PHPStan und Tests stehen Platzhalter der Enfold-Klassen in `stubs/enfold.php`.
Die Enfold-Schnittstelle ist nicht offiziell dokumentiert — **nach Enfold-Updates das Element kurz
im Editor prüfen** (zuletzt getestet mit Enfold 8.3.2).

#### Enfold-Theme (optional, wie auf der Live-Seite)

Die KMC-Website nutzt das kostenpflichtige Theme **Enfold**. Es darf nicht ins Repository,
lässt sich aber lokal einbinden, um den Block mit dem echten Styling zu sehen:

1. ZIP aus dem Envato-Account herunterladen und das darin enthaltene `enfold.zip` nach
   `.local/themes/` entpacken, sodass `.local/themes/enfold/style.css` existiert.
   (`.local/` ist in `.gitignore`.)
2. Datei `.wp-env.override.json` (ebenfalls ignoriert) mit diesem Inhalt anlegen:
   ```json
   { "themes": [ "./.local/themes/enfold" ] }
   ```
3. `npm start`, dann unter *Design → Themes* Enfold aktivieren.

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

Der `EversportsClient` legt API-Antworten als WordPress-Transient zwischen.

**Entwickler (WP-CLI im Container-Terminal):**
```bash
npx wp-env run cli wp transient delete --all
```

**Im WordPress-Admin:** *Einstellungen → KMC Eversports → „Cache leeren"*. Ohne Zutun läuft
der Cache automatisch ab.

---

## Release erstellen

Ein Release macht das Plugin als installationsfertiges ZIP verfügbar — für den manuellen
Upload in einem frischen WordPress, und als Quelle für automatische Update-Benachrichtigungen
auf bereits installierten Instanzen.

```bash
composer release
```

Führt interaktiv durch den ganzen Ablauf: prüft, dass `main` sauber und synchron mit
`origin/main` ist, schlägt anhand der `[Unreleased]`-Einträge eine neue Versionsnummer vor
(`### Added` vorhanden → Minor, sonst Patch — jederzeit überschreibbar), benennt die
`[Unreleased]`-Sektion in `CHANGELOG.md` in die neue Version um und legt eine frische leere
`[Unreleased]`-Sektion darüber an, setzt den `Version:`-Header in `kmc-eversports.php`,
committet und taggt lokal — und fragt vor jedem der beiden folgenreichen Schritte (lokale
Änderung, Push) einzeln nach Bestätigung. Bricht ohne Änderungen ab, falls `[Unreleased]`
leer ist, `main` nicht sauber/synchron ist, oder die eingegebene Version ungültig ist.

Der finale Tag-Push löst `.github/workflows/release.yml` aus: baut das Plugin (Composer +
npm), verpackt die Laufzeitdateien als ZIP, extrahiert den passenden Abschnitt aus
`CHANGELOG.md` als Release-Beschreibung und veröffentlicht ein GitHub Release mit dem ZIP als
Anhang. Weicht die Tag-Nummer vom `Version:`-Header ab, bricht der Workflow ab, statt ein
falsch versioniertes Release zu erzeugen.

**Erstinstallation** (frisches WordPress): ZIP vom GitHub Release herunterladen, dann
*wp-admin → Plugins → Installieren → Datei hochladen*.

**Updates auf bereits installierten Instanzen**: erscheinen automatisch unter
*wp-admin → Dashboard → Aktualisierungen*, sobald ein neues Release existiert — kein erneuter
manueller Download nötig.

## Projektstruktur

```
kmc-eversports.php           WordPress-Plugin-Entry (Plugin-Header, Block- und Shortcode-Registrierung)
src/                         Der Code des Projekts
  EversportsClient.php         Holt Termine von der Eversports-API (paginiert, gecacht)
  ActivityParser.php           Wandelt die API-Antwort in ClassGroup-Objekte um
  ActivityNode.php             Zwischen-Typ: eine flache Zeile aus der API-Antwort
  Appointment.php              Ein einzelner Termin (Start, Ende, Anmeldelink)
  ClassGroup.php               Eine Kursgruppe mit Titel, Beschreibung, Bild und Terminen
  EventsShortcode.php          Shortcode [kmc-eversports-events] → gleiche Ausgabe wie der Block
  AviaEventsIntegration.php    Enfold-unabhängige Logik fürs Avia-Element (Registrierung, Formular, Kursliste, Attribute)
  GroupIdList.php              Kommagetrennte Gruppen-IDs → Liste (Shortcode + Avia-Element)
enfold/                      Wird nur von Enfold geladen (Filter avia_load_shortcodes)
  KmcEversportsEventsAviaElement.php  Avia-Layout-Builder-Element „Eversports Events"
stubs/enfold.php             Platzhalter der Enfold-Klassen für PHPStan und Tests (Enfold ist nicht in CI)
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
phpmd.xml                    Code-Smell-Regeln (PHPMD)
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
- **Git-Tag** – eine feste Markierung auf einem bestimmten Commit, meist für Versionsnummern
  (`v2.1.0`). Anders als ein Branch bewegt sich ein Tag nicht mehr, sobald er gesetzt ist.
- **GitHub Release** – eine veröffentlichte Version des Projekts auf GitHub, verknüpft mit
  einem Tag; kann Dateien (*Assets*, hier: das Plugin-ZIP) zum Download bereitstellen.
- **Update Checker** – Bibliothek ([Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)),
  die WordPress beibringt, bei GitHub Releases nach neuen Versionen zu schauen, obwohl das
  Plugin nicht bei wordpress.org gelistet ist.
