# Theme System

## Objetivo

Temas devem controlar apresentacao publica e configuracao visual sem carregar regra de negocio critica.

## Entregue ate agora

- convencao de manifesto em `themes/<Diretorio>/theme.json`
- contrato publico para manifesto de tema
- discovery leve por varredura de diretorios
- validacao de estrutura minima
- validacao de compatibilidade minima com o core
- tratamento isolado para manifestos invalidos e incompativeis
- registro persistido dos temas descobertos
- sincronizacao entre discovery e banco
- lifecycle administrativo compartilhado com o registry de extensoes
- tema ativo persistido no core
- pagina administrativa minima para listar temas e selecionar o tema ativo
- integracao do frontend com views do tema ativo e fallback para views do core
- preparacao inicial para `views/` e `assets/` dentro de cada tema

## Estrutura minima do manifesto

Campos obrigatorios:

- `name`
- `slug`
- `description`
- `version`
- `author`
- `core.min`

Campos opcionais:

- `core.max`
- `vendor`
- `requires`
- `critical`
- `capabilities`

## Dois conceitos separados

Assim como plugins, temas agora distinguem:

- discovery no filesystem
- lifecycle administrativo no registry
- estado operacional persistido

### Discovery status

- `valid`
- `invalid`
- `incompatible`

### Operational status

- `discovered`
- `installed`
- `enabled`
- `disabled`

### Lifecycle administrativo

- `discovered`
- `installed`
- `removed`

## Registro persistido

Cada tema sincronizado grava, no minimo:

- tipo
- slug
- nome
- versao detectada
- caminho
- caminho do manifesto
- status de discovery
- lifecycle administrativo
- estado operacional
- erros de discovery
- manifesto bruto
- manifesto normalizado

## Tema ativo nesta etapa

O core agora persiste um tema ativo em `core_settings`, grupo `themes`, chave `active_theme_slug`.

Regras atuais:

- apenas temas `valid` podem ser ativados
- temas com registro incompleto sao bloqueados
- a ativacao pode instalar o tema no lifecycle administrativo quando ele ainda estiver apenas `discovered`
- a ativacao nao toca o filesystem
- a ativacao nao executa provider porque tema nao entra no pipeline de plugins

O tema ativo e usado como preferencia de apresentacao publica do frontend.

## Integracao com o frontend

O core usa Blade de forma direta, sem engine nova:

- o resolvedor do tema ativo registra um namespace de views do tema
- a rota publica inicial tenta carregar a view correspondente do tema ativo
- quando a view nao existe no tema, o sistema usa fallback para a view padrao do core

Estrutura minima suportada nesta etapa:

- `themes/<ThemeName>/theme.json`
- `themes/<ThemeName>/views/`
- `themes/<ThemeName>/assets/`

O suporte a `assets/` ainda e apenas preparatorio:

- sem pipeline de build
- sem registro automatico de bundles
- sem editor visual

## Regras de seguranca atuais

- tema invalido ou incompativel continua visivel no registro
- tema invalido ou incompativel nao pode ser selecionado como ativo
- tema nao e tratado como provider nem bootado como plugin
- se o tema ativo perder elegibilidade, o frontend cai para fallback do core
- troca de tema ativo e auditada no admin

## Ainda nao implementado

- editor visual de tema
- customizacao avancada por tema
- pipeline completo de assets
- marketplace
- upload, install fisico ou update remoto de temas
- sistema complexo de templating
- configuracoes visuais persistidas por tema

## Direcao futura

O sistema de temas deve operar sobre contratos publicos estaveis e consumir o mesmo modelo de registro persistido usado para plugins, sem misturar apresentacao com discovery bruto de filesystem.

Proximas evolucoes naturais, sem implementacao nesta etapa:

- settings visuais por tema
- areas configuraveis e presets
- resolucao mais rica de templates publicos
- assets com pipeline controlado
- integracoes futuras com widgets, menus e contracts publicos do core
