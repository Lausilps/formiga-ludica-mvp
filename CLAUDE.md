# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Formiga Lúdica MVP — a web platform for a board game rental business ("locadora de jogos"). Plain PHP (no framework), MySQL, vanilla JS, no build step. Public catalog + WhatsApp-based checkout, an admin panel for managing games, and a Gemini-powered RAG recommendation feature ("Formiguinha").

## Running the project

There is no framework CLI, no test suite, and no bundler/npm. Development happens directly against PHP files:

- **Local (XAMPP):** drop the repo in `htdocs` and hit it through Apache, or run PHP's built-in server: `php -S localhost:8080`.
- **Docker:** `Dockerfile` uses `php:8.2-cli` with `mysqli`, `pdo_mysql`, `curl` extensions and serves via `php -S 0.0.0.0:${PORT:-8080}`.
- **Dependencies:** `composer install` (only dependency is `dompdf/dompdf` for PDF reports).
- **No linter, formatter, or test suite is configured** — there's nothing to run beyond manually exercising the app in a browser.

### Required environment / config

- `config/conexao.php` reads `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `DB_PORT` from env (falling back to XAMPP-style defaults), and connects with `mysqli_connect`. Note: the port fallback references a `PORTA` constant that is **not defined anywhere in the codebase** — if `DB_PORT` isn't set in the environment, this will fatal error.
- `config/gemini.php` requires `GEMINI_API_KEY` in env or calls `die()`. Defines `geminiEmbedding()`, `geminiChat()`, and `cosineSimilarity()` used by the recommendation feature.
- `config/ludopedia.php` and `config/groq.php` are **gitignored** (real API credentials live there locally, not in the repo) — `LUDOPEDIA_APP_ID`, `LUDOPEDIA_APP_SECRET`, `LUDOPEDIA_TOKEN`, `LUDOPEDIA_CALLBACK`. Anyone recreating this file locally needs Ludopedia API credentials.
- `ADMIN_IMPORT_TOKEN` in env gates the HTTP-triggered embedding batch (`controllers/gerarEmbeddings.php`) and Ludopedia sync (`controllers/importarLudopediaController.php`) endpoints when hit outside the admin panel button. If unset, those endpoints refuse all requests (fail closed) — CLI runs are unaffected.
- No `.sql` schema file exists in the repo — the `jogos` table structure has to be inferred from the queries in `controllers/`.
- **Production database is hosted on Railway** (MySQL service), not local XAMPP — Laura owns the Railway project/account. Connection vars (`MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`) are found in the Railway dashboard → MySQL service → **Connect** tab. Local XAMPP (`localhost`/`root`) is only used for local dev, per `config/conexao.php`'s fallback defaults.
- `config/googleDrive.php` (gitignored, local only) or the `GOOGLE_DRIVE_CLIENT_ID` / `GOOGLE_DRIVE_CLIENT_SECRET` / `GOOGLE_DRIVE_REDIRECT_URI` env vars (Railway) hold the OAuth2 credentials for the per-admin Google Drive backup feature, loaded by `config/googleDriveLoader.php`. Missing `CLIENT_ID`/`CLIENT_SECRET` makes it `die()`. `MYSQLDUMP_BIN` env var optionally overrides the `mysqldump` binary path (used locally to point at the XAMPP one on Windows).

## Backups

Railway's automatic/scheduled backups only exist on **Pro-tier** accounts — this project is not on Pro, so there is no automatic backup. Two ways to cover that gap:

- **In-app (admin panel):** each admin connects their own Google Drive once (OAuth2, "Conectar Google Drive" button in `views/jogos/listar.php`), then a "Fazer backup agora" button runs `mysqldump` server-side (`controllers/gerarBackupController.php`) and uploads the `.sql` straight to that admin's personal Drive via `helpers/googleDriveHelper.php`. Tokens live in the `usuarios_google_drive` table (created by `migracao_google_drive.sql` — run it manually on the Railway console, it's not an auto-applied migration). This is still an on-demand click, not a scheduled job.
- **Manual (CLI), as a fallback / for local dumps:**

```powershell
& "C:\xampp\mysql\bin\mysqldump.exe" -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> <MYSQLDATABASE> > backup_YYYY-MM-DD.sql
```

(XAMPP already ships `mysqldump.exe` at that path — no extra install needed.) Store the resulting `.sql` file somewhere outside the local disk too (Drive/OneDrive). Do a dump (either way) daily, or after each cadastro session, whenever someone is actively adding games, since there's no safety net otherwise. Never commit real Railway credentials into this repo or into `.sql` dumps that get committed — treat them like any other secret.

## Architecture

There is no router or front controller. Each `.php` file at the root or under `views/`/`controllers/` is hit directly as a URL, and HTML/PHP forms POST/GET straight to controller scripts by relative path (e.g. `<form action="controllers/loginController.php">`). Controllers typically `require_once` config/helpers, do their work, then either `header("Location: ...")` back to a view or `echo json_encode(...)` for AJAX endpoints.

### Two areas of the site

- **Public catalog** (root level): `index.php` (catalog + infinite scroll + WhatsApp cart + direct/shareable game links), `recomendacao_form.php` + `controllers/recomendacaoController.php` (AI recommendation flow), `views/jogos/recomendacao.php` (results page). No auth.
- **Admin panel** (`views/jogos/*.php` + matching `controllers/*Controller.php`): listing/search/pagination, cadastrar (create), editar (edit), relatório (PDF report), imports. Every admin view calls `helpers/authHelper.php::protegerAdmin()` at the top, which redirects to `login.php` unless `$_SESSION['tipo'] === 'admin'`. Session-based auth only, single user table (`usuarios`), passwords via `password_hash`/`password_verify` (see `gerar_senha.php` for hash generation, `controllers/loginController.php`, `logout.php`).

### AI recommendation flow (RAG)

This is the core "smart" feature, split across:
- `controllers/gerarEmbeddings.php` — batch job that generates a Gemini embedding for every game missing one and stores it as JSON text in the `jogos.embedding` column. Guarded by a `?token=...` query token (checked against the `ADMIN_IMPORT_TOKEN` env var) when not run from CLI.
- `controllers/recomendacaoController.php` — takes the user's free-text description + player count/age/time filters, embeds the query via Gemini, pre-filters candidate games in SQL (players/age/time), ranks by `cosineSimilarity()` computed in PHP against stored embeddings, takes the top 12, then asks Gemini to pick and justify 6 of them via a prompt requiring strict JSON output. Renders `views/jogos/recomendacao.php`.
- `controllers/maisRecomendacoesController.php` — same flow as an AJAX/JSON endpoint for the "+ Recomendações" button, excluding games already shown (`ids_exibidos`).
- `helpers/recomendacaoHelper.php::rankearJogosPorSimilaridade()` — besides sorting by `cosineSimilarity()`, games curated as store highlights (`ordem_destaque` not null — the same ones shown in index.php's "Recomendações da loja" carousel) that also fit the request's filters are boosted ahead of non-highlighted games of similar score. `montarObservacaoDestaque()` adds a line to the Gemini prompt telling it to prefer those `[Recomendação da loja]`-tagged candidates when they genuinely fit, never forcing one that doesn't. Shared by both the initial flow and "+ Recomendações".
- All steps log heavily to `logs/sistema.log` via `helpers/logHelper.php::registrarLog()` — useful for debugging Gemini prompt/response issues.

### External integrations

- **Gemini** (`config/gemini.php`): embeddings (`models/gemini-embedding-2`) and chat (`models/gemini-2.5-flash`) via raw cURL to `generativelanguage.googleapis.com`. `CURLOPT_SSL_VERIFYPEER/HOST` are disabled to work around XAMPP's SSL setup.
- **Ludopedia API** (`config/ludopedia.php`, `controllers/importarLudopediaController.php`): paginated import/sync of the rental shop's game collection, auto-generates Portuguese descriptions per game via Gemini, maps categories into a `categorias`/`jogos_categorias` join. Also gated by the same `?token=...`/`ADMIN_IMPORT_TOKEN` mechanism. `relatorio_faltantes.php` cross-checks the local DB against the full Ludopedia collection to find games not yet imported.
- **OlaClick import** (`controllers/importarOlaClickController.php`): one-off import from a pasted JSON blob (product catalog), regex-parses player count/age/duration out of free-text descriptions.
- **WhatsApp**: no API — both `index.php` and `views/jogos/recomendacao.php` build a `wa.me` deep link with a pre-filled order message client-side.

### Cart / selection state

`assets/js/carrinho.js` exposes a tiny `Carrinho` object (`obter()`/`salvar()`) wrapping `localStorage` under key `formigaludica_carrinho`. Both `index.php` (catalog) and `views/jogos/recomendacao.php` (recommendation results) embed their own near-duplicate inline `<script>` blocks that read/write this same cart, so a selection made on one page persists into the other — if you change cart behavior, check both files, not just `carrinho.js`.

### Direct/shareable game links

`index.php`'s catalog modal reflects the open game in the URL as `?jogo=<slug>` (e.g. `?jogo=bananagrams`) via `history.pushState`, so the address bar itself becomes a shareable link, and a "Compartilhar jogo" button in the modal uses the Web Share API (`navigator.share`) to open the device's native share sheet, falling back to copy-to-clipboard where `navigator.share` isn't available (most desktop browsers).
- `helpers/slugHelper.php::gerarSlug()` — turns a game name into a slug using a **fixed accent-mapping table** (á/à/â/ã/ä → a, ç → c, etc.), not `iconv(...TRANSLIT...)` — that was tried first but its output for accented chars is inconsistent across systems/locales (e.g. produced `edic-ao` instead of `edicao` locally). There is no `slug` column in the DB; the slug is always computed on the fly from `nome`.
- `controllers/buscarJogoPorIdAjax.php` — despite the filename (kept from when it looked up by numeric ID), it now takes `?slug=...` and finds the matching game by comparing `gerarSlug()` against every active game's `nome` (no indexed lookup — fine at this catalog's size, would need revisiting if the collection grows a lot).
- `controllers/listarJogosAjax.php` and `controllers/carrosseisAjax.php` also include `slug` in every game they return, so cards built anywhere in the catalog (grid, carousels) carry it in `dataset.slug` for the share button to use.

### Data access patterns (inconsistent — know both when editing)

- `controllers/listarJogosAjax.php` (catalog search/filter/infinite-scroll endpoint) uses proper `mysqli_prepare`/bound params.
- Most CRUD controllers (`jogosController.php`, `editarJogoController.php`, `loginController.php`, `importarOlaClickController.php`, `gerarRelatorioJogosPdf.php`) interpolate `$_POST`/`$_GET` values directly into SQL strings. Be careful when touching these — follow the prepared-statement pattern from `listarJogosAjax.php` rather than the string-interpolation pattern when adding new queries.

### PDF reports


`controllers/gerarRelatorioJogosPdf.php` uses `dompdf/dompdf` (via Composer/`vendor/`) to render a filtered games table as a landscape A4 PDF, with a "sintético" vs "analítico" (includes description) mode toggle.

## Project rules / preferences

- Keep the project in plain PHP. Do not introduce Laravel, frameworks, routers, or MVC rewrites unless explicitly requested.
- Prefer small, focused changes instead of large refactors.
- When changing SQL, use prepared statements and avoid string interpolation.
- Preserve the existing visual identity: cute/geek, playful, board-game rental theme.
- Keep variable and function names in Portuguese when editing existing PHP/JS.
- Do not change database structure without explaining the impact first.
- Do not remove existing features while improving layout or code.
- When editing cart behavior, always check both `index.php`, `views/jogos/recomendacao.php`, and `assets/js/carrinho.js`.
- When editing recommendation logic, preserve the Gemini RAG flow unless the change is explicitly about the AI pipeline.
- Before making changes, explain what files will be touched and why.
- After making changes, summarize exactly what was changed.

## Known risks

- Some controllers still use raw SQL interpolation. New code should follow the prepared statement pattern from `controllers/listarJogosAjax.php`.
- `config/conexao.php` may fail if `DB_PORT` is missing because `PORTA` is referenced but not defined.
- Cart logic is duplicated in inline scripts and should be changed carefully.

## Working with Laura

**Never add an AI co-authorship/credit line to commit messages** (e.g. `Co-Authored-By: Claude ...`) — Laura has asked for this explicitly more than once. Commit messages should read as if she wrote them herself, no exceptions.

Laura prefers incremental development.

Never refactor unrelated code.

Never change the project's architecture unless explicitly requested.

Always explain the reasoning before modifying files.

When multiple solutions exist, present the alternatives with their trade-offs instead of choosing one automatically.

Prefer maintaining the current coding style over introducing new patterns.

When a task affects multiple files, explain why each file needs to be modified.

Always preserve the visual identity unless asked to redesign it.