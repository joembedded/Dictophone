# Diktafon

> **Browser-basierte Diktier-App mit KI-Korrektur** — optimiert für Smartphone (Android)

Diktierter Text lässt sich direkt per WhatsApp teilen, in die Zwischenablage kopieren  
oder von einer KI (OpenAI) korrigieren und umformulieren.

---

## Motivation

Asynchrone Sprachkommunikation wie bei Gemini gefällt mir gut — ich wollte etwas Ähnliches  
für WhatsApp, Mails usw. haben.

Die Web Speech API liefert in Chrome/Edge bereits brauchbare Ergebnisse, hat aber  
Schwächen bei Satzzeichen. Diktafon ergänzt daher gesprochene Schlüsselwörter für  
Satzzeichen, Zeilenumbrüche und Absätze.

Noch witziger: eine KI korrigiert und formatiert den Text auf Wunsch.  
Der Prompt ist standardmäßig auf *„geschäftsmäßig und nüchtern"* gestellt,  
kann aber auch freie Formatierungsanweisungen annehmen.

---

## Beispiele

### Standard-Korrektur

| Eingabe | Ergebnis |
|--------|---------|
| `der Zug kommt gegen 16 Uhr an ich würde danach noch eine Kleinigkeit essen` | `Der Zug kommt gegen 16 Uhr an. Ich würde danach noch eine Kleinigkeit essen.` |

### Mit Formatierungsanweisung

Sprich einfach eine Anweisung vor den eigentlichen Text:

> *„formatiere blumig und mit vielen Emojis und als 8-zeiliges Gedicht. Der Zug kommt gegen 16 Uhr an …"*

```
Der Zug rollt an um vier Uhr nachmittags 🌅🚂,
Ein Abenteuer beginnt, oh welch ein Tag! 🌟✨
Danach ein Häppchen, klein und fein 🍽️,
Ein Fest für den Gaumen, so soll es sein! 🎉🥂
…
```

---

## Bedienung

| Schaltfläche   | Funktion |
|---------------|---------|
| **Aufnehmen** | Mikrofon starten / stoppen — Text wird an der Cursor-Position eingefügt |
| **Korrigieren** | Text per KI (OpenAI) korrigieren und formatieren |
| **An WhatsApp** | Text über Share-API teilen oder WhatsApp-Link öffnen |
| **Kopieren** | Gesamten Text in die Zwischenablage kopieren |
| **Zurück** | Letzten Schritt rückgängig machen (Undo-Stack) |
| **✕** | Gesamten Text löschen (ebenfalls im Undo-Stack gesichert) |

> Das Textfeld öffnet die Tastatur erst nach Antippen — so stört sie beim Diktieren nicht.

---

## Sprachbefehle für Satzzeichen

| Gesprochen       | Ergebnis |
|-----------------|---------|
| `Punkt`         | `.`     |
| `Komma`         | `,`     |
| `Fragezeichen`  | `?`     |
| `Ausrufezeichen`| `!`     |
| `Doppelpunkt`   | `:`     |
| `Semikolon`     | `;`     |
| `Gedankenstrich`| `-`     |
| `Neue Zeile`    | `↵`     |
| `Neuer Absatz`  | `↵↵`    |

> Bei mehr als einem gesprochenen Wort ohne abschließendes Satzzeichen wird automatisch ein Punkt ergänzt.

---

## Projektstruktur

```
sw/
├── index.html            # Haupt-Seite
├── css/
│   └── dictophone.css    # Dark-Theme, responsives Layout
├── js/
│   └── dictophone.js     # App-Logik (Web Speech API, Undo, KI-Korrektur)
└── api/
    └── correct.php       # PHP-Backend: Textkorrektur via OpenAI API
```

---

## Technische Details

### HTML (`index.html`)
- `<div class="controls">` mit sechs Aktions-Buttons, `<textarea id="output">` als Ausgabefeld.
- `<dialog id="msg-dialog">` als stilisierter Ersatz für `alert()`.
- `inputmode="none"` verhindert automatisches Öffnen der Tastatur beim Laden.

### JavaScript (`dictophone.js`)
- **Web Speech API** (`SpeechRecognition`) — Chrome, Edge, Android-Browser.
- **`keyWordReplace(text)`** — ersetzt gesprochene Keywords durch Satzzeichen.
- **`enabler()`** — steuert den `disabled`-Zustand aller Buttons abhängig von `isRecording` / `isCorrecting`.
- **`setRecordText()`** — aktualisiert Button-Text und CSS-Klasse (`recording-on` / `recording-off`).
- **`historyStack`** — Undo-Stack; wird bei Einfügen, Korrektur, Enter und Löschen befüllt.
- **`showMsg(title, html)`** — öffnet den `<dialog>` modal (Ersatz für `alert()`).
- **`correctText()`** — sendet Text per `fetch` an `api/correct.php` und schreibt das Ergebnis zurück.

### PHP (`api/correct.php`)
- Empfängt Text per `POST`, leitet ihn an die OpenAI API weiter.
- Gibt `{ correctedText: "…" }` als JSON zurück.
- Modell: `gpt-4o` (schnell & kostengünstig, höhere Modelle ebenfalls kompatibel).
- API-Key aus `secret/keys.inc.php` (Konstante `OPENAI_API_KEY`, **nicht im Repository**).

---

## Voraussetzungen

- Webserver mit PHP (z. B. Apache/localhost)
- OpenAI API-Key in `secret/keys.inc.php`
- Chrome, Edge oder Chromium-basierter Android-Browser

---

## Lizenz

MIT License — © 2025 JoEmbedded

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED.

---

**[joembedded.de](https://joembedded.de)**
