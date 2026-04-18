# Seo Plugin

Plugin oficial enxuto para resolver metadados SEO no frontend sem acoplar essa responsabilidade ao core ou aos plugins de conteudo.

## O que entrega nesta fase

- manifesto completo e compativel com o sistema de extensoes
- settings globais do plugin persistidos pelo core
- resolvedor simples de metadados SEO com fallback previsivel
- renderizacao de title, description, canonical, robots e Open Graph basico
- integracao leve com `Pages` e `Blog`
- sitemap XML publico simples em `/sitemap.xml`

## Permissoes declaradas

- `seo.manage_settings`

## Settings reais nesta etapa

- `default_meta_title_suffix`
- `default_meta_description`
- `indexing_enabled`

## Contrato atual

O plugin publica o contrato:

- `Plugins\Seo\Contracts\SeoMetadataResolver`

E retorna um DTO simples:

- `Plugins\Seo\Support\SeoMetadata`
- `Plugins\Seo\Support\SitemapUrl`

Tambem expõe um gerador pequeno e previsivel para sitemap:

- `Plugins\Seo\Support\SeoSitemapGenerator`

O contexto aceito nesta fase e pequeno e explicito, com chaves como:

- `title`
- `description`
- `canonical`
- `noindex`
- `og_type`
- `og_image`

## Integracoes entregues

- `Pages`: paginas publicas usam defaults globais do plugin e featured image quando existir
- `Blog`: listagem publica, post publico, listagem por categoria e listagem por tag usam defaults globais do plugin e metadados especificos de conteudo quando houver

## Sitemap XML nesta etapa

O plugin publica uma rota simples:

- `GET /sitemap.xml`

Entradas consideradas nesta fase:

- home publica
- paginas `published` do plugin `Pages`, quando `Pages` estiver `valid + installed + enabled`
- indice publico `/blog`
- posts `published` do plugin `Blog`, quando `Blog` estiver `valid + installed + enabled`
- paginas publicas de categoria do `Blog` que possuam ao menos um post publicado
- paginas publicas de tag do `Blog` que possuam ao menos um post publicado

Regras atuais:

- conteudos `draft` nao entram
- se `Pages` ou `Blog` nao estiverem elegiveis, o sitemap simplesmente omite suas URLs
- nao existe sitemap index, image sitemap, news sitemap ou configuracao editorial avancada
- a geracao permanece no plugin `Seo`, sem mover SEO para o core

## Limites desta fase

- sem schema avancado
- sem analise de SEO
- sem redirecionamentos
- sem acoplamento forte ao core
