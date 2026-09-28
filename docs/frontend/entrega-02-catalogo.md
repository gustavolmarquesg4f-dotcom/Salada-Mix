# FE-02 — Navegação e catálogo real (stacked sobre FE-01)

## Escopo
- Formulário GET /buscar no cabeçalho: busca por nome/descrição do produto e nome comercial do vendedor.
- Navegação de departamentos com links dinâmicos, provenientes das categorias ativas.
- Filtros GET de departamento, vendedor público, preço mínimo/máximo em BRL sem float e ordenação.
- Listagem paginada com query string preservada, estados vazios e página de oferta real.
- Consulta exclusivamente por PublicCatalog::visibleOffers(): nunca exibe empresa inativa, produto/oferta não aprovado, categoria inativa ou estoque sem disponibilidade.
- Escape explícito para %, _ e ! em consultas LIKE parametrizadas.
- Consulta ao MySQL no servidor; não injeta JSON de tabelas privadas no frontend.
- Sem novas dependências, migrations, imagens fictícias ou integrações financeiras.

## Dependência entre PRs
Esta branch parte da cabeça de FE-01; abrir PR de FE-02 sobre feature/frontend-marketplace. Após merge de FE-01 em main, alterar base do PR de FE-02 para main.

## Contratos
- GET /buscar?q=&category=<slug>&seller=<ulid>&min_price=10,50&max_price=150&sort=recent|price_asc|price_desc&page=2
- GET /categorias/{category:slug}?q=&seller=&min_price=&max_price=&sort=&page=
- GET /ofertas/{offer} — sem checkout
- Produto e vendedor apenas se habilitados no domínio de catálogo.

## Fora do escopo
Cálculo de CEP, carrinho, checkout, imagens de produto, favoritos, notas fiscais, avaliações e split. Busca textual usa LIKE e poderá ser evoluída com índice especializado caso o volume exija.

## Aceite
Executar php artisan test, php artisan route:list e npm run build em CI MySQL 8.4. Conferir desktop/mobile em homologação Laravel. Checkout deve permanecer indisponível.
