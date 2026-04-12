# AGENTS.md

## 1. Visão do produto

Este projeto é uma plataforma base extensível construída em Laravel, com arquitetura plugin-first e suporte a temas, pensada para servir como núcleo comercializável para múltiplos tipos de produto, incluindo CMS, blog, portal, docs, loja e integrações diversas.

O objetivo principal não é criar um CMS monolítico. O objetivo é criar um core leve, previsível, extensível, fácil de instalar, fácil de manter e extremamente documentado, para que plugins e temas possam ser desenvolvidos, ativados, desativados e comercializados com segurança.

---

## 2. Princípios inegociáveis

### 2.1 Plugin-first
Se uma funcionalidade puder ser implementada como plugin, ela deve ser plugin.

### 2.2 Core mínimo
O core deve conter apenas o necessário para hospedar, configurar, proteger e operar o ecossistema.

### 2.3 Tema separado de regra de negócio
Tema controla apresentação visual.
Plugin controla funcionalidade.
Core controla a infraestrutura.

### 2.4 Admin como centro de operação
Todo o sistema deve ser configurável, monitorável e operável pelo admin sempre que isso for tecnicamente viável e seguro.

### 2.5 Instalação amigável
O produto deve priorizar a experiência do usuário final. A instalação deve ocorrer por fluxo web guiado, com o mínimo possível de etapas manuais.

### 2.6 Documentação como parte do produto
Nenhuma funcionalidade estrutural deve ser considerada concluída sem documentação compatível.

### 2.7 Leveza real
Plugins desativados não devem impor custo significativo de boot, render, query ou memória.

### 2.8 Estrutura previsível
O sistema deve ter convenções claras de código, pastas, contratos e extensões.

### 2.9 Evolução segura
Mudanças devem respeitar compatibilidade, versionamento semântico e changelog.

### 2.10 UI premium obrigatória
Toda interface nova deve seguir a identidade visual oficial definida neste projeto. Interfaces genéricas de template pronto são consideradas incorretas.

---

## 3. O que pertence ao core

O core deve conter apenas infraestrutura base.

### 3.1 Base do sistema
- bootstrap da aplicação
- autenticação
- sessões
- usuários
- papéis e permissões
- painel administrativo base

### 3.2 Extensibilidade
- gerenciador de plugins
- gerenciador de temas
- manifests
- registro de extensões
- hooks, actions, filters, events e listeners
- carregamento condicional de extensões
- APIs internas estáveis para plugins e temas

### 3.3 Operação e administração
- dashboard base
- settings globais
- logs
- auditoria
- manutenção
- limpeza de cache
- health checks
- status do ambiente
- diagnóstico do sistema

### 3.4 Plataforma
- media manager básico
- instalador web
- bloqueio de reinstalação
- versionamento do core
- mecanismo base de upgrades
- estrutura de documentação

---

## 4. O que deve virar plugin

Tudo abaixo deve ser plugin por padrão, salvo necessidade crítica de infraestrutura:

- blog avançado
- loja / e-commerce
- SEO avançado
- formulários
- newsletter
- analytics
- chatbot
- comentários
- docs públicas
- integrações com APIs
- gateways de pagamento
- CRM / ERP connectors
- automações
- memberships
- cursos
- fórum
- importadores/exportadores especializados
- page builder avançado
- backups avançados
- search avançado
- módulos de IA

---

## 5. O que pode existir no core apenas em forma mínima

Os itens abaixo podem existir em versão mínima no core se forem necessários para usabilidade inicial da plataforma:

- páginas simples
- menus simples
- mídia básica
- settings globais
- usuários e permissões
- logs e manutenção

Se uma evolução desses módulos aumentar muito a complexidade, a expansão deve ser extraída para plugin oficial.

---

## 6. Fronteira entre core, plugin e tema

### 6.1 Core
Responsável por infraestrutura, segurança, ciclo de vida das extensões, administração central, instalação, manutenção e APIs internas.

### 6.2 Plugin
Responsável por adicionar funcionalidade, entidades, rotas, views, settings, permissões, jobs, integrações, comandos e superfícies administrativas específicas.

### 6.3 Tema
Responsável por layout público, templates, assets, componentes visuais, configurações visuais e apresentação pública.

### 6.4 Regra obrigatória
Tema não deve carregar regra de negócio complexa.
Plugin não deve redefinir a arquitetura base do sistema.
Core não deve absorver o que puder ser extensão.

---

## 7. Contrato mínimo de plugin

Todo plugin deve possuir:

- nome
- slug único
- descrição
- versão
- autor
- compatibilidade mínima com o core
- manifesto
- classe principal de bootstrap/provider

Todo plugin pode registrar:

- migrations
- seeders
- rotas web
- rotas api
- views
- translations
- assets
- permissões
- settings
- menus administrativos
- widgets/blocos
- eventos/listeners
- comandos
- jobs
- hooks e filters

### 7.1 Ciclo de vida do plugin
- discovered
- installed
- enabled
- disabled
- updated
- removed

### 7.2 Regras obrigatórias
- plugin desativado não deve bootar sem necessidade
- plugin não deve editar arquivos do core
- plugin deve usar contratos públicos do core
- plugin deve declarar dependências
- plugin deve declarar compatibilidade de versão
- plugin deve falhar de forma isolada sempre que possível

---

## 8. Contrato mínimo de tema

Todo tema deve possuir:

- nome
- slug único
- descrição
- versão
- autor
- compatibilidade com o core
- manifesto

Todo tema pode fornecer:

- layouts
- partials
- assets
- templates públicos
- settings visuais
- áreas configuráveis
- componentes de interface
- presets visuais

### 8.1 Regras obrigatórias
- tema não deve conter regra de negócio crítica
- tema deve operar pelos contratos públicos do sistema
- tema deve poder ser ativado e desativado pelo admin
- tema pode declarar dependência de plugin quando necessário

---

## 9. Admin base obrigatório

O admin base do core deve conter:

### 9.1 Dashboard
- visão geral
- atalhos
- status resumido

### 9.2 Usuários e permissões
- usuários
- papéis
- permissões

### 9.3 Plugins
- listar
- instalar
- ativar
- desativar
- remover
- visualizar compatibilidade
- acessar settings do plugin

### 9.4 Temas
- listar
- ativar
- desativar
- acessar settings do tema

### 9.5 Configurações
- branding
- nome do site
- emails base
- locale
- timezone
- footer
- scripts globais
- settings gerais

### 9.6 Mídia básica
- upload
- listagem
- metadados
- exclusão

### 9.7 Logs e auditoria
- erros
- atividades administrativas
- eventos relevantes do sistema

### 9.8 Manutenção
- limpar caches
- rebuilds simples
- modo manutenção
- comandos operacionais seguros

### 9.9 Ambiente e saúde
- versão do core
- PHP
- banco
- cache
- filas
- permissões de diretórios
- extensões necessárias
- health checks

---

## 10. Instalação

O projeto deve priorizar o fluxo mais simples para o usuário final.

### 10.1 Fluxo desejado
1. Usuário sobe os arquivos.
2. Usuário cria o banco de dados.
3. Usuário acessa o instalador web.
4. Usuário preenche os dados.
5. Sistema conclui o restante automaticamente.

### 10.2 O instalador deve:
- validar requisitos
- validar permissões de diretório
- coletar e testar dados do banco
- gerar `.env`
- gerar app key
- rodar migrations
- rodar seed inicial
- criar usuário administrador
- marcar instalação como concluída
- bloquear reinstalação

### 10.3 install.php
Pode existir como ponto de entrada amigável, mas a arquitetura principal deve ser um instalador web guiado.

---

## 11. Documentação obrigatória

Toda etapa estrutural relevante deve atualizar documentação.

### 11.1 Estrutura mínima
- README.md
- AGENTS.md
- CHANGELOG.md
- CONTRIBUTING.md
- docs/00-visao-geral.md
- docs/01-arquitetura-core.md
- docs/02-instalacao.md
- docs/03-admin.md
- docs/04-plugin-system.md
- docs/05-theme-system.md
- docs/06-hooks-e-extensoes.md
- docs/07-criando-plugin.md
- docs/08-criando-tema.md
- docs/09-versionamento-e-upgrades.md
- docs/10-troubleshooting.md

### 11.2 Regra
Nenhuma mudança estrutural importante está concluída sem:
- documentação atualizada
- changelog atualizado quando aplicável
- notas de compatibilidade, se necessário

---

## 12. Padrão visual obrigatório

A identidade visual oficial do produto deve seguir estes princípios:

- premium
- profissional
- confiável
- moderno
- orientado a operação B2B

### 12.1 Base visual
- dark mode como base
- glassmorphism controlado
- alto contraste
- brilho neon controlado
- superfícies arredondadas

### 12.2 Paleta-base
- roxo neon como identidade institucional
- azul neon como apoio e profundidade
- cyan como ação, energia e feedback positivo
- slate profundo para fundos
- branco suave para textos principais
- slate claro para textos secundários
- rose controlado para risco/atenção

### 12.3 Regras de interface
- evitar visual infantil
- evitar excesso de cores quentes
- evitar telas genéricas de template pronto
- usar cards grandes, arejados e com hierarquia clara
- usar CTA principal com gradiente premium
- manter consistência entre PageHeader, GlassCard, InputField, tabelas, badges, cards métricos e modais futuros

### 12.4 Regra prática
Se uma tela parecer um painel admin genérico, ela está fora da identidade do produto.

---

## 13. Performance e leveza

### 13.1 Regras
- evitar dependências desnecessárias
- lazy load sempre que possível
- plugin desativado não deve impor custo relevante
- cachear settings e estruturas estáveis quando apropriado
- evitar boot excessivo
- evitar consultas redundantes
- evitar acoplamento entre plugins

### 13.2 Prioridade
A legibilidade e a previsibilidade são mais importantes que truques excessivos de abstração.

---

## 14. Segurança

Toda implementação deve considerar:

- validação forte de entrada
- autorização por policy/gate/permissão
- proteção contra XSS
- uploads seguros
- logs para ações sensíveis
- falha segura de extensões
- isolamento razoável de plugins
- proteção de rotas administrativas
- proteção da instalação após setup concluído

---

## 15. Versionamento e compatibilidade

### 15.1 Versionamento
Usar versionamento semântico sempre que possível.

### 15.2 Compatibilidade
Plugins e temas devem declarar compatibilidade mínima com o core.

### 15.3 Mudanças breaking
Toda mudança breaking exige:
- documentação explícita
- changelog
- nota de upgrade
- atualização dos contratos públicos afetados

---

## 16. Como o Codex deve trabalhar neste projeto

### 16.1 Estratégia
O projeto será desenvolvido em etapas curtas e incrementais.

### 16.2 Regras de execução
- executar uma responsabilidade principal por prompt
- não ampliar escopo sem necessidade
- respeitar integralmente a arquitetura definida neste AGENTS.md
- preservar convenções existentes
- não introduzir sistemas paralelos fora do padrão definido
- documentar mudanças relevantes
- manter o core mínimo
- mover para plugin tudo que for naturalmente extensível

### 16.3 Em cada resposta técnica relevante, informar:
- resumo do que foi implementado
- arquivos criados
- arquivos alterados
- decisões técnicas importantes
- riscos ou pendências
- impacto em documentação
- impacto em compatibilidade, se houver

---

## 17. Checklist antes de concluir qualquer etapa

Antes de considerar uma etapa concluída, verificar:

- isso realmente precisa estar no core?
- isso deveria ser plugin?
- isso deveria ser tema?
- isso está documentado?
- isso respeita a identidade visual?
- isso mantém o sistema leve?
- isso é seguro?
- isso preserva compatibilidade?
- isso pode ser entendido por outro dev sem contexto oculto?

---

## 18. Regra final

Este projeto não deve evoluir como um CMS monolítico.
Ele deve evoluir como uma plataforma extensível, previsível, documentada e comercializável.