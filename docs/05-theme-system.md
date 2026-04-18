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
- primeira camada real de slots/regioes de layout no frontend inicial

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

## Slots de tema nesta etapa

O sistema agora possui uma camada pequena e explicita de regioes de layout para o frontend inicial.

Slots suportados pelo core nesta fase:

- `hero`
- `sidebar`
- `footer_cta`

Contrato publico:

- `App\Core\Contracts\Extensions\Themes\ThemeSlotRegistry`
- `App\Core\Extensions\Hooks\ThemeSlotBlock`
- `App\Core\Themes\ThemeSlotRenderer`

Regras atuais:

- o core resolve o slot e aplica o fallback de renderizacao
- o tema continua responsavel pela apresentacao final
- plugins so podem contribuir quando estiverem `valid`, `installed` e `enabled`
- contribuicoes sao blocos explicitos, com `slot`, `view`, `priority`, `plugin_slug` e override opcional de view pelo tema
- um bloco tambem pode resolver dados no backend antes da renderizacao, sem empurrar query para Blade
- nao existe editor visual, drag-and-drop ou nesting complexo

Como um tema pode suportar um slot explicitamente:

- `themes/<Theme>/views/slots/hero.blade.php`
- `themes/<Theme>/views/slots/sidebar.blade.php`
- `themes/<Theme>/views/slots/footer_cta.blade.php`

Quando a view do slot nao existe no tema ativo:

- o core usa o fallback `resources/views/front/slots/default.blade.php`

Quando o tema quiser sobrescrever a view de um bloco especifico:

- o bloco pode expor um `themeView` explicito
- o tema cria a view correspondente em `themes/<Theme>/views/plugins/<plugin>/slots/<block>.blade.php`
- se essa view nao existir, o core volta para a view original do plugin

Quando nao ha contribuicoes registradas para o slot:

- a renderizacao retorna vazia
- o frontend continua funcionando sem placeholder artificial

## Integracao oficial atual

O frontend inicial agora possui dois exemplos oficiais mais uteis de blocos publicados por plugins:

- `Blog` publica um bloco rico de posts recentes no slot `sidebar`
- `Forms` publica um CTA simples e reutilizavel no slot `footer_cta`

O plugin `Blog` tambem preserva o bloco simples inicial de `footer_cta` como exemplo pequeno de contribuicao estavel.

Objetivo dessas contribuicoes:

- provar a cadeia completa de hook frontend com dados resolvidos no backend
- manter o exemplo pequeno e realmente util para sites institucionais
- preparar terreno para widgets mais ricos sem virar page builder

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
- editor visual de regioes
- page builder
- sistema ilimitado de blocos no frontend

## Direcao futura

O sistema de temas deve operar sobre contratos publicos estaveis e consumir o mesmo modelo de registro persistido usado para plugins, sem misturar apresentacao com discovery bruto de filesystem.

Proximas evolucoes naturais, sem implementacao nesta etapa:

- settings visuais por tema
- areas configuraveis e presets
- resolucao mais rica de templates publicos
- assets com pipeline controlado
- integracoes futuras com widgets, menus e contracts publicos do core
- superficies de slot mais ricas, desde que continuem pequenas, explicitas e versionaveis
