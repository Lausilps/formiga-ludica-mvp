<?php
require_once 'config/conexao.php';
require_once 'config/ludopediaLoader.php';

// Busca todos os id_ludopedia que já temos no banco
$result = mysqli_query($conexao, "SELECT id_ludopedia FROM jogos WHERE id_ludopedia IS NOT NULL");
$idsNoBanco = [];
while ($row = mysqli_fetch_assoc($result)) {
    $idsNoBanco[] = (int)$row['id_ludopedia'];
}

// Busca a coleção completa da Ludopedia
$url = "https://ludopedia.com.br/api/v1/colecao?lista=colecao&fl_tem=1&page=1&rows=100";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . LUDOPEDIA_TOKEN],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$raw      = curl_exec($ch);
$response = json_decode($raw, true);
curl_close($ch);

$colecao = $response['colecao'] ?? [];
$total   = $response['total']   ?? 0;

// Filtra os que não estão no banco
$faltantes = array_filter($colecao, function($jogo) use ($idsNoBanco) {
    return !in_array((int)$jogo['id_jogo'], $idsNoBanco);
});

echo "<h2>Relatório de jogos não importados</h2>";
echo "<p>Total na Ludopedia: <b>{$total}</b> | No banco: <b>" . count($idsNoBanco) . "</b> | Faltantes nessa página: <b>" . count($faltantes) . "</b></p>";

if (empty($faltantes)) {
    echo "<p>✅ Todos os jogos dessa página já estão no banco!</p>";
} else {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>id_ludopedia</th><th>Nome</th><th>Link</th></tr>";
    foreach ($faltantes as $jogo) {
        echo "<tr>";
        echo "<td>{$jogo['id_jogo']}</td>";
        echo "<td>{$jogo['nm_jogo']}</td>";
        echo "<td><a href='{$jogo['link']}' target='_blank'>Ver na Ludopedia</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}