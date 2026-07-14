<?php
set_time_limit(0);
ini_set('max_execution_time', 0);

// TEMPORÁRIO: desativa a geração de descrição via IA durante a sincronização
// pra não esgotar a cota do Gemini no meio do sync. Reativar virando pra true
// quando a cota normalizar.
define('GERAR_DESCRICAO_VIA_IA', false);

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    $tokenEsperado = getenv('ADMIN_IMPORT_TOKEN') ?: '';
    $token = $_GET['token'] ?? '';
    if ($tokenEsperado === '' || $token !== $tokenEsperado) {
        die("Acesso negado.");
    }
}

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/gemini.php';
require_once __DIR__ . '/../config/ludopediaLoader.php';
require_once __DIR__ . '/../helpers/logHelper.php';
require_once __DIR__ . '/../helpers/recomendacaoHelper.php';

// ============================================================
// FUNÇÕES AUXILIARES
// ============================================================

// Retorna null em qualquer falha (rede OU erro da API, como rate limit),
// pra nunca confundir "a Ludopedia recusou a chamada" com "o jogo não tem
// nome" — antes disso já causou jogo importado com nome vazio.
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

    if (empty($response) || isset($response['error'])) {
        if (isset($response['error'])) {
            registrarLog('ERRO', "Ludopedia recusou a chamada: {$response['error']} | URL: {$url}");
        }
        return null;
    }

    return $response;
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

// Processa uma única página da coleção da Ludopedia: importa/atualiza os
// jogos, gera descrição via IA pra quem não tem (a Ludopedia não fornece) e
// gera o embedding na hora — sem precisar de um segundo passo manual depois.
function processarPaginaLudopedia(mysqli $conexao, int $pagina, int $rows): array {
    $resultado = [
        'totalGeral'    => 0,
        'processados'   => 0,
        'inseridos'     => 0,
        'atualizados'   => 0,
        'erros'         => 0,
        'colecaoVazia'  => true,
        'falhaColecao'  => false,
    ];

    $url = "https://ludopedia.com.br/api/v1/colecao?lista=colecao&fl_tem=1&page={$pagina}&rows={$rows}";
    $response = ludopediaGet($url);

    // ludopediaGet() retorna null tanto pra "deu erro/rate limit" quanto pra
    // "sem resposta" — mas aqui isso NÃO pode ser confundido com "acabou a
    // coleção de verdade", senão o sync encerra cedo demais (achando que
    // terminou) toda vez que a Ludopedia recusar essa chamada específica.
    if ($response === null) {
        registrarLog('ERRO', "Falha ao buscar página {$pagina} da coleção (possível rate limit). Não é fim da coleção.");
        $resultado['colecaoVazia'] = false;
        $resultado['falhaColecao'] = true;
        return $resultado;
    }

    $resultado['totalGeral'] = (int)($response['total'] ?? 0);

    if (empty($response['colecao'])) {
        registrarLog('INFO', "Página {$pagina} vazia. Fim real da coleção.");
        return $resultado;
    }

    $colecao = $response['colecao'];
    $resultado['colecaoVazia'] = false;
    registrarLog('INFO', "Página {$pagina}: " . count($colecao) . " jogos recebidos. Total geral: {$resultado['totalGeral']}");

    foreach ($colecao as $item) {
        $idLudopedia = (int)$item['id_jogo'];
        $resultado['processados']++;

        $detalhes = ludopediaGet("https://ludopedia.com.br/api/v1/jogos/{$idLudopedia}");

        if (!$detalhes) {
            registrarLog('ERRO', "Falha ao buscar detalhes: {$item['nm_jogo']} (id_ludopedia: {$idLudopedia})");
            $resultado['erros']++;
            usleep(300000);
            continue;
        }

        $nome    = $detalhes['nm_jogo'] ?? '';
        $semNome = empty($nome);

        if ($semNome) {
            $nome = "SEM NOME (Ludopedia #{$idLudopedia})";
            registrarLog('ALERTA', "Jogo sem nome recebido da API (id_ludopedia: {$idLudopedia}). Inserido/mantido inativo com nome placeholder.");
        }

        // A Ludopedia manda só a miniatura ("_t"), que fica borrada quando
        // exibida maior no catálogo. O mesmo storage tem a versão em
        // tamanho normal no mesmo nome de arquivo, só sem o "_t".
        $thumb  = $detalhes['thumb'] ?? '';
        $imagem = $thumb !== '' ? preg_replace('/_t(\.[a-zA-Z0-9]+)$/', '$1', $thumb) : '';
        $minJog     = (int)($detalhes['qt_jogadores_min'] ?? 1);
        $maxJog     = (int)($detalhes['qt_jogadores_max'] ?? 10);
        $idadeMin   = (int)($detalhes['idade_minima']     ?? 0);
        $duracao    = (int)($detalhes['vl_tempo_jogo']    ?? 0);
        $tpJogo     = ($detalhes['tp_jogo'] ?? 'b') === 'e' ? 'expansao' : 'base';
        $linkLudo   = $detalhes['link']               ?? '';
        $categorias = $detalhes['categorias']         ?? [];

        $stmt = mysqli_prepare($conexao, "SELECT id_jogo, nome, descricao, embedding, ativo FROM jogos WHERE id_ludopedia = ?");
        mysqli_stmt_bind_param($stmt, 'i', $idLudopedia);
        mysqli_stmt_execute($stmt);
        $jogoAtual = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($jogoAtual) {
            // ── ATUALIZA jogo existente ──
            // Não mexe no embedding aqui: com 600+ jogos no catálogo, resetar
            // e regerar tudo a cada sincronização (mesmo sem mudança real)
            // deixaria a rotina inteira lenta e cara. Só gera de novo se
            // faltar (tratado mais abaixo).
            $idJogo         = (int)$jogoAtual['id_jogo'];
            $descricaoAtual = $jogoAtual['descricao'] ?? '';
            $embeddingAtual = $jogoAtual['embedding'] ?? '';

            // Jogo sem nome nunca fica ativo — não aparece no catálogo nem
            // é recomendado pela IA até alguém corrigir o nome manualmente.
            // Se o nome salvo era o placeholder e agora veio um nome de
            // verdade, reativa sozinho (provavelmente foi vítima do bug do
            // rate limit, não uma desativação manual). Fora isso, preserva
            // o ativo/inativo que o admin já tinha escolhido.
            $tinhaPlaceholder = str_starts_with((string)($jogoAtual['nome'] ?? ''), 'SEM NOME (Ludopedia #');

            if ($semNome) {
                $ativo = 0;
            } elseif ($tinhaPlaceholder) {
                $ativo = 1;
            } else {
                $ativo = (int)$jogoAtual['ativo'];
            }

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
                    ativo           = ?,
                    atualizado_em   = NOW()
                WHERE id_jogo = ?
            ");
            mysqli_stmt_bind_param($stmt, 'ssiiiissii',
                $nome, $imagem, $minJog, $maxJog,
                $idadeMin, $duracao, $tpJogo, $linkLudo, $ativo, $idJogo
            );

            try {
                mysqli_stmt_execute($stmt);
            } catch (\mysqli_sql_exception $e) {
                // Nome colidindo com outro jogo já cadastrado (índice único
                // uk_jogos_nome ignora acento/maiúscula/espaço no fim) não
                // pode derrubar a página inteira — sem isso, a sincronização
                // trava pra sempre nessa mesma página, repetindo o erro.
                registrarLog('ERRO', "Falha ao atualizar '{$nome}' (id_ludopedia: {$idLudopedia}): " . $e->getMessage());
                $resultado['erros']++;
                usleep(300000);
                continue;
            }

            vincularCategorias($conexao, $idJogo, $categorias);

            $sufixoLog = $semNome ? ' [inativo por falta de nome]' : ($tinhaPlaceholder ? ' [nome recuperado, reativado automaticamente]' : '');
            registrarLog('INFO', "Atualizado: {$nome} (id: {$idJogo}, id_ludopedia: {$idLudopedia}){$sufixoLog}");
            $resultado['atualizados']++;

        } else {
            // ── INSERE jogo novo SEM descrição (a Ludopedia não fornece) ──
            // Sem nome, entra inativo — não aparece no catálogo nem é
            // recomendado até alguém corrigir o nome manualmente.
            $ativo = $semNome ? 0 : 1;

            $stmt = mysqli_prepare($conexao, "
                INSERT INTO jogos (
                    id_ludopedia, nome, imagem, descricao,
                    min_jogadores, max_jogadores, idade_minima,
                    duracao_minutos, tp_jogo, link_ludopedia,
                    preco, ativo, origem, criado_em
                ) VALUES (?, ?, ?, '', ?, ?, ?, ?, ?, ?, 0.00, ?, 'ludopedia', NOW())
            ");
            mysqli_stmt_bind_param($stmt, 'issiiiissi',
                $idLudopedia, $nome, $imagem,
                $minJog, $maxJog, $idadeMin, $duracao, $tpJogo, $linkLudo, $ativo
            );

            try {
                mysqli_stmt_execute($stmt);
            } catch (\mysqli_sql_exception $e) {
                registrarLog('ERRO', "Falha ao inserir '{$nome}' (id_ludopedia: {$idLudopedia}): " . $e->getMessage());
                $resultado['erros']++;
                usleep(300000);
                continue;
            }

            $idJogo         = (int)mysqli_insert_id($conexao);
            $descricaoAtual = '';
            $embeddingAtual = '';
            vincularCategorias($conexao, $idJogo, $categorias);

            registrarLog('INFO', "Inserido: {$nome} (id: {$idJogo}, id_ludopedia: {$idLudopedia})");
            $resultado['inseridos']++;
        }

        // Gera descrição via IA só se o jogo não tiver nenhuma ainda (e tiver
        // nome de verdade — sem nome, o jogo já está inativo, não vale a
        // pena gastar chamada do Gemini com ele).
        $descricaoFoiGerada = false;

        if (GERAR_DESCRICAO_VIA_IA && !$semNome && trim((string)$descricaoAtual) === '') {
            try {
                $descricaoGerada = gerarDescricaoComIA($detalhes);

                if (!empty($descricaoGerada)) {
                    $stmt = mysqli_prepare($conexao, "UPDATE jogos SET descricao = ? WHERE id_jogo = ?");
                    mysqli_stmt_bind_param($stmt, 'si', $descricaoGerada, $idJogo);
                    mysqli_stmt_execute($stmt);
                    $descricaoAtual     = $descricaoGerada;
                    $descricaoFoiGerada = true;
                    registrarLog('INFO', "Descrição gerada via IA para: {$nome}");
                }
            } catch (Exception $e) {
                registrarLog('ERRO', "Falha ao gerar descrição via IA para {$nome}: " . $e->getMessage());
            }

            sleep(1); // respeita rate limit do Gemini
        }

        // Só gera embedding se estiver faltando (jogo novo) ou se acabamos de
        // gerar a descrição agora (o embedding antigo, se existisse, estaria
        // desatualizado). Jogos que só tiveram metadado atualizado (jogadores,
        // idade etc.) mantêm o embedding que já tinham — evita reprocessar os
        // 600+ jogos do catálogo inteiro a cada sincronização de rotina.
        if (!$semNome && (trim((string)$embeddingAtual) === '' || $descricaoFoiGerada)) {
            $textoEmbedding = montarTextoEmbeddingJogo([
                'nome'            => $nome,
                'descricao'       => $descricaoAtual,
                'resumo_regras'   => null,
                'min_jogadores'   => $minJog,
                'max_jogadores'   => $maxJog,
                'idade_minima'    => $idadeMin,
                'duracao_minutos' => $duracao,
                'dificuldade'     => null,
            ]);

            $embedding = geminiEmbedding($textoEmbedding);

            if (!empty($embedding)) {
                $json = json_encode($embedding);
                $stmt = mysqli_prepare($conexao, "UPDATE jogos SET embedding = ?, embedding_atualizado_em = NOW() WHERE id_jogo = ?");
                mysqli_stmt_bind_param($stmt, 'si', $json, $idJogo);
                mysqli_stmt_execute($stmt);
                registrarLog('INFO', "Embedding gerado para: {$nome}");
            } else {
                registrarLog('ERRO', "Falha ao gerar embedding para: {$nome} (id: {$idJogo})");
            }

            sleep(1); // respeita rate limit do Gemini
        }

        usleep(300000); // pausa curta, respeitosa com a API da Ludopedia
    }

    return $resultado;
}

// ============================================================
// EXECUÇÃO
// ============================================================

if ($isCli) {
    // Modo CLI: processa a coleção inteira, página por página, sem limite de
    // tempo — não tem servidor web no meio pra derrubar a conexão por timeout.
    echo "🐜 Iniciando importação da Ludopedia...\n\n";

    $pagina = 1;
    $rows   = 50;
    $totais = ['inseridos' => 0, 'atualizados' => 0, 'erros' => 0, 'processados' => 0];

    do {
        $r = processarPaginaLudopedia($conexao, $pagina, $rows);
        $totais['inseridos']   += $r['inseridos'];
        $totais['atualizados'] += $r['atualizados'];
        $totais['erros']       += $r['erros'];
        $totais['processados'] += $r['processados'];

        echo "📄 Página {$pagina} — Inseridos: {$r['inseridos']} | Atualizados: {$r['atualizados']} | Erros: {$r['erros']}\n";
        $pagina++;
    } while (!$r['colecaoVazia']);

    $resumo = "Importação concluída! Total: {$totais['processados']} | Inseridos: {$totais['inseridos']} | Atualizados: {$totais['atualizados']} | Erros: {$totais['erros']}";
    echo "\n✅ {$resumo}\n";
    registrarLog('INFO', $resumo);

} else {
    // Modo HTTP: processa só uma página pequena por requisição, pra nunca
    // correr risco de timeout do servidor/proxy no meio da sincronização.
    // Quem chama (o botão no admin) encadeia as chamadas até `temMais` ser falso.
    $pagina = max(1, (int)($_GET['pagina'] ?? 1));
    $rows   = 6;

    if ($pagina === 1) {
        registrarLog('INFO', 'Iniciando importação da Ludopedia (via admin).');
    }

    $r = processarPaginaLudopedia($conexao, $pagina, $rows);

    if ($r['colecaoVazia']) {
        registrarLog('INFO', "Importação concluída na página {$pagina}.");
    }

    header('Content-Type: application/json');
    echo json_encode([
        'pagina'       => $pagina,
        'totalGeral'   => $r['totalGeral'],
        'processados'  => $r['processados'],
        'inseridos'    => $r['inseridos'],
        'atualizados'  => $r['atualizados'],
        'erros'        => $r['erros'],
        'temMais'      => !$r['colecaoVazia'],
        'falhaColecao' => $r['falhaColecao'],
    ]);
}

mysqli_close($conexao);
