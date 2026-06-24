<?php

require_once '../config/conexao.php';

require_once '../helpers/logHelper.php';

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

    $verificaSql = "SELECT id_jogo FROM jogos WHERE nome = '$nome'";
    $verificaResultado = mysqli_query($conexao, $verificaSql);

   if (mysqli_num_rows($verificaResultado) > 0) {

        registrarLog(
            'ALERTA',
            "Tentativa de cadastro duplicado do jogo: $nome"
        );

        $dados = http_build_query([
            'erro' => 'duplicado',
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
        ]);

        header("Location: ../views/jogos/cadastrar.php?$dados");
        exit;
    }

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

        $dados = http_build_query([
            'erro' => 'jogadores_invalidos',
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
        ]);

        header("Location: ../views/jogos/cadastrar.php?$dados");
        exit;
    }

    $sql = "INSERT INTO jogos (
                nome,
                descricao,
                preco,
                min_jogadores,
                max_jogadores,
                idade_minima,
                duracao_minutos,
                dificuldade,
                resumo_regras,
                link_tutorial
            ) VALUES (
                '$nome',
                '$descricao',
                '$preco',
                '$min_jogadores',
                '$max_jogadores',
                '$idade_minima',
                '$duracao_minutos',
                '$dificuldade',
                '$resumo_regras',
                '$link_tutorial'
            )";

    if (mysqli_query($conexao, $sql)) {

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