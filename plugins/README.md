# Plugins

Esta pasta abriga plugins da plataforma.

Nesta etapa ainda nao existe plugin manager completo, mas ja existe discovery basico por manifesto.

Convencao atual:

- cada plugin vive em seu proprio diretório
- cada plugin deve expor `plugin.json` na raiz do diretório
- o discovery atual apenas le e valida o manifesto
- nenhum plugin e carregado automaticamente por boot nesta etapa

Diretrizes iniciais:

- cada plugin deve declarar manifesto e compatibilidade com o core
- plugin desativado nao deve impor custo de boot relevante
- plugin nao deve editar arquivos do core
- funcionalidades extensivas devem nascer aqui, nao no core
