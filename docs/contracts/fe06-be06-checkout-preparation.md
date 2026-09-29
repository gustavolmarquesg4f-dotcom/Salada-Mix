# FE-06 + BE-06 — Preparação de checkout, endereço e prontidão logística

## Uma entrega vertical

Esta etapa integra a tela real de comprador, serviço de domínio e contrato BFF no Laravel. A lógica de preparação é **somente leitura**. A FE-06 não chama `POST /bff/v1/orders/drafts` nem ativa reservas. A BE-06 desta fase não é uma cotação de transportadora.

## Contratos
- `GET /minha-conta/preparar-compra?address=<ULID>`: Blade server-rendered, autenticado e e-mail verificado.
- `GET /bff/v1/checkout/preparation?address=<ULID>`: JSON com addresses do usuário, selected_address, preview e readiness.
- O parâmetro address é opcional; sem ele o primeiro endereço padrão é selecionado. ID de outra conta responde 404; dado inválido 422 em JSON.
- Os dados por vendedor são derivados de `CheckoutPreview` e `CartManager`, sem duplicar cálculo de preço/estoque.

## Regras de segurança
- Lista de endereços restrita a user_id. Selecionar endereço só altera a visualização da requisição.
- Anúncio que perdeu elegibilidade não expõe nome de produto/empresa.
- O status de origem do vendedor é somente um indicador de prontidão, sem vazar endereço do depósito.
- Nenhum endpoint desta etapa grava pedidos, subpedidos, reservas ou pagamentos.
- Valores em centavos vêm do servidor. Frete, prazo e total final são `null`, não R$ 0.
- `readiness.can_place_order` fica sempre false até homologação separada de provedor de frete, PSP, reconciliação e go-live.
- Dados de demonstração FE-03 (fotos, vendedores e preços) somente em local/testing. Não injetar fotos fictícias em anúncios reais.

## Próxima fatia BE-06B + FE-06B
Contrato com provedor de frete por vendedor: CEP de origem/destino, peso/dimensões reais, cotações com expiração, assinatura/consistência do orçamento e testes de mudança de endereço/estoque. Nenhuma tarifa estimada deve ser apresentada como real.

## Aceite
- `php artisan test --filter=CheckoutPreparationTest`
- `php artisan test`
- `npm run build`
- `node preview/tests/smoke.cjs`
- Revisão visual desktop/mobile e homologação dos dados de endereço em ambiente privado.
