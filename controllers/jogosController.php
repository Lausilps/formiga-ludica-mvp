<?php

require_once '../config/conexao.php';

require_once '../helpers/logHelper.php';
require_once '../helpers/jogoHelper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

    $dadosFormulario = [
        'nome' => $nome,
        'descricao' => $descricao,
        'preco' => $preco,
        'min_jogadores' => $min_jogadores,
        'max_jogadores' => $max_jogadores,
        'idade_minima' => $idade_minima,
        'duracao_minutos' => $duracao_minutos,
        'dificuldade' => $dificuldade,
        'resumo_regras' => $resumo_regras,
        'link_tutorial' => $link_tutorial
    ];

    if (jogoNomeDuplicado($conexao, $nome)) {

        registrarLog(
            'ALERTA',
            "Tentativa de cadastro duplicado do jogo: $nome"
        );

        redirecionarComDados('../views/jogos/cadastrar.php', 'duplicado', $dadosFormulario);
    }

    if (!camposObrigatoriosPreenchidos([
        $nome, $descricao, $preco, $min_jogadores, $max_jogadores,
        $idade_minima, $duracao_minutos, $dificuldade
    ])) {

        registrarLog(
            'ALERTA',
            "Tentativa de cadastro sem preenchimento dos campos obrigatórios. Jogo: $nome"
        );

        header("Location: ../views/jogos/cadastrar.php?erro=campos_obrigatorios");
        exit;
    }

    if ($min_jogadores > $max_jogadores) {

        registrarLog(
            'ALERTA',
            "Tentativa de cadastro inválido. Min jogadores ($min_jogadores) maior que Max jogadores ($max_jogadores). Jogo: $nome"
        );

        redirecionarComDados('../views/jogos/cadastrar.php', 'jogadores_invalidos', $dadosFormulario);
    }

    $imagem = uploadImagemJogo($_FILES['imagem'] ?? null);

    $sql = "INSERT INTO jogos (
                nome,
                imagem,
                descricao,
                preco,
                min_jogadores,
                max_jogadores,
                idade_minima,
                duracao_minutos,
                dificuldade,
                resumo_regras,
                link_tutorial
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        'sssssssssss',
        $nome,
        $imagem,
        $descricao,
        $preco,
        $min_jogadores,
        $max_jogadores,
        $idade_minima,
        $duracao_minutos,
        $dificuldade,
        $resumo_regras,
        $link_tutorial
    );

    if (mysqli_stmt_execute($stmt)) {

        registrarLog(
            'INFO',
            "Novo jogo cadastrado: $nome"
        );

        header("Location: ../views/jogos/cadastrar.php?sucesso=1");
        exit;

    } else {

        $erro = mysqli_error($conexao);

        registrarLog(
            'ERRO',
            "Falha ao cadastrar jogo '$nome': $erro"
        );

        echo "Ocorreu um erro ao cadastrar o jogo.";
    }
}
