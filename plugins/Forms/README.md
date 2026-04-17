# Forms Plugin

Plugin oficial de referencia para interacao publica simples com usuarios, criado para demonstrar formularios server-rendered, submissao persistida e consulta administrativa no ecossistema do core.

## O que entrega nesta fase

- manifesto completo e compativel com o sistema de extensoes
- permissoes proprias sincronizadas no RBAC central
- migrations proprias em `database/migrations`
- area administrativa em Blade para listar, criar, editar e remover formularios
- area administrativa em Blade para listar, criar e editar campos por formulario
- area administrativa em Blade para consultar submissões persistidas por formulario
- renderizacao publica simples de formularios publicados
- submissao publica com validacao backend forte
- fallback por tema em `themes/<Theme>/views/plugins/forms/show.blade.php`
- hooks reais para menu admin e painel simples de dashboard

## Estrutura

- `plugin.json`
- `Providers/FormsServiceProvider.php`
- `Models/Form.php`
- `Models/FormField.php`
- `Models/FormSubmission.php`
- `Models/FormSubmissionValue.php`
- `Enums/FormStatus.php`
- `Enums/FormFieldType.php`
- `Enums/FormsPermission.php`
- `Support/FormSubmissionService.php`
- `Http/Controllers/Admin`
- `Http/Controllers`
- `Http/Requests`
- `database/migrations`
- `resources/views/admin`
- `resources/views/front`
- `routes/admin.php`
- `routes/web.php`

## Permissoes declaradas

- `forms.view_forms`
- `forms.create_forms`
- `forms.edit_forms`
- `forms.publish_forms`
- `forms.delete_forms`
- `forms.view_form_submissions`

## Modelagem nesta etapa

### Formularios

- `title`
- `slug`
- `description`
- `success_message`
- `status`
- `created_at`
- `updated_at`

### Campos

- `form_id`
- `label`
- `name`
- `type`
- `placeholder`
- `help_text`
- `options`
- `is_required`
- `sort_order`
- `created_at`
- `updated_at`

### Submissoes

- `form_id`
- `submitted_at`
- `ip_address`
- `user_agent`
- `created_at`
- `updated_at`

### Valores de submissao

- `submission_id`
- `form_field_id`
- `field_name`
- `field_label`
- `value`
- `created_at`
- `updated_at`

## Tipos de campo suportados

- `text`
- `email`
- `textarea`
- `select`
- `checkbox`

Regras atuais:

- `select` exige opcoes explicitas
- `checkbox` usa validacao de aceite quando marcado como obrigatorio
- nao ha upload de arquivo
- nao ha logica condicional
- nao ha drag-and-drop

## Status editorial do formulario

O plugin usa um recorte pequeno e previsivel:

- `draft`
- `published`

Regra publica atual:

- apenas formularios `published` podem ser acessados e submetidos no frontend

## Superficies administrativas

- listagem de formularios
- criacao de formulario
- edicao de formulario
- exclusao de formulario
- gestao basica de campos por formulario
- consulta administrativa de submissões

## Renderizacao publica

Rota publica principal:

- `/forms/{slug}`

Comportamento:

- `GET /forms/{slug}` exibe o formulario publicado
- `POST /forms/{slug}` valida e persiste a submissao
- a resposta de sucesso volta para a mesma pagina com mensagem simples

Views suportadas para override por tema:

- `themes/<Theme>/views/plugins/forms/show.blade.php`

Fallback padrao do plugin:

- `forms::front.show`

## Regras desta fase

- sem builder visual
- sem logica condicional
- sem uploads de arquivo
- sem webhooks
- sem anti-spam avancado
- sem automacoes
- sem notificacoes transacionais

## Migrations

As migrations do plugin ficam em:

- `plugins/Forms/database/migrations`

Nesta fase, o core ja consegue detectar e executar pendencias do plugin pelo admin, sem exigir CLI no fluxo basico.
