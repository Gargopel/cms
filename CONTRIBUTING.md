# Contributing

## Objetivo

Este repositório abriga a fundacao de uma plataforma Laravel plugin-first. Toda contribuicao deve proteger a leveza do core, a previsibilidade da arquitetura e a separacao clara entre core, plugins e temas.

## Antes de alterar qualquer coisa

Leia e siga:

- `AGENTS.md`
- `README.md`
- `CHANGELOG.md`
- arquivos relevantes dentro de `docs/`

## Regras de contribuicao

- mantenha o core minimo
- nao mova para o core algo que possa virar plugin
- nao coloque regra de negocio em tema
- nao introduza dependencias pesadas sem justificativa clara
- nao implemente modulos grandes fora do escopo pedido
- documente toda mudanca estrutural relevante
- preserve compatibilidade sempre que possivel

## Fluxo recomendado

1. Entenda o problema e confirme se ele pertence ao core.
2. Se a responsabilidade puder ser extensao, planeje como plugin.
3. Implemente a menor mudanca viavel.
4. Atualize a documentacao afetada.
5. Registre impacto no `CHANGELOG.md` quando aplicavel.
6. Rode os testes e verificacoes basicas.

## Convencoes

- PHP: siga os padroes do Laravel e PSR-12
- nomes: priorize clareza e previsibilidade
- arquitetura: prefira contratos publicos e pontos de extensao bem delimitados
- pastas reservadas para futuro: nao preencha com implementacoes falsas

## Verificacao minima antes de abrir mudanca

- `php artisan test`
- `php artisan about`

Se a alteracao mexer com autoload ou providers, rode tambem:

```bash
composer dump-autoload
```

## Documentacao obrigatoria

Mudancas estruturais nao estao completas sem:

- documentacao atualizada em `docs/`
- ajuste do `README.md` quando necessario
- ajuste do `CHANGELOG.md` quando houver impacto relevante

## Escopo desta fase

Neste momento o projeto ainda esta em fundacao. Contribuicoes devem evitar:

- blog
- loja
- SEO avancado
- formularios
- analytics
- automacoes
- page builder

Essas capacidades devem nascer como plugins quando a base do ecossistema estiver pronta.
