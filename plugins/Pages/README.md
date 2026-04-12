# Pages Plugin

Plugin oficial de referencia para o ecossistema desta plataforma.

## Objetivo

Demonstrar como um plugin real deve se integrar ao core existente sem quebrar a arquitetura plugin-first:

- manifesto completo e normalizado
- permissoes proprias sincronizadas no RBAC central
- menu admin via hooks publicos
- painel simples de dashboard via hooks publicos
- rotas administrativas proprias
- persistencia propria
- renderizacao publica com suporte a tema ativo e fallback seguro

## Permissoes publicadas

- `pages.view_pages`
- `pages.create_pages`
- `pages.edit_pages`
- `pages.publish_pages`
- `pages.delete_pages`

## Superficies administrativas publicadas

- item de navegacao `Pages` no admin
- painel simples `Pages Library` no dashboard

Ambos respeitam a permissao `pages.view_pages`.

## Rotas administrativas

- `GET /admin/pages`
- `GET /admin/pages/create`
- `POST /admin/pages`
- `GET /admin/pages/{page}/edit`
- `PUT /admin/pages/{page}`
- `DELETE /admin/pages/{page}`

## Persistencia

Tabela propria do plugin:

- `plugin_pages_pages`

Campos atuais:

- `title`
- `slug`
- `content`
- `status`
- `created_at`
- `updated_at`

Status atuais:

- `draft`
- `published`

## Renderizacao publica

Rota publica atual:

- `/pages/{slug}`

Somente paginas `published` ficam acessiveis publicamente.

Fallback de views:

- override por tema ativo: `themes/<Theme>/views/plugins/pages/show.blade.php`
- fallback do plugin: `pages::front.show`

## Nota operacional

Nesta fase, o plugin ja carrega migrations pelo provider.
Enquanto o core ainda nao possui um runner administrativo de migrations por plugin, o ambiente precisa rodar `php artisan migrate` apos habilitar o plugin para materializar a tabela.
