# Arquitetura Salada Mix 2.0

Decisão: monólito modular Laravel, MySQL por instância, tenant lógico identificado por seller_id. Projeto novo.
As áreas comprador/vendedor/plataforma compartilham o deploy, **não** suas autorizações.

## Limites de domínio
- **Identity:** usuário, confirmação de e-mail, sessões, papéis de plataforma.
- **Seller:** onboarding aberto, revisão manual, vínculo de equipe e isolamento.
- **Catalog/Inventory:** categorias, ofertas, moderação e estoque por vendedor.
- **Orders:** compra agrupadora e subpedidos; snapshots de itens.
- **Payments:** adaptador PSP, eventos autenticados e idempotência; sem custódia.
- **Shipping:** preço e prazo por subpedido, origem por vendedor.
- **Privacy:** minimização, direitos dos titulares, retenção e auditoria.

Controller valida/delega; Action coordena transação; Model persiste; Policy/Gate autoriza; Integração externa não acessa controller diretamente.

## Gates
Cadastro não equivale a aprovação. Aprovação não equivale a habilitação comercial. Checkout permanece desligado até PSP, logística, política comercial, fiscal e LGPD estarem homologados.

Veja [ADR-001](../adr/001-monolito-modular.md), [tenancy](tenancy.md), [checkout](checkout.md) e [deploy](../runbooks/go-live.md).
