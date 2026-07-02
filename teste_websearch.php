<?php
require_once 'config/gemini.php';

$url  = "https://generativelanguage.googleapis.com/v1beta/" . GEMINI_LLM_MODEL . ":generateContent?key=" . GEMINI_API_KEY;
$body = json_encode([
    "contents" => [["parts" => [["text" => "Quem criou o jogo Catan e em que ano foi lançado?"]]]],
    "tools"    => [["google_search" => (object)[]]]
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

echo "<pre>";
print_r($response);
echo "</pre>";