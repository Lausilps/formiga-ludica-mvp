<?php

// O container do Railway não guarda nada em disco de um deploy pro
// outro — sessão de PHP por padrão fica salva em arquivo local, então
// todo mundo era deslogado a cada novo deploy (e a gente fez muitos
// deploys seguidos essa semana, por isso ficou tão perceptível).
// Guarda a sessão na tabela sessoes_php em vez de arquivo, e aumenta a
// validade do cookie — assim quem já logou hoje continua logado amanhã,
// e um deploy novo não derruba ninguém.

require_once __DIR__ . '/../config/conexao.php';

class SessaoBancoDados implements SessionHandlerInterface
{
    public function __construct(private mysqli $conexao)
    {
    }

    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        $stmt = mysqli_prepare($this->conexao, "SELECT dados FROM sessoes_php WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 's', $id);
        mysqli_stmt_execute($stmt);
        $linha = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        return $linha['dados'] ?? '';
    }

    public function write($id, $dados): bool
    {
        $stmt = mysqli_prepare($this->conexao, "
            INSERT INTO sessoes_php (id, dados, atualizado_em) VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE dados = VALUES(dados), atualizado_em = NOW()
        ");
        mysqli_stmt_bind_param($stmt, 'ss', $id, $dados);

        return mysqli_stmt_execute($stmt);
    }

    public function destroy($id): bool
    {
        $stmt = mysqli_prepare($this->conexao, "DELETE FROM sessoes_php WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 's', $id);

        return mysqli_stmt_execute($stmt);
    }

    public function gc($max_lifetime): int|false
    {
        $stmt = mysqli_prepare($this->conexao, "DELETE FROM sessoes_php WHERE atualizado_em < (NOW() - INTERVAL ? SECOND)");
        mysqli_stmt_bind_param($stmt, 'i', $max_lifetime);
        mysqli_stmt_execute($stmt);

        return mysqli_affected_rows($this->conexao);
    }
}

function iniciarSessaoPersistente(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    global $conexao;

    // Se a tabela ainda não existir (ex: código já subiu mas a migração
    // ainda não rodou), cai pro comportamento padrão do PHP em vez de
    // travar o site inteiro — assim a ordem de deploy vs. SQL não importa.
    $tabelaExiste = @mysqli_query($conexao, "SELECT 1 FROM sessoes_php LIMIT 1") !== false;

    if ($tabelaExiste) {
        session_set_save_handler(new SessaoBancoDados($conexao), true);
    }

    // 30 dias — "lembrar login" de verdade, não só enquanto a aba tá aberta.
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
