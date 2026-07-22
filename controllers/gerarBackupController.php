<?php

set_time_limit(0);
ini_set('max_execution_time', 0);

require_once '../config/conexao.php';
require_once '../helpers/authHelper.php';
require_once '../helpers/logHelper.php';
require_once '../config/googleDriveLoader.php';
require_once '../helpers/googleDriveHelper.php';

protegerAdmin('../login.php');

header('Content-Type: application/json');

$idUsuario = $_SESSION['id_usuario'];
$accessToken = obterTokenValidoGoogleDrive($conexao, $idUsuario);

if ($accessToken === null) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Conecte seu Google Drive antes de fazer o backup.']);
    exit;
}

$host  = getenv('DB_HOST')     ?: 'localhost';
$user  = getenv('DB_USER')     ?: 'root';
$senha = getenv('DB_PASSWORD') ?: '';
$banco = getenv('DB_NAME')     ?: 'formiga_ludica';
$porta = getenv('DB_PORT')     ?: '3306';

// Em produção (Dockerfile com default-mysql-client) "mysqldump" já está no
// PATH. Localmente no Windows dá pra apontar pro binário do XAMPP via essa
// variável, sem precisar mudar nada no container.
$mysqldumpBin = getenv('MYSQLDUMP_BIN') ?: 'mysqldump';

$nomeArquivo = 'backup_formiga_ludica_' . date('Y-m-d_His') . '.sql';
$caminhoTemp = sys_get_temp_dir() . '/fl_backup_' . $idUsuario . '_' . bin2hex(random_bytes(6)) . '.sql';

$comando = escapeshellarg($mysqldumpBin)
    . ' --host=' . escapeshellarg($host)
    . ' --port=' . escapeshellarg($porta)
    . ' --user=' . escapeshellarg($user)
    . ' --single-transaction --routines --no-tablespaces '
    . escapeshellarg($banco);

$descritores = [
    1 => ['file', $caminhoTemp, 'w'],
    2 => ['pipe', 'w'],
];

// Senha via variável de ambiente do processo (MYSQL_PWD), nunca como
// argumento -p na linha de comando — um -p fica visível a outros
// processos do mesmo host via /proc/<pid>/cmdline ou "ps".
$processo = proc_open($comando, $descritores, $pipes, null, ['MYSQL_PWD' => $senha]);

$stderr = stream_get_contents($pipes[2]);
fclose($pipes[2]);
$codigoSaida = proc_close($processo);

@chmod($caminhoTemp, 0600);

if ($codigoSaida !== 0 || !file_exists($caminhoTemp) || filesize($caminhoTemp) === 0) {
    registrarLog('ERRO', "Falha ao gerar dump do banco: $stderr");
    @unlink($caminhoTemp);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao gerar o backup do banco.']);
    exit;
}

$idArquivoDrive = enviarArquivoParaGoogleDrive($accessToken, $caminhoTemp, $nomeArquivo);
@unlink($caminhoTemp);

if ($idArquivoDrive === false) {
    registrarLog('ERRO', "Backup gerado mas falhou o upload pro Google Drive ({$_SESSION['email']}).");
    echo json_encode(['sucesso' => false, 'mensagem' => 'Backup gerado, mas falhou o envio pro Google Drive.']);
    exit;
}

registrarLog('INFO', "Backup enviado ao Google Drive por {$_SESSION['email']}: $nomeArquivo");
echo json_encode(['sucesso' => true, 'mensagem' => "Backup enviado ao seu Google Drive: $nomeArquivo"]);
