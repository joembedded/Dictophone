// Dictophone.js - Einfache Diktier-App mit Web Speech API

// Browser-Kompatibilität prüfen (Chrome nutzt meist webkitPrefix)
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
if (!SpeechRecognition) { document.addEventListener('DOMContentLoaded', () => showMsg('Fehler', 'Browser unterstützt Web Speech API nicht. Nutze Chrome/Edge/Android.')); }


const recordBtn = document.getElementById('record-btn');
const correctBtn = document.getElementById('correct-btn');
const whatsappBtn = document.getElementById('whatsapp-btn');
const copyBtn = document.getElementById('copy-btn');
const undoBtn = document.getElementById('undo-btn');
const delBtn = document.getElementById('del-btn');

const output = document.getElementById('output');

const recognition = new SpeechRecognition();
// Einstellungen
recognition.lang = 'de-DE';
recognition.continuous = false; // Nur SIngle läuft auf Handy vernünftig
recognition.interimResults = true; // Macht aber auf Handy kein Sinn

let isRecording = false;
let isCorrecting = false; // Verhindert Aufnahme während Korrektur

function enabler() {
    const recording = isRecording !== 0;
    recordBtn.disabled = isCorrecting;
    correctBtn.disabled = recording || isCorrecting;
    whatsappBtn.disabled = recording || isCorrecting;
    copyBtn.disabled = recording || isCorrecting;
    undoBtn.disabled = recording || isCorrecting;
    delBtn.disabled = recording || isCorrecting;
}

function setRecordText() {
    if (!isRecording) {
        recordBtn.textContent = "Aufnehmen";
        recordBtn.classList.replace('recording-on', 'recording-off');
    } else {
        recordBtn.textContent = "Stoppen";
        recordBtn.classList.replace('recording-off', 'recording-on');
    }
    enabler();
}

function stopRecording() {
    if (!isRecording) return;
    recognition.stop();
    isRecording = 0;
    setRecordText();
}

const msgDialog = document.getElementById('msg-dialog');
const msgTitle = document.getElementById('msg-title');
const msgText = document.getElementById('msg-text');

document.getElementById('msg-ok').addEventListener('click', () => {
    msgDialog.close();
    output.focus();
});

function showMsg(title, ihtml) {
    msgTitle.textContent = title;
    msgText.innerHTML = ihtml;
    msgDialog.showModal();
}


// ---- Recognition-Events ----
function keyWordReplace(text) {
    const replacements = {
        "punkt": ".",
        "komma": ",",
        "fragezeichen": "?",
        "ausrufezeichen": "!",
        "doppelpunkt": ":",
        "semikolon": ";",
        "gedankenstrich": "-",

        // Spezialbefehle
        "neue zeile": "\n",
        "neuer absatz": "\n\n",

    };
    for (const [key, value] of Object.entries(replacements)) {
        const regex = new RegExp(`\\b${key}\\b`, 'gi');
        text = text.replace(regex, value);
    }

    console.log(`[filtered] '${text}'`);
    let anzWords = 0;
    const words = text.split(/\s+/);
    words.forEach(element => {
        const et = element.trim();
        if (et.length > 1) anzWords++;
        if (Object.values(replacements).includes(et)) anzWords = 0;
    });
    if (anzWords > 1) text += '.';
    console.log(`[Text: '${text}]' - [Wörter: ${anzWords}]`);
    return text;
}


recognition.onerror = (event) => {
    console.error('[recognition error]', event.error);
    isRecording = 0;
    setRecordText();
};

recognition.onend = () => {
    if (isRecording) {
        isRecording = 0;
        setRecordText();
    }
};

const historyStack = [];

recognition.onresult = (event) => {
    const resultIdx = event.resultIndex;
    const transRes = event.results[resultIdx][0].transcript.trim();
    const isFinal = event.results[resultIdx].isFinal;

    if (!isFinal) { // Das hat eher akademischen Wert
        console.log(`[interim] '${transRes}'`);
    }

    if (isFinal) {
        console.log(`[final] '${transRes}'`);
        let filteredFinal = keyWordReplace(transRes);
        // Text an der Caret-Position einfügen
        const start = output.selectionStart;
        const end = output.selectionEnd;
        const nowState = { value: output.value, cursorPos: start };
        historyStack.push(nowState);

        const vorne = output.value.slice(0, start);
        const prefix = vorne.split(/\s+/).slice(-1)[0] ? ' ' : '';
        const hinten = output.value.slice(end);
        output.value = vorne + prefix + filteredFinal + hinten;
        // Cursor hinter den eingefügten Text setzen
        const newPos = start + prefix.length + filteredFinal.length;
        output.focus();
        output.setSelectionRange(newPos, newPos);
    }
}

async function correctText() {
    const originalText = output.value;

    if (!originalText) { showMsg('Hinweis', 'Nichts zum Korrigieren da!'); return; }
    isCorrecting = true;
    enabler();

    const start = output.selectionStart;
    const nowState = { value: output.value, cursorPos: start };
    historyStack.push(nowState);

    // Visuelles Feedback
    output.value = "Korrigiere...\nBitte warten...";

    const formData = new FormData();
    formData.append('text', originalText);

    try {
        const apiurl = './api/correct.php';
        const response = await fetch(apiurl, {
            method: 'POST',
            body: formData
        });

        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error("Ungültige JSON-Antwort:", responseText);
            throw new Error("Server-Antwort ist kein gültiges JSON");
        }

        if (data.correctedText) {
            output.value = data.correctedText + ' ';
        } else {
            showMsg('Fehler', data.error);
            output.value = originalText; // Text zurücksetzen bei Fehler
        }
    } catch (error) {
        console.error("Fehler beim API-Aufruf:", error);
        output.value = originalText;
    } finally {
        isCorrecting = false;
        enabler();
        output.focus();
    }
}


// ------------Event-Listener----------
recordBtn.addEventListener('click', () => {
    output.focus();
    if (!isRecording) {
        recognition.start();
        isRecording = 1;
        setRecordText();
        setOutputKeyboard('none');
    } else {
        recognition.stop();
        isRecording = 0;
        setRecordText();
    }
});

document.getElementById('whatsapp-btn').addEventListener('click', () => {
    const text = document.getElementById('output').value.trim();
    if (!text) {
        showMsg('Hinweis', 'Nichts zum Teilen da!');
    } else {

        if (navigator.share) {
            navigator.share({
                title: 'Text DictoPhone',
                text: text
            }).catch((error) => {
                console.log('Fehler beim Teilen', error);
            });
        } else {
            // Fallback: WhatsApp-Link
            const url = 'https://wa.me/?text=' + encodeURIComponent(text);
            window.open(url, '_blank');
        }
    }
    output.focus();

});

// Button-Event verknüpfen
document.getElementById('correct-btn').addEventListener('click', correctText);


copyBtn.addEventListener('click', () => {
    const selektierterText = output.value;
    if (!selektierterText) {
        showMsg('Hinweis', 'Nichts zum Kopieren da!');
    } else {
        navigator.clipboard.writeText(selektierterText).then(() => {
            showMsg('Kopiert', selektierterText);
        }).catch(() => {
            showMsg('Fehler', 'Fehler beim Kopieren!');
        });
    }
    output.focus();
});

document.getElementById('undo-btn').addEventListener('click', () => {
    output.focus();
    if (historyStack.length > 0) {
        const prevState = historyStack.pop();
        output.value = prevState.value;
        output.setSelectionRange(prevState.cursorPos, prevState.cursorPos);
    }
});

document.getElementById('info-btn').addEventListener('click', () => {
    const info = `Diktier-APP mit KI-Korrektur<br>
<div style="display: inline;">'DictoPhone' ist ein kleines Tool, welches ich primär für mich selbst entwickelt habe,
um schnell und unkompliziert Texte zu diktieren, aufzuhübschen, zu korrigieren und z.B. mit WhatsApp weiterzuverwenden oder in schnell in die Zwischenablage zu kopieren.<br>
<small>Datenschutz: DictoPhone ist experimentelle Software. Zur Spracherkennung wird die Chrome-API verwendet, zur Korrektur OpenAI-API.
Beide APIs laufen auf externen Servern, auf die ich keinen Einfluss habe. Mein Server speichert eine temporäre Logdatei mit den ersten paar Zeichen der korrigierten Texte (siehe 'correct.php').
Für eigen Verwendung empfehle ich, das Projekt selbst zu hosten. Benötigt wird lediglich PHP. Das Projekt ist komplett frei auf meiner GitHub-Seite verfügbar.</small></div>

Info: &nbsp; &nbsp; <a href='https://github.com/joembedded/Dictophone' target='_blank'>https://github.com/joembedded/Dictophone</a>
Mail: &nbsp; &nbsp; <a href='mailto:joembedded@gmail.com>' target='_blank'>joembedded@gmail.com</a>
Version: 0.1 (12.04.2026) <br>
<small>(C)JoEmbedded - MIT-Lizenz</small>`;
    showMsg("DictoPhone", info);
});

document.getElementById('del-btn').addEventListener('click', () => {
    const start = output.selectionStart;
    const nowState = { value: output.value, cursorPos: start };
    historyStack.push(nowState);

    output.value = '';
    output.focus();
});

// -- fuers Output--
// 'text' oder 'none' (= Default)
function setOutputKeyboard(mode) {
    if (output.inputMode !== mode) {
        output.inputMode = mode;
        output.blur();
        setTimeout(() => output.focus(), 100);
    }
}

output.addEventListener('click', () => {
    setOutputKeyboard('text');
});

output.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        historyStack.push({ value: output.value, cursorPos: output.selectionStart });
    }
});


output.focus();
