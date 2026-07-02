<?php
set_time_limit(0);
ini_set('max_execution_time', 0);

if (php_sapi_name() !== 'cli') {
    $token = $_GET['token'] ?? '';
    if ($token !== 'formiga2024') {
        die("Acesso negado. Use ?token=formiga2024");
    }
}

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/gemini.php';
require_once __DIR__ . '/../config/ludopedia.php';
require_once __DIR__ . '/../helpers/logHelper.php';

// ============================================================
// FUNÇÕES AUXILIARES
// ============================================================

function ludopediaGet(string $url): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . LUDOPEDIA_TOKEN],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($raw, true);
    return $response ?? null;
}

function gerarDescricaoComIA(array $jogo): string {
    $mecanicas  = implode(', ', array_column($jogo['mecanicas']  ?? [], 'nm_mecanica'));
    $temas      = implode(', ', array_column($jogo['temas']      ?? [], 'nm_tema'));
    $categorias = implode(', ', array_column($jogo['categorias'] ?? [], 'nm_categoria'));

    $prompt = <<<PROMPT
Você é um especialista em jogos de tabuleiro. Escreva uma descrição em português brasileiro para o jogo "{$jogo['nm_jogo']}", com as seguintes características:
- Jogadores: {$jogo['qt_jogadores_min']} a {$jogo['qt_jogadores_max']}
- Tempo de jogo: {$jogo['vl_tempo_jogo']} minutos
- Idade mínima: {$jogo['idade_minima']} anos
- Mecânicas: {$mecanicas}
- Temas: {$temas}
- Categorias: {$categorias}

A descrição deve ter entre 3 e 5 frases, ser envolvente e informativa, destacando o que torna o jogo especial e para qual tipo de público ele é ideal. Não mencione preço. Responda APENAS com o texto da descrição, sem título, sem aspas, sem formatação.
PROMPT;

    $url  = "https://generativelanguage.googleapis.com/v1beta/" . GEMINI_LLM_MODEL . ":generateContent?key=" . GEMINI_API_KEY;
    $body = json_encode([
        "contents" => [["parts" => [["text" => $prompt]]]]
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

    if (isset($response['error']['code']) && $response['error']['code'] == 429) {
        throw new Exception('LIMITE_EXCEDIDO');
    }

    return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
}

function obterOuCriarCategoria(mysqli $conexao, string $nomeCategoria): int {
    $stmt = mysqli_prepare($conexao, "SELECT id_categoria FROM categorias WHERE nome = ?");
    mysqli_stmt_bind_param($stmt, 's', $nomeCategoria);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);

    if ($row) {
        return (int)$row['id_categoria'];
    }

    $stmt = mysqli_prepare($conexao, "INSERT INTO categorias (nome, ativo) VALUES (?, 1)");
    mysqli_stmt_bind_param($stmt, 's', $nomeCategoria);
    mysqli_stmt_execute($stmt);

    return (int)mysqli_insert_id($conexao);
}

function vincularCategorias(mysqli $conexao, int $idJogo, array $categorias): void {
    $stmt = mysqli_prepare($conexao, "DELETE FROM jogos_categorias WHERE id_jogo = ?");
    mysqli_stmt_bind_param($stmt, 'i', $idJogo);
    mysqli_stmt_execute($stmt);

    $stmt = mysqli_prepare($conexao, "INSERT IGNORE INTO jogos_categorias (id_jogo, id_categoria) VALUES (?, ?)");
    foreach ($categorias as $cat) {
        $idCategoria = obterOuCriarCategoria($conexao, $cat['nm_categoria']);
        mysqli_stmt_bind_param($stmt, 'ii', $idJogo, $idCategoria);
        mysqli_stmt_execute($stmt);
    }
}

// ============================================================
// SCRIPT PRINCIPAL
// ============================================================

registrarLog('INFO', 'Iniciando importação da Ludopedia.');
echo "🐜 Iniciando importação da Ludopedia...\n\n";

$pagina      = 1;
$porPagina   = 20;
$totalJogos  = 0;
$inseridos   = 0;
$atualizados = 0;
$erros       = 0;

do {
    $url      = "https://ludopedia.com.br/api/v1/colecao?fl_tem=1&pagina={$pagina}";
    $response = ludopediaGet($url);

    if (empty($response['colecao'])) {
        registrarLog('INFO', "Página {$pagina} vazia ou sem resposta. Encerrando.");
        break;
    }

    $colecao = $response['colecao'];
    registrarLog('INFO', "Página {$pagina}: " . count($colecao) . " jogos recebidos.");
    echo "📄 Página {$pagina} — " . count($colecao) . " jogos\n";

    foreach ($colecao as $item) {
        $idLudopedia = (int)$item['id_jogo'];
        $totalJogos++;

        registrarLog('INFO', "Processando [{$totalJogos}]: {$item['nm_jogo']} (id_ludopedia: {$idLudopedia})");
        echo "  → [{$totalJogos}] {$item['nm_jogo']}\n";

        // Busca detalhes completos do jogo
        $detalhes = ludopediaGet("https://ludopedia.com.br/api/v1/jogos/{$idLudopedia}");

        if (!$detalhes) {
            echo "  ✗ Erro ao buscar detalhes: {$item['nm_jogo']}\n";
            registrarLog('ERRO', "Falha ao buscar detalhes: {$item['nm_jogo']} (id: {$idLudopedia})");
            $erros++;
            usleep(500000);
            continue;
        }

        $nome       = $detalhes['nm_jogo']           ?? '';
        $imagem     = $detalhes['thumb']              ?? '';
        $minJog     = (int)($detalhes['qt_jogadores_min'] ?? 1);
        $maxJog     = (int)($detalhes['qt_jogadores_max'] ?? 10);
        $idadeMin   = (int)($detalhes['idade_minima']     ?? 0);
        $duracao    = (int)($detalhes['vl_tempo_jogo']    ?? 0);
        $tpJogo     = ($detalhes['tp_jogo'] ?? 'b') === 'e' ? 'expansao' : 'base';
        $linkLudo   = $detalhes['link']               ?? '';
        $categorias = $detalhes['categorias']         ?? [];

        // Verifica se jogo já existe no banco
        $stmt = mysqli_prepare($conexao, "SELECT id_jogo, descricao FROM jogos WHERE id_ludopedia = ?");
        mysqli_stmt_bind_param($stmt, 'i', $idLudopedia);
        mysqli_stmt_execute($stmt);
        $result    = mysqli_stmt_get_result($stmt);
        $jogoAtual = mysqli_fetch_assoc($result);

        if ($jogoAtual) {
            // ── ATUALIZA jogo existente ──
            $idJogo = (int)$jogoAtual['id_jogo'];

            $stmt = mysqli_prepare($conexao, "
                UPDATE jogos SET
                    nome            = ?,
                    imagem          = ?,
                    min_jogadores   = ?,
                    max_jogadores   = ?,
                    idade_minima    = ?,
                    duracao_minutos = ?,
                    tp_jogo         = ?,
                    link_ludopedia  = ?,
                    embedding       = NULL,
                    atualizado_em   = NOW()
                WHERE id_jogo = ?
            ");
            mysqli_stmt_bind_param($stmt, 'ssiiiissi',
                $nome, $imagem, $minJog, $maxJog,
                $idadeMin, $duracao, $tpJogo, $linkLudo, $idJogo
            );
            mysqli_stmt_execute($stmt);

            vincularCategorias($conexao, $idJogo, $categorias);

            echo "  ✓ Atualizado: {$nome}\n";
            registrarLog('INFO', "Atualizado: {$nome} (id: {$idJogo}, id_ludopedia: {$idLudopedia})");
            $atualizados++;

        } else {
            // ── INSERE jogo novo ──
            $descricao = '';

            try {
                echo "  🤖 Gerando descrição: {$nome}...\n";
                registrarLog('INFO', "Gerando descrição via Gemini para: {$nome}");
                $descricao = gerarDescricaoComIA($detalhes);
                registrarLog('INFO', "Descrição gerada com sucesso: {$nome}");
                sleep(2); // respeita rate limit
            } catch (Exception $e) {
                if ($e->getMessage() === 'LIMITE_EXCEDIDO') {
                    echo "\n⚠️  Rate limit atingido! Progresso salvo. Rode novamente amanhã.\n";
                    registrarLog('ERRO', "Rate limit Gemini atingido! Inseridos: {$inseridos} | Atualizados: {$atualizados} | Erros: {$erros}");
                    echo "✅ Parcial: {$inseridos} inseridos | {$atualizados} atualizados | {$erros} erros\n";
                    exit;
                }
                registrarLog('ERRO', "Erro ao gerar descrição para {$nome}: " . $e->getMessage());
                $descricao = '';
            }

            $stmt = mysqli_prepare($conexao, "
                INSERT INTO jogos (
                    id_ludopedia, nome, imagem, descricao,
                    min_jogadores, max_jogadores, idade_minima,
                    duracao_minutos, tp_jogo, link_ludopedia,
                    preco, ativo, origem, criado_em
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, 1, 'ludopedia', NOW())
            ");
            mysqli_stmt_bind_param($stmt, 'isssiiiiss',
                $idLudopedia, $nome, $imagem, $descricao,
                $minJog, $maxJog, $idadeMin, $duracao, $tpJogo, $linkLudo
            );
            mysqli_stmt_execute($stmt);

            $idJogo = (int)mysqli_insert_id($conexao);
            vincularCategorias($conexao, $idJogo, $categorias);

            echo "  ✨ Inserido: {$nome}\n";
            registrarLog('INFO', "Inserido: {$nome} (id: {$idJogo}, id_ludopedia: {$idLudopedia})");
            $inseridos++;
        }

        usleep(300000); // 0.3s entre jogos
    }

    $pagina++;
    sleep(1); // 1s entre páginas

} while (count($colecao) === $porPagina);

$resumo = "Importação concluída! Total: {$totalJogos} | Inseridos: {$inseridos} | Atualizados: {$atualizados} | Erros: {$erros}";
echo "\n✅ {$resumo}\n";
registrarLog('INFO', $resumo);

mysqli_close($conexao);