# Salada Mix — Manual de Identidade Visual Digital v1.1 / Entrega FE-03

**Status:** proposta visual para homologação; identidade já aplicada ao código do PR #20. Esta especificação complementa e atualiza o manual v1.0. Em caso de divergência cromática, prevalecem as regras abaixo.

## Fundamento da identidade

Salada Mix significa **variedade de departamentos**, não saladas ou alimentação. O conceito da marca combina um símbolo geométrico de blocos complementares e o nome Salada Mix. O público editorial prioritário são mulheres, mas navegação, linguagem, fotografia e arquitetura comercial são inclusivas de outros públicos. Evitar tigela/folhas/vegetais e rosa dominante.

A cor não substitui a experiência: buscamos diferenciação, consistência, boa hierarquia e contraste. **Nenhuma cor isolada garante conversão, confiança ou comportamento de compra**. A eficácia deve ser validada por pesquisa com usuários, testes de usabilidade, dados de busca e experimentos A/B quando houver tráfego suficiente.

## Paleta e lógica por componente

| Token | HEX | Função estratégica | Aplicação |
|---|---|---|---|
| Jade | #12694E | Assinatura reconhecível | Marca, CTA principal, botão buscar, links e seleção |
| Verde profundo | #173F34 | Estrutura, contraste, densidade visual | Barra superior, títulos de destaque, rodapé |
| Branco | #FFFFFF | Valorizar fotos e produtos heterogêneos | Fundo predominante, cards, lista, checkout |
| Menta | #E8F6EF | Separar conteúdo sem ruído comercial | Faixa secundária, contexto, estados de informação |
| Coral | #FF785F | Contraponto pontual e calor da marca | Selo promocional verificado, campanha, detalhe |
| Lima | #DDF29A | Personalidade e composição | Detalhe gráfico e pequenos destaques |
| Texto carvão | #202B27 | Legibilidade em fundo claro | Texto, preço, descrição |
| Cinza névoa | #F3F5F4 | Superfícies auxiliares | Fundo alternativo, separadores |

**Distribuição visual indicativa, não fórmula:** 70% branco e superfícies muito claras; 20% jade e verde profundo; até 7% coral; até 3% lima. Menta integra as superfícies claras. Evitar telas totalmente verdes ou excesso simultâneo de coral+lima. As fotografias têm protagonismo; o layout não deve competir com elas.

## Justificativa das escolhas

- **Jade:** cria reconhecimento e permite distinguir o Salada Mix de concorrentes. Também torna CTA e busca consistentes em categorias diferentes. Não deve ser associado automaticamente a natureza/saúde.
- **Verde profundo:** contraste e hierarquia em pequenas áreas; não cobrir a página inteira.
- **Branco/neutros:** amplitude para produtos femininos, tecnologia, casa, infantil e outros; preços e fotos são mais fáceis de comparar.
- **Menta:** descanso visual, apoio a informação secundária; nunca substituir indicador de status textual.
- **Coral:** acento comercial e proximidade sem feminilizar toda a loja. Não inventar descontos ou frete.
- **Lima:** traço lúdico controlado do símbolo de mistura; preferir detalhes, não texto corrido ou botão principal.

## Acessibilidade

Contraste estimado WCAG usando os HEX especificados:
- Branco sobre jade: **6,65:1**.
- Branco sobre verde profundo: **11,69:1**.
- Texto carvão #192E28 sobre coral: **5,54:1**.
- Branco sobre coral: **2,59:1**, **não usar como texto normal**.
- Verde profundo sobre menta: **10,50:1**.
- Texto carvão sobre lima: **11,79:1**.

Usar texto escuro em coral/lima; branco em jade e verde profundo. Garantir foco visível, estados que não dependam só de cor, toque acessível, contraste validado nas imagens reais e teste em desktop/mobile. Conferir WCAG 2.2 em cada componente final.

## Aplicação ao FE-03

Aplicação real: `resources/css/salada-visual-demo.css`, `resources/views/storefront/home.blade.php`, `resources/views/storefront/_offer-card.blade.php`, `resources/views/storefront/offer.blade.php`, `app/Support/DemoMedia.php`; espelho estático de homologação em `preview/`.

A mídia de demonstração é vinculada **somente a slugs DEMO explícitos**, disponível apenas em local/testing com checkout desligado. Produtos reais sem foto devem usar fallback honesto. A prévia usa preços/vendedores sintéticos, não avaliações, descontos, frete gratuito, parcelamento ou entregas inventadas. Substituir fotos de terceiros por imagens licenciadas e hospedadas no serviço definido para produção.

## Critérios de aprovação

1. Reconhecimento da marca em desktop/mobile sem associação com alimentação.
2. Home com busca dominante, navegação por departamentos, fotografia e preços legíveis.
3. Contraste e estados acessíveis.
4. Card, busca e detalhe visuais consistentes com a mesma paleta.
5. Ofertas fictícias identificadas e checkout não habilitado.
6. Revisão de fotógrafo/imagens/licenças e textos antes da operação comercial.
7. Acompanhamento de conversão e usabilidade após entrada em produção, sem atribuir causalidade à cor isolada.

**Manual completo diagramado:** entregar PDF v1.1 juntamente com PR FE-03.