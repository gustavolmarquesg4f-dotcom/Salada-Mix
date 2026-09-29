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



## HML isolada — visual FE-06 e catálogo funcional

A URL raiz continua apresentando a prévia visual FE-06; `/loja` é a vitrine Laravel ligada ao banco e `/buscar` pesquisa os mesmos registros. A publicação de homologação executa `MarketplaceDemoSeeder` **depois do backup e das migrações**, somente se:

- APP_ENV é `staging` e APP_URL possui o host `ivory-rook-276202.hostingersite.com`;
- checkout, drafts comerciais e pagamentos estão desativados;
- a base não contém empresas não sintéticas, produtos não sintéticos ou pedidos existentes.

A carga é idempotente e cria três lojas técnicas com e-mails `.test`, nove ofertas e estoque demonstrativo. Não há credenciais públicas padrão. Fotos ilustrativas somente neste domínio e local/testing; a rota real permanece bloqueada para compras. **Não rodar em produção ou em base com dados reais.** O deploy recusa a carga ao identificar mistura e não deve ser contornado.

Critérios verificados no deploy: `/loja` deve exibir Fone Bluetooth (DEMO) e imagens, e `/buscar?q=vitamina` deve localizar Sérum (DEMO), além das provas existentes da FE-06. Se falhar, ocorre rollback do código; o backup do banco permanece disponível.
