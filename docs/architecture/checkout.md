# Checkout planejado — bloqueado neste scaffold

Cliente pode agrupar itens de várias empresas, mas o MVP prevê um subpedido e uma transação **por vendedor**. Não anunciar pagamento único 1:N sem contrato e testes do PSP.

Fluxo: validar itens e vendedor ativo -> bloquear estoque e registrar snapshot -> gerar transação idempotente -> verificar webhook + consultar PSP -> confirmar subpedido -> conciliar.
Reentrega e chegada fora de ordem de eventos não podem duplicar efeito. Aprovação tardia depois da expiração de reserva exige exceção tratada; nunca recriar estoque automaticamente.

Credenciais Mercado Pago e tokens OAuth são segredos do servidor; não comitar, logar ou renderizar em HTML. MARKETPLACE_CHECKOUT_ENABLED inicia false.
