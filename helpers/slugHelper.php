<?php

// Mapa fixo de acentuação -> letra sem acento. Usado em vez de
// iconv(...TRANSLIT...), cujo resultado pra "ç"/"é"/etc varia de acordo com
// o sistema/locale (gera lixo tipo "edic-ao" em vez de "edicao" em alguns
// ambientes) — com o mapa o resultado é sempre o mesmo, local ou em produção.
const MAPA_ACENTOS = [
    'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
    'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
    'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
    'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
    'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
    'ç' => 'c', 'ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y',
];

// Gera um slug a partir do nome do jogo (ex: "Bananagrams!" -> "bananagrams"),
// usado nos links diretos (?jogo=slug) do catálogo.
function gerarSlug(string $texto): string
{
    $texto = strtolower($texto);
    $texto = strtr($texto, MAPA_ACENTOS);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim($texto, '-');
}
