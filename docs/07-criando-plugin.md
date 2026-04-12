# Criando Plugin

## Estado atual

Ja existe um fluxo minimo e executavel para discovery do manifesto, mas ainda nao existe instalacao, ativacao ou boot do plugin.

## Estrutura minima

Crie um diretório dentro de `plugins`:

```text
plugins/
  MeuPlugin/
    plugin.json
```

## Manifesto minimo

```json
{
    "name": "Meu Plugin",
    "slug": "meu-plugin",
    "description": "Descricao curta do plugin.",
    "version": "0.1.0",
    "author": "Seu Nome",
    "core": {
        "min": "0.1.0"
    }
}
```

## Regras atuais

- `plugin.json` deve ficar na raiz do diretório do plugin
- `slug` deve usar letras minusculas, numeros e hifens
- `version` e `core.min` usam versionamento semantico
- `core.max` pode ser informado quando houver teto de compatibilidade
- `provider` pode ser declarado, mas ainda nao e carregado pelo core

## O que o discovery faz hoje

- localiza o diretório do plugin
- encontra `plugin.json`
- valida a estrutura minima
- valida compatibilidade com a versao atual do core
- retorna um objeto utilizavel por camadas futuras
- isola falhas sem derrubar o restante do sistema

## Responsabilidades esperadas

Plugins poderao registrar futuramente:

- migrations
- rotas
- views
- translations
- assets
- settings
- permissoes
- comandos
- jobs
- menus e widgets administrativos

## Regra desta fase

Nao criar plugins reais de negocio antes que o boot condicional, a habilitacao e os contratos de extensao do core estejam amadurecidos.
