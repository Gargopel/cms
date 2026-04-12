# Plugin System

## Objetivo

O sistema de plugins permitira extender a plataforma sem alterar o core.

## Entregue ate agora

- convencao de manifesto em `plugins/<Diretorio>/plugin.json`
- contrato publico para manifesto de plugin
- discovery leve por varredura de diretorios
- validacao segura de estrutura minima
- validacao de compatibilidade com a versao atual do core
- tratamento isolado para extensoes invalidas e incompativeis
- registro persistido das extensoes descobertas
- servico de sincronizacao entre discovery e banco
- estado operacional simples para preparar enable e disable futuros
- primeira camada de boot condicional de plugins
- acoes administrativas reais de sync manual, enable e disable via admin do core
- acoes administrativas reais de install e remove logicos no registry/admin
- camada central de elegibilidade operacional antes de enable e disable
- manifesto normalizado persistido para reduzir dependencia do `raw_manifest`
- health check especifico do ecossistema de extensoes
- capabilities operacionais observaveis via contrato explicito do core
- camada operacional inicial de migrations por plugin
- primeiro plugin oficial real do ecossistema em `plugins/Pages`

## Estrutura minima do manifesto

Campos obrigatorios normalizados nesta etapa:

- `name`
- `slug`
- `description`
- `version`
- `author`
- `core.min`

Campos opcionais normalizados nesta etapa:

- `core.max`
- `vendor`
- `provider`
- `requires`
- `critical`
- `capabilities`
- `permissions`

Estrutura opcional esperada para migrations nesta etapa:

- `database/migrations/*.php` dentro do diretorio do plugin

O plugin oficial `Pages` usa esse contrato para declarar:

- provider proprio
- capability `admin_pages`
- permissoes administrativas do plugin
- rotas e views registradas pelo provider

## Pontos de extensao publicos nesta etapa

O core agora tambem oferece um conjunto pequeno de pontos de extensao publicos para plugins habilitados:

- itens de navegacao do admin
- paineis simples de dashboard

Esses pontos de extensao nao sao declarados por manifesto nesta fase.
Eles sao registrados pelo provider do plugin usando contratos publicos do core, o que mantem o acoplamento explicito e previsivel.

Regras atuais:

- apenas plugins `valid`, `installed` e `enabled` podem publicar contribuicoes
- temas nao participam dessa camada
- contribuicoes podem exigir permissao especifica
- o admin filtra contribuicoes por permissao do usuario atual
- a view nao contem regra de elegibilidade do plugin

O plugin oficial `Pages` ja consome esses contratos para publicar:

- item de menu `Pages`
- painel simples `Pages Library`

## Raw manifest x manifesto normalizado

O core mantem os dois snapshots:

- `raw_manifest`: copia bruta do JSON lido no disco, preservada para troubleshooting e compatibilidade
- `normalized_manifest`: estrutura validada, com defaults previsiveis e tipos coerentes, usada como contrato principal da aplicacao

Sempre que possivel, servicos operacionais e a UI administrativa devem consumir o manifesto normalizado.
O acesso direto a `raw_manifest` deve ficar restrito a fallback, troubleshooting ou migracao incremental.

Observacoes nesta fase:

- `requires` agora participa de bloqueios operacionais diretos de `enable` e `disable`
- `critical` pode ser usado para bloquear disable de uma extensao marcada como critica para operacao do sistema
- `capabilities` ja pode ser normalizado e persistido, mas ainda esta em fase preparatoria para evolucoes futuras
- `permissions` agora pode ser normalizado e sincronizado no sistema central de autorizacao do core
- manifestos parcialmente invalidos ainda podem gerar snapshot normalizado parcial e warnings uteis para registry e admin

## Permissoes de plugin nesta etapa

Plugins podem declarar permissoes proprias no manifesto por meio do campo `permissions`.

Formato suportado:

```json
{
  "permissions": [
    {
      "slug": "view_reports",
      "name": "View Reports",
      "description": "Read plugin reports."
    }
  ]
}
```

Regras atuais:

- apenas plugins podem declarar `permissions`
- o manifesto deve declarar slugs locais, sem prefixo do plugin
- o core normaliza e expande o slug para o formato global `<plugin-slug>.<permission-slug>`
- a permissao recebe `scope` explicito no banco no formato `plugin:<plugin-slug>`
- permissoes de plugin entram no catalogo central reutilizando o sincronizador de permissoes do core

Critério escolhido nesta fase:

- permissoes de plugin existem no sistema apenas quando o plugin esta `valid` no discovery
- e quando o plugin esta `installed` no lifecycle administrativo
- plugins apenas `discovered` ainda nao publicam permissoes
- plugins `enabled` continuam usando as mesmas permissoes, sem diferenca adicional nesta fase
- plugins `removed`, invalidos ou incompativeis perdem suas permissoes sincronizadas no banco

Isso mantem o catalogo de auth coerente com o lifecycle administrativo do ecossistema, sem exigir boot do plugin nem auto-registro magico.

## Migrations de plugin nesta etapa

O core agora possui uma camada operacional minima para localizar, diagnosticar e executar migrations de plugins.

Regras atuais:

- as migrations sao lidas apenas de `plugins/<Plugin>/database/migrations`
- o core reutiliza a infraestrutura nativa de migrations do Laravel
- a execucao administrativa so e permitida para plugins:
  - `type = plugin`
  - `discovery_status = valid`
  - `lifecycle_status = installed`
- o plugin nao precisa estar `enabled` para executar migrations nesta fase
- plugins `invalid`, `incompatible`, `removed` ou sem migrations declaradas nao entram nesse fluxo

Justificativa:

- `installed` representa que o plugin ja foi aceito no lifecycle administrativo do core
- permitir migrations antes do `enabled` remove dependencia de CLI para preparar persistencia de plugins oficiais
- isso preserva a separacao entre lifecycle administrativo e operacao runtime

O admin do core ja consegue:

- exibir se o plugin possui diretorio de migrations
- exibir quantos arquivos foram encontrados
- exibir quantas migrations estao pendentes
- executar apenas as pendencias de forma segura e auditada

Limites desta fase:

- sem rollback completo
- sem update manager
- sem migrations remotas
- sem auto-run em cascata
- sem mutacao do filesystem do plugin

Exemplo real nesta etapa:

- `pages.view_pages`
- `pages.create_pages`
- `pages.edit_pages`
- `pages.publish_pages`
- `pages.delete_pages`

## Capabilities nesta etapa

Nesta fase, `capabilities` deixou de ser apenas um array cru e passou a seguir um contrato normalizado e observavel.

O core reconhece inicialmente:

- `admin_pages`
- `widgets`
- `commands`
- `migrations`
- `providers`
- `assets`
- `integrations`
- `health_checks`
- `api_routes`

Regras atuais:

- entries validas sao normalizadas em colecao estavel e sem duplicidades
- entries desconhecidas nao quebram o sistema e sao tratadas como capabilities custom
- entries malformadas geram warning de manifesto
- o admin expoe capabilities reconhecidas e custom por extensao
- o health de extensoes pode sinalizar warnings de capabilities ou inconsistencias leves

Ainda nao existe:

- execucao automatica por capability
- hook engine completo
- event bus de plugins
- marketplace ou resolucao dinamica por capability

## Dois conceitos separados

O core agora distingue explicitamente:

- discovery no filesystem
- lifecycle administrativo no registry
- estado operacional persistido

### Discovery status

Valores atuais:

- `valid`
- `invalid`
- `incompatible`

### Operational status

Valores atuais:

- `discovered`
- `installed`
- `enabled`
- `disabled`

Essa separacao evita confundir "o plugin existe no disco" com "o plugin esta habilitado para operar".

### Lifecycle administrativo

Valores atuais:

- `discovered`
- `installed`
- `removed`

Nesta etapa:

- `install` significa promover uma extensao descoberta para uso administrativo no registry
- `remove` significa desregistrar logicamente a extensao no lifecycle administrativo
- nenhuma dessas acoes instala, move, baixa ou apaga arquivos do filesystem
- o sync continua atualizando os metadados do que foi descoberto no disco, mas preserva o estado `removed`

## Operacao administrativa atual

O admin do core agora pode operar extensoes sem virar plugin manager completo:

- sincronizar manualmente o discovery com o registro persistido
- instalar extensoes no lifecycle administrativo
- remover extensoes do lifecycle administrativo
- habilitar extensoes elegiveis
- desabilitar extensoes habilitadas

Essas acoes continuam separadas do boot condicional.
Elas apenas governam o registro persistido e deixam o runtime decidir o que bootar no ciclo seguinte.

## Avaliacao operacional previa

Antes de executar `enable` ou `disable`, o core agora avalia a extensao com uma camada central de elegibilidade operacional.

Essa avaliacao responde:

- se a acao e permitida
- quais bloqueios existem
- quais warnings operacionais existem

Bloqueios atuais incluem:

- tentativa redundante de instalar o que ja esta instalado
- tentativa redundante de remover o que ja esta removido
- registro incompleto para operacao segura
- `discovery_status` diferente de `valid` no `enable`
- `lifecycle_status` diferente de `installed` no `enable`
- tentativa redundante de habilitar o que ja esta habilitado
- tentativa redundante de desabilitar o que ja esta desabilitado
- extensao marcada como critica para operacao do sistema
- dependencia obrigatoria ausente no registro
- dependencia obrigatoria presente, mas desabilitada
- tentativa de desabilitar extensao que possui dependentes ativos habilitados
- tentativa de remover extensao habilitada
- tentativa de remover extensao critica
- tentativa de remover extensao que possui dependentes ativos habilitados

Warnings atuais incluem:

- install administrativo de extensao com dependencias ausentes ou desabilitadas, sem resolucao automatica
- tema habilitado no registro nao participa do pipeline de providers
- plugin sem provider declarado pode ser habilitado no registro, mas nao bootara provider
- extensao com dependencias declaradas ainda nao recebe resolucao automatica profunda, versionamento ou cascata
- slug de dependencia ambiguo pode gerar warning de consistencia, sem virar package manager nesta fase

Essa estrutura foi desenhada para aceitar bloqueios futuros por dependencia sem exigir refactor grande no formato de retorno.

## Health de extensoes nesta etapa

O core agora executa um diagnostico especifico do ecossistema de extensoes reutilizando:

- `normalized_manifest`
- `ExtensionDependencyService`
- estado persistido do registry
- metadados operacionais ja existentes

Problemas tratados como `warning` nesta etapa:

- extensao incompatível com o core atual
- warnings de manifesto normalizado
- dependencia ausente ou desabilitada em extensao ainda nao habilitada
- inconsistencias leves de dependencia, como ambiguidade de slug
- capabilities custom ou warnings de capability relevantes para observabilidade

Problemas tratados como `error` nesta etapa:

- extensao com manifesto invalido
- extensao habilitada com dependencia ausente
- extensao habilitada com dependencia desabilitada
- extensao critica fora do estado habilitado
- extensao habilitada com inconsistencia operacional relevante
- capability `providers` declarada em tema ou plugin sem provider coerente

Ainda nao existe:

- auto-fix
- install/remove/update
- resolucao profunda de arvore
- versoes minimas de dependencia
- dependencias opcionais
- correcoes automaticas via health

## Condicoes atuais de boot

Um plugin so pode ser considerado para boot quando:

- `type = plugin`
- `discovery_status = valid`
- `operational_status = enabled`
- `provider` foi declarado no manifesto

Mesmo nesses casos, o provider ainda passa por validacoes adicionais:

- a classe deve existir
- a classe deve extender `Illuminate\Support\ServiceProvider`
- o registro do provider deve ocorrer sem excecao fatal nao tratada

## Regras de seguranca atuais

- plugin invalido ou incompativel continua aparecendo no registro
- plugin invalido ou incompativel nao pode ser instalado no lifecycle administrativo desta fase
- plugin invalido ou incompativel nao pode ser habilitado pelo servico operacional
- plugin invalido ou incompativel nao publica permissoes no catalogo central
- acoes administrativas de enable e disable reutilizam os servicos centrais de estado operacional
- acoes administrativas de install e remove reutilizam a mesma camada central de elegibilidade
- acoes administrativas passam primeiro por uma camada de pre-validacao operacional
- plugin sem provider declarado nao e bootado
- plugin com provider ausente, invalido ou com falha de registro nao derruba o sistema de forma cega
- se uma extensao previamente habilitada passar a invalida ou incompativel, o registro e rebaixado para `disabled` por seguranca
- temas podem ter estado operacional alterado no registro, mas nunca entram no pipeline de provider boot
- extensoes marcadas como `critical` no manifesto persistido nao podem ser desabilitadas nesta fase
- extensoes marcadas como `critical` no manifesto persistido nao podem ser removidas nesta fase
- extensoes com dependencias obrigatorias nao satisfeitas nao podem ser habilitadas
- extensoes com dependentes ativos habilitados nao podem ser desabilitadas
- extensoes com dependentes ativos habilitados nao podem ser removidas
- plugins removidos do lifecycle administrativo deixam de publicar permissoes no auth central

## Observabilidade inicial

O bootstrapper de plugins produz um relatorio simples com:

- plugins considerados para boot
- plugins registrados com sucesso
- plugins ignorados
- plugins com falha de provider
- erros sistemicos, como indisponibilidade do registro

Ao registrar providers dinamicamente, o core tambem sincroniza router e `UrlGenerator` para manter rotas nomeadas e redirects consistentes para plugins oficiais habilitados.

Esse formato foi desenhado para reaproveitamento futuro por admin e logs.

## Integracao atual com o admin do core

O primeiro admin operacional do core ja consome dois artefatos centrais deste sistema:

- o registro persistido de extensoes
- o ultimo relatorio de bootstrap armazenado pelo core

Com isso, a plataforma ja consegue expor visualmente:

- plugins e temas descobertos
- estados de discovery e operacao
- erros de manifesto ou incompatibilidade
- panorama do ultimo boot de providers
- acoes administrativas reais de sync, enable e disable
- origem explicita de permissoes do core e de plugins instalados
- e a primeira area administrativa real de um plugin oficial em `/admin/pages`

As operacoes sensiveis dessa area sao auditadas pelo core.
Tentativas bloqueadas tambem podem ser auditadas para manter rastreabilidade operacional.

Essa integracao ainda nao representa um plugin manager completo. Ela apenas torna o estado do ecossistema observavel de forma segura e incremental.

## Ainda nao implementado

- plugin manager completo
- ciclo de vida rico de install, update e remove
- instalacao fisica de arquivos
- remocao fisica de arquivos
- update remoto
- resolucao profunda de dependencias entre plugins
- versionamento de dependencias
- dependencias opcionais
- operacoes em cascata
- hooks e filters avancados
- instalacao pelo usuario final
- menus, policies, settings ou hooks auto-registrados a partir das permissoes do plugin

## Diretrizes para as proximas etapas

- manter o boot estritamente condicionado ao estado persistido
- usar o relatorio de bootstrap como base para observabilidade
- evoluir para lifecycle mais rico sem romper a separacao entre discovery, registro e runtime
