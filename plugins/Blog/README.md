# Blog Plugin

Plugin oficial editorial do ecossistema, criado como segunda referencia real de como plugins devem se integrar ao core.

## O que entrega nesta fase

- manifesto completo e compativel com o sistema de extensoes
- permissoes proprias sincronizadas no RBAC central
- settings persistidos do plugin declarados no manifesto e geridos pelo core
- migrations proprias em `database/migrations`
- area administrativa em Blade para listar, criar, editar e remover posts
- area administrativa em Blade para listar, criar e editar categorias editoriais
- area administrativa em Blade para listar, criar e editar tags editoriais
- status editoriais minimos `draft` e `published`
- rota publica para listagem de posts publicados
- rota publica para visualizar post publicado por slug
- rota publica para listagem de posts publicados por categoria
- rota publica para listagem de posts publicados por tag
- integracao com tema ativo por override opcional de views
- hooks reais para menu admin e painel simples de dashboard
- contribuicao simples para slot de tema `footer_cta` na home publica

## Estrutura

- `plugin.json`
- `Providers/BlogServiceProvider.php`
- `Models/Post.php`
- `Models/Category.php`
- `Models/Tag.php`
- `Enums/PostStatus.php`
- `Enums/BlogPermission.php`
- `Http/Controllers/Admin`
- `Http/Controllers`
- `Http/Requests`
- `database/migrations`
- `resources/views/admin`
- `resources/views/front`
- `resources/views/slots`
- `routes/admin.php`
- `routes/web.php`

## Permissoes declaradas

- `blog.view_posts`
- `blog.create_posts`
- `blog.edit_posts`
- `blog.publish_posts`
- `blog.delete_posts`
- `blog.manage_categories`
- `blog.manage_tags`
- `blog.manage_settings`

## Categorias nesta etapa

O plugin agora suporta categorias editoriais simples com uma escolha deliberada:

- cada post possui no maximo uma categoria principal
- a categoria e opcional
- o objetivo e manter o plugin enxuto e previsivel nesta fase

Persistencia minima da categoria:

- `name`
- `slug`
- `description`
- `created_at`
- `updated_at`

Superficies administrativas:

- listagem de categorias
- criacao de categoria
- edicao de categoria
- selecao de categoria principal no formulario do post

Rota publica adicional:

- `/blog/category/{slug}`

## Tags nesta etapa

O plugin agora suporta tags editoriais simples com modelagem explicita:

- multiplas tags por post
- relacao persistida em pivot propria do plugin
- foco editorial e previsivel, sem virar taxonomia generica do core

Persistencia minima da tag:

- `name`
- `slug`
- `description`
- `created_at`
- `updated_at`

Superficies administrativas:

- listagem de tags
- criacao de tag
- edicao de tag
- selecao multipla de tags no formulario do post

Rota publica adicional:

- `/blog/tag/{slug}`

## Settings reais nesta etapa

O plugin publica um catalogo pequeno e explicito de settings no manifesto:

- `blog_title`
- `blog_intro`
- `show_excerpts`

Criterio operacional:

- os settings ficam acessiveis quando o plugin esta `valid` e `installed`
- o plugin nao precisa estar `enabled` para ser configurado
- os valores ficam persistidos em `core_settings` sob o grupo `plugin:blog`

Uso real atual:

- `blog_title` define o titulo da listagem publica
- `blog_intro` define o texto curto de apoio
- `show_excerpts` controla a exibicao do resumo dos posts na listagem

## Imagem destacada

- cada post pode referenciar opcionalmente um `media_assets.id` do core
- a selecao acontece no formulario administrativo do plugin usando a biblioteca central de midia
- apenas assets de imagem validos sao aceitos como `featured_image_id`
- a imagem destacada aparece na listagem publica e na pagina individual quando existir
- quando nenhuma imagem e selecionada, o frontend continua com fallback textual simples

## Regras desta fase

- sem comentarios
- sem SEO avancado
- sem revisao/versionamento
- sem editor rico complexo

## Renderizacao publica

Views suportadas para override por tema:

- `themes/<Theme>/views/plugins/blog/index.blade.php`
- `themes/<Theme>/views/plugins/blog/show.blade.php`
- `themes/<Theme>/views/plugins/blog/category.blade.php`
- `themes/<Theme>/views/plugins/blog/tag.blade.php`

Fallbacks padrao do plugin:

- `blog::front.index`
- `blog::front.show`
- `blog::front.category`
- `blog::front.tag`

## Slot de tema nesta etapa

O plugin agora tambem publica um bloco simples para o slot:

- `footer_cta`

Objetivo:

- demonstrar como um plugin oficial pode contribuir com o frontend sem virar page builder
- manter a responsabilidade visual no tema ativo
- reaproveitar o registry central de hooks do core

View publicada pelo plugin:

- `blog::slots.footer-cta`

Comportamento:

- o bloco so entra no registro quando o plugin estiver `valid`, `installed` e `enabled`
- se o tema ativo declarar `views/slots/footer_cta.blade.php`, ele decide o wrapper final
- se o tema nao declarar esse slot explicitamente, o core usa o fallback `front.slots.default`

## Migrations

As migrations do plugin ficam em:

- `plugins/Blog/database/migrations`

Nesta fase, o core ja consegue detectar e executar pendencias do plugin pelo admin, sem exigir CLI no fluxo basico.
