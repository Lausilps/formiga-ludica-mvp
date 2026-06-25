<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

$nome = $_GET['nome'] ?? '';
$preco_de = $_GET['preco_de'] ?? '';
$preco_ate = $_GET['preco_ate'] ?? '';
$duracao_de = $_GET['duracao_de'] ?? '';
$duracao_ate = $_GET['duracao_ate'] ?? '';
$jogadores_de = $_GET['jogadores_de'] ?? '';
$jogadores_ate = $_GET['jogadores_ate'] ?? '';
$idade_de = $_GET['idade_de'] ?? '';
$idade_ate = $_GET['idade_ate'] ?? '';
$dificuldade = $_GET['dificuldade'] ?? '';
$tipo = $_GET['tipo'] ?? 'sintetico';

$where = [];

if ($nome !== '') {
    $nome = mysqli_real_escape_string($conexao, $nome);
    $where[] = "nome LIKE '%$nome%'";
}

if ($preco_de !== '') $where[] = "preco >= $preco_de";
if ($preco_ate !== '') $where[] = "preco <= $preco_ate";

if ($duracao_de !== '') $where[] = "duracao_minutos >= $duracao_de";
if ($duracao_ate !== '') $where[] = "duracao_minutos <= $duracao_ate";

if ($jogadores_de !== '') $where[] = "max_jogadores >= $jogadores_de";
if ($jogadores_ate !== '') $where[] = "min_jogadores <= $jogadores_ate";

if ($idade_de !== '') $where[] = "idade_minima >= $idade_de";
if ($idade_ate !== '') $where[] = "idade_minima <= $idade_ate";

if ($dificuldade !== '') {
    $dificuldade = mysqli_real_escape_string($conexao, $dificuldade);
    $where[] = "dificuldade = '$dificuldade'";
}

if (!isset($_GET['mostrar_inativos'])) {
    $where[] = "ativo = 1";
}

if (isset($_GET['somente_incompletos'])) {
    $where[] = "(
        descricao IS NULL OR descricao = ''
        OR preco IS NULL OR preco = 0
        OR min_jogadores IS NULL
        OR max_jogadores IS NULL
        OR idade_minima IS NULL
        OR duracao_minutos IS NULL
    )";
}

$whereSql = '';

if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$sql = "SELECT *
        FROM jogos
        $whereSql
        ORDER BY nome ASC";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
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
        </tr>
    </thead>
    <tbody>
';

while ($jogo = mysqli_fetch_assoc($resultado)) {
    $html .= '
        <tr>
            <td>' . htmlspecialchars($jogo['nome']) . '</td>
            <td>' . $jogo['min_jogadores'] . ' a ' . $jogo['max_jogadores'] . '</td>
            <td>' . $jogo['idade_minima'] . '+</td>
            <td>' . $jogo['duracao_minutos'] . ' min</td>
            <td>' . ucfirst($jogo['dificuldade']) . '</td>
            <td>R$ ' . number_format($jogo['preco'], 2, ',', '.') . '</td>
            <td>' . ($jogo['ativo'] ? 'Ativo' : 'Inativo') . '</td>
        </tr>
    ';

    if ($tipo === 'analitico') {
        $html .= '
            <tr>
                <td colspan="7" class="descricao">
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