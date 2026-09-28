# Entrega 01 — Fundação visual

Status: implementada na branch feature/frontend-marketplace; validação final depende de CI e revisão do PR.

## Incluído
- Identidade Salada Mix (símbolo geométrico e logo SVG, não uma marca de alimentos).
- Tokens oficiais: jade #12694E, deep #173F34, menta #E8F6EF, coral #FF785F, lima #DDF29A.
- Layout Blade global, componentes header/footer/icon, navegação desktop e mobile sem JavaScript adicional.
- Hierarquia de leitura, responsividade, foco visível, atalho para o conteúdo.
- Home e cards com a mesma linguagem visual, usando categorias e ofertas reais existentes.
- Aviso global explícito de ausência de transações comerciais.
- Testes de renderização da fundação visual.

## Fora do escopo
- Busca funcional, CEP, carrinho, favoritos, pagamentos, media reais de produtos, painel visual completo.
- Qualquer alteração de políticas/escopo de vendedores, migrations ou integração com provedores.
- Novas dependências Node/PHP. A loja continua Laravel + Blade/Livewire + MySQL.

## Integração
Os endereços usados no header/footer se restringem às rotas existentes: home, login,
register, buyer.account, seller.apply, admin.sellers.index e admin.catalog.index.
A busca, o CEP e o carrinho são mostrados como indisponíveis: nenhuma transação é simulada.
O catálogo continua consultando as entidades atuais do servidor, sem mock global.

## Critérios de aceite
1. `php artisan test --filter=FrontendFoundationTest` passa.
2. `npm run build` passa e publica CSS pela entrada Vite existente.
3. Home, login, cadastro, categoria, anúncio, portal do vendedor e admin renderizam.
4. Menu nativo `<details>` é navegável por teclado em tela pequena.
5. Sem links quebrados para funcionalidades ainda não implementadas.
6. Nenhum dado secreto, cartão ou compra real é tratado pelo frontend.

## Próxima etapa
Entrega 02: busca e navegação real com contratos e modelos do catálogo, CEP e carrinho
permanecendo bloqueados até que seus serviços existam.
