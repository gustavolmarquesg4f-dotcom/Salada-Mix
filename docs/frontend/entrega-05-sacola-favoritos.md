# FE-05 — Sacola e favoritos ligados ao Laravel

## Base e dependência
- Branch `feature/frontend-cart-wishlist-fe05` construída sobre FE-04 (PR #25) para preservar identidade e minha conta. PR FE-05 usa FE-04 como base; rebasing após integração.
- Endpoints BFF previamente existentes: GET/PUT/DELETE cart e wishlist; sessão web + CSRF.
- Tela GET /sacola e GET /favoritos somente para usuário autenticado e com e-mail verificado.

## Entregas reais
- Cards e página de oferta: ação de salvar na sacola e nos favoritos; guest é encaminhado para login.
- Sacola agrupa anúncios por vendedor, exibe foto DEMO somente quando mapeada explicitamente, quantidade 1–20, preço/subtotal do servidor e remoção de anúncio indisponível.
- Favoritos persistidos por conta, foto DEMO, preço vivo e ação de adicionar à sacola.
- Header e Minha Conta com entradas diretas de navegação.
- Frontend acessa somente BFF same-origin e não envia preços/seller_id nem escreve em localStorage; revalidação usa resposta do servidor.
- Resumo financeiro e checkout continuam BLOQUEADOS. Não há cotação de frete, pedido, cobrança, reserva ou estoque mutado pelo cliente.
- Snapshot de itens suspensos não pode expor nome do vendedor/produto; exibe apenas indicação de indisponibilidade e opção de remover.

## Estado da prévia Pages
- Rota FE-05 em /fe05/ com dados **sintéticos**, interações de sacola/favoritos **apenas locais à prévia** e zeradas ao recarregar.
- Não simula login real nem chama as rotas BFF.
- Apenas a aplicação Laravel tem persistência real em MySQL por usuário.

## Aceite
`php artisan test`, `npm run build`, `node preview/tests/smoke.cjs`. Validar navegação desktop/mobile e divergência entre preço/estoque/quantidade pelo contrato já existente.
