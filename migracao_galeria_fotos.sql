-- Rode isso direto no banco do Railway (console de query do MySQL do projeto).
-- Cria a tabela de galeria e migra a foto atual de cada jogo pra dentro dela,
-- como a primeira foto (ordem 0) — nenhum jogo fica sem foto depois disso.

CREATE TABLE jogos_imagens (
    id_imagem INT AUTO_INCREMENT PRIMARY KEY,
    id_jogo INT NOT NULL,
    caminho VARCHAR(500) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jogo) REFERENCES jogos(id_jogo) ON DELETE CASCADE
);

INSERT INTO jogos_imagens (id_jogo, caminho, ordem)
SELECT id_jogo, imagem, 0
FROM jogos
WHERE imagem IS NOT NULL AND imagem <> '';
