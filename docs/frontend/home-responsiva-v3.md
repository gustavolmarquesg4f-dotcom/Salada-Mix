# Home responsiva v3 — gate antes da próxima etapa

A home v3 substitui a composição desktop da v2 mantendo a mesma identidade oficial do Salada Mix.

## Desktop e notebook
- Hero limitado e equilibrado em 44/56.
- Uma imagem principal e três cards laterais empilhados, sem colunas extras que cortem conteúdo.
- Título com escala controlada; CTAs visíveis na primeira dobra.
- Categorias leves e circulares.
- Grade de produtos: 5 colunas em desktop grande, 4 em notebook.

## Tablet
- Hero mantém duas colunas até 860px.
- Abaixo disso, texto e visual são empilhados.
- Categorias reorganizam em cinco colunas; produtos em três.

## Celular
- Header móvel existente + busca própria + dock inferior.
- Hero empilhado, imagem principal com altura limitada.
- Cards secundários em trilho horizontal com scroll-snap.
- Categorias em trilho horizontal.
- Produtos em duas colunas; em telas de até 360px, uma coluna.
- CTAs empilham em até 430px.

## Segurança e conteúdo
Não são exibidos descontos, frete grátis, avaliações, prazo ou pagamento como reais quando esses dados não existem. Conteúdo DEMO continua identificado na HML.

## Gate de deploy
CI valida MySQL, MariaDB 11.8, testes, build e Sonar. O smoke da Hostinger exige `sm-home-v3`, título, departamentos, CTA de vendedor e catálogo DEMO antes de confirmar o release.
