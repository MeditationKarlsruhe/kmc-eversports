# Eversports-API-Token

Das Plugin braucht einen API-Token, um die Kursdaten bei Eversports abzurufen. Er wird bei jeder
Anfrage als Header `Authorization: Bearer <Token>` mitgeschickt.

## Herkunft

Den Token stellt Eversports aus. Selbst erzeugen kann man ihn nicht.

## Ablage

- Der Token ist ausschließlich in WordPress hinterlegt: *Einstellungen → KMC Eversports →
  Eversports API-Token* (in der Datenbank als Option `kmc_eversports_api_token`).
- Nach dem Speichern zeigt WordPress den Wert nicht mehr an, nur den Hinweis „Token ist
  gespeichert".
- Außerhalb von WordPress wird der Token nirgends aufbewahrt. Er gehört nie ins Repository.
- Für die lokale Entwicklung liegt ein Token in `.secrets/eversports-api.txt`
  (siehe [README](../README.md), Abschnitt „API-Zugang (Geheimnis)").

## Neuer Token

Ist der Token ungültig, funktioniert nicht mehr oder ist verloren gegangen (z. B. nach einer
Neuinstallation von WordPress ohne Datenbank), muss bei Eversports ein neuer Token beantragt
werden. Danach:

1. WordPress-Admin: *Einstellungen → KMC Eversports*, neuen Token eintragen, *Speichern*.
2. „Cache leeren" klicken und eine Kursplan-Seite prüfen.

Wird die Website samt Datenbank aus einem Backup wiederhergestellt, ist der Token automatisch
wieder da.
