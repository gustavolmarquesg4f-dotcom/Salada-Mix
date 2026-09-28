# BE-02 — Contrato de integração para a Entrega 2 do frontend

Data: 2026-09-28. Backend/banco desenvolvido em branch própria, sem alterar os componentes de FE-01.

## Catálogo público (JSON, sem login)

| Método | URL | Finalidade |
| --- | --- | --- |
| GET | /api/v1/catalog/categories | Departamentos ativos (id, name, slug, parent_id) |
| GET | /api/v1/catalog/offers | Busca, filtro, ordenação, paginação |
| GET | /api/v1/catalog/offers/{offer} | Detalhe público de oferta elegível |

Parâmetros de /offers: q (até 100 caracteres), category (slug), seller (ULID público), min_price_cents e max_price_cents (inteiros, BRL), sort = newest|recent|price_asc|price_desc|name_asc, per_page = 1..24, page = 1..10000. Retorno: data[] com id, name, slug, category {name,slug}, seller {id,name}, price_cents, currency, available, url; meta {current_page,last_page,per_page,total}; links {next,prev}. Detalhe inclui description. Rejeições de entrada: HTTP 422. Oferta inelegível: HTTP 404.

Busca textual trata %, _ e ! como caracteres literais usando parâmetros bindados e ESCAPE. O campo seller é um filtro restritivo, não substitui o gate de visibilidade.

O servidor só expõe oferta quando vendedor = active, produto/oferta = approved, categoria ativa e saldo > reserva. Não há preço, vendedor ou disponibilidade autoritativos no JavaScript.

## Carrinho persistido por comprador (JSON, sessão web autenticada e e-mail verificado)

| Método | URL | Payload |
| --- | --- | --- |
| GET | /minha-conta/carrinho | — |
| PUT | /minha-conta/carrinho/{offer} | {"quantity": 1..20} |
| DELETE | /minha-conta/carrinho/{offer} | — |

O carrinho devolve items[] (offer_id, quantity, available, offer ou null, line_total_cents ou null), subtotal_cents e checkout_enabled=false. O servidor consulta preço atual; parâmetros de preço e seller enviados pelo cliente não são aceitos como fonte de verdade. Linha que perdeu elegibilidade permanece marcada indisponível, sem dados privados. O PUT é absoluto e idempotente. **Não reserva estoque e não gera pedido.**

## Favoritos persistidos por comprador (JSON, sessão web autenticada e e-mail verificado)

| Método | URL | Finalidade |
| --- | --- | --- |
| GET | /minha-conta/favoritos | Somente ofertas públicas atuais |
| PUT | /minha-conta/favoritos/{offer} | Salvar |
| DELETE | /minha-conta/favoritos/{offer} | Remover |

A lista filtra ofertas que perderam visibilidade, sem vazar dados de outro vendedor.

## Orientação ao frontend FE-02

- Entrega 1 possui header/brand/layout e catálogo Blade, mas busca é apenas texto 'Em breve'; integrar o form de busca à API/catalog query sem criar mocks comerciais.
- Consumir IDs reais de offer para produto/carrinho/favoritos. CSRF para PUT/DELETE via meta csrf-token, same-origin e sessão, sem expor tokens em URL.
- Mostrar estado 401/403 (login/verificação), 404 (produto saiu do ar), 422 (quantidade/escolha inválida) e linha indisponível.
- Não apresentar botão de pagar: checkout_enabled está sempre false.
- CEP e cotação de frete permanecem bloqueados: provedor de frete e regras de entrega por empresa ainda não foram implementados.
- Não mesclar diretamente o backend no branch do frontend; integrar por PR para main após CI e coordenação visual.

## Estrutura do banco

- cart_items: chave composta (user_id, offer_id), quantity, timestamps.
- wishlist_items: chave composta (user_id, offer_id), timestamps.
- FKs impedem itens órfãos. Nenhum dado de cartão/PSP.
- Em cada operação de checkout futuro, revalidar preço, elegibilidade e saldo sob transação; o carrinho não garante reserva.

## Próximas entregas

BE-03: endereços (CEP com validação, lookup externo com fallback), cotação por vendedor, estoque com reserva transacional e pedido agrupado/subpedidos. BE-04: PSP/OAuth 1:1 e reconciliação. Checkout fechado até gates fiscais, LGPD e pagamentos.

