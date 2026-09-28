# Prévia pública FE-01 + FE-02 — Salada Mix 2.0

Esta prévia estática reproduz a marca oficial e os componentes visuais do storefront Blade. As folhas `salada-foundation.css` e `salada-catalog.css` são cópias exatas dos arquivos em `resources/css/`; os SVGs são os originais de `public/assets/salada/`. O smoke test verifica essa igualdade.

**Não é a aplicação Laravel em execução.** A busca, categoria, filtro de preço, ordenação e detalhe funcionam no navegador com nove ofertas fictícias equivalentes ao `MarketplaceDemoSeeder`, que só pode ser usado localmente. Não há MySQL remoto, login, cadastro real, frete, reservas, pedidos, pagamentos nem armazenamento de dados dos formulários.

O link é https://gustavolmarquesg4f-dotcom.github.io/Salada-Mix/ .

Fonte da prévia: `preview/` em `main`. Publicação: copiar exclusivamente seu conteúdo para a raiz de `gh-pages` (branch de GitHub Pages). A mera atualização de `main` não publica automaticamente a nova tela: atualizar `preview/` e sincronizar `gh-pages` após cada entrega visual.

Validação: `node --check preview/assets/app.js` e `node preview/tests/smoke.cjs`. A aplicação verdadeira permanece em `app/`, `resources/`, `database/` e `tests/`.
