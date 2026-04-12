# Versionamento e Upgrades

## Diretriz

O projeto deve adotar versionamento semantico sempre que possivel.

## Regras

- mudancas breaking exigem documentacao explicita
- contratos publicos afetados exigem nota de compatibilidade
- upgrades do core devem considerar plugins e temas suportados
- changelog deve acompanhar as evolucoes relevantes

## Compatibilidade inicial de extensoes

Plugins e temas declaram compatibilidade diretamente no manifesto:

- `core.min`: versao minima do core suportada
- `core.max`: versao maxima suportada, opcional

O discovery usa esses campos para classificar extensoes como:

- `valid`
- `invalid`
- `incompatible`

## Persistencia e upgrades

Com a introducao do registro persistido, o sistema passa a manter duas camadas complementares:

- dados detectados no filesystem
- estado operacional salvo no banco

Isso e importante para upgrades porque permite:

- comparar o que foi detectado agora com o que ja estava registrado
- preservar decisoes operacionais como `enabled` e `disabled`
- impedir reativacao indevida de extensoes invalidas ou incompativeis

## Boot condicional e compatibilidade

O boot atual so considera plugins que passem por todas estas etapas:

1. manifesto valido
2. compatibilidade com o core
3. sincronizacao bem-sucedida no registro
4. estado operacional `enabled`
5. provider resolvivel e compativel com `ServiceProvider`

Esse encadeamento prepara upgrades mais seguros, porque reduz a chance de um plugin quebrado ou incompativel entrar no runtime apenas por existir no disco.

## Estado atual

Ainda nao ha mecanismo de upgrade implementado. Nesta etapa, a prioridade foi preparar a fundacao para upgrades seguros, com compatibilidade declarada em manifesto, registro persistido de estado operacional, boot condicional controlado e um instalador web inicial que marca explicitamente o sistema como instalado.

## Relacao entre instalacao e upgrades futuros

Com a introducao do instalador web, o core passa a ter:

- configuracao inicial persistida em `.env`
- marcador explicito de instalacao concluida
- criacao do administrador inicial no fluxo guiado

Isso e relevante para upgrades futuros porque ajuda a separar claramente:

- primeira instalacao do produto
- atualizacoes de versao do core
- migracoes de extensoes

O instalador atual nao executa upgrades. Ele apenas estabelece um ponto de partida previsivel para que upgrades futuros possam respeitar estado ja instalado, contrato de compatibilidade e bloqueio de reinstalacao.
