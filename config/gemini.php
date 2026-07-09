<?php

define('GEMINI_API_KEY', trim(getenv('GEMINI_API_KEY') ?: ''));

if (GEMINI_API_KEY === '') {
    die('GEMINI_API_KEY não configurada.');
}
define('GEMINI_EMBED_MODEL', 'models/gemini-embedding-2');
define('GEMINI_LLM_MODEL', 'models/gemini-2.5-flash');

function geminiEmbedding(string $texto): array {
    $url = "https://generativelanguage.googleapis.com/v1beta/"
         . GEMINI_EMBED_MODEL . ":embedContent?key=" . GEMINI_API_KEY;

    $body = json_encode([
        "model"   => GEMINI_EMBED_MODEL,
        "content" => ["parts" => [["text" => $texto]]]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_SSL_VERIFYPEER => false, // corrige problema SSL do XAMPP
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $raw      = curl_exec($ch);
    $response = json_decode($raw, true);
    curl_close($ch);

    if (empty($response['embedding']['values'])) {
        echo "ERRO API: " . htmlspecialchars($raw) . "\n";
        flush();
    }

    return $response['embedding']['values'] ?? [];
}

function geminiChat(string $prompt): string {
    $url = "https://generativelanguage.googleapis.com/v1beta/"
         . GEMINI_LLM_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

    $body = json_encode([
        "contents" => [["parts" => [["text" => $prompt]]]]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $raw      = curl_exec($ch);
    $response = json_decode($raw, true);
    curl_close($ch);

        // DEBUG TEMPORÁRIO
    file_put_contents(
        __DIR__ . '/../logs/gemini_debug.log',
        date('Y-m-d H:i:s') . "\nRESPOSTA BRUTA:\n" . $raw . "\n\n",
        FILE_APPEND
    );

    return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
}

function cosineSimilarity(array $a, array $b): float {
    $dot = 0; $normA = 0; $normB = 0;
    foreach ($a as $i => $val) {
        $dot   += $val * $b[$i];
        $normA += $val * $val;
        $normB += $b[$i] * $b[$i];
    }
    if ($normA == 0 || $normB == 0) return 0;
    return $dot / (sqrt($normA) * sqrt($normB));
}