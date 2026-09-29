# Plano de entregas — Salada Mix comercial (FE + BE + Admin)

Decisão vigente: marketplace completo para aprovação e homologação ANTES de inserir catálogo comercial real e abrir vendas. Documento vinculado ao [plano mestre](plano-lancamento-comercial.md) e [backlog LC](backlog-lancamento-comercial.md). As entregas são incrementos internos demonstráveis, não aberturas comerciais parciais.

## Regras fixas de execução

1. **Entrega vertical:** UI desktop/mobile + endpoint/serviço Laravel + banco/migration quando houver + autenticação/autorização + validações + testes + documentação + prévia visual quando cabível. Não fazer uma FE isolada aguardando BE.
2. **Demo e homologação privadas:** dados sintéticos identificados; integrações externas em sandbox contratualmente válido; não representar um mock como pagamento/frete real. O GitHub Pages é apenas apoio visual.
3. **Painel administrativo cedo:** conteúdo e produtos reais depois da homologação serão criados/publicados pela UI, nunca via commit/SQL manual. Operador não deve precisar de desenvolvedor para cadastrar produto.
4. **Gates independentes:** revisão visual/funcional de cada entrega; aprovação integral da proposta ao final da E16; homologação completa na E17; carga real e autorização comercial na E18.
5. Não inventar fornecedor, contrato, taxas, prazo, comissão ou alcance. Integrações reais dependem de escolha e credenciais fornecidas por canais seguros.
6. Toda mudança de estoque/preço, seller e pedido mantém trilha de auditoria e isolamento por empresa. Admin exige MFA; mídia/documentação segura.
7. Pagamento real, checkout e publicação comercial ficam desabilitados até autorização explícita após os gates.

## Bloco A — Base gerenciável (E06–E10)

### E06 — Encerrar preparação de compra [FE-06 + BE-06 existente]
- **Usuário:** seleção de endereço próprio; sacola agrupada por loja; diagnóstico de origem, frete e pagamento ainda indisponíveis.
- **Servidor:** serviço read-only de prontidão e contrato BFF; nenhuma reserva ou pedido.
- **Aceite:** testes verdes, revisão do PR #31 contra main atualizada, aprovação da UI e integração. Não ativar checkout.
- **Demonstração:** comprador troca endereço e visualiza pendências de duas lojas.

### E07 — Fundação do Admin e categorias [LC-01.1/01.2]
- **Admin:** menu/sidebar, dashboard, lista/busca/filtros de categorias e produtos, permissões e estados visíveis; criação/edição/desativação de categoria e ordem de exibição.
- **Servidor:** RBAC/MFA, actions, schema/validação e auditoria; slugs/duplicidade e visibilidade pública consistentes.
- **Aceite:** operador autorizado cria categoria sem Git/SQL; não-admin recebe negação; mudanças aparecem no catálogo público após publicação. Nenhum menu falsamente funcional.

### E08 — Produto, fotos e publicação pelo Admin [LC-01.3–01.6]
- **Admin e seller autorizado:** ficha de produto/oferta com categoria, SKU, nome, descrição, atributos, preço/estoque, peso/dimensões; upload de capa/galeria/alt; prévia; rascunho→revisão→aprovação→publicação/despublicação. Importação planilha validada em subentrega E08B se o volume exigir.
- **Servidor:** persistência, ACL por empresa, armazenamento/transformação segura de imagem, validação de MIME/limites, centavos, integridade/concorrência de estoque, auditoria, moderação.
- **Aceite:** criar, alterar e publicar produto DEMO pela interface; rascunho/suspenso nunca vazam na vitrine; arquivos não executáveis; reverter/despublicar; versão mobile.
- **Demo:** operador sobe imagens, define R$/estoque, visualiza e aprova; vitrine atualiza sem deploy.

### E09 — Vendedor e captação [LC-02]
- **Público:** página "Venda no Salada Mix", apresentação real do processo, formulário de interesse opcional e caminho para conta/cadastro empresarial.
- **Seller/Admin:** inscrição, verificação, análise com motivo, perfil da loja, origem de envio e convites/equipe (revisão do PR #23).
- **Servidor:** status auditáveis, validação CNPJ no fluxo formal, papéis e isolamento; captação separada de seller aprovado.
- **Aceite:** interessado vira inscrição rastreável; admin analisa; empresa não aprovada não publica ofertas nem recebe pagamentos.

### E10 — Vitrine/conta comercial integradas [LC-03]
- **Comprador:** home real com campanhas administráveis, departamentos, busca/filtros, detalhe/galeria, cadastro/login, favoritos, sacola e endereços.
- **Servidor:** indexação elegível a partir dos produtos reais de teste, filtro por seller/publicação/estoque, preço oficial do servidor, endpoints same-origin.
- **Aceite:** produto publicado na E08 aparece e é encontrável; despublicado some; preço não vem do browser; sem DEMO rotulado como venda real.

## Bloco B — Operação transacional (E11–E15)

### E11 — Logística e cotação [LC-04]
- **Comprador/seller/admin:** endereço, origem por loja, escolha/resultado da cotação e falhas legíveis.
- **Servidor:** peso/dimensões válidos, contrato com provedor escolhido, cotação por subpedido, prazo/preço persistidos com validade, recálculo por mudança de sacola/endereço e tratamento de indisponibilidade.
- **Aceite:** um carrinho com duas lojas apresenta duas cotações; sem CEP/dimensões/origem não simular tarifa; falha externa segura e explícita.

### E12 — Checkout e pedidos técnicos [LC-05]
- **Comprador:** revisão final, frete, endereços, total, estados de processamento/expiração; confirmação indisponível até integrar pagamento.
- **Servidor:** BE-05 de reserva e snapshot ligado a cotações válidas, idempotência, transações por vendedor, expiração/cancelamento e concorrência de estoque.
- **Aceite:** duas solicitações simultâneas não vendem além do saldo; mudança de preço/estoque invalida ou recalcula; repetição não duplica subpedido.

### E13 — Pagamento integrado [LC-06]
- **Comprador:** método permitido, resultado pendente/aprovado/recusado, recibo/status.
- **Servidor:** PSP de marketplace com contrato e onboarding, sandbox, transação conforme subpedido, webhook assinado, idempotência, reconciliação, falha/estorno e não confiar no retorno do navegador.
- **Aceite:** cenário sandbox aprovado, recusado, callback repetido e fora de ordem, expiração, refund e divergência conciliados. Pagamento real permanece OFF.

### E14 — Pedidos e pós-venda [LC-07]
- **Comprador:** "Meus pedidos", detalhe, status, envio, cancelamento/solicitações quando aplicáveis.
- **Seller:** fila e acompanhamento apenas de seus subpedidos, separação, expedição e dados necessários à entrega.
- **Admin/backend:** eventos e transições validadas, notificações, suporte, histórico e isolamento.
- **Aceite:** comprador, duas lojas e admin veem apenas dados apropriados; status não pode pular etapas; tratamento de devolução/estorno definido.

### E15 — Administração operacional completa [LC-08]
- **Admin:** painel único de empresas, catálogo, pedidos, suporte, logística, financeiro/conciliação, banners/conteúdo, permissões, indicadores e auditoria.
- **Servidor:** políticas por papel, filas, filtros paginados, exportação segura, histórico/monitoramento.
- **Aceite:** operador cadastra e publica produtos, modera vendedores, acompanha pedidos e financeiro sem SQL/Git; ações sensíveis auditadas e MFA exigido.

## Bloco C — Aprovação, homologação e lançamento (E16–E18)

### E16 — PRIMEIRA proposta integral para aprovação [LC-09.1]
- Demo no Laravel funcional e ambiente controlado com contas sintéticas por perfil, produto/fotos de teste e provedores sandbox: jornada de comprador, inscrição de seller, cadastro/publicação pelo admin, frete, pedido, pagamento sandbox e pós-venda.
- Apresentar também os casos de falha: produto indisponível, seller suspenso, frete não cotado, pagamento recusado, cancelamento e acesso cruzado.
- Entregáveis: link privado da demonstração, roteiro por perfil, decisões de negócio pendentes, checklist visual/mobile, manual inicial do admin e registro de aprovação/ajustes.
- **Gate:** aprovação funcional/visual da proposta inteira. Não é autorização de venda real.

### E17 — Homologação completa [LC-09.2]
- Exercitar cenários E2E, concorrência real no banco, PSP/frete conforme contratos, e-mail e notificações, privacidade, MFA/permissões, upload seguro, acessibilidade e mobile; infraestrutura, restore de backup, rollback, monitoramento e suporte.
- Registrar evidências, corrigir falhas e executar regressão antes de aprovar.
- **Gate:** aceite de homologação + políticas/contratos/comissão/devolução e rotinas fiscais validados. Nenhuma carga comercial antecipada.

### E18 — Catálogo real e abertura comercial [LC-10]
- Preparar produção sem DEMO e com checkout OFF; administrar categorias, vendedores/contratos, fotos próprias/licenciadas, produtos/SKU, preços, saldos, peso/dimensões e origem de cada seller na UI. Importação em lote controlada, prévia, moderação e publicação.
- Verificar integração de cada loja com PSP/frete, políticas, e-mail, atendimento, backups e primeira compra real controlada com conciliação e expedição.
- **Gate:** autorização explícita para abertura gradual; monitorar pedidos iniciais e manter rollback operacional. Abertura não acontece automaticamente por merge.

## Dependências e trilhas paralelas

**Trilha A (valor visível cedo):** E07 → E08 → E09/E10. E07/E08 vêm antes de frete, pois fotos, SKU, peso, dimensões, preço e estoque precisam ser administráveis.

**Trilha B (caminho crítico da venda):** E06 → E11 → E12 → E13 → E14. O contrato do PSP e do frete deve ser decidido no início, não após criar telas.

**Trilha C (governança):** E15 cresce junto com E08–E14. A consolidação final é uma entrega própria para evitar painel só no encerramento.

**Convergência:** A+B+C → E16 (aprovação integral) → E17 (homologação) → E18 (catálogo real/go-live).

## Definition of Done de TODA entrega técnica

- Interface desktop/mobile e estados acessíveis de loading/erro/vazio; sem CTA enganoso.
- Endpoint/serviço Laravel integrado à mesma jornada, banco/migration compatível MariaDB e MySQL quando aplicável.
- Validação backend, autenticação/autorização, isolamento por seller, CSRF, rate limit e auditoria quando pertinente.
- Testes unitários/feature/integração e casos negativos; smoke da prévia se houver.
- CI/testes/build/Sonar aprovado; documentação do contrato e decisão de negócio.
- Demonstrável em ambiente apropriado. Sem secrets no Git nem dado pessoal em preview estática.
- PR revisado; merge só após o gate. Produção e homologação são ambientes diferentes.

## Estado do projeto na elaboração deste plano

FE-04 e FE-05 integradas na main; BE-05 já fornece rascunhos técnicos/reservas atrás de feature flag; FE-06+BE-06 no PR #31 e equipe de seller no PR #23. O PR #33 guarda o plano. Esses ativos serão aproveitados, não reescritos. Não afirmar deploy funcional apenas por checks SSH/DB verdes.
