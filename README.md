# 🐜 Formiga Lúdica MVP

Plataforma web para uma locadora de jogos de tabuleiro: catálogo público com carrinho via WhatsApp, painel administrativo completo e recomendação de jogos por IA (RAG com Gemini). PHP puro, sem framework, sem build step.

---

## Sobre o projeto

O Formiga Lúdica MVP nasceu pra automatizar o dia a dia de uma locadora de jogos: cadastro e catálogo dos jogos, pedidos via WhatsApp, importação/sincronização automática da coleção cadastrada na Ludopedia (com geração de descrição por IA) e uma recomendação inteligente ("Formiguinha") que sugere jogos com base no perfil do grupo.

---

## Funcionalidades

- [x] Catálogo público com busca, filtros (idade, jogadores, tempo) e scroll infinito
- [x] Carrinho de seleção (localStorage) com finalização de pedido via WhatsApp
- [x] Painel administrativo (login com sessão): cadastro, edição, listagem e busca de jogos
- [x] Recomendação inteligente de jogos com IA (RAG: embeddings + Gemini)
- [x] Importação e sincronização automática via API da Ludopedia (com retomada de onde parou)
- [x] Geração automática de descrição dos jogos via IA na importação
- [x] Importação avulsa a partir de um JSON colado (catálogo do OlaClick)
- [x] Relatório de jogos em PDF (sintético/analítico), com filtros e sinalização de possíveis duplicados
- [x] Relatório de jogos ainda não importados da Ludopedia

---

## Tecnologias

- PHP 8.2 (sem framework)
- MySQL (via `mysqli`)
- JavaScript puro (sem build step, sem bundler)
- HTML5 / CSS3
- [Gemini API](https://ai.google.dev/) — embeddings e chat (recomendação)
- [Ludopedia API](https://ludopedia.com.br/) — importação da coleção
- `dompdf/dompdf` (via Composer) — geração de PDF
- Docker (deploy) / Railway (hospedagem atual)

---

## Estrutura do projeto

```text
formiga-ludica-mvp/
│
├── assets/              # CSS, JS e imagens
├── config/               # Conexão com banco e integrações (Gemini, Ludopedia)
├── controllers/          # Lógica de cada ação (CRUD, importações, recomendação, relatórios)
├── helpers/               # Funções auxiliares (auth, log, jogos, recomendação)
├── views/
│   ├── jogos/             # Telas do painel admin (listar, cadastrar, editar, relatório...)
│   └── partials/          # Header/footer reaproveitados
├── uploads/jogos/         # Imagens de jogos enviadas pelo admin
├── logs/                  # Log de sistema (gitignorado, exceto .gitkeep)
├── index.php              # Catálogo público
├── recomendacao_form.php  # Formulário da recomendação por IA
├── login.php / logout.php # Autenticação do admin
├── relatorio_faltantes.php # Compara banco local x coleção completa da Ludopedia
└── Dockerfile
```

---

## Como rodar localmente

### Opção 1 — XAMPP

1. Clone o repositório dentro da pasta `htdocs` do XAMPP.
2. Rode `composer install` na raiz do projeto (só dependência: `dompdf/dompdf`).
3. Crie o banco de dados MySQL e as tabelas (ver [Banco de dados](#banco-de-dados) abaixo — não existe um `.sql` de schema no repositório).
4. Configure as variáveis de ambiente (ver seção abaixo). No XAMPP local, o jeito mais simples é criar um `.htaccess` na raiz do projeto com `SetEnv NOME_DA_VAR valor` para cada uma (o `.htaccess` **não deve** ser commitado se tiver valores reais).
5. Acesse pelo Apache (`http://localhost/formiga-ludica-mvp`) ou suba com o servidor embutido do PHP:
   ```bash
   php -S localhost:8080
   ```

### Opção 2 — Docker

```bash
docker build -t formiga-ludica .
docker run -p 8080:8080 --env-file .env formiga-ludica
```
O `Dockerfile` usa `php:8.2-cli` com as extensões `mysqli`, `pdo_mysql` e `curl`, e serve com `php -S 0.0.0.0:${PORT:-8080}`.

---

## Variáveis de ambiente

Nenhuma credencial real fica no código — tudo é lido via `getenv()`. Em produção (Railway), configure na aba **Variables** do serviço.

| Variável | Usada em | Obrigatória? | Descrição |
|---|---|---|---|
| `DB_HOST` | `config/conexao.php` | Recomendado | Host do MySQL (padrão: `localhost`) |
| `DB_USER` | `config/conexao.php` | Recomendado | Usuário do MySQL (padrão: `root`) |
| `DB_PASSWORD` | `config/conexao.php` | Recomendado | Senha do MySQL (padrão: vazia) |
| `DB_NAME` | `config/conexao.php` | Recomendado | Nome do banco (padrão: `formiga_ludica`) |
| `DB_PORT` | `config/conexao.php` | **Sim** | Porta do MySQL. ⚠️ Ver [problema conhecido](#problemas-conhecidos) abaixo — sem essa variável, a aplicação **quebra** (fatal error) |
| `GEMINI_API_KEY` | `config/gemini.php` | Sim, pra usar a IA | Chave da API do Gemini (embeddings + recomendação). Sem ela, `die()` imediato |
| `LUDOPEDIA_APP_ID` | `config/ludopediaLoader.php` | Sim, pra importar da Ludopedia | Credencial do app na Ludopedia |
| `LUDOPEDIA_APP_SECRET` | `config/ludopediaLoader.php` | Sim, pra importar da Ludopedia | Credencial do app na Ludopedia |
| `LUDOPEDIA_TOKEN` | `config/ludopediaLoader.php` | Sim, pra importar da Ludopedia | Token de acesso à coleção |
| `LUDOPEDIA_CALLBACK` | `config/ludopediaLoader.php` | Sim, pra importar da Ludopedia | URL de callback do OAuth da Ludopedia |
| `ADMIN_IMPORT_TOKEN` | `controllers/gerarEmbeddings.php`, `controllers/importarLudopediaController.php` | Sim, pra usar os botões de IA/import no admin | Token que protege os endpoints de geração de embeddings e sincronização Ludopedia quando chamados por HTTP (fora do CLI). Invente uma string aleatória longa — não precisa decorar, só configurar |

**Localmente**, em vez de `LUDOPEDIA_APP_ID/APP_SECRET/TOKEN/CALLBACK` via env, dá pra criar um arquivo `config/ludopedia.php` (gitignorado) com as constantes definidas direto — é o que `config/ludopediaLoader.php` procura primeiro antes de cair pras variáveis de ambiente. Exemplo:
```php
<?php
define('LUDOPEDIA_APP_ID', 'seu_app_id');
define('LUDOPEDIA_APP_SECRET', 'seu_app_secret');
define('LUDOPEDIA_TOKEN', 'seu_token');
define('LUDOPEDIA_CALLBACK', 'http://localhost/formiga-ludica-mvp/callback');
```

---

## Banco de dados

Não existe um arquivo `.sql` de schema no repositório — as tabelas usadas hoje (inferidas das queries) são:

- **`jogos`** — catálogo (nome, imagem, descrição, preço, min/max jogadores, idade mínima, duração, dificuldade, `ativo`, `id_ludopedia`, `embedding` como texto JSON, `criado_em`...)
- **`usuarios`** — login do admin (`id_usuario`, `nome`, `email`, `senha` com hash bcrypt, `tipo`, `ativo`)
- **`categorias`** / **`jogos_categorias`** — categorias dos jogos, mapeadas na importação da Ludopedia

### Criar um novo usuário admin

Já logado no painel, tem um botão "+ Novo usuário" na listagem de jogos
(`views/usuarios/criar.php` / `controllers/criarUsuarioController.php`) —
só quem já está autenticado como admin consegue criar outro. Não existe
cadastro público.

Pro **primeiro** usuário (antes de existir qualquer admin pra criar os
próximos), ainda é preciso inserir direto no banco. Gere o hash da senha:
```bash
php -r "echo password_hash('SUA_SENHA_AQUI', PASSWORD_DEFAULT), PHP_EOL;"
```
E insira no banco:
```sql
INSERT INTO usuarios (nome, email, senha, tipo, ativo)
VALUES ('Seu Nome', 'seu@email.com', 'HASH_GERADO_ACIMA', 'admin', 1);
```

---

## Fluxo de administração (IA e Ludopedia)

Depois de logado no painel (`login.php` → `views/jogos/listar.php`), tem dois botões no topo da listagem:

- **🧠 Atualizar IA** — roda `controllers/gerarEmbeddings.php`, que gera o embedding (Gemini) de todo jogo que ainda não tem, em lotes, até terminar. Precisa de `GEMINI_API_KEY` e `ADMIN_IMPORT_TOKEN` configurados.
- **🎲 Sincronizar Ludopedia** — roda `controllers/importarLudopediaController.php`, que importa/atualiza os jogos da coleção da Ludopedia (gera descrição via IA automaticamente, salva de onde parou se cair no meio). Precisa das credenciais `LUDOPEDIA_*` e de `ADMIN_IMPORT_TOKEN`.

Ambos os controllers também podem ser rodados direto via terminal (sem precisar do token, já que CLI é liberado por padrão):
```bash
php controllers/gerarEmbeddings.php
php controllers/importarLudopediaController.php
```

**`relatorio_faltantes.php`** (acessado direto pela URL, ex: `/relatorio_faltantes.php`) compara o banco local com a coleção completa da Ludopedia e lista o que ainda não foi importado — útil pra conferir depois de uma sincronização parcial.

> ⚠️ Em `controllers/importarLudopediaController.php` existe uma constante `GERAR_DESCRICAO_VIA_IA` que pode estar `false` temporariamente (foi desativada em algum momento pra não estourar cota do Gemini durante um sync grande). Se as descrições pararem de vir automáticas na importação, é o primeiro lugar pra checar.

---

## Problemas conhecidos

- **`DB_PORT` é obrigatória.** `config/conexao.php` tem um fallback pra uma constante `PORTA` que **não existe em lugar nenhum do código** — se `DB_PORT` não estiver definida no ambiente, a aplicação quebra com fatal error. Sempre configure `DB_PORT` explicitamente (ex: `3306`) em qualquer hospedagem nova.
- Parte dos controllers de CRUD (`jogosController.php`, `editarJogoController.php`, `loginController.php`, `importarOlaClickController.php`, `gerarRelatorioJogosPdf.php`) monta SQL por interpolação de string em vez de prepared statements. Funciona, mas qualquer query nova deve seguir o padrão de `controllers/listarJogosAjax.php` (prepared statements).

---

## Deploy (Railway)

O `Dockerfile` já está pronto pro Railway: builda com `composer install --no-dev`, expõe a porta via `${PORT}` (o Railway injeta essa variável sozinho). Basta:
1. Criar o serviço apontando pro repositório.
2. Adicionar um serviço de MySQL e configurar `DB_*` (host/porta/usuário/senha do próprio serviço do Railway).
3. Configurar as demais variáveis de ambiente da tabela acima na aba **Variables**.

---

## Desenvolvido por

Laura Lopes
