# BE-03A — Endereços, origens de envio e preparação de pedidos

Entrega incremental sobre o Laravel existente. Nenhum pagamento, cotação, reserva ou pedido é criado por esta API.

## Rotas autenticadas + e-mail verificado + CSRF

Comprador:
- GET /minha-conta/enderecos — lista apenas endereços próprios.
- POST /minha-conta/enderecos — cria (até 10); o primeiro é principal.
- DELETE /minha-conta/enderecos/{address} — escopo por user_id, 404 quando é de outro comprador.
- GET /minha-conta/resumo-compra — HTML ou JSON conforme Accept; agrupa sacola real por seller_id.

Vendedor:
- GET /vendedor/{seller}/origens — leitura por membro da empresa.
- POST /vendedor/{seller}/origens — somente owner/manager, até 5 origens.
- DELETE /vendedor/{seller}/origens/{origin} — somente owner/manager e seller_id correspondente.

CEP é validado quanto a formato (8 dígitos), armazenado normalizado e não equivale à validação de endereço ou cotação. A UF deve pertencer à lista de unidades federativas brasileiras. O usuário informa o endereço e é responsável pela conferência até integração futura.

## Pré-checkout seguro

O resumo lê o carrinho persistido via CartManager. Produtos inelegíveis não expõem preço ou vendedor. O frontend vê itens agrupados por empresa e subtotal com preço atual do servidor. O frete retorna status origin_not_configured ou provider_not_configured, sempre amount_cents=null. Retorna checkout_enabled=false, grand_total_cents=null. Nunca simular valor, prazo, pedido ou pagamento.

## Preparação do esquema

Migrations criam orders, suborders, suborder_items e stock_reservations para implantação futura, com chaves compostas seller_id que impedem associar oferta da empresa B ao subpedido da A. Nenhuma rota grava nessas tabelas neste incremento.

## Para concluir BE-03B

Integrar provedor real de frete, medidas e peso dos produtos, embalagem, expiração de cotações; definir snapshot do endereço; criar pedido/subpedido em transação com lock do estoque e expiração idempotente. Só então BE-04 habilitará pagamentos após contrato e homologação. Não modificar MARKETPLACE_CHECKOUT_ENABLED para true.
