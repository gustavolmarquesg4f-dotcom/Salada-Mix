# ADR-002 — Checkout agrupado, cobrança por vendedor

Status: aceito conceitualmente, integração pendente de homologação.
O carrinho poderá reunir várias empresas, mas o MVP não presume split 1:N. Cada subpedido possui uma tentativa e um pagamento independentes.
O resultado da compra agrupadora pode ser parcialmente pago. A transação confirmada depende de webhook autenticado e consulta à API do PSP, não do redirect do navegador.
Os provedores são acessados por contratos internos. O adaptador atual sempre recusa checkout.

