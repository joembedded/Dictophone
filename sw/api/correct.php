<?php

/**
 * OpenAI Korrektor
 * http://localhost/wrk/ai/dictophone/sw/api/correct.php?text=Elefanntenrenen
 *
 */

declare(strict_types=1);

// Keine PHP-Fehler in den Output ausgeben – würde JSON korrumpieren
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Configuration
// ACHTUNG: Der Liveserver WILL bei internen Aenderungen das File neu laden!

$log = 1; // 0: Silent, 1: Logfile schreiben, 2: Log complete Reply

$xlog = "correct";
include_once __DIR__ . '/../php_tools/logfile.php';

// CORS headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load API keys
include_once __DIR__ . '/../secret/keys.inc.php';
$apiKey = OPENAI_API_KEY;

// ========== Funktionen ==========

// Den Text vom JavaScript-Frontend empfangen
$inputText = $_REQUEST['text'] ?? '';

if (empty($inputText)) {
    echo json_encode(['error' => 'Kein Text empfangen']);
    exit;
}

$systemPrompt = <<<'EOT'
Du bist DictoPhone, ein Editor für diktierte Texte. Deine Rolle ist es, Diktate professionell und präzise zu korrigieren.

PRIORITÄTEN (in dieser Reihenfolge):
1. Befolge ausdrückliche Anweisungen im Diktat oder den Zusatzangaben
2. Erkenne Stilwünsche an Schlüsselwörtern wie 'Anweisung', 'Stil', 'Textart', 'Form', 'Formulierung'
3. Wende die Standardregeln dieses Prompts an

STANDARDVERHALTEN:
- Nüchtern, klar und geschäftsmässig (ausser anderer Stil ist verlangt)
- Tone of Voice: präzise und zuverlässig

KERNAUFGABEN:
- Korrigiere Grammatik, Rechtschreibung und Zeichensetzung
- Erhalte den ursprünglichen Sinn und die Absicht vollständig
- Minimale Umformulierung für Flüssigkeit und Lesbarkeit
- Erfinde KEINE neuen Inhalte, Details oder Fakten (ausser ausdrücklich verlangt)
- Behalte URLs, E-Mail-Adressen und technische Daten exakt unverändert
- Formatiere übersichtlich mit sinnvollen Absätzen und Strukturierung

TEXTART-ANPASSUNG (Form beibehalten, Inhalt unverändert):
- Chat-Nachrichten: kurz, prägnant, gut lesbar
- E-Mails: passende Anrede und Schlussformel ergänzen, falls fehlend
- Neutrale Standardformeln bei unbekanntem Empfänger/Absender
- Geschäftstexte: formell und strukturiert

BESONDERE WÜNSCHE:
Wenn der Anwender fordert: 'sehr knapp', 'freundlich', 'mit Emojis', 'kreativ', 'poetisch' – befolge diese Anweisung.
Emojis, bildhafte Sprache oder stilistische Verzierungen NUR bei ausdrücklich kreativen oder schönen Formen.

UNVOLLSTÄNDIGE TEXTE:
Bei stichwortartigen oder abgebrochenen Diktaten: nur so weit ausformulieren, dass ein natürlich lesbarer Text entsteht. Keine neuen Informationen hinzufügen.

PERSONALISIERTE REGELN:
- Mein Name: Jürgen
- Laura (Tochter): oft falsch geschrieben als 'Laura Lee', Kosename 'Laurali' → verwende ein zufälliges liebevolles Emoji (😘 🌻 🌞 ❤️ 🥰)
- Ute (Ehefrau): auch 'Uti' geschrieben → verwende ein zufälliges liebevolles Emoji (😘 🌻 ❤️ 🌞 🥰)
- Marcus: immer mit 'c', nicht 'k'

AUSGABE:
- NUR den fertigen, korrigierten Text
- Keine Erklärungen, Kommentare oder Einleitungen
EOT;

$data = [
    "model" => "gpt-5.4-mini", //  "gpt-4o", etc...
    "messages" => [
        [
            "role" => "system",
            "content" => $systemPrompt
        ],
        [
            "role" => "user",
            "content" => $inputText
        ]
    ],
    "temperature" => 0.2
];

if (1) {
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);

    $response = curl_exec($ch);
    //curl_close($ch);

    if ($response === false) {
        echo json_encode(['error' => 'curl error: ' . curl_error($ch)]);
        exit;
    }

    $result = json_decode($response, true);
    $correctedText = $result['choices'][0]['message']['content'] ?? 'Fehler bei der Korrektur';
} else {
    $correctedText = "Dummy: '" . $inputText ."'"; // Zum Testen ohne API-Aufruf";
}

// Ergebnis als JSON zurückgeben
echo json_encode(['correctedText' => trim($correctedText)]);

// Text kürzen wenn länger als $maxlog Zeichen
$maxlog = 120;
$displayText = $correctedText;
if (strlen($correctedText) > $maxlog) {
    $displayText = substr($correctedText, 0, $maxlog) . '... (' . strlen($correctedText) . ')';
}

// Zeichen < 32 escapen (außer Tab, LF, CR für lesbarkeit)
$displayText = str_replace("\n", '\n', $displayText);

log2file("$xlog: $displayText");
