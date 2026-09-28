# BE-05 — Rascunhos técnicos e reservas de estoque

Este módulo prepara pedidos técnicos e subpedidos por vendedor. Não aceita pagamento, não calcula frete, não emite pedidos pagos e não altera a URL estática do GitHub Pages.

## Feature flag e API

MARKETPLACE_ORDER_DRAFTS_ENABLED=false por padrão. Ativar apenas para homologação e teste de reservas. MARKETPLACE_DRAFT_RESERVATION_MINUTES=15, limitado no serviço a 1–60.

| Verbo | Caminho BFF same-origin | Comportamento |
| --- | --- | --- |
| GET | /bff/v1/orders/drafts | Listar rascunhos próprios, até 20 por página |
| POST | /bff/v1/orders/drafts | address_id ULID próprio e idempotency_key aleatória de 16–64 caracteres |
| GET | /bff/v1/orders/drafts/{order} | Ler snapshots apenas do comprador |
| DELETE | /bff/v1/orders/drafts/{order} | Cancelar, liberar holds idempotentemente |
| GET | /bff/v1/sellers/{seller}/order-reservations | Metadados da própria empresa sem endereço/telefone do comprador |
| GET | /bff/v1/admin/order-drafts | Contagem agregada, exige MFA |

Autenticação por sessão web com CSRF, e-mail verificado e throttle. Idempotency key duplicada retorna o mesmo pedido. No máximo um rascunho ativo por comprador. A nova reserva não remove o carrinho. Linhas indisponíveis impedem criar o pedido e a transação é revertida.

## Regras de persistência

- Order: reserved → cancelled ou expired. Suborders e stock_reservations acompanham a transição. Estados de pagamento não são criados neste módulo.
- Preços em centavos, nome/SKU e endereço são capturados do servidor na transação. A resposta do vendedor não contém PII do comprador.
- Cada offer tem stock_level com lock FOR UPDATE; update condicional exige saldo livre e migration impõe CHECK quantidade reservada <= estoque físico no MySQL 8.4.
- Cada alteração cria evento imutável de pedido e trilha de auditoria; repetir cancelamento/expiração não libera estoque duas vezes.
- Shipping e total financeiro permanecem NULL; checkout_enabled e payments_enabled permanecem false.

## Agendamento VPS

Registrar cron do Laravel a cada minuto (diretório exemplificativo):

    * * * * * cd /var/www/salada-mix && php artisan schedule:run >> /dev/null 2>&1

Comando de manutenção manual: php artisan marketplace:expire-reservations --limit=50. O agendador usa withoutOverlapping. Alertar caso haja ordens reserved com expires_at no passado por muito tempo. Se o worker falhar, o próximo draft do mesmo comprador libera a reserva anterior expirada.

## Segurança antes do go-live

Não habilitar reservas para clientes em produção até homologar frete, idempotência/assinatura de PSP, reconciliação, política de cancelamento, conversão de hold em baixa física, proteção contra abuso, testes multiprocessados MySQL e recuperação de worker. Os testes deste PR simulam compradores concorrentes de forma sequencial e verificam a condição atômica; não substituem teste de carga com duas transações reais.

Próximas fases: BE-06 (frete por vendedor e cotação persistida) e BE-07 (pagamento 1:1 com webhook/reconciliação).
