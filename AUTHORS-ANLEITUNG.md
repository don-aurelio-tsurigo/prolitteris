# Autoren-Mitgliedernummern verwalten

## Übersicht

Wenn Autor*innen selbst ProLitteris-Mitglieder sind, kann ihre **Member ID** bei der Artikelmeldung direkt mitgemeldet werden. Dies ermöglicht eine direkte Zuordnung und korrekte Entschädigung.

## Datei: authors.json

Die Datei `authors.json` enthält die Zuordnung von Autoren zu ihren ProLitteris Member IDs.

### Format

```json
{
  "authors": {
    "Vollständiger Name": {
      "firstName": "Vorname",
      "surName": "Nachname",
      "memberId": "123456"
    }
  }
}
```

### Beispiel

```json
{
  "authors": {
    "Valerie Wendenburg": {
      "firstName": "Valerie",
      "surName": "Wendenburg",
      "memberId": "262868"
    },
    "Max Mustermann": {
      "firstName": "Max",
      "surName": "Mustermann",
      "memberId": "123456"
    }
  }
}
```

## Wie füge ich eine neue Autor*in hinzu?

1. **Öffne die Datei:** `authors.json`

2. **Füge einen neuen Eintrag hinzu:**
   ```json
   {
     "authors": {
       "Valerie Wendenburg": {
         "firstName": "Valerie",
         "surName": "Wendenburg",
         "memberId": "262868"
       },
       "Neuer Name": {
         "firstName": "Vorname",
         "surName": "Nachname",
         "memberId": "999999"
       }
     }
   }
   ```

3. **Wichtig:**
   - Der **vollständige Name** muss **exakt** so geschrieben sein, wie er auf bajour.ch erscheint
   - Groß-/Kleinschreibung wird beim Vergleich ignoriert
   - Das System erkennt auch umgekehrte Reihenfolge (Nachname Vorname vs. Vorname Nachname)

4. **Speichern** und fertig!

## Wie finde ich die Member ID eines Autors?

Die Member ID muss von der jeweiligen Autor*in selbst oder von ProLitteris bereitgestellt werden.

**Beispiel:**
- Valerie Wendenburg → Member ID: `262868`

## Automatische Zuordnung

Wenn du `test-scraper.php` oder `report-csv.php` ausführst, wird automatisch geprüft, ob für die gefundenen Autor*innen eine Member ID hinterlegt ist.

### Beispiel-Ausgabe:

```
=== Autoren ===
- Valerie Wendenburg (AUTHOR)

=== JSON Export ===
{
    "participants": [
        {
            "participation": "AUTHOR",
            "firstName": "Valerie",
            "surName": "Wendenburg",
            "memberId": "262868"
        }
    ]
}
```

### Im Log:

```
[2026-01-19T14:51:57.003011+01:00] prolitteris.INFO: Member ID gefunden für Valerie Wendenburg: 262868
```

## Was passiert, wenn keine Member ID hinterlegt ist?

Wenn keine Member ID in der `authors.json` Datei gefunden wird, wird der Artikel **trotzdem** korrekt gemeldet – nur ohne die Member ID im `participants` Feld.

**Ohne Member ID:**
```json
{
    "participation": "AUTHOR",
    "firstName": "Max",
    "surName": "Mustermann"
}
```

**Mit Member ID:**
```json
{
    "participation": "AUTHOR",
    "firstName": "Valerie",
    "surName": "Wendenburg",
    "memberId": "262868"
}
```

## Häufige Probleme

### ❌ Member ID wird nicht erkannt

**Problem:** Die Member ID wird nicht zum Artikel hinzugefügt

**Lösungen:**
1. Prüfe die **Schreibweise** des Namens in `authors.json`
2. Vergleiche mit dem Namen, der auf bajour.ch angezeigt wird
3. Schaue ins Log: `logs/report.log` → suche nach "Member ID gefunden"

### ❌ JSON-Fehler

**Problem:** `authors.json` ist ungültig

**Lösung:**
- Prüfe, ob alle **Kommas** korrekt gesetzt sind
- Prüfe, ob alle **Anführungszeichen** geschlossen sind
- Nutze einen JSON-Validator: https://jsonlint.com/

## Test

Du kannst die Member ID Zuordnung testen:

```bash
php test-scraper.php "https://bajour.ch/a/das-restaurant-sonne-in-bottmingen-schliesst"
```

Erwartetes Ergebnis:
```
[INFO] Member ID gefunden für Valerie Wendenburg: 262868

=== JSON Export ===
{
    "participants": [
        {
            "participation": "AUTHOR",
            "firstName": "Valerie",
            "surName": "Wendenburg",
            "memberId": "262868"
        }
    ]
}
```

---

**Letzte Aktualisierung:** Januar 2026
**Erstellt von:** bajour.ch Team
