# 🎙️ DictoPhone

> Browser-basierte **Diktier-App mit KI-Korrektur** — optimiert für Smartphone (Android)

Diktierter Text lässt sich direkt per **WhatsApp** teilen, in die **Zwischenablage** kopieren
oder von einer **KI (OpenAI)** korrigieren und umformulieren. Als PWA auf Android installierbar.

🔗 **Live-Demo:** [joembedded.de/x3/dictophone](https://joembedded.de/x3/dictophone)

---

## 💡 Motivation

Asynchrone Sprachkommunikation wie bei Gemini gefällt mir gut — ich wollte etwas Ähnliches
für WhatsApp, Mails usw. haben.

Die Web Speech API liefert in Chrome/Edge bereits brauchbare Ergebnisse, hat aber Schwächen
bei Satzzeichen. DictoPhone ergänzt daher gesprochene Schlüsselwörter für Satzzeichen,
Zeilenumbrüche und Absätze.

Noch witziger: eine KI korrigiert und formatiert den Text auf Wunsch — der Prompt ist
standardmäßig auf *„geschäftsmäßig und nüchtern"* gestellt, kann aber auch freie
Formatierungsanweisungen annehmen.

---

## 🖱️ Bedienung

| Schaltfläche    | Funktion                                                                |
| --------------- | ----------------------------------------------------------------------- |
| **Aufnehmen**   | Mikrofon starten / stoppen — Text wird an der Cursor-Position eingefügt |
| **Korrigieren** | Text per KI (OpenAI) korrigieren und formatieren                        |
| **An WhatsApp** | Text über Share-API teilen oder WhatsApp-Link öffnen                    |
| **Kopieren**    | Gesamten Text in die Zwischenablage kopieren                            |
| **Zurück**      | Letzten Schritt rückgängig machen (Undo-Stack)                          |
| **(ℹ️)**        | App-Info anzeigen                                                       |
| **✕**           | Gesamten Text löschen (ebenfalls im Undo-Stack gesichert)               |

> 💬 Das Textfeld öffnet die Tastatur erst nach Antippen — so stört sie beim Diktieren nicht.

---

## 🗣️ Sprachbefehle für Satzzeichen

| Gesprochen        | Ergebnis |
| ----------------- | :------: |
| `Punkt`           | `.`      |
| `Komma`           | `,`      |
| `Fragezeichen`    | `?`      |
| `Ausrufezeichen`  | `!`      |
| `Doppelpunkt`     | `:`      |
| `Semikolon`       | `;`      |
| `Gedankenstrich`  | `–`      |
| `Neue Zeile`      | `↵`      |
| `Neuer Absatz`    | `↵↵`     |

> Bei mehr als einem Wort ohne abschließendes Satzzeichen wird automatisch ein Punkt ergänzt.

---

## 🤖 KI-Korrektur — Beispiele

DictoPhone formatiert Texte automatisch korrekt, lesbar und passend zur Textart.
Direktive Steuerinfos lassen sich einfach im Diktat mitsprechen:

**📧 Standard — geschäftliche Mail oder Chat**
```
Ich schaffe den Termin nachmittag nicht
und melde mich nächste Woche wieder.
```

**💚 Kreativ — persönliche Nachricht**
```
Schreibe das als liebevolle Nachricht an Anna, mit etwas Verzierung und warmem Ton:
danke für deine Hilfe, ich habe mich sehr darüber gefreut.
```

**✨ Kreativ — Gedicht**
```
Formuliere das als kleines Gedicht für Paul: ich wünsche dir viel Glück
für deinen neuen Anfang und hoffe, dass du deinen Weg findest.
```

---

## 📁 Projektstruktur

```
sw/
├── index.html            # Haupt-Seite (PWA-fähig)
├── manifest.json         # PWA-Manifest (Name, Icons, Theme-Farbe)
├── icons/
│   └── icon.svg          # App-Icon (Mikrofon, Dark-Theme)
├── css/
│   └── dictophone.css    # Dark-Theme, responsives Grid-Layout
├── js/
│   └── dictophone.js     # App-Logik (Web Speech API, Undo, KI-Korrektur)
└── api/
    └── correct.php       # PHP-Backend: Textkorrektur via OpenAI API
```

---

## ⚙️ Technische Details

### HTML — `index.html`

- Grid-Layout mit allen Aktions-Buttons, `<textarea id="output">` als Ausgabefeld.
- `<dialog id="msg-dialog">` als stilisierter Ersatz für `alert()`.
- `inputmode="none"` verhindert automatisches Öffnen der Tastatur beim Laden.
- PWA-Manifest und `theme-color` eingebunden.

### JavaScript — `dictophone.js`

| Funktion / Symbol       | Beschreibung                                                              |
| ----------------------- | ------------------------------------------------------------------------- |
| Web Speech API          | `SpeechRecognition` — Chrome, Edge, Android-Browser                       |
| `keyWordReplace(text)`  | Ersetzt gesprochene Keywords durch Satzzeichen                            |
| `enabler()`             | Steuert `disabled`-Zustand aller Buttons (`isRecording` / `isCorrecting`) |
| `setRecordText()`       | Aktualisiert Button-Text und CSS-Klasse (`recording-on` / `recording-off`)|
| `historyStack`          | Undo-Stack für Einfügen, Korrektur, Enter und Löschen                     |
| `showMsg(title, html)`  | Öffnet `<dialog>` modal (Ersatz für `alert()`)                            |
| `correctText()`         | Sendet Text per `fetch` an `api/correct.php`, schreibt Ergebnis zurück    |

### PHP — `api/correct.php`

- Empfängt Text per `POST`, leitet ihn an die OpenAI API weiter.
- Gibt `{ correctedText: "…" }` als JSON zurück.
- Modell: `gpt-4o` — schnell & kostengünstig, höhere Modelle kompatibel.
- API-Key aus `secret/keys.inc.php` (Konstante `OPENAI_API_KEY`, **nicht im Repository**).

---

## ✅ Voraussetzungen

- Webserver mit PHP (z. B. Apache / localhost)
- OpenAI API-Key in `secret/keys.inc.php`
- Chrome, Edge oder Chromium-basierter Android-Browser
- HTTPS oder `localhost` für PWA-Installation und Mikrofon-Zugriff

---

## 📄 Lizenz

**MIT License** — © JoEmbedded

> Permission is hereby granted, free of charge, to any person obtaining a copy of this software
> and associated documentation files (the "Software"), to deal in the Software without restriction,
> including without limitation the rights to use, copy, modify, merge, publish, distribute,
> sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
> furnished to do so, subject to the following conditions:
>
> The above copyright notice and this permission notice shall be included in all copies or
> substantial portions of the Software.
>
> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED.

---

**[joembedded.de](https://joembedded.de)**