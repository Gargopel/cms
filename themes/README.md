# Themes

Esta pasta abriga temas da plataforma.

Nesta etapa ja existe uma base real do theme manager do core, com discovery por manifesto, tema ativo persistido e resolucao de views com fallback seguro para o core.

Convencao atual:

- cada tema vive em seu proprio diretorio
- cada tema deve expor `theme.json` na raiz do diretorio
- o discovery atual le, valida e registra o manifesto
- o theme manager pode selecionar um tema ativo sem tocar no filesystem
- o frontend tenta carregar views do tema ativo antes de cair no fallback do core

Estrutura inicial recomendada:

- `theme.json`
- `views/`
- `assets/`

Diretrizes iniciais:

- tema cuida de layout, assets e composicao visual
- tema nao deve concentrar regra de negocio complexa
- tema deve operar por contratos publicos da plataforma
