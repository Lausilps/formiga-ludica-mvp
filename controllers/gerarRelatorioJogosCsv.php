<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';
require_once '../helpers/jogoHelper.php';
require_once '../helpers/relatorioJogosHelper.php';
require_once '../helpers/authHelper.php';

protegerAdmin('../login.php');

$tipo = $_GET['tipo'] ?? 'sintetico';

$jogos = buscarJogosRelatorio($conexao, filtrosRelatorioJogosDoGet($_GET));

if ($jogos === null) {
    registrarLog('ERRO', 'Falha ao gerar relatório de jogos: ' . mysqli_error($conexao));
    die('Erro ao gerar relatório.');
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="relatorio_jogos.csv"');

$saida = fopen('php://output', 'w');

// BOM pra acentuação abrir certo quando o arquivo for aberto no Excel
fwrite($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Separador ; em vez de , — é o padrão que o Excel em português espera
// (ponto e vírgula é o separador de campo quando a vírgula já é usada
// como separador decimal no formato numérico brasileiro).
$separador = ';';

$cabecalho = [
    'Nome', 'Jogadores', 'Idade mínima', 'Duração (min)', 'Dificuldade',
    'Preço', 'Status', 'Importado em', 'Importado da Ludopedia', 'Possível duplicado'
];

if ($tipo === 'analitico') {
    $cabecalho[] = 'Descrição';
}

fputcsv($saida, $cabecalho, $separador);

foreach ($jogos as $jogo) {
    $linha = [
        $jogo['nome'],
        $jogo['min_jogadores'] . ' a ' . $jogo['max_jogadores'],
        $jogo['idade_minima'] . '+',
        $jogo['duracao_minutos'],
        formatarDificuldade($jogo['dificuldade']),
        number_format($jogo['preco'], 2, ',', '.'),
        $jogo['ativo'] ? 'Ativo' : 'Inativo',
        !empty($jogo['criado_em']) ? date('d/m/Y', strtotime($jogo['criado_em'])) : '',
        $jogo['origem'] === 'ludopedia' ? 'Sim' : 'Não',
        $jogo['duplicado'] ? 'Sim' : 'Não',
    ];

    if ($tipo === 'analitico') {
        $linha[] = $jogo['descricao'] ?? '';
    }

    fputcsv($saida, $linha, $separador);
}

fclose($saida);

registrarLog('INFO', 'Relatorio CSV de jogos gerado.');
exit;
