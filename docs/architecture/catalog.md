# Catálogo, ofertas e estoque — fase backend/database

- categories: taxonomia pública administrada pela plataforma; seed sem dados pessoais.
- products: dados comuns e status de revisão; criado por vendedor inicialmente, passível de consolidação futura.
- seller_offers: preço e SKU isolados por seller; revisão própria; BRL, centavos inteiros.
- stock_levels: saldo e reserva por oferta, com FK composta para impedir vínculo cruzado seller/offer.
- inventory_movements: registro da quantidade inicial; ajustes e reserva transacional são uma entrega separada.

## Invariantes

1. Somente owner/manager de empresa aprovada ou ativa pode propor uma oferta.
2. Controller nunca aceita seller_id do formulário como fonte de autorização.
3. Produto e oferta são criados na mesma transação, com estoque e evento de auditoria.
4. Apenas admin decide moderação. Produto e oferta precisam estar aprovados.
5. Vitrine exige vendedor **active**, categoria ativa, produto e oferta aprovados e disponibilidade.
6. Aprovação de empresa e aprovação de oferta **não ativam venda** nem conectam o PSP.
7. Não há upload de mídias, variação de SKU, cálculo de frete nem checkout nesta etapa.
8. Não há contas de teste em seeder de produção.

