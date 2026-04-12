# Arquitetura do Core

## Papel do core

O core deve conter apenas o necessario para hospedar, configurar, proteger e operar o ecossistema.

Nesta fundacao, o core comeca com:

- bootstrap da aplicacao Laravel
- provider minimo para consolidar infraestrutura do core
- estrutura reservada para admin, instalacao, contratos e extensoes
- utilitarios compartilhados de baixo acoplamento

## Estrutura inicial

- `app/Core/Foundation`: bootstrap e servicos internos do core
- `app/Core/Contracts`: contratos publicos que poderao nascer nas proximas etapas
- `app/Core/Extensions`: reservado para o futuro sistema de extensoes
- `app/Core/Install`: reservado para o futuro instalador web
- `app/Core/Admin`: reservado para o futuro painel administrativo base
- `app/Support`: apoio leve reutilizavel pelo core

## Decisao importante

Nao ha implementacao de plugin manager ou theme manager nesta etapa. A arquitetura foi apenas preparada para recebe-los sem antecipar responsabilidades.
