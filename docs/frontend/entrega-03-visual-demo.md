# FE-03 — Direção comercial no código (fotos e dados DEMO)

- Views Laravel/Blade reais para Home, busca, cards e produto, não somente screenshots.
- Regra cromática: branco dominante, jade de marca, coral discreto, lima pontual. Sem mascote de tigela no cabeçalho.
- Em local/testing, após rodar MarketplaceDemoSeeder, nove ofertas sintéticas podem receber fotografias ilustrativas de Unsplash.
- Fotos mapeadas somente por slug DEMO explícito em App\Support\DemoMedia; nunca atribuídas automaticamente aos vendedores reais.
- Os produtos continuam no MySQL local com as mesmas regras de publicação e estoque.
- A demonstração não inventa avaliações, descontos, parcelamentos ou benefícios comerciais. Checkout permanece desligado.
- Preview do Pages é estática e não se conecta ao banco Laravel.
- Imagens ilustrativas externas dependem de terceiros e conexão. Substituir por mídia licenciada e hospedada antes da produção.
- Executar: php artisan migrate --seed; php artisan db:seed --class=MarketplaceDemoSeeder; npm ci; npm run build; php artisan test --filter=FrontendVisualDemoTest.
