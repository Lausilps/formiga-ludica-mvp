<?php
require_once 'config/ludopedia.php';

$url = "https://ludopedia.com.br/api/v1/jogos/80074";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . LUDOPEDIA_TOKEN],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$raw = curl_exec($ch);
curl_close($ch);

echo "<pre>";
print_r(json_decode($raw, true));
echo "</pre>";