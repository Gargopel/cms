# Example Plugin

Este plugin existe apenas como exemplo minimo de manifesto para validar o discovery.

Ele declara um provider tecnico minimo apenas para validar o boot condicional de plugins habilitados.

Ele tambem declara permissoes proprias no manifesto para validar a sincronizacao central de permissoes de plugins nesta fase:

- `example-plugin.view_example_dashboard`
- `example-plugin.manage_example_plugin`

Quando habilitado de forma elegivel, o provider tecnico do plugin tambem publica:

- um item simples de navegacao no admin
- um painel simples no dashboard

Ele nao registra rotas, assets ou regras de negocio nesta etapa.
