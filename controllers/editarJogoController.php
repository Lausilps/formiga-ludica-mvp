<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_jogo = $_POST['id_jogo'];

    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $min_jogadores = $_POST['min_jogadores'];
    $max_jogadores = $_POST['max_jogadores'];
    $idade_minima = $_POST['idade_minima'];
    $duracao_minutos = $_POST['duracao_minutos'];
    $dificuldade = $_POST['dificuldade'];
    $resumo_regras = $_POST['resumo_regras'];
    $link_tutorial = $_POST['link_tutorial'];
    $ativo = isset($_POST['inativar']) ? 0 : 1;

    // Validação de campos obrigatórios
    if (
        empty($nome) ||
        empty($descricao) ||
        empty($preco) ||
        empty($min_jogadores) ||
        empty($max_jogadores) ||
        empty($idade_minima) ||
        empty($duracao_minutos) ||
        empty($dificuldade)
    ) {

        registrarLog(
            'ALERTA',
            "Tentativa de edição sem preenchimento dos campos obrigatórios. Jogo ID: $id_jogo"
        );

        $dados = http_build_query([
            'id' => $id_jogo,
            'nome' => $nome,
            'descricao' => $descricao,
            'preco' => $preco,
            'min_jogadores' => $min_jogadores,
            'max_jogadores' => $max_jogadores,
            'idade_minima' => $idade_minima,
            'duracao_minutos' => $duracao_minutos,
            'dificuldade' => $dificuldade,
            'ativo' => $ativo,
            'resumo_regras' => $resumo_regras,
            'link_tutorial' => $link_tutorial
        ]);

        header("Location: ../views/jogos/editar.php?erro=campos_obrigatorios&$dados");
        exit;
    }

    // Validação min/max jogadores
    if ($min_jogadores > $max_jogadores) {

        registrarLog(
            'ALERTA',
            "Tentativa de edição inválida. Min jogadores ($min_jogadores) maior que Max jogadores ($max_jogadores). Jogo ID: $id_jogo"
        );

        $dados = http_build_query([
            'id' => $id_jogo,
            'nome' => $nome,
            'descricao' => $descricao,
            'preco' => $preco,
            'min_jogadores' => $min_jogadores,
            'max_jogadores' => $max_jogadores,
            'idade_minima' => $idade_minima,
            'duracao_minutos' => $duracao_minutos,
            'dificuldade' => $dificuldade,
            'ativo' => $ativo,
            'resumo_regras' => $resumo_regras,
            'link_tutorial' => $link_tutorial
        ]);
        header("Location: ../views/jogos/editar.php?erro=jogadores_invalidos&$dados");
        exit;
    }

    // Verifica duplicidade ignorando o próprio jogo
    $verificaSql = "SELECT id_jogo
                    FROM jogos
                    WHERE nome = '$nome'
                    AND id_jogo <> '$id_jogo'";

    $verificaResultado = mysqli_query($conexao, $verificaSql);

    if (mysqli_num_rows($verificaResultado) > 0) {

        registrarLog(
            'ALERTA',
            "Tentativa de renomear jogo para nome já existente: $nome"
        );

        $dados = http_build_query([
                    'id' => $id_jogo,
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'preco' => $preco,
                    'min_jogadores' => $min_jogadores,
                    'max_jogadores' => $max_jogadores,
                    'idade_minima' => $idade_minima,
                    'duracao_minutos' => $duracao_minutos,
                    'dificuldade' => $dificuldade,
                    'ativo' => $ativo,
                    'resumo_regras' => $resumo_regras,
                    'link_tutorial' => $link_tutorial
                ]);
    
        header("Location: ../views/jogos/editar.php?erro=duplicado&$dados");
        exit;
    }

    // Tratamento da imagem
    $sqlImagem = "";

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {

        $pastaDestino = "../uploads/jogos/";

        if (!is_dir($pastaDestino)) {
            mkdir($pastaDestino, 0777, true);
        }

        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $nomeImagem = uniqid("jogo_") . "." . $extensao;

        $caminhoDestino = $pastaDestino . $nomeImagem;

        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminhoDestino)) {

            $sqlImagem = ", imagem = 'uploads/jogos/$nomeImagem'";

            registrarLog(
                'INFO',
                "Imagem atualizada para o jogo ID: $id_jogo | Arquivo: $nomeImagem"
            );

        } else {

            registrarLog(
                'ERRO',
                "Falha ao mover imagem enviada para o jogo ID: $id_jogo"
            );
        }

    } elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {

        registrarLog(
            'ERRO',
            "Erro no upload da imagem do jogo ID: $id_jogo | Código: " . $_FILES['imagem']['error']
        );
    }

    // Atualização
    $sql = "UPDATE jogos SET
                nome = '$nome',
                descricao = '$descricao',
                preco = '$preco',
                min_jogadores = '$min_jogadores',
                max_jogadores = '$max_jogadores',
                idade_minima = '$idade_minima',
                duracao_minutos = '$duracao_minutos',
                dificuldade = '$dificuldade',
                resumo_regras = '$resumo_regras',
                link_tutorial = '$link_tutorial',
                ativo = '$ativo'
                $sqlImagem
            WHERE id_jogo = '$id_jogo'";

    if (mysqli_query($conexao, $sql)) {

        registrarLog(
            'INFO',
            "Jogo atualizado com sucesso: $nome | ID: $id_jogo"
        );

        header("Location: ../views/jogos/listar.php?sucesso=editado");
        exit;

    } else {

        $erro = mysqli_error($conexao);

        registrarLog(
            'ERRO',
            "Falha ao atualizar jogo '$nome' | ID: $id_jogo | Erro: $erro"
        );

        echo "Ocorreu um erro ao atualizar o jogo.";
    }
}