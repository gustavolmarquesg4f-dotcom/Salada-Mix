# Experiência Salada Mix v2 — consolidação antes da homologação

## Decisão visual
A home aprovada usa a identidade existente do Salada Mix (símbolo de quatro blocos e wordmark), com jade/verde profundo, coral, menta e branco. A aplicação não troca a marca por mascote ou símbolo alternativo.

## Mudança de arquitetura da HML
A raiz `/` passa a servir a home Laravel dinâmica ligada ao banco. A FE-06 antiga continua em `/fe06/` somente como referência histórica. `/loja` continua apontando para o catálogo dinâmico para compatibilidade.

## Experiência
- Header de marketplace com busca prioritária, conta, favoritos, sacola e CTA de vendedor.
- Barra de confiança com linguagem compatível com o estágio de homologação.
- Hero responsivo com mosaico de departamentos, CTAs e conteúdo sem promessas comerciais inventadas.
- Todos os 10 departamentos do seeder possuem referência visual na HML.
- Categorias viram carrossel horizontal com scroll-snap em telas pequenas.
- Cards de produto, busca, detalhe, conta, sacola e checkout recebem acabamento visual comum.
- Navegação móvel fixa com início, busca, favoritos, sacola e conta.
- Portal de vendedor e moderação/admin seguem a identidade Salada Mix; removido o visual violeta inconsistente.
- Preço no cadastro do vendedor é informado em reais e convertido no servidor para centavos.

## Segurança e limites
A HML continua com checkout comercial desligado e integrações SANDBOX identificadas. Nenhum texto da home afirma frete grátis, prazo de entrega, desconto, avaliação ou condição financeira que não tenha origem em dados reais.

## Gate
Para merge: testes PHP/MySQL/MariaDB, build frontend, Sonar e smoke HTTP da Hostinger. O smoke da raiz precisa encontrar a home v2 dinâmica, catálogo DEMO e navegação principal; o histórico FE-06 também deve continuar acessível.
