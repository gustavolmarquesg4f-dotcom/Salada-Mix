# Catálogo de demonstração local para FE-02

A aplicação Laravel consulta dados de verdade no MySQL. Em um ambiente local novo, a vitrine fica vazia até haver categorias, empresas comercialmente ativas e ofertas aprovadas. Para trabalhar a interface sem abrir pagamentos nem fazer cadastro legal real, há um seeder separado:

\`\`\`bash
php artisan migrate --seed
php artisan db:seed --class=MarketplaceDemoSeeder
php artisan test --filter=MarketplaceDemoSeederTest
\`\`\`

O seeder cria três vendedores marcados DEMO e nove ofertas fictícias, com preços em centavos e estoque inicial. É idempotente; contas técnicas usam e-mails \`.test\` e senhas aleatórias não divulgadas. Os CNPJs são deliberadamente fictícios. O seeder **não** é incluído no \`DatabaseSeeder\` padrão e falha quando \`APP_ENV\` não é local/testing ou quando \`MARKETPLACE_CHECKOUT_ENABLED=true\`.

Não executar em produção, staging ou com dados de clientes reais. Não usar o seeder para simular KYC, aprovação contratual, pagamentos ou logística. A preview do GitHub Pages tem seus próprios dados estáticos e não acessa o MySQL.

Integração FE-02: \`GET /buscar\` e categorias no Laravel poderão mostrar essas ofertas reais localmente; a API \`/api/v1/catalog/offers\` retorna as mesmas entidades públicas a partir da base de elegibilidade.

