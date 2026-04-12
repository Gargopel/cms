# Instalacao

## Estado atual

O projeto agora possui uma primeira versao real do instalador web guiado do core.

## Fluxo web entregue nesta etapa

Rotas principais:

- `GET /install`
- `GET /install/requirements`
- `GET /install/database`
- `POST /install/database`
- `GET /install/administrator`
- `POST /install/administrator`
- `POST /install/run`
- `GET /install/complete`

Etapas do wizard:

1. boas-vindas
2. checagem de requisitos e permissoes
3. configuracao do banco
4. dados do administrador inicial
5. tela final de sucesso

## O que o instalador faz hoje

Durante a execucao do setup, o core:

- valida requisitos minimos do ambiente
- valida permissoes basicas de escrita
- testa a conexao com o banco informado
- cria ou atualiza o arquivo `.env`
- gera `APP_KEY` quando necessario
- aplica configuracao inicial essencial
- roda migrations
- sincroniza a base de seguranca do core
- cria o administrador inicial
- marca a instalacao como concluida
- bloqueia reinstalacao acidental

## Estado instalado

O estado de instalado e explicito:

- o core grava um marcador de instalacao concluida
- o instalador passa a redirecionar para o login do admin
- rotas administrativas deixam de apontar para o wizard e passam a usar auth normal

## Compatibilidade com hospedagem comum

O fluxo foi mantido server-rendered e sem dependencia de ferramentas externas para o usuario final.

Isso significa que, nesta fase, a instalacao pode ocorrer apenas com:

- acesso web ao sistema
- diretorios gravaveis
- banco previamente criado pelo usuario

## Setup manual continua possivel

Para ambiente de desenvolvimento, o fluxo manual ainda funciona:

1. `composer install`
2. criar `.env` a partir de `.env.example`
3. configurar banco de dados
4. `php artisan key:generate`
5. `php artisan migrate`
6. `php artisan db:seed`
7. `php artisan serve`

## O que ficou de fora de proposito

- upgrade entre versoes
- reinstalacao assistida
- instalacao de plugins durante o setup
- health checks profundos
- diagnostico avancado de hospedagem

O objetivo desta etapa foi entregar um instalador inicial, seguro e compreensivel, sem transformar o core em um sistema de update completo.
