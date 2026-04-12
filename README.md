# CMS Platform Core

Fundacao inicial de uma plataforma extensivel em Laravel 11, desenhada para evoluir com arquitetura plugin-first e suporte a temas.

O foco do projeto continua sendo um core leve, previsivel e documentado. Nesta etapa, o produto ganhou o primeiro plugin oficial real do ecossistema, `Pages`, provando na pratica discovery, lifecycle, RBAC, hooks administrativos, persistencia propria e renderizacao publica com suporte ao tema ativo.

## Objetivos desta fundacao

- manter o core minimo
- preservar a separacao entre core, plugins e temas
- preparar pontos de extensao para instalador web, admin e sistema de extensoes
- organizar documentacao desde o inicio
- evitar implementacoes prematuras de modulos grandes

## Stack

- PHP 8.3+
- Laravel 11
- SQLite por padrao no bootstrap inicial do Laravel

## Estrutura principal

- `app/Core`: infraestrutura do core e pontos de extensao internos
- `app/Support`: utilitarios compartilhados e classes de apoio leves
- `plugins`: raiz para plugins e seus manifestos
- `themes`: raiz para temas e seus manifestos
- `docs`: documentacao arquitetural e operacional do produto

O detalhamento da estrutura atual esta em `docs/11-estrutura-inicial-core.md`.

## Estado atual

Esta base ainda nao implementa:

- plugin manager completo
- theme manager completo
- painel admin completo
- modulos como blog, loja, SEO, formularios ou analytics

Esta base ja inclui:

- contratos minimos para manifestos de plugin e tema
- discovery basico por manifesto em `plugins/*/plugin.json` e `themes/*/theme.json`
- validacao de estrutura minima e compatibilidade com a versao atual do core
- tratamento seguro para extensoes invalidas ou incompativeis
- registro persistido em banco das extensoes sincronizadas
- estado operacional simples com `discovered`, `installed`, `enabled` e `disabled`
- acoes de `enable` e `disable` sem bootar providers
- boot condicional apenas para plugins `enabled` e `valid` com provider seguro
- rotas administrativas iniciais do core com protecao basica
- autenticacao administrativa por sessao com tela de login dedicada
- base de usuarios, papeis e permissoes do core
- autorizacao do admin baseada em permission slugs
- area administrativa inicial para usuarios, cargos e permissoes do core
- base persistida de settings globais do core
- leitura e escrita centralizada de configuracoes globais
- tela administrativa inicial para settings globais
- persistencia propria para logs e auditoria administrativa do core
- servico central para registrar eventos auditaveis de acoes sensiveis
- tela administrativa inicial para consulta de logs de auditoria
- camada inicial de health checks e diagnostico operacional do core
- servico agregador para executar checks estruturados de saude do sistema
- tela administrativa inicial para consulta de system health
- diagnostico especifico do ecossistema de extensoes com severidade por extensao
- instalador web guiado em etapas claras
- validacao de requisitos e permissoes basicas antes do setup
- configuracao inicial de banco e ambiente sem exigir CLI do usuario final
- criacao do administrador inicial durante a instalacao
- estado explicito de instalacao concluida com bloqueio de reinstalacao
- dashboard operacional minimo do admin
- listagem administrativa inicial das extensoes registradas
- pagina inicial de manutencao com acoes seguras de limpeza de cache
- relatorio visual simples do ultimo bootstrap de plugins
- acoes operacionais reais de extensoes pelo admin do core
- sincronizacao manual do discovery com o registro persistido
- enable e disable de extensoes elegiveis pela interface administrativa
- camada central de pre-validacao operacional para actions de extensoes
- bloqueios consistentes para acoes redundantes, registros inelegiveis e extensoes criticas
- manifesto normalizado persistido para extensoes validas, incompativeis e parcialmente invalidas
- warnings de normalizacao para reduzir dependencia de leitura crua de `raw_manifest`
- capabilities operacionais normalizadas e consultaveis por servico dedicado
- camada operacional inicial de migrations por plugin, com deteccao de pendencias e execucao segura pelo admin
- base do theme manager com tema ativo persistido no core
- selecao segura do tema ativo pelo admin
- resolucao de views do frontend a partir do tema ativo com fallback para o core
- estrutura inicial de temas com suporte a `views/` e `assets/`
- primeira camada real de hooks/pontos de extensao do core para plugins habilitados
- registro central de contribuicoes de menu admin e paineis simples de dashboard
- primeiro plugin oficial real em `plugins/Pages`
- plugin Pages com CRUD administrativo minimo, permissoes proprias e rota publica para paginas publicadas

## Manifestos, discovery, registro e boot

As extensoes usam manifestos JSON simples e previsiveis:

- plugins: `plugins/<Slug>/plugin.json`
- temas: `themes/<Slug>/theme.json`

O sistema separa:

- `discovery_status`: `valid`, `invalid`, `incompatible`
- `lifecycle_status`: `discovered`, `installed`, `removed`
- `operational_status`: `discovered`, `installed`, `enabled`, `disabled`

O core tambem diferencia duas camadas de manifesto:

- `raw_manifest`: snapshot bruto do JSON encontrado no filesystem
- `normalized_manifest`: estrutura validada e previsivel usada como contrato principal do core

Nesta etapa, o manifesto normalizado suporta:

- `name`
- `slug`
- `type`
- `version`
- `description`
- `author`
- `vendor`
- `provider`
- `critical`
- `requires`
- `capabilities`
- `permissions`
- `core.min`
- `core.max`

Uso operacional atual desses campos:

- `provider` participa do boot condicional de plugins elegiveis
- `critical` pode bloquear `disable`
- `critical` pode bloquear `remove`
- `requires` bloqueia `enable` quando a dependencia nao existe ou nao esta habilitada
- `requires` bloqueia `disable` quando existem dependentes ativos da extensao alvo
- `requires` bloqueia `remove` quando existem dependentes ativos da extensao alvo
- `capabilities` agora sao normalizadas, classificadas entre reconhecidas e custom e expostas no admin e no health
- `permissions` agora permite que plugins validos e administrativamente instalados sincronizem permissoes proprias no catalogo central do core
- `migrations` agora permite detectar pendencias e executar migrations de plugins validos e administrativamente instalados sem exigir CLI no fluxo basico do admin

Capabilities reconhecidas inicialmente pelo core:

- `admin_pages`
- `widgets`
- `commands`
- `migrations`
- `providers`
- `assets`
- `integrations`
- `health_checks`
- `api_routes`

Limites atuais de `requires`:

- sem versoes minimas ou ranges semanticos
- sem auto-enable em cascata
- sem disable em cascata
- sem instalacao automatica
- sem resolucao profunda de arvore

Permissoes de plugin nesta etapa:

- sao declaradas apenas por plugins em `permissions`
- usam slugs locais no manifesto, como `manage_reports`
- sao sincronizadas no auth central como slugs globais, como `analytics-hub.manage_reports`
- recebem escopo explicito `plugin:<slug-do-plugin>`
- existem no sistema apenas para plugins `valid` e `installed`
- nao dependem de o plugin estar `enabled`
- sao removidas do catalogo quando o plugin fica `removed`, invalido, incompativel ou fora do lifecycle administrativo elegivel

Migrations de plugin nesta etapa:

- sao localizadas em `plugins/<Plugin>/database/migrations`
- usam a infraestrutura nativa de migrations do Laravel, sem mecanismo paralelo
- podem ser executadas pelo admin apenas para plugins `valid` e `installed`
- nao exigem que o plugin esteja `enabled`, permitindo preparar a persistencia antes do uso operacional pleno
- nao incluem rollback completo, update remoto ou upgrade manager

O boot atual so considera plugins quando:

- `type = plugin`
- `discovery_status = valid`
- `operational_status = enabled`
- `provider` esta declarado no manifesto
- a classe do provider existe e extende `ServiceProvider`

Temas nunca sao tratados como providers nesta fase.

## Hooks e pontos de extensao iniciais

O core agora expoe uma camada pequena e explicita de pontos de extensao publicos para plugins:

- itens de navegacao do admin
- paineis simples de dashboard

Regras desta fase:

- apenas plugins `valid`, `installed` e `enabled` podem publicar contribuicoes
- o registro e centralizado e em memoria por requisicao
- contribuicoes carregam o `plugin_slug` de origem
- contribuicoes podem declarar permissao exigida, e o admin so as exibe quando o usuario atual puder acessa-las

O plugin oficial `Pages` ja usa essa base para publicar:

- item de navegacao `Pages` no admin
- painel simples `Pages Library` no dashboard
- permissao exigida `pages.view_pages`

Ainda nao existe:

- hook engine amplo
- event bus
- widgets drag-and-drop
- menus automaticos a partir de manifesto
- surfaces dinamicas arbitrarias

## Tema ativo e frontend

O core agora possui um conceito explicito de tema ativo:

- o slug do tema ativo e persistido em `core_settings` no grupo `themes`
- apenas temas `valid` e elegiveis podem ser selecionados
- quando necessario, a selecao instala o tema no lifecycle administrativo antes de marca-lo como ativo
- a selecao do tema ativo nao instala arquivos, nao executa providers e nao cria engine de template nova

No frontend:

- o core resolve `views/` do tema ativo usando Blade
- quando a view esperada nao existe no tema, o sistema usa fallback seguro do core
- assets ja possuem pasta reservada no tema, mas ainda nao existe pipeline avancado nesta etapa

O plugin oficial `Pages` ja usa essa camada com:

- rota publica `GET /pages/{slug}` para paginas `published`
- override opcional por tema em `themes/<Theme>/views/plugins/pages/show.blade.php`
- fallback padrao para a view `pages::front.show`

## Instalador web

O produto agora possui um instalador guiado em `/install` com as etapas:

- welcome
- requirements
- database
- administrator
- complete

Durante a instalacao, o wizard:

- valida requisitos de runtime e permissoes de escrita
- coleta dados do banco
- testa a conexao antes do setup
- grava a configuracao inicial em `.env`
- gera `APP_KEY` quando necessario
- roda migrations
- sincroniza a seguranca base do core
- cria o administrador inicial
- marca a instalacao como concluida
- bloqueia reinstalacao acidental

Depois da conclusao, `/install` passa a redirecionar para o login do admin.

## Admin inicial do core

O admin atual continua propositalmente pequeno e focado em operacao:

- `GET /admin/login` para autenticacao administrativa
- `GET /admin` com resumo operacional do core
- `GET /admin/extensions` com a listagem do registro persistido de plugins e temas
- `GET /admin/themes` com a listagem de temas descobertos e selecao do tema ativo
- `GET /admin/pages` com a area administrativa do plugin oficial Pages, quando o plugin estiver instalado e habilitado
- `POST /admin/extensions/sync` para sincronizacao manual do registro
- `POST /admin/extensions/{extension}/migrations/run` para executar migrations pendentes de plugin elegivel
- `GET /admin/maintenance` com acoes seguras de limpeza de cache e status basico do ambiente
- `GET /admin/users`, `GET /admin/roles` e `GET /admin/permissions` para governanca inicial de acesso
- `GET /admin/settings` para configuracao global minima da instancia
- `GET /admin/audit` para consulta operacional dos logs de auditoria do core
- `GET /admin/health` para diagnostico operacional minimo do sistema

O acesso agora usa autenticacao e autorizacao reais do core:

- o usuario precisa estar autenticado
- o usuario precisa da permissao `access_admin`
- cada area protegida exige permissoes especificas adicionais
- acoes de manutencao exigem permissao propria

Permissoes iniciais do core:

- `access_admin`
- `view_dashboard`
- `view_extensions`
- `manage_extensions`
- `view_themes`
- `manage_themes`
- `view_maintenance`
- `run_maintenance_actions`
- `manage_users`
- `manage_roles`
- `manage_permissions`
- `view_settings`
- `manage_settings`
- `view_audit_logs`
- `view_system_health`

Permissoes publicadas pelo plugin oficial Pages:

- `pages.view_pages`
- `pages.create_pages`
- `pages.edit_pages`
- `pages.publish_pages`
- `pages.delete_pages`

Os settings globais atuais do core ficam organizados no grupo `general`, com persistencia propria e leitura centralizada para:

- nome do site
- tagline
- email base do sistema
- timezone
- locale
- footer
- scripts globais opcionais armazenados com escopo controlado

O core agora tambem registra auditoria administrativa minima para:

- login e logout do admin
- criacao e edicao de usuarios
- criacao e edicao de cargos
- alteracoes de permissoes por cargo
- atualizacao de settings globais
- troca de tema ativo
- acoes de manutencao acionadas pelo painel

O core agora tambem executa health checks basicos para leitura administrativa de:

- estado de instalacao
- conexao com banco
- diretorios criticos gravaveis
- disponibilidade do cache
- APP_KEY configurada
- superficie basica do registro de extensoes
- saude operacional do ecossistema de extensoes

Na area de extensoes do admin, o core agora permite:

- sincronizar manualmente o discovery do filesystem com o registro persistido
- instalar extensoes no lifecycle administrativo sem tocar no filesystem
- remover extensoes do lifecycle administrativo sem apagar arquivos do disco
- habilitar extensoes elegiveis
- desabilitar extensoes habilitadas
- visualizar se o plugin possui migrations locais e quantas estao pendentes
- executar migrations pendentes de plugins elegiveis sem depender de terminal
- auditar essas acoes como operacoes sensiveis do admin
- visualizar metadados do manifesto normalizado, incluindo criticidade, provider, vendor, dependencias declaradas e warnings de normalizacao
- visualizar dependentes ativos quando isso impacta a operacao de disable

Nesta etapa:

- `install` significa registrar a extensao como instalada para uso administrativo no core
- `remove` significa desregistrar logicamente a extensao no lifecycle administrativo, preservando os arquivos no filesystem
- o proximo `sync` continua atualizando metadados do discovery, mas preserva o estado `removed` em vez de reinstalar automaticamente a extensao

Antes de enable ou disable, o core agora executa uma avaliacao operacional centralizada que pode:

- permitir a acao
- bloquear a acao com motivo claro
- retornar warnings operacionais leves

O `enable` agora depende de duas camadas separadas:

- a extensao precisa estar `valid` no discovery
- a extensao precisa estar `installed` no lifecycle administrativo

Dependencias entre extensoes agora ja bloqueiam operacoes inconsistentes em nivel direto, mas o core ainda nao implementa resolucao automatica profunda, cascata ou versionamento de dependencias.

Regra atual para migrations de plugin:

- o plugin precisa estar `valid` no discovery
- o plugin precisa estar `installed` no lifecycle administrativo
- o plugin pode estar `disabled`; isso nao bloqueia migrations nesta fase
- plugins `invalid`, `incompatible`, `removed` ou sem diretorio/arquivos de migration nao recebem execucao

O health de extensoes verifica nesta etapa:

- extensoes com manifesto invalido ou incompativel
- extensoes com warnings relevantes de manifesto normalizado
- extensoes com dependencias ausentes
- extensoes com dependencias desabilitadas
- extensoes criticas fora do estado habilitado
- extensoes habilitadas com inconsistencias operacionais relevantes
- capabilities malformadas ou custom com warnings relevantes
- inconsistencias leves entre capabilities declaradas e metadados conhecidos

Severidade atual:

- `ok`: sem problemas relevantes detectados
- `warning`: atencao operacional sem inconsistencia critica imediata
- `error`: risco operacional relevante, especialmente para extensoes habilitadas ou criticas

O health continua somente diagnostico:

- sem auto-fix
- sem mutacao de estado
- sem instalacao automatica
- sem resolucao profunda de dependencias
- sem execucao dinamica automatica por capability
- sem hook engine ou event bus de plugins

Em ambientes `local` e `testing`, o seeder do core pode criar um administrador inicial configuravel sem comprometer producao.

Antes da instalacao ser concluida, as rotas administrativas redirecionam para o instalador.

## Como subir localmente

1. Instale as dependencias:

```bash
composer install
```

2. Prepare o ambiente:

```bash
copy .env.example .env
php artisan key:generate
```

3. Ajuste as configuracoes do banco no `.env`.

4. Se preferir o fluxo guiado, inicie o servidor e acesse `/install`.

5. Se quiser o fluxo manual de desenvolvimento, rode as migrations base:

```bash
php artisan migrate
```

6. Carregue os seeds do core para ter papeis, permissoes e admin local:

```bash
php artisan db:seed
```

7. Inicie o servidor local:

```bash
php artisan serve
```

8. Acesse o admin em `/admin/login`.

Credenciais locais padrao, se o seeder estiver habilitado:

- email: `admin@example.test`
- senha: `admin12345`

Esses valores podem ser sobrescritos por ambiente com:

- `CORE_ADMIN_SEED_LOCAL`
- `CORE_ADMIN_LOCAL_NAME`
- `CORE_ADMIN_LOCAL_EMAIL`
- `CORE_ADMIN_LOCAL_PASSWORD`

## Documentacao

- `AGENTS.md`
- `CHANGELOG.md`
- `CONTRIBUTING.md`
- `docs/00-visao-geral.md`
- `docs/01-arquitetura-core.md`
- `docs/02-instalacao.md`
- `docs/03-admin.md`
- `docs/04-plugin-system.md`
- `docs/05-theme-system.md`
- `docs/06-hooks-e-extensoes.md`
- `docs/07-criando-plugin.md`
- `docs/08-criando-tema.md`
- `docs/09-versionamento-e-upgrades.md`
- `docs/10-troubleshooting.md`
- `docs/11-estrutura-inicial-core.md`

## Principios de arquitetura

- tudo que puder ser plugin deve ser plugin no futuro
- o core hospeda infraestrutura, seguranca, operacao e contratos publicos
- temas cuidam da apresentacao, nao da regra de negocio
- documentacao faz parte do produto
- evolucao deve ser incremental, previsivel e compativel

## Proximos marcos naturais

- evoluir o admin para areas futuras de plugins, temas, settings, logs e ambiente
- evoluir o instalador para health checks e diagnosticos mais ricos
- evoluir o boot condicional para lifecycle mais rico
- ampliar a governanca de seguranca sem virar um modulo enterprise pesado
- introduzir hooks apenas quando o carregamento de extensoes estiver consolidado

Nada disso foi implementado nesta etapa para manter o escopo restrito e incremental.
