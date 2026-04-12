# Estrutura Inicial do Core

## Objetivo

Registrar de forma curta como a fundacao atual organiza responsabilidades.

## Pastas principais

- `app/Core/Auth`: autenticacao, autorizacao, modelos de role/permission e bootstrap de seguranca do core
- `app/Core/Foundation`: bootstrap e servicos centrais do core
- `app/Core/Admin`: admin base do core, middleware, controllers e servicos operacionais
- `app/Core/Audit`: modelagem e servico central de logs e auditoria administrativa
- `app/Core/Health`: contratos, checks e agregacao de diagnostico do sistema
- `app/Core/Install`: estado de instalacao, setup guiado, middlewares e servicos do instalador web
- `app/Core/Settings`: catalogo, modelagem, persistencia e leitura centralizada de settings globais do core
- `app/Core/Themes`: tema ativo, resolucao de views do frontend e contrato inicial do theme manager
- `app/Core/Extensions/Capabilities`: catalogo, normalizacao observavel e consulta de capabilities declaradas
- `app/Core/Extensions/Dependencies`: leitura de dependencias declaradas e dependentes reversos
- `app/Core/Extensions/Hooks`: DTOs e registro central dos primeiros pontos de extensao publicos do core
- `app/Core/Extensions/Migrations`: diagnostico e execucao segura de migrations de plugins elegiveis
- `app/Core/Extensions/Permissions`: contrato, normalizacao derivada do manifesto e sincronizacao de permissoes publicadas por plugins
- `app/Core/Extensions/Health`: diagnostico especifico do ecossistema de extensoes
- `app/Core/Extensions/Discovery`: discovery por manifesto
- `app/Core/Extensions/Validation`: validacao, normalizacao de manifesto e compatibilidade
- `app/Core/Extensions/Registry`: sincronizacao e estado persistido das extensoes
- `app/Core/Extensions/Boot`: resolucao e registro condicional de providers de plugins
- `app/Core/Extensions/Models`: modelagem persistida do registro
- `app/Core/Extensions/Enums`: tipos e estados do sistema de extensoes
- `app/Core/Contracts`: contratos publicos estaveis do core
- `app/Support`: utilitarios leves e compartilhados
- `plugins`: raiz reservada para plugins
- `themes`: raiz reservada para temas
- `docs`: documentacao oficial do produto

Referencia oficial atual do ecossistema:

- `plugins/Pages`: primeiro plugin oficial real, usado como exemplo de manifesto, permissoes, hooks administrativos, rotas proprias, persistencia e renderizacao publica com fallback por tema

## Responsabilidades do core nesta fase

- hospedar a aplicacao Laravel 11
- manter a fundacao estrutural do produto
- expor utilitarios basicos sem criar modulos prematuros
- descobrir plugins e temas por manifesto sem bootar funcionalidades de negocio
- validar compatibilidade minima com a versao atual do core
- normalizar manifestos em uma estrutura explicita e previsivel antes do consumo operacional
- expor capabilities declaradas de forma explicita, consultavel e observavel
- sincronizar permissoes declaradas por plugins validos e instalados com o auth central do core
- localizar migrations de plugins validos e instalados sem depender de CLI no fluxo administrativo basico
- detectar pendencias de migration por plugin elegivel
- executar migrations pendentes de plugin de forma segura e auditavel pelo admin
- aceitar contribuicoes administrativas de plugins validos, instalados e habilitados em superficies controladas
- persistir o registro das extensoes sincronizadas
- manter o estado operacional separado do discovery bruto
- manter o lifecycle administrativo separado do discovery e do estado operacional
- registrar providers apenas para plugins habilitados, validos e seguros
- expor um admin operacional inicial para observabilidade e manutencao basica
- permitir operacao administrativa minima das extensoes sem quebrar a separacao entre discovery, registro e runtime
- permitir install e remove logicos no registry/admin sem mutar o filesystem
- avaliar previamente a elegibilidade operacional das extensoes antes de alterar estado
- bloquear operacoes inconsistentes com base em dependencias diretas declaradas entre extensoes
- diagnosticar a saude do ecossistema de extensoes sem mutar estado
- governar o acesso administrativo por usuario autenticado, papel e permissao
- persistir e aplicar settings globais minimos do core com leitura centralizada
- persistir o tema ativo e resolver fallback seguro de views publicas
- registrar acoes administrativas sensiveis com contexto basico para consulta operacional
- executar health checks basicos com retorno estruturado para diagnostico administrativo
- permitir instalacao web guiada antes do primeiro acesso ao admin
- bloquear reinstalacao depois da conclusao do setup

## Recortes importantes do admin atual

- `app/Core/Auth/Http/Middleware`: autenticacao administrativa e redirecionamento para login
- `app/Core/Auth/Http/Controllers`: login e logout do admin
- `app/Core/Auth/Models`: role e permission do core
- `app/Core/Auth/Support`: sincronizacao inicial de permissions e regras de governanca de seguranca
- `app/Core/Audit`: log de eventos auditaveis, ator, alvo, metadados simples e contexto da requisicao
- `app/Core/Extensions/Registry`: sincronizacao manual e mudanca segura de estado operacional reutilizadas pelo admin
- `app/Core/Extensions/Registry`: sincronizacao manual, lifecycle administrativo e mudanca segura de estado operacional reutilizadas pelo admin
- `app/Core/Extensions/Dependencies`: leitura centralizada de dependencias declaradas, faltantes, desabilitadas e dependentes ativos
- `app/Core/Extensions/Capabilities`: service para capabilities reconhecidas, custom e consultas por capability
- `app/Core/Extensions/Health`: relatorio agregado por extensao e visao sistemica do ecossistema
- `app/Core/Extensions/Hooks`: registro central de menu admin e paineis simples de dashboard vindos de plugins elegiveis
- `app/Core/Extensions/Migrations`: leitura de diretorio `database/migrations`, deteccao de pendencias e execucao segura via admin
- `app/Core/Extensions/Permissions`: definicoes de permissao por plugin e sincronizacao incremental com a tabela `permissions`
- `app/Core/Extensions/Validation`: normalizador e validador usados pelo discovery antes do registry
- `app/Core/Extensions/Operations`: elegibilidade operacional, bloqueios e warnings antes de enable e disable
- `app/Core/Extensions/Operations`: elegibilidade operacional, bloqueios e warnings antes de install, remove, enable e disable
- `app/Core/Health`: checks basicos como instalacao, banco, cache, app key, escrita e extensoes
- `app/Core/Settings`: servico central de leitura/escrita e catalogo do grupo `general`
- `app/Core/Themes`: manager do tema ativo, elegibilidade de ativacao e resolvedor de views com fallback
- `app/Core/Admin/Http/Controllers`: dashboard, extensoes, manutencao, usuarios, cargos, permissoes, settings, auditoria e health
- `app/Core/Admin/Http/Controllers`: dashboard, extensoes, temas, manutencao, usuarios, cargos, permissoes, settings, auditoria e health
- `app/Core/Admin/Http/Requests`: validacao das operacoes de governanca e settings no admin
- `app/Core/Admin/Support`: agregacao de dados do admin sobre extensoes, bootstrap e ambiente
- `app/Core/Admin/Support`: consumo das contribuicoes administrativas publicadas por plugins elegiveis
- `resources/views/components/admin`: componentes visuais reutilizaveis do design system do admin
- `resources/views/components/layouts`: layout base server-rendered do painel
- `resources/views/admin`: paginas do dashboard, extensoes, manutencao, usuarios, cargos, permissoes, settings, auditoria e health
- `resources/views/front`: fallback de views publicas do core quando o tema ativo nao fornece template
- `resources/views/admin/auth`: login administrativo
- `routes/admin.php`: rotas administrativas do core

Recorte importante fora do core, mas ja integrante da arquitetura:

- `plugins/Pages/resources/views/admin`: telas Blade do plugin oficial Pages
- `plugins/Pages/resources/views/front`: fallback publico do plugin oficial Pages
- `plugins/Pages/routes`: rotas administrativas e publicas do plugin oficial
- `plugins/Pages/database/migrations`: persistencia propria do plugin oficial
- `plugins/<Plugin>/database/migrations`: convencao atual para migrations proprias de plugins oficiais ou de terceiros

## Recortes importantes do instalador atual

- `app/Core/Install/InstallationState`: leitura e escrita do estado explicito de instalado
- `app/Core/Install/Environment`: manipulacao segura do arquivo `.env`
- `app/Core/Install/Support`: checagem de requisitos e conexao de banco
- `app/Core/Install/Setup`: execucao do setup base do produto
- `app/Core/Install/Http/Middleware`: guarda antes e depois da instalacao
- `app/Core/Install/Http/Controllers`: wizard do instalador
- `resources/views/install`: telas do fluxo guiado

## O que ficou de fora de proposito

- plugin manager real
- theme manager real
- hooks e filters avancados
- resolucao de dependencias
- funcionalidades de negocio

## Nota de fronteira

Mesmo com o admin inicial entregue, o core ainda nao possui:

- area completa de plugins
- area completa de temas
- gestao avancada de usuarios, papeis e permissoes
- catalogo mais rico de settings globais e settings de plugins/temas
- retencao, exportacao e observabilidade mais avancadas de auditoria
- health checks mais ricos, com possivel extensao por plugins e diagnosticos adicionais
- install, remove e update completos de extensoes
- rollback completo ou update manager de migrations por plugin
- install, remove e update fisicos de extensoes
- resolucao automatica de dependencias e restricoes operacionais mais ricas entre extensoes

Essa restricao ajuda a manter o core minimo e a evolucao futura previsivel.
