# Kmc.Eversports

WordPress-Plugin, das den **Kursplan des Kadampa Meditationszentrums Karlsruhe** aus
[Eversports](https://www.eversports.de/) auf der KMC-Website anzeigt. Es ruft dazu die
**offizielle Eversports-GraphQL-API** auf, gruppiert die Kurse und rendert sie.

> **Hinweis zum Stand:** Das Projekt wird gerade neu aufgebaut und ist **noch kein
> fertiges, installierbares Plugin**. Aktuell existiert der getestete *Kern* (das
> Einlesen der API-Daten in typisierte Objekte) samt kompletter Entwicklungs- und
> Test-Umgebung. Die eigentlichen WordPress-Teile (Plugin-Datei, Shortcode, HTML-Ausgabe,
> Caching, Admin-Einstellungen, der Live-Aufruf der API) sind **noch nicht gebaut** —
> siehe [Roadmap](#roadmap).

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
ActivityParser   →   ClassGroup-Objekte   →   (später) WordPress-Shortcode → HTML
```

- Die API liefert eine Liste einzelner **Termine** (jeder Termin gehört zu einer Kursgruppe).
- `ActivityParser` validiert die Antwort und fasst die Termine zu eindeutigen
  **Kursgruppen** (`ClassGroup`) zusammen.
- Die Anzeige auf der Website (Shortcode + HTML-Template) folgt in einem späteren Schritt.

---

## Voraussetzungen

Auf dem eigenen Rechner werden nur **drei** Dinge gebraucht — **kein PHP, kein Composer**,
denn die gesamte Entwicklungsumgebung steckt in einem Container:

| Werkzeug | Wozu |
|---|---|
| [Docker Desktop](https://www.docker.com/products/docker-desktop/) | führt den Entwicklungs-Container aus (muss laufen) |
| [Visual Studio Code](https://code.visualstudio.com/) | der Editor |
| VS-Code-Extension **„Dev Containers"** (`ms-vscode-remote.remote-containers`) | öffnet das Projekt im Container |

Optional: [Git](https://git-scm.com/) und die [GitHub CLI](https://cli.github.com/) (`gh`)
für die Versionsverwaltung.

## Schnellstart

1. Repository klonen und den Ordner in **VS Code** öffnen.
2. VS Code erkennt die Container-Definition und schlägt unten rechts **„Reopen in
   Container"** vor — anklicken. (Alternativ: `F1` → *„Dev Containers: Reopen in
   Container"*.) Beim ersten Mal lädt Docker das Image und baut den Container; das dauert
   ein paar Minuten.
3. Sobald VS Code „im Container" ist (grünes Feld unten links), öffnet sich ein Terminal,
   in dem PHP und Composer fertig installiert sind. Die Projekt-Abhängigkeiten werden
   automatisch installiert; falls nicht, einmalig:
   ```bash
   composer install
   ```
4. Prüfen, dass alles läuft:
   ```bash
   composer test    # Tests
   composer stan    # statische Analyse
   composer cs      # Coding-Standard
   ```
   Alle drei sollten ohne Fehler durchlaufen.

> **Wichtig:** Alle `composer`-Befehle laufen **im Container-Terminal** von VS Code, nicht
> in einem normalen Windows-Terminal.

## API-Zugang (Geheimnis)

Der Zugriff auf die Eversports-API braucht einen **Studio-API-Token**. Dieser ist ein
Geheimnis und gehört **niemals ins Repository**.

- Ablage: Datei `.secrets/eversports-api.txt` (der Ordner `.secrets/` ist in `.gitignore`
  und wird nie eingecheckt).
- Endpoint: `https://provider-api.eversportsmanager.io/api/graphql`,
  Authentifizierung per Header `Authorization: Bearer <token>`.
- Den Token bekommt man in den API-Einstellungen des Eversports-Studio-Accounts.

> Aktuell wird der Token nur manuell verwendet (zum Erkunden der API, siehe `spike/`). Wenn
> der Live-Aufruf ins Plugin eingebaut wird, kommt der Schlüssel als Konstante in die
> WordPress-Konfiguration (`wp-config.php`), nicht in die Datenbank oder das Admin-Panel.

---

## Tägliche Befehle

| Befehl | Bedeutung |
|---|---|
| `composer test` | führt die Tests aus (PHPUnit) |
| `composer stan` | statische Code-Analyse (PHPStan, schärfste Stufe) |
| `composer cs` | prüft den Coding-Standard (PSR-12) |
| `composer cs:fix` | korrigiert Formatierungs-Verstöße automatisch |

**Einzelne Tests / Debugging in VS Code:**
- Im **Test-Explorer** (Becherglas-Symbol links) lässt sich jeder Test per Klick starten
  oder mit „Debug Test" mit gesetztem Haltepunkt (Breakpoint) durchsteppen.
- Beim **Speichern** einer PHP-Datei wird sie automatisch nach PSR-12 formatiert.

## Projektstruktur

```
src/                         Der Code des Projekts (Domäne)
  ActivityParser.php           Liest die API-Antwort und baut Kursgruppen (validiert streng)
  ClassGroup.php               Eine Kursgruppe (Wert-Objekt: ID + Titel)
  MalformedActivitiesResponse.php  Fehler, wenn die API-Antwort nicht das erwartete Format hat
tests/
  Unit/ActivityParserTest.php  Tests für den Parser, gegen eine echte Beispiel-Antwort
spike/                       Erkundung der API: Notizen + gespeicherte echte Antworten
  FINDINGS.md                  Was beim API-Test herauskam (Schema, Felder, Entscheidungen)
  sample-activities.json       Echte API-Antwort — dient als Test-Vorlage (Fixture)
.devcontainer/               Definition der Entwicklungsumgebung (PHP, Werkzeuge)
.vscode/                     Editor-Einstellungen (Debugging, Format-on-Save)
composer.json                Abhängigkeiten + Befehls-Kürzel (test/stan/cs)
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
  gemischt. Beispiel: `ActivityParser::parse()` verdrahtet nur; die Logik steckt in den
  privaten Methoden `decodeGroups()` und `toClassGroups()`.
- **Fail-fast:** Unerwartete API-Antworten werden nicht stillschweigend geschluckt,
  sondern werfen sofort eine klare Exception.
- **Tests ohne „Mocks":** Logik wird über Unit-Tests abgesichert; an der einzigen externen
  Grenze (die HTTP-API) wird später mit gespeicherten echten Antworten gearbeitet, nicht
  mit künstlichen Attrappen.

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

## Roadmap

Bereits vorhanden: Dev-Umgebung, der getestete API-Parser (Kursgruppen), die Qualitäts-Gates
(Tests, statische Analyse, Coding-Standard).

Noch offen:
1. **Live-Anbindung & vollständige Daten** – Kursgruppen um Termine, Anmeldelink und Bild
   erweitern; echten API-Aufruf einbauen.
2. **WordPress-Integration** – Plugin-Datei, Shortcode `[eversports-events …]`,
   HTML-Template, Caching, Admin-Einstellungen.
3. **Tests gegen echtes WordPress** (wp-env) inkl. Snapshot-Tests der gerenderten Ausgabe.
4. **CI** (GitHub Actions): Tests/Analyse/Standard laufen automatisch bei jedem Push.
5. **Umstellung („Cutover")** – altes Plugin + Scraper + Azure-Funktion ablösen.
