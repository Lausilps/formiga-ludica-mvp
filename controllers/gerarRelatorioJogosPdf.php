<?php

// O dompdf consome muita memória ao montar o CSS/DOM interno, proporcional
// ao número de linhas da tabela — o padrão de 128M do PHP estoura ao gerar
// o relatório sem filtro (catálogo inteiro) no modo analítico, que dobra as
// linhas com a descrição de cada jogo.
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 0);

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';
require_once '../helpers/jogoHelper.php';
require_once '../helpers/relatorioJogosHelper.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

$tipo = $_GET['tipo'] ?? 'sintetico';

$jogos = buscarJogosRelatorio($conexao, filtrosRelatorioJogosDoGet($_GET));

if ($jogos === null) {
    registrarLog('ERRO', 'Falha ao gerar relatório de jogos: ' . mysqli_error($conexao));
    die('Erro ao gerar relatório.');
}

$html = '
<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #333;
    }

    h1 {
        color: #2d3a22;
        margin-bottom: 4px;
    }

    .subtitulo {
        margin-bottom: 18px;
        color: #666;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #6b8e23;
        color: white;
        padding: 7px;
        text-align: left;
        font-size: 10px;
    }

    td {
        border-bottom: 1px solid #ddd;
        padding: 6px;
        vertical-align: top;
    }

    .descricao {
        font-size: 10px;
        color: #444;
        line-height: 1.4;
    }

    .linha-duplicada td {
        background: #fff3cd;
    }

    .flag-duplicado {
        color: #8a6d00;
        font-weight: bold;
    }
</style>

<h1>Formiga Ludica - Relatorio de Jogos</h1>
<div class="subtitulo">
    Gerado em ' . date('d/m/Y H:i') . ' | Tipo: ' . ucfirst($tipo) . '
</div>

<table>
    <thead>
        <tr>
            <th>Nome</th>
            <th>Jogadores</th>
            <th>Idade</th>
            <th>Duracao</th>
            <th>Dificuldade</th>
            <th>Preco</th>
            <th>Status</th>
            <th>Importado em</th>
            <th>Importado?</th>
            <th>Duplicado?</th>
        </tr>
    </thead>
    <tbody>
';

foreach ($jogos as $jogo) {
    $classeLinha = $jogo['duplicado'] ? ' class="linha-duplicada"' : '';

    $html .= '
        <tr' . $classeLinha . '>
            <td>' . htmlspecialchars($jogo['nome']) . '</td>
            <td>' . $jogo['min_jogadores'] . ' a ' . $jogo['max_jogadores'] . '</td>
            <td>' . $jogo['idade_minima'] . '+</td>
            <td>' . $jogo['duracao_minutos'] . ' min</td>
            <td>' . formatarDificuldade($jogo['dificuldade']) . '</td>
            <td>R$ ' . number_format($jogo['preco'], 2, ',', '.') . '</td>
            <td>' . ($jogo['ativo'] ? 'Ativo' : 'Inativo') . '</td>
            <td>' . (!empty($jogo['criado_em']) ? date('d/m/Y', strtotime($jogo['criado_em'])) : '—') . '</td>
            <td>' . ($jogo['origem'] === 'ludopedia' ? 'Sim' : 'Não') . '</td>
            <td class="flag-duplicado">' . ($jogo['duplicado'] ? '⚠ Sim' : '—') . '</td>
        </tr>
    ';

    if ($tipo === 'analitico') {
        $html .= '
            <tr' . $classeLinha . '>
                <td colspan="10" class="descricao">
                    <strong>Descricao:</strong> ' . nl2br(htmlspecialchars($jogo['descricao'] ?? '')) . '
                </td>
            </tr>
        ';
    }
}

$html .= '
    </tbody>
</table>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

registrarLog('INFO', 'Relatorio PDF de jogos gerado.');

$dompdf->stream('relatorio_jogos.pdf', ['Attachment' => false]);
exit;