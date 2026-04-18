# Forms Plugin

Plugin oficial de referencia para interacao publica simples com usuarios, criado para demonstrar formularios server-rendered, submissao persistida, notificacao simples e consulta administrativa no ecossistema do core.

## O que entrega nesta fase

- manifesto completo e compativel com o sistema de extensoes
- permissoes proprias sincronizadas no RBAC central
- catalogo pequeno de settings por plugin para notificacao e comportamento pos-envio
- migrations proprias em `database/migrations`
- area administrativa em Blade para listar, criar, editar e remover formularios
- area administrativa em Blade para listar, criar e editar campos por formulario
- area administrativa em Blade para consultar submissoes persistidas por formulario
- renderizacao publica simples de formularios publicados
- submissao publica com validacao backend forte
- notificacao simples por email quando habilitada e configurada
- redirect local seguro opcional apos envio bem-sucedido
- fallback por tema em `themes/<Theme>/views/plugins/forms/show.blade.php`
- hooks reais para menu admin e painel simples de dashboard
- CTA simples e reutilizavel em `footer_cta` via theme slots

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
- `Mail/FormSubmissionNotificationMail.php`
- `Support/FormSubmissionService.php`
- `Support/FormSubmissionResult.php`
- `Http/Controllers/Admin`
- `Http/Controllers`
- `Http/Requests`
- `database/migrations`
- `resources/views/admin`
- `resources/views/front`
- `resources/views/mail`
- `routes/admin.php`
- `routes/web.php`

## Permissoes declaradas

- `forms.view_forms`
- `forms.create_forms`
- `forms.edit_forms`
- `forms.publish_forms`
- `forms.delete_forms`
- `forms.view_form_submissions`
- `forms.manage_settings`

## Settings reais nesta etapa

- `recipient_email`
- `success_message`
- `redirect_url`
- `notifications_enabled`

Regras atuais:

- `success_message` do formulario continua tendo precedencia quando existir
- `success_message` do plugin funciona como fallback global
- `redirect_url` aceita apenas paths locais iniciados por `/`
- notificacoes so tentam envio quando `notifications_enabled = true` e `recipient_email` for um email valido
- falha no envio nao quebra a submissao publica inteira; o plugin registra a excecao e preserva o fluxo do usuario

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
- consulta administrativa de submissoes
- settings do plugin pelo mecanismo central do core em extensoes

## Renderizacao publica

Rota publica principal:

- `/forms/{slug}`

Comportamento:

- `GET /forms/{slug}` exibe o formulario publicado
- `POST /forms/{slug}` valida e persiste a submissao
- quando nao houver redirect valido configurado, a resposta de sucesso volta para a mesma pagina com mensagem simples
- quando houver `redirect_url` local valido configurado, o visitante e redirecionado apos o envio
- quando notificacoes estiverem habilitadas e configuradas, o plugin envia um email simples com os valores da submissao

Views suportadas para override por tema:

- `themes/<Theme>/views/plugins/forms/show.blade.php`

Fallback padrao do plugin:

- `forms::front.show`

## Slot de tema nesta etapa

O plugin agora tambem publica um bloco pequeno e reutilizavel para:

- `footer_cta`

Objetivo:

- expor um CTA institucional simples para formularios publicados
- provar contribuicoes de frontend com dados resolvidos no backend
- manter o plugin util sem virar builder visual

Views relacionadas:

- fallback do plugin: `forms::slots.contact-cta`
- override opcional do tema: `themes/<Theme>/views/plugins/forms/slots/contact-cta.blade.php`

Regra atual:

- o bloco so entra no registro quando o plugin estiver `valid`, `installed` e `enabled`
- se nao houver formulario `published`, o bloco nao e renderizado

## Regras desta fase

- sem builder visual
- sem logica condicional
- sem uploads de arquivo
- sem webhooks
- sem anti-spam avancado
- sem automacoes
- sem filas complexas
- sem multiplos canais de notificacao

## Migrations

As migrations do plugin ficam em:

- `plugins/Forms/database/migrations`

Nesta fase, o core ja consegue detectar e executar pendencias do plugin pelo admin, sem exigir CLI no fluxo basico.
