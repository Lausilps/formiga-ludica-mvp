-- Rode no console do Railway. Tabela pra sessão de login persistente —
-- sem ela, todo mundo é deslogado a cada deploy (disco do container não
-- sobrevive entre um deploy e outro).

CREATE TABLE sessoes_php (
    id VARCHAR(128) PRIMARY KEY,
    dados MEDIUMTEXT,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);
