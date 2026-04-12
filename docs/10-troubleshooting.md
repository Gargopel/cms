# Troubleshooting

## Problemas comuns nesta etapa inicial

### Dependencias nao instalam

- confirme a versao do PHP compativel com Laravel 11
- rode `composer diagnose`
- valide extensoes obrigatorias do PHP

### Aplicacao nao sobe

- confirme que o `.env` existe
- rode `php artisan key:generate`
- confira as credenciais do banco

### Erro ao migrar

- valide conexao com o banco
- confirme permissoes de escrita quando usar SQLite
- rode `php artisan migrate:fresh` apenas em ambiente local descartavel

### Classes novas nao sao encontradas

- rode `composer dump-autoload`

## Observacao

Como ainda nao ha plugin manager, theme manager ou instalador web, problemas relacionados a esses recursos serao tratados em etapas futuras.
