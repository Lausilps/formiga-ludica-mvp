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
$origem = $_GET['origem'] ?? '';
$importado_de = $_GET['importado_de'] ?? '';
$importado_ate = $_GET['importado_ate'] ?? '';
$tipo = $_GET['tipo'] ?? 'sintetico';

function dataValida(string $data): bool {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

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

if ($origem === 'manual' || $origem === 'ludopedia') {
    $where[] = "origem = '$origem'";
}

if ($importado_de !== '' && dataValida($importado_de)) {
    $where[] = "criado_em >= '$importado_de 00:00:00'";
}

if ($importado_ate !== '' && dataValida($importado_ate)) {
    $where[] = "criado_em <= '$importado_ate 23:59:59'";
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

$jogos = [];
while ($linha = mysqli_fetch_assoc($resultado)) {
    $jogos[] = $linha;
}

// Marca como possível duplicado quando outro jogo do resultado tem um nome
// muito parecido (não precisa ser idêntico — pega acento, "de" a mais, etc.),
// quando um nome é o outro com um sufixo de edição/ano na frente (ex:
// "Acquire" vs "Acquire (2023 Edition)" — o similar_text() sozinho não pega
// esse caso: nome base curto + sufixo comprido derruba o percentual), ou
// quando têm a mesma descrição.
const LIMIAR_SIMILARIDADE_NOME = 85.0;

function normalizarNomeJogo(string $nome): string {
    $nome = iconv('UTF-8', 'ASCII//TRANSLIT', $nome) ?: $nome;
    $nome = mb_strtolower($nome);
    $nome = preg_replace('/[^a-z0-9 ]+/', ' ', $nome);
    $nome = preg_replace('/\s+/', ' ', trim($nome));
    return $nome;
}

// Verifica se $curto é o começo de $longo terminando em fronteira de palavra
// (não um trecho solto no meio). Exige tamanho mínimo pra não marcar nomes
// curtos genéricos (ex: "Go") como prefixo de qualquer coisa.
function nomeEhPrefixoDoOutro(string $curto, string $longo): bool {
    if ($curto === '' || $curto === $longo || mb_strlen($curto) < 4) {
        return false;
    }

    return (bool) preg_match('/^' . preg_quote($curto, '/') . '(\s|$)/', $longo);
}

$nomesNormalizados = array_map(fn($jogo) => normalizarNomeJogo($jogo['nome'] ?? ''), $jogos);

$contagemDescricao = [];
foreach ($jogos as $jogo) {
    $chaveDescricao = trim($jogo['descricao'] ?? '');
    if ($chaveDescricao !== '') {
        $contagemDescricao[$chaveDescricao] = ($contagemDescricao[$chaveDescricao] ?? 0) + 1;
    }
}

$total = count($jogos);
$duplicadoPorIndice = array_fill(0, $total, false);

for ($i = 0; $i < $total; $i++) {
    if ($nomesNormalizados[$i] === '') continue;

    for ($j = $i + 1; $j < $total; $j++) {
        if ($nomesNormalizados[$j] === '') continue;

        similar_text($nomesNormalizados[$i], $nomesNormalizados[$j], $percentual);

        $nomeParecido = $percentual >= LIMIAR_SIMILARIDADE_NOME
            || nomeEhPrefixoDoOutro($nomesNormalizados[$i], $nomesNormalizados[$j])
            || nomeEhPrefixoDoOutro($nomesNormalizados[$j], $nomesNormalizados[$i]);

        if ($nomeParecido) {
            $duplicadoPorIndice[$i] = true;
            $duplicadoPorIndice[$j] = true;
        }
    }
}

$jogos = array_values($jogos);
foreach ($jogos as $indice => &$jogo) {
    $chaveDescricao = trim($jogo['descricao'] ?? '');

    $jogo['duplicado'] = $duplicadoPorIndice[$indice]
        || ($chaveDescricao !== '' && $contagemDescricao[$chaveDescricao] > 1);
}
unset($jogo);

if (isset($_GET['somente_duplicados'])) {
    $jogos = array_filter($jogos, fn($jogo) => $jogo['duplicado']);
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