# KMC Eversports – Betriebs- und Recovery-Handbuch

Stand: November 2026 · Plugin-Version 2.2.2

Dieses Dokument beschreibt, wie die Kurse aus Eversports auf die Website
<https://meditation-karlsruhe.de/> kommen, und was zu tun ist, wenn dabei etwas nicht
funktioniert. Teil A richtet sich an die Redaktion, Teil B an eine technisch versierte Person,
die die Betreuung übernimmt.

---

## Teil A – Für die Redaktion

### Was das Plugin tut

Kurse, Termine, Beschreibungen, Bilder und Anmeldelinks werden ausschließlich in Eversports
gepflegt. Das WordPress-Plugin „KMC Eversports" holt diese Daten automatisch und zeigt sie auf
den Kursplan-Seiten an. Auf der Website selbst muss nichts nachgepflegt werden.

- Änderungen in Eversports erscheinen spätestens nach einer Stunde auf der Website
  (Zwischenspeicher, „Cache").
- Zusätzlich nutzt die Website ein Seiten-Cache-Plugin (WP-Optimize). Wenn eine Änderung auch
  nach einer Stunde nicht sichtbar ist, muss ggf. auch dieser Cache geleert werden (siehe unten).
- Angezeigt werden die Termine der nächsten 52 Wochen, nach dem nächsten Termin sortiert.

### Kurse auf einer Seite einbinden

Die Seiten sind mit dem Avia Layout Builder (Theme Enfold) gebaut. Dort gibt es ein eigenes
Element:

1. Seite in WordPress bearbeiten, im Layout Builder unter *Content Elements* das Element
   „Eversports Events" auf die Seite ziehen.
2. Auf dem Element *Bearbeiten* klicken.
3. Unter *Kurse* einen oder mehrere Kurse per Klick auswählen. Das Feld „Kurse filtern …" hilft
   beim Suchen. Rechts erscheint sofort eine Vorschau.
4. *Bilder anzeigen* auf „Ja" oder „Nein" stellen, *Save* klicken und die Seite speichern.

Das Element zeigt genau die ausgewählten Kurse. Ein neu in Eversports angelegter Kurs erscheint
auf einer Seite, sobald er dort im Element ausgewählt wird.

Ein Kurs, der in Eversports archiviert wurde, bleibt im Element als „Nicht mehr aktiver Kurs (…)"
ausgewählt, wird auf der Website aber nicht mehr angezeigt. Er kann einfach abgewählt werden.

### Wenn etwas nicht stimmt

| Beobachtung | Was tun |
|---|---|
| Eine Änderung aus Eversports fehlt nach über einer Stunde noch | Im WordPress-Admin *Einstellungen → KMC Eversports* → „Cache leeren" klicken. Danach im WP-Optimize-Menü den Seiten-Cache leeren und die Seite neu laden. |
| Die Kursliste ist auf einer Seite komplett leer | Erst „Cache leeren" wie oben versuchen. Das Plugin zeigt bei Fehlern absichtlich nichts an anstatt einer Fehlermeldung, das muss im Code überprüft werden. |
| Im Editor erscheint beim Element ein roter Hinweis „Kursliste nicht ladbar" | Möglicherweise ist der Eversports-Zugang (API-Token) abgelaufen oder ungültig. Aktuelles Token bei Eversports erfragen und in *Einstellungen → KMC Eversports → Eversports API-Token* eintragen. Die bestehende Kursauswahl bleibt beim Speichern erhalten. |
| Das Element „Eversports Events" fehlt im Layout Builder | Das kann nach einem Update des Themes Enfold vorkommen und benötigt eine Anpassung des Plugins. |

---

## Teil B – Technik

### Zweck und Architektur

Das Plugin zeigt Kursangebote aus Eversports auf der WordPress-Website an. Es nutzt dazu die
offizielle Eversports-GraphQL-API.

| Baustein | Aufgabe |
|---|---|
| Eversports-API | `https://provider-api.eversportsmanager.io/api/graphql`, Authentifizierung per Header `Authorization: Bearer <Token>` (siehe [API-Token](#api-token)) |
| `EversportsClient` | holt alle Termine der nächsten 52 Wochen (max. 50 pro Anfrage) und die Kursgruppen; speichert die Antworten 1 Stunde als WordPress-Transient |
| `ActivityParser` | wandelt die API-Antwort in Kursgruppen mit Terminen um |
| Ausgabe | Avia-Element „Eversports Events" (Enfold), Gutenberg-Block „Eversports Events" und Shortcode `[kmc-eversports-events]` – alle drei erzeugen dieselbe HTML-Ausgabe |
| Einstellungsseite | *Einstellungen → KMC Eversports*: API-Token eintragen, Cache leeren |
| REST-Endpunkt | `/wp-json/kmc-eversports/v1/groups` listet alle aktiven Kursgruppen mit ID und Name (öffentlich lesbar) |

Bei einem Fehler (z. B. Token ungültig, API nicht erreichbar) rendert das Plugin nichts und
schreibt die Ursache mit dem Präfix `KMC Eversports:` ins PHP-Fehlerprotokoll des Webhostings.
Mit aktiviertem `WP_DEBUG` steht die Meldung zusätzlich als HTML-Kommentar im Seitenquelltext.

### API-Token

Den Eversports-API-Token stellt Eversports aus. Er wird in WordPress unter *Einstellungen → KMC
Eversports* eingetragen und gibt dem Plugin Zugriff auf die Kursdaten. Nach dem Speichern zeigt
die Einstellungsseite den Wert nicht mehr an. Der Token ist geheim und gehört nicht in dieses
öffentliche Repository. Ist er ungültig oder verloren, gilt Szenario 2.

### Quellcode und Auslieferung

- Quellcode: <https://github.com/MeditationKarlsruhe/kmc-eversports> (öffentliches Repository;
  Schreibzugriff über das GitHub-Konto der Organisation, webmaster@meditation-karlsruhe.de).
- Die [README](../README.md) im Repository beschreibt Entwicklungsumgebung, Befehle und
  Architektur ausführlich und ist die maßgebliche technische Dokumentation.
- Jede Änderung durchläuft automatisch Tests, statische Analyse und Coding-Standard-Prüfung
  (GitHub Actions).
- Releases: Ein Git-Tag `vX.Y.Z` erzeugt automatisch ein GitHub Release mit der
  installationsfertigen Datei `kmc-eversports.zip`:
  <https://github.com/MeditationKarlsruhe/kmc-eversports/releases>
- Updates: Das Plugin fragt selbst bei GitHub nach neuen Releases. Neue Versionen erscheinen in
  WordPress unter *Dashboard → Aktualisierungen* wie bei jedem anderen Plugin.

### Wiederherstellungsszenarien

#### Szenario 1: Plugin defekt oder versehentlich gelöscht

1. Unter <https://github.com/MeditationKarlsruhe/kmc-eversports/releases> beim neuesten Release
   (*Latest*) die Datei `kmc-eversports.zip` herunterladen. Dafür ist kein Login nötig.
2. WordPress-Admin: *Plugins → Installieren → Plugin hochladen*, ZIP auswählen, installieren,
   aktivieren. Ist das Plugin noch vorhanden, bietet WordPress an, es zu ersetzen.
3. *Einstellungen → KMC Eversports*: Ist kein Token gespeichert, den
   [API-Token](#api-token) eintragen und speichern.
4. „Cache leeren" klicken, dann eine Kursplan-Seite aufrufen und mit Eversports vergleichen.

Die Kursauswahl auf den Seiten steckt in den Seiteninhalten, nicht im Plugin. Sie bleibt beim
Neuinstallieren erhalten.

#### Szenario 2: API-Token abgelaufen oder ungültig

Erkennbar an leeren Kurslisten und dem roten Hinweis „Kursliste nicht ladbar" im Editor.

1. Im Zweifel (Token ungültig, funktioniert nicht oder verloren) bei Eversports melden und einen
   neuen Token ausstellen lassen.
2. WordPress-Admin: *Einstellungen → KMC Eversports*, neuen Token eintragen, *Speichern*, dann
   Cache leeren.

#### Szenario 3: Nach einem Enfold-Update fehlt das Element oder es funktioniert nicht

Das Avia-Element nutzt eine nicht offiziell dokumentierte Schnittstelle von Enfold (zuletzt
getestet mit Enfold 8.3.2). Nach Enfold-Updates kann es daher brechen. Die Frontend-Ausgabe
sollte nicht betroffen sein, nur der Editor.

**Sofortlösung:** Statt des Elements ein Avia-Element „Text Block" mit dem Shortcode einsetzen:

```
[kmc-eversports-events group-ids="<UUID>,<UUID>" show-image="true"]
```

Die Gruppen-IDs (UUIDs) liefert <https://meditation-karlsruhe.de/wp-json/kmc-eversports/v1/groups>.
`show-image` mit einem anderen Wert als `true` blendet die Bilder aus.

**Dauerhafte Lösung:** Das Element im Repository an die neue Enfold-Version anpassen und ein neues
Release erstellen (siehe [README](../README.md), Abschnitte „Avia-Layout-Builder-Element" und
„Release erstellen").

#### Szenario 4: Eversports ändert die API

Das Plugin bricht bewusst ab, wenn die API-Antwort nicht dem erwarteten Format entspricht (leere
Liste, Meldung im PHP-Fehlerprotokoll). Die Anpassung erfolgt im Repository
([`src/EversportsClient.php`](../src/EversportsClient.php),
[`src/ActivityParser.php`](../src/ActivityParser.php)). Gespeicherte Beispielantworten und
Erkenntnisse zur API liegen im Ordner `spike/` ([`FINDINGS.md`](../spike/FINDINGS.md)).

#### Szenario 5: Weiterentwicklung oder Fehlersuche

Die Entwicklungsumgebung ist vollständig im Repository beschrieben (Dev Container mit PHP, Node
und lokalem WordPress). Benötigt werden nur Docker Desktop, Visual Studio Code und die Extension
„Dev Containers". Details: [README](../README.md), Abschnitte „Voraussetzungen" und
„Schnellstart".
