<?php
require_once 'config/gemini.php';

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . GEMINI_API_KEY;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$raw = curl_exec($ch);
curl_close($ch);

$response = json_decode($raw, true);

echo "<h3>Modelos que suportam generateContent:</h3><ul>";
foreach ($response['models'] ?? [] as $model) {
    if (in_array('generateContent', $model['supportedGenerationMethods'] ?? [])) {
        echo "<li><strong>{$model['name']}</strong> — {$model['displayName']}</li>";
    }
}
echo "</ul>";

echo "<h3>Modelos que suportam embedContent:</h3><ul>";
foreach ($response['models'] ?? [] as $model) {
    if (in_array('embedContent', $model['supportedGenerationMethods'] ?? [])) {
        echo "<li><strong>{$model['name']}</strong> — {$model['displayName']}</li>";
    }
}
echo "</ul>";