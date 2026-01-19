# Workflow: Artikel für ProLitteris Meldung vorbereiten

## Übersicht

Dieser Workflow beschreibt, wie du die Liste der zu meldenden Artikel für ProLitteris vorbereitest.

## Voraussetzungen

- ✅ Artikel haben bereits ProLitteris Zählmarken eingebaut
- ✅ Zugriff auf Google Analytics
- ✅ Artikel müssen mindestens **500 Zugriffe** im Kalenderjahr erreicht haben

## Schritt-für-Schritt Anleitung

### 1. Artikel in Google Analytics identifizieren

1. Öffne **Google Analytics**
2. Wähle den gewünschten **Zeitraum** (z.B. Kalenderjahr 2024)
3. Gehe zu: **Verhalten** → **Website-Content** → **Alle Seiten**
4. Filtere nach Seiten mit **≥ 500 Seitenaufrufen**
5. Exportiere die Daten (z.B. als CSV)

### 2. Daten in Google Sheets / Excel bereinigen

1. **Importiere** die exportierten Daten in Google Sheets oder Excel
2. **URL ergänzen:**
   - Die GA-Daten enthalten oft nur den Pfad (z.B. `/a/artikel-titel`)
   - Ergänze die volle URL: `https://bajour.ch` + Pfad
   - Formel in Sheets: `=CONCATENATE("https://bajour.ch", A2)`

3. **Nur Artikel behalten:**
   - **Behalten:** URLs mit `/a/` in der URL (= Artikel)
   - **Entfernen:** URLs mit `/tag/` (= Tag-Seiten, keine Artikel)
   - **Entfernen:** Startseite, Kategorieseiten, etc.

4. **Filter anwenden:**
   ```
   Filter 1: URL enthält "/a/"
   Filter 2: URL enthält NICHT "/tag/"
   ```

5. **Nur URL-Spalte behalten:**
   - Lösche alle anderen Spalten (Seitenaufrufe, etc.)
   - Behalte nur eine Spalte mit den vollständigen URLs

### 3. Als CSV speichern

1. **Speichern als:** `artikel.csv`
2. **Format:** UTF-8 (ohne BOM)
3. **Eine URL pro Zeile**, keine Header-Zeile
4. **Speicherort:** Im ProLitteris-Projekt-Verzeichnis

### 4. Format prüfen

Die Datei sollte so aussehen:

```
https://bajour.ch/a/artikel-titel-1
https://bajour.ch/a/artikel-titel-2
https://bajour.ch/a/artikel-titel-3
```

**Wichtig:**
- ✅ Vollständige URLs mit `https://bajour.ch`
- ✅ Alle URLs beginnen mit `/a/` (Artikel)
- ✅ Eine URL pro Zeile
- ❌ Keine Header-Zeile
- ❌ Keine Kommas oder Trennzeichen
- ❌ Keine Tag-Seiten (`/tag/`)

### 5. Artikel melden

```bash
php report-csv.php artikel.csv
```

## Beispiel-Filter in Google Sheets

### Formel: URL zusammensetzen
```
=CONCATENATE("https://bajour.ch", A2)
```

### Filter: Nur Artikel
```
=FILTER(A:A, REGEXMATCH(A:A, "/a/"), NOT(REGEXMATCH(A:A, "/tag/")))
```

## Häufige Probleme

### ❌ URLs ohne Domain
**Problem:** `/a/artikel-titel` statt `https://bajour.ch/a/artikel-titel`

**Lösung:** URLs mit `https://bajour.ch` ergänzen

### ❌ Tag-Seiten dabei
**Problem:** Tag-Seiten wie `/tag/basel` sind in der Liste

**Lösung:** Alle URLs mit `/tag/` entfernen

### ❌ Doppelte Einträge
**Problem:** Gleiche Artikel mehrfach in der Liste

**Lösung:** Duplikate entfernen in Sheets: `Data → Remove duplicates`

## Tipps

### Mehrere Jahre melden
Wenn du Artikel aus mehreren Jahren melden willst:

1. Erstelle separate GA-Exporte pro Jahr
2. Kombiniere sie in einer CSV
3. Entferne Duplikate

### Bezahlte Artikel
Bezahlte Artikel (Paywall) werden automatisch mit Faktor 4 gezählt. Keine spezielle Behandlung nötig, solange die Zählmarke eingebaut wurde.

### Test vor Massenverarbeitung
Teste immer erst mit 1-2 URLs:

```bash
echo "https://bajour.ch/a/test-artikel" > test.csv
php report-csv.php test.csv
```

## Zusammenfassung

| Schritt | Tool | Aktion |
|---------|------|--------|
| 1 | Google Analytics | Artikel mit ≥500 Zugriffen exportieren |
| 2 | Google Sheets | URLs mit `https://bajour.ch` ergänzen |
| 3 | Google Sheets | Nur `/a/` behalten, `/tag/` entfernen |
| 4 | Google Sheets | Als `artikel.csv` speichern |
| 5 | Terminal | `php report-csv.php artikel.csv` |

## Automatisierung (Optional)

Für die Zukunft könnte man:
- Google Analytics API anbinden
- Automatischen Export erstellen
- Cronjob einrichten

Siehe: `AUTOMATION.md` (noch nicht erstellt)

---

**Letzte Aktualisierung:** Januar 2026
**Erstellt von:** bajour.ch Team
