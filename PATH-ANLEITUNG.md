# PHP zum Windows PATH hinzufügen

## Methode 1: Über die Systemeinstellungen (GUI)

### Windows 11:

1. **Öffne die Systemeinstellungen:**
   - Drücke `Windows-Taste + I` (öffnet Einstellungen)
   - Oder: Rechtsklick auf das Windows-Logo → "System"

2. **Gehe zu den erweiterten Systemeinstellungen:**
   - Scrolle nach unten und klicke auf "Erweiterte Systemeinstellungen" (rechts)
   - Oder suche nach "Umgebungsvariablen" in der Windows-Suche

3. **Öffne die Umgebungsvariablen:**
   - Im "Systemeigenschaften" Dialog
   - Klicke unten auf "Umgebungsvariablen..."

4. **Bearbeite den PATH:**
   - Im Bereich "Benutzervariablen für [dein Name]"
   - Suche die Variable "Path" und markiere sie
   - Klicke auf "Bearbeiten..."

5. **Füge PHP hinzu:**
   - Klicke auf "Neu"
   - Gib ein: `C:\xampp\php`
   - Klicke "OK"

6. **Speichern:**
   - Klicke "OK" in allen offenen Dialogen

7. **Teste:**
   - Öffne eine **NEUE** CMD oder PowerShell
   - Gib ein: `php --version`
   - Du solltest die PHP-Version sehen

### Windows 10:

1. Rechtsklick auf "Dieser PC" → "Eigenschaften"
2. Klicke links auf "Erweiterte Systemeinstellungen"
3. Klicke auf "Umgebungsvariablen..."
4. Weiter ab Schritt 4 wie bei Windows 11

---

## Methode 2: Mit PowerShell (Schneller!)

Öffne PowerShell **als Administrator** und führe aus:

```powershell
# Prüfe aktuellen PATH
$env:Path

# Füge PHP zum User-PATH hinzu
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\xampp\php", "User")

# ODER füge zu System-PATH hinzu (benötigt Admin-Rechte)
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\xampp\php", "Machine")
```

Dann:
1. **Schließe PowerShell**
2. **Öffne neue PowerShell**
3. Teste: `php --version`

---

## Methode 3: Mit CMD (als Administrator)

```cmd
setx PATH "%PATH%;C:\xampp\php"
```

Dann:
1. **Schließe CMD**
2. **Öffne neue CMD**
3. Teste: `php --version`

---

## Troubleshooting

### "php --version" funktioniert immer noch nicht

**Problem 1: Alte Terminal-Session**
- Du musst das Terminal/CMD/PowerShell **schließen und neu öffnen**
- PATH-Änderungen werden nur in neuen Sessions wirksam

**Problem 2: Falscher Pfad**
- Prüfe ob PHP wirklich unter `C:\xampp\php\php.exe` liegt
- Passe den Pfad entsprechend an

**Problem 3: XAMPP nicht unter C:\xampp**
- Wenn XAMPP woanders installiert ist (z.B. `C:\Program Files\xampp`)
- Verwende den korrekten Pfad

### Pfad prüfen in CMD:

```cmd
where php
```

Sollte anzeigen: `C:\xampp\php\php.exe`

### Aktuellen PATH anzeigen:

**CMD:**
```cmd
echo %PATH%
```

**PowerShell:**
```powershell
$env:Path -split ';'
```

---

## Für Git Bash

Wenn du Git Bash verwendest, bearbeite `~/.bashrc`:

```bash
# In Git Bash:
nano ~/.bashrc

# Füge am Ende hinzu:
export PATH="/c/xampp/php:$PATH"

# Speichern: Ctrl+O, Enter, Ctrl+X

# Neu laden:
source ~/.bashrc

# Teste:
php --version
```

---

## Zusammenfassung

**Kürzester Weg:**

1. Suche in Windows nach: **"Umgebungsvariablen"**
2. Klicke auf: **"Umgebungsvariablen für dieses Konto bearbeiten"**
3. Wähle **"Path"** → **"Bearbeiten"**
4. Klicke **"Neu"**
5. Gib ein: `C:\xampp\php`
6. Klicke 3x **"OK"**
7. **Neues Terminal öffnen**
8. Teste: `php --version`

✅ Fertig!
