# Hooks e Extensoes

## Visao

Hooks, actions, filters, events e listeners continuam fazendo parte da estrategia de extensibilidade da plataforma.

Nesta fase, o projeto ainda nao implementa uma engine ampla de hooks. Em vez disso, o core ganhou a primeira camada real de pontos de extensao publicos, pequenos e controlados.

## Base consolidada antes dos hooks amplos

Antes de abrir um sistema mais rico de extensoes, o core ja consegue responder com clareza:

- o que existe no filesystem
- o que foi sincronizado no banco
- o que esta `valid`, `installed` e `enabled`
- o que esta apto ao boot de provider
- o que publica permissoes no RBAC central
- o que pode contribuir com superficies administrativas seguras

Essa base reduz o risco de criar hooks sobre extensoes em estados ambiguos.

## Pontos de extensao entregues nesta etapa

Superficies suportadas agora:

- itens de navegacao do admin
- paineis simples de dashboard
- slots simples de tema no frontend inicial

Contratos publicos expostos pelo core:

- `App\Core\Contracts\Extensions\Admin\AdminNavigationRegistry`
- `App\Core\Contracts\Extensions\Admin\AdminDashboardPanelRegistry`
- `App\Core\Contracts\Extensions\Themes\ThemeSlotRegistry`

DTOs usados para contribuicoes:

- `App\Core\Extensions\Hooks\AdminNavigationItem`
- `App\Core\Extensions\Hooks\AdminDashboardPanel`
- `App\Core\Extensions\Hooks\ThemeSlotBlock`

Registro central:

- `App\Core\Extensions\Hooks\ExtensionHookRegistry`

Servico de consumo no admin:

- `App\Core\Admin\Support\AdminExtensionPointService`

Consumidores oficiais desta camada:

- plugin `Pages`, que publica um item de menu administrativo e um painel simples de dashboard
- plugin `Blog`, que publica um item de menu administrativo, um painel simples de dashboard e um bloco inicial de `footer_cta` para o frontend
- plugin `Forms`, que publica um item de menu administrativo e um painel simples de dashboard para consulta operacional de formularios

## Regras de seguranca e elegibilidade

Nesta fase, uma contribuicao so entra no registro central quando vier de plugin:

- `type = plugin`
- `discovery_status = valid`
- `lifecycle_status = installed`
- `operational_status = enabled`

Regras adicionais:

- a contribuicao precisa carregar o `plugin_slug` de origem
- o registro central nao executa auto-discovery magico de menus
- o registro central nao executa auto-discovery magico de blocos de frontend
- a view nao decide elegibilidade de plugin
- a view nao decide autorizacao
- se a contribuicao declarar permissao exigida, o admin so a mostra para usuarios que possam acessa-la
- slots de frontend nao abrem uma engine visual nova; eles apenas registram blocos server-rendered em regioes pequenas e conhecidas

## O que isso habilita no futuro

Com essa base, o sistema agora ja consegue evoluir para:

- primeiras paginas administrativas oficiais de plugins
- paineis operacionais simples no dashboard
- grupos controlados de surfaces futuras, como settings sections
- regioes publicas pequenas e previsiveis no tema ativo
- integracao de plugins oficiais sem acoplamento improvisado ao layout do admin

Os plugins `Pages` e `Blog` ja demonstram essa evolucao nesta etapa sem introduzir framework magico de hooks.

No frontend, o `Blog` e o `Forms` agora demonstram a mesma ideia sem criar page builder:

- o `Blog` publica um bloco rico de posts recentes em `sidebar`
- o `Blog` preserva um CTA simples em `footer_cta`
- o `Forms` publica um CTA simples e reutilizavel em `footer_cta`
- o renderer resolve dados do bloco no backend quando necessario
- o tema continua podendo sobrescrever o wrapper do slot
- o tema tambem pode sobrescrever a view do bloco quando o contrato expuser um `themeView`
- o core cai para fallback quando o tema nao declarar o slot ou o bloco explicitamente

## O que ainda nao existe

- framework amplo de hooks
- actions e filters genericos
- event bus de plugins
- registro de listeners de plugin
- widgets drag-and-drop
- automacoes baseadas em hooks
- menus automaticos a partir de manifesto
- surfaces dinamicas arbitrarias
- page builder
- layout builder visual

## Motivo

Criar um sistema gigante de hooks cedo demais tende a gerar contratos ruins e acoplamento desnecessario.

O caminho escolhido aqui foi:

1. consolidar discovery, registry, lifecycle, permissao e boot seguro
2. abrir um conjunto pequeno de pontos de extensao realmente util
3. observar a ergonomia desses contratos antes de ampliar para hooks mais genericos

## Diretrizes futuras

- contratos publicos e versionados
- nomes previsiveis
- baixo custo quando nao utilizados
- compatibilidade com carregamento condicional de plugins habilitados
- expansao por superficies controladas antes de qualquer engine ampla de hooks
