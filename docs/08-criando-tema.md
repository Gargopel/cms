# Criando Tema

## Estado atual

Ja existe um fluxo minimo e executavel para discovery do manifesto, mas ainda nao existe ativacao ou resolucao real de layouts.

## Estrutura minima

Crie um diretório dentro de `themes`:

```text
themes/
  MeuTema/
    theme.json
```

## Manifesto minimo

```json
{
    "name": "Meu Tema",
    "slug": "meu-tema",
    "description": "Descricao curta do tema.",
    "version": "0.1.0",
    "author": "Seu Nome",
    "core": {
        "min": "0.1.0"
    }
}
```

## Regras atuais

- `theme.json` deve ficar na raiz do diretório do tema
- `slug` deve usar letras minusculas, numeros e hifens
- `version` e `core.min` usam versionamento semantico
- `core.max` pode ser informado quando houver teto de compatibilidade

## O que o discovery faz hoje

- localiza o diretório do tema
- encontra `theme.json`
- valida a estrutura minima
- valida compatibilidade com a versao atual do core
- retorna um objeto utilizavel por camadas futuras
- isola falhas sem interromper o sistema

## Responsabilidades esperadas

- layouts
- partials
- assets
- templates publicos
- configuracoes visuais
- componentes de interface

## Restricao obrigatoria

Tema nao deve carregar regra de negocio critica.
