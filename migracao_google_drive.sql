-- Rode no console do Railway. Tabela pra guardar o token do Google Drive de
-- cada admin que conectar a própria conta (Laura primeiro, Jander depois) —
-- cada admin autoriza o próprio Drive uma vez, e os backups sobem lá.

CREATE TABLE usuarios_google_drive (
    id_usuario INT PRIMARY KEY,
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    token_expira_em DATETIME NOT NULL,
    escopo VARCHAR(255) NOT NULL,
    conectado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);
