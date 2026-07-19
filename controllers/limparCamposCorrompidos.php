<?php

$isCli = php_sapi_name() === 'cli';

// Proteção simples: só roda via terminal ou com token
if (!$isCli) {
    $tokenEsperado = getenv('ADMIN_IMPORT_TOKEN') ?: '';
    $token = $_GET['token'] ?? '';
    if ($tokenEsperado === '' || $token !== $tokenEsperado) {
        die("Acesso negado.");
    }
}

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../helpers/logHelper.php';

header('Content-Type: text/plain; charset=UTF-8');

// jogo_form_campos.php chamava htmlspecialchars() em campos que podiam vir
// nulos do banco (resumo_regras e link_tutorial nunca são preenchidos na
// importação da Ludopedia). O PHP mostra um aviso de "deprecated" nesse
// caso, e como o site exibe avisos na tela, esse aviso aparecia dentro da
// própria caixa de texto — e quem salvasse o jogo sem apagar aquele texto
// visualmente estranho acabava gravando o aviso como se fosse o valor de
// verdade. Já corrigido pra não acontecer de novo; isso aqui só limpa quem
// já foi salvo assim.

echo "🐜 Limpando campos corrompidos por aviso do PHP...\n\n";

$sql1 = "UPDATE jogos SET resumo_regras = '' WHERE resumo_regras LIKE '%Deprecated%htmlspecialchars%'";
mysqli_query($conexao, $sql1);
$afetadosRegras = mysqli_affected_rows($conexao);

$sql2 = "UPDATE jogos SET link_tutorial = '' WHERE link_tutorial LIKE '%Deprecated%htmlspecialchars%'";
mysqli_query($conexao, $sql2);
$afetadosLink = mysqli_affected_rows($conexao);

$resumo = "Limpeza concluída. resumo_regras corrigidos: $afetadosRegras | link_tutorial corrigidos: $afetadosLink";
echo "✅ $resumo\n";
registrarLog('INFO', $resumo);
