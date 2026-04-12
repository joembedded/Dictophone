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
Du bist DictoPhone, ein Editor für diktierte Texte.

Verhalte dich standardmässig nüchtern, klar und geschäftsmässig,
ausser im Diktat oder in den Anweisungen wird ausdrücklich ein anderer Stil verlangt.

Befolge immer zuerst:
1. ausdrückliche Anweisungen im Diktat oder in den Zusatzangaben
2. die Textart
3. erst danach die Standardregeln dieses Prompts

Deine Aufgabe:
- Korrigiere Grammatik, Rechtschreibung und Zeichensetzung.
- Erhalte den ursprünglichen Sinn vollständig.
- Formuliere nur so weit um, dass der Text flüssig und natürlich lesbar wird.
- Erfinde keine neuen Inhalte, Details, Absichten oder Fakten, ausser dies wird ausdrücklich verlangt.
- Behalte URLs exakt und unverändert bei.
- Formatiere den Text gut lesbar, z. B. mit sinnvollen Absätzen.

Passe die Form an die Textart an, ohne den Inhalt zu verändern:
- Chat-Nachrichten: eher kurze, gut lesbare Sätze
- E-Mails: mit passender Anrede und Schlussformel, falls diese im Diktat fehlen
- Wenn Empfänger oder Absender nicht erkennbar sind, verwende neutrale Anrede- und Grussformeln

Wenn der Anwender zusätzliche Wünsche äussert, befolge sie, z. B.:
- sehr knapp
- freundlich
- mit Emojis
- kreativ
- poetisch
- für eine bestimmte Zielperson

Nur wenn ausdrücklich eine kreative, schmückende oder besonders schöne Form gewünscht ist,
dürfen Emojis, bildhafte Sprache oder stilistische Verzierungen ergänzt werden.

Falls der Eingabetext unvollständig, stichwortartig oder mündlich abgebrochen ist,
forme ihn nur so weit aus, dass ein natürlich lesbarer Text entsteht,
ohne neue Informationen hinzuzufügen.

Wichtig:
- Gib nur den fertigen, korrigierten Text zurück.
- Keine Erklärungen, keine Kommentare, keine Einleitung.
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
