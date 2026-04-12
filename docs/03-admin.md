# Admin

## Estado atual

Existe agora uma base administrativa funcional do core em Blade, mas ainda nao existe um admin completo nem um modulo enterprise de IAM.

## O que foi preparado

- rotas administrativas do core sob prefixo configuravel
- autenticacao administrativa por sessao
- autorizacao administrativa baseada em papeis e permissoes
- layout base server-rendered em Blade
- componentes reutilizaveis coerentes com o design system do produto
- dashboard operacional minimo
- pagina inicial de extensoes registradas
- acoes operacionais reais para sincronizar e alterar estado de extensoes elegiveis
- consumo de contribuicoes administrativas publicadas por plugins habilitados
- pagina inicial de manutencao
- paginas administrativas iniciais de usuarios, cargos e permissoes
- pagina administrativa inicial de settings globais do core
- pagina administrativa inicial de logs de auditoria do core
- pagina administrativa inicial de health checks e diagnostico do sistema
- consumo do registro persistido de extensoes e do ultimo relatorio de bootstrap

## Rotas entregues nesta etapa

- `GET /admin/login`
- `POST /admin/login`
- `POST /admin/logout`
- `GET /admin`
- `GET /admin/extensions`
- `GET /admin/themes`
- `POST /admin/extensions/sync`
- `POST /admin/extensions/{extension}/install`
- `POST /admin/extensions/{extension}/remove`
- `POST /admin/extensions/{extension}/migrations/run`
- `POST /admin/themes/{extension}/activate`
- `GET /admin/maintenance`
- `POST /admin/maintenance/cache/application-clear`
- `POST /admin/maintenance/cache/views-clear`
- `GET /admin/users`
- `GET /admin/users/create`
- `GET /admin/users/{user}/edit`
- `GET /admin/roles`
- `GET /admin/roles/create`
- `GET /admin/roles/{role}/edit`
- `GET /admin/permissions`
- `GET /admin/settings`
- `GET /admin/audit`
- `GET /admin/health`
- `GET /admin/pages`
- `GET /admin/pages/create`
- `GET /admin/pages/{page}/edit`

O prefixo pode evoluir por configuracao do core sem alterar a organizacao do admin.

## Protecao atual de acesso

O admin depende de autenticacao e autorizacao reais:

- guest e redirecionado para o login administrativo
- usuario autenticado sem `access_admin` recebe acesso negado
- cada area do painel exige permissao propria
- acoes de manutencao exigem permissao adicional de execucao

Antes da instalacao ser concluida, o admin nao abre. O acesso administrativo redireciona para o instalador web do produto.

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

## Base de usuarios, papeis e permissoes

O core agora possui modelagem propria para:

- `users`
- `roles`
- `permissions`
- `role_user`
- `permission_role`

Essa base foi mantida propositalmente enxuta:

- sem equipes
- sem organizacoes
- sem multitenancy
- sem UX complexa de IAM

Ela existe para governar o painel administrativo do core e para preparar permissoes futuras registradas por plugins.

## Seeder local e de testes

O core sincroniza permissoes e o papel administrativo base por seeder.

Em `local` e `testing`, o projeto pode criar um administrador inicial com:

- email padrao `admin@example.test`
- senha padrao `admin12345`

Esse comportamento e controlado por configuracao e nao deve ser tratado como fluxo de producao.

## Governanca administrativa entregue nesta etapa

### Users

- listagem de usuarios
- criacao de usuario
- edicao de usuario
- atribuicao de cargos ao usuario quando o ator possui permissao para isso

### Roles

- listagem de cargos
- criacao de cargo
- edicao de cargo
- atribuicao de permissoes ao cargo quando o ator possui permissao para isso

### Permissions

- listagem de permissoes registradas
- exibicao de origem e escopo explicitos para permissao do core e de plugins
- leitura pronta para crescer com permissoes futuras registradas por plugins

### Plugin Pages

- listagem administrativa minima de paginas do plugin oficial
- criacao e edicao de paginas simples
- mudanca de status entre `draft` e `published`
- protecao por permissoes reais do plugin no catalogo central do core

### Settings

- leitura dos settings globais do grupo `general`
- edicao dos settings globais por usuarios com permissao apropriada
- persistencia centralizada de nome do site, tagline, email base, timezone, locale, footer e scripts globais opcionais
- base pronta para expansao futura de settings por plugin e tema sem misturar tudo no core agora

### Themes

- listagem administrativa minima dos temas descobertos
- leitura de discovery, lifecycle e estrutura basica de views/assets
- selecao do tema ativo da instancia com auditoria
- fallback seguro para views do core quando o tema nao possui template correspondente

### Audit Logs

- listagem server-rendered do historico de acoes sensiveis do core
- filtros simples por acao, usuario e periodo
- leitura de ator, alvo, resumo, contexto tecnico basico e timestamp
- base pronta para receber eventos auditaveis futuros de plugins e temas

### System Health

- execucao agregada de health checks basicos do core
- classificacao simples por `ok`, `warning` e `error`
- leitura pronta para checks futuros de plugins sem exigir refactor grande
- foco em utilidade operacional, sem virar monitoramento enterprise

## Regras de seguranca relevantes

- usuario sem permissao adequada nao entra nas areas de governanca
- quem nao possui `manage_roles` nao consegue alterar cargos via UI nem por submissao direta
- quem nao possui `manage_permissions` nao consegue atribuir permissoes a cargos
- quem nao possui `manage_settings` nao consegue alterar configuracoes globais do core
- quem nao possui `view_audit_logs` nao consegue consultar a trilha administrativa do sistema
- quem nao possui `view_system_health` nao consegue consultar a area de diagnostico do sistema
- quem nao possui `manage_extensions` nao consegue sincronizar nem alterar o estado operacional das extensoes
- quem nao possui `view_themes` nao consegue consultar a area de temas
- quem nao possui `manage_themes` nao consegue trocar o tema ativo
- apenas super administrador pode atribuir o cargo administrativo principal do core
- o sistema impede a remocao do ultimo super administrador
- o sistema impede autoescalonamento indevido quando um operador sem privilegio maximo tenta alterar os proprios cargos

## O que cada area mostra

### Dashboard

- contadores de extensoes registradas
- resumo de discovery
- resumo de estado operacional
- panorama do ultimo bootstrap de plugins
- atividade recente do registro
- paineis simples publicados por plugins elegiveis quando o usuario possui a permissao exigida

### Extensions

- leitura do registro persistido de plugins e temas
- sincronizacao manual entre discovery do filesystem e banco
- install logico no lifecycle administrativo sem tocar no filesystem
- remove logico no lifecycle administrativo sem apagar arquivos
- enable de extensoes elegiveis e disable de extensoes habilitadas
- visualizacao de migrations locais de plugin e contagem de pendencias
- execucao administrativa de migrations pendentes para plugins elegiveis
- avaliacao operacional previa antes de cada acao sensivel
- uso das mesmas regras centrais de validade, compatibilidade e estado operacional
- auditoria administrativa das acoes de sync, install, remove, enable, disable e migrations
- leitura preferencial do manifesto normalizado persistido, sem depender do JSON bruto como contrato principal
- resumo de health do ecossistema de extensoes
- indicador por extensao com severidade `ok`, `warning` ou `error`
- capabilities reconhecidas e custom visiveis por extensao
- permissoes declaradas por plugins sincronizadas de forma central no auth do core quando a extensao esta `valid` e `installed`

- nome
- slug
- tipo
- versao detectada
- vendor quando declarado
- indicacao de extensao critica quando aplicavel
- dependencias declaradas e seu estado operacional direto
- capabilities reconhecidas pelo core
- capabilities custom tratadas como metadado observavel
- dependencias ausentes ou desabilitadas quando bloquearem `enable`
- dependentes ativos quando bloquearem `disable`
- `discovery_status`
- `lifecycle_status`
- `operational_status`
- caminho
- erro ou incompatibilidade quando aplicavel
- warnings de normalizacao do manifesto quando existirem
- status de migrations do plugin quando aplicavel
- acoes operacionais quando o usuario possui permissao para isso
- motivo resumido quando uma acao operacional estiver indisponivel

As acoes de extensao agora tambem respeitam dependencias diretas declaradas em `requires`:

- `install` nao instala dependencias automaticamente e pode exibir warnings quando a extensao depende de algo ausente ou desabilitado
- `enable` e bloqueado se uma dependencia declarada nao existir no registro
- `enable` e bloqueado se a dependencia existir, mas nao estiver habilitada
- `disable` e bloqueado se outra extensao habilitada depender da extensao alvo
- `remove` e bloqueado se outra extensao habilitada depender da extensao alvo
- `run migrations` e bloqueado para plugins fora do criterio `valid + installed`

Separacao atual de estados:

- `discovery_status` diz se a extensao foi lida e validada no filesystem
- `lifecycle_status` diz se ela esta apenas descoberta, instalada para uso administrativo ou removida logicamente do registry
- `operational_status` diz se ela esta apenas descoberta pelo runtime, habilitada ou desabilitada para operacao

Regra importante desta fase:

- `install` e administrativo, nao fisico
- `remove` e administrativo, nao apaga arquivos
- `run migrations` opera apenas sobre o banco e nunca altera o filesystem do plugin
- o sync manual continua atualizando o registro a partir do disco, mas preserva extensoes marcadas como `removed`

Essa protecao ainda e propositalmente simples:

- sem auto-enable
- sem disable em cascata
- sem semver de dependencias
- sem dependencias opcionais
- sem rollback completo de migrations por plugin

Regra atual para migrations de plugin no admin:

- o plugin precisa estar `valid` no discovery
- o plugin precisa estar `installed` no lifecycle administrativo
- o plugin pode estar `disabled`; isso nao bloqueia a execucao nesta fase
- plugins sem diretorio/arquivos de migrations ou com bloqueios operacionais conhecidos nao recebem a acao

O diagnostico de extensoes verifica nesta etapa:

- manifesto invalido ou incompativel
- warnings de manifesto normalizado
- dependencias ausentes
- dependencias desabilitadas
- extensoes criticas desabilitadas
- extensoes habilitadas com inconsistencias operacionais relevantes
- capabilities malformadas ou capabilities inconsistentes com metadados conhecidos

### Plugin Surfaces

- o sidebar do admin pode exibir itens de navegacao publicados por plugins habilitados
- o dashboard pode exibir paineis simples publicados por plugins habilitados
- essas contribuicoes usam contratos publicos do core, nao parsing magico de view
- contribuicoes continuam sujeitas a permissao do usuario atual

Primeiro exemplo oficial nesta camada:

- menu `Pages`
- painel `Pages Library`
- permissao exigida `pages.view_pages`

### Themes

- leitura dos temas conhecidos pelo registry
- indicacao do tema ativo atual
- selecao segura do tema ativo
- visualizacao de `discovery_status`, `lifecycle_status`, views e assets preparados
- warnings operacionais leves quando o tema ainda depende de fallback do core

Nesta etapa:

- `activate theme` nao instala arquivos
- o core apenas registra o slug do tema ativo e resolve views a partir desse tema
- se uma view nao existir no tema, o frontend usa fallback do core

### Maintenance

- limpeza segura de cache de aplicacao
- limpeza segura de views compiladas
- status basico de ambiente e sistema
- resumo do ultimo bootstrap para diagnostico rapido

### Settings

- formulario server-rendered para configuracoes globais do grupo `general`
- leitura centralizada com fallback seguro para configuracoes base do Laravel
- armazenamento separado do core para nao misturar runtime, `.env` e preferencia operacional
- campo de scripts globais apenas armazenado nesta fase, sem injecao automatica pelo core

### Audit

- login e logout administrativo
- criacao e edicao de usuarios do core
- criacao e edicao de cargos, incluindo permissoes
- alteracao de settings globais
- acoes de manutencao suportadas pelo admin
- captura de IP e user agent quando disponiveis

### Permissions Catalog

- permissoes do core continuam com `scope = core`
- permissoes de plugin entram com `scope = plugin:<slug>`
- o slug de autorizacao fica no formato `<plugin-slug>.<permission-slug>`
- a UI administrativa de permissoes passa a mostrar a origem explicita do registro

Exemplo real nesta etapa:

- `pages.view_pages`
- `pages.create_pages`
- `pages.edit_pages`
- `pages.publish_pages`
- `pages.delete_pages`

### System Health

- aplicacao instalada
- conexao com banco
- diretorios criticos gravaveis
- cache disponivel
- APP_KEY configurada
- status agregado das extensoes registradas
- diagnostico agregado do ecossistema de extensoes com top issues
- mensagens uteis sem expor segredos ou detalhes sensiveis desnecessarios

## Como isso prepara o admin futuro

O admin de plugins e temas podera consumir:

- o registro persistido de extensoes
- os estados de discovery e operacao
- o relatorio de bootstrap de providers
- a mesma infraestrutura de roles e permissions ja usada pelo core

Isso permite listar, diagnosticar e operar plugins sem misturar manifestos brutos, filesystem e runtime numa unica tela ou fluxo.

## Escopo futuro do admin base

- dashboard operacional
- usuarios, papeis e permissoes
- plugins
- temas
- configuracoes globais
- logs e auditoria administrativas
- diagnostico de saude do sistema
- midia basica
- logs e auditoria
- manutencao
- ambiente e health checks

## O que ficou de fora de proposito

- plugin manager completo
- upload de plugins ou temas
- instalacao via painel
- marketplace
- logs completos e auditoria completa
- gestao avancada de usuarios e papeis
- 2FA, recovery completo e seguranca enterprise
- modulos de negocio

O admin deve continuar nascendo como centro operacional do sistema, mas sem transformar o core em um modulo de negocio inchado.
