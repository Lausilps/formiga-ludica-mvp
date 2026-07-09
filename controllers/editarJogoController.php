<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';
require_once '../helpers/jogoHelper.php';

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

    $dadosFormulario = [
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
    ];

    // Validação de campos obrigatórios
    if (!camposObrigatoriosPreenchidos([
        $nome, $descricao, $preco, $min_jogadores, $max_jogadores,
        $idade_minima, $duracao_minutos, $dificuldade
    ])) {

        registrarLog(
            'ALERTA',
            "Tentativa de edição sem preenchimento dos campos obrigatórios. Jogo ID: $id_jogo"
        );

        redirecionarComDados('../views/jogos/editar.php', 'campos_obrigatorios', $dadosFormulario);
    }

    // Validação min/max jogadores
    if ($min_jogadores > $max_jogadores) {

        registrarLog(
            'ALERTA',
            "Tentativa de edição inválida. Min jogadores ($min_jogadores) maior que Max jogadores ($max_jogadores). Jogo ID: $id_jogo"
        );

        redirecionarComDados('../views/jogos/editar.php', 'jogadores_invalidos', $dadosFormulario);
    }

    // Verifica duplicidade ignorando o próprio jogo
    if (jogoNomeDuplicado($conexao, $nome, (int) $id_jogo)) {

        registrarLog(
            'ALERTA',
            "Tentativa de renomear jogo para nome já existente: $nome"
        );

        redirecionarComDados('../views/jogos/editar.php', 'duplicado', $dadosFormulario);
    }

    // Tratamento da imagem
    $novaImagem = uploadImagemJogo($_FILES['imagem'] ?? null);

    if ($novaImagem !== null) {

        registrarLog(
            'INFO',
            "Imagem atualizada para o jogo ID: $id_jogo | Arquivo: $novaImagem"
        );

    } elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {

        registrarLog(
            'ERRO',
            "Erro no upload da imagem do jogo ID: $id_jogo | Código: " . $_FILES['imagem']['error']
        );
    }

    // Atualização
    if ($novaImagem !== null) {

        $sql = "UPDATE jogos SET
                    nome = ?,
                    descricao = ?,
                    preco = ?,
                    min_jogadores = ?,
                    max_jogadores = ?,
                    idade_minima = ?,
                    duracao_minutos = ?,
                    dificuldade = ?,
                    resumo_regras = ?,
                    link_tutorial = ?,
                    ativo = ?,
                    imagem = ?
                WHERE id_jogo = ?";

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssssssss',
            $nome,
            $descricao,
            $preco,
            $min_jogadores,
            $max_jogadores,
            $idade_minima,
            $duracao_minutos,
            $dificuldade,
            $resumo_regras,
            $link_tutorial,
            $ativo,
            $novaImagem,
            $id_jogo
        );

    } else {

        $sql = "UPDATE jogos SET
                    nome = ?,
                    descricao = ?,
                    preco = ?,
                    min_jogadores = ?,
                    max_jogadores = ?,
                    idade_minima = ?,
                    duracao_minutos = ?,
                    dificuldade = ?,
                    resumo_regras = ?,
                    link_tutorial = ?,
                    ativo = ?
                WHERE id_jogo = ?";

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssssss',
            $nome,
            $descricao,
            $preco,
            $min_jogadores,
            $max_jogadores,
            $idade_minima,
            $duracao_minutos,
            $dificuldade,
            $resumo_regras,
            $link_tutorial,
            $ativo,
            $id_jogo
        );
    }

    if (mysqli_stmt_execute($stmt)) {

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
