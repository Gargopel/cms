# Seo Plugin

Plugin oficial enxuto para resolver metadados SEO no frontend sem acoplar essa responsabilidade ao core ou aos plugins de conteudo.

## O que entrega nesta fase

- manifesto completo e compativel com o sistema de extensoes
- settings globais do plugin persistidos pelo core
- resolvedor simples de metadados SEO com fallback previsivel
- renderizacao de title, description, canonical, robots e Open Graph basico
- integracao leve com `Pages` e `Blog`

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

## Limites desta fase

- sem sitemap
- sem schema avancado
- sem analise de SEO
- sem redirecionamentos
- sem acoplamento forte ao core
