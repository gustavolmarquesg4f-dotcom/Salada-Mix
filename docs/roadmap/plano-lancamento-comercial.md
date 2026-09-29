# Salada Mix 2.0 — Plano mestre de lançamento comercial (v2)

**Decisão do produto, 29/09/2026:** O Salada Mix vai vender. A primeira proposta para aprovação deve demonstrar a experiência completa do marketplace, de ponta a ponta, com frontend, backend e administração funcionando em ambiente controlado. Após homologação, a equipe cadastrará vendedores e produtos reais (fotos, preço, estoque, dimensões e condições) pelo painel administrativo, sem editar código. Só então será liberada a operação comercial.

Este documento SUBSTITUI o plano anterior de "site público apenas para pré-cadastro". A captação de lojistas é uma funcionalidade do marketplace, não um produto paralelo nem um pré-requisito para deixar a solução comercial incompleta. O PR deste documento é planejamento: não liga checkout, não publica produtos nem realiza deploy.

## 1. Resultado final esperado

- **Loja pública:** marca e identidade visual oficiais, departamentos, busca, filtros, fotos, ofertas reais elegíveis, cadastro/login, favoritos, sacola, endereços, frete por vendedor, checkout, pagamento, pedidos e suporte.
- **Portal do vendedor:** inscrição e análise empresarial, gestão do próprio catálogo, imagens, preço, estoque, origem de envio, pedidos próprios, indicadores e equipe autorizada.
- **Administração central:** configurar departamentos e regras, qualificar e aprovar empresas, operar catálogo e publicações, moderação, pedidos, logística, financeiro, suporte, auditoria, equipe e configurações administráveis.
- **Infraestrutura:** homologação isolada de produção, banco, mídias privadas/públicas apropriadas, e-mail, SSL, backups, recuperação, monitoramento, deploy e rollback.
- **Proposta de aprovação:** uma demonstração navegável REAL do Laravel (não só Pages) com dados sintéticos identificados e provedores sandbox/fake explicitamente sinalizados. Apresentação e critérios de aceite por perfil. Sem pagamento real nesta etapa.
- **Carga comercial posterior:** somente depois da homologação e das decisões contratuais, operadores autorizados cadastram conteúdo real pelo admin; a vitrine pública continua bloqueada até a revisão final.

## 2. Gates e ordem de execução

**G0 — Alinhamento da proposta.** Validar fluxo completo, papéis, regras de moderação, meio de pagamento, modelo de frete, responsabilidades e telas. Não inventar taxas, parceiros, datas, comissão ou promessa de entrega.

**G1 — Construção integrada.** Evoluir FE+BE+schema+testes em cada PR vertical. Criar painel admin de catálogo CEDO para não adiar a operação até o fim. Usar dados DEMO somente em local/testing e contas sandbox quando homologadas.

**G2 — Aprovação da proposta completa.** Apresentar home, jornada de comprador, captação/inscrição de vendedores, portal vendedor, admin, upload/media, publicação, pedido, frete, pagamento sandbox e pós-venda; coletar alterações e aceite formal. Uma tela estética isolada não equivale a aprovação do marketplace.

**G3 — Homologação técnica/operacional.** E2E em homologação privada, contrato financeiro/split, frete real, concorrência de estoque, webhook/idempotência, cancelamento, privacidade, permissões, segurança, e-mail, backup/restauração, rollback e testes mobile. Registro de evidências por cenário.

**G4 — Carga e curadoria comercial.** Ambiente comercial preparado, ainda fechado ao público para compras; criar empresas reais com autorizações, categorias, produtos, fotos licenciadas/próprias, SKU/variação, preço, peso/dimensões, saldo e origem de envio pelo admin/portal; revisão de cada anúncio. Nenhum dado DEMO em produção.

**G5 — Liberação comercial gradual.** Verificação final de cotação, pagamento real em valor controlado, conciliação, emissão/rotina fiscal aplicável, logística, atendimento e alertas; habilitação explícita de sellers e checkout e abertura ao público. Monitorar primeiro lote e plano de reversão.

## 3. Arquitetura funcional da proposta

### 3.1 Comprador
Home editorial, departamentos, busca, filtros e ordenação, cards/galeria/detalhe, cadastro/SSO quando configurado, verificação de e-mail, endereço, favoritos e sacola. Checkout dividido por vendedor: cotação/prazo por origem, resumo antes do pagamento, transações/subpedidos conforme contrato do PSP, confirmação a partir de webhook verificável, "Meus pedidos", status de separação/envio/entrega, suporte, cancelamento/devolução conforme política.

Não apresentar frete "grátis", avaliações, descontos, selo de segurança, preço anterior, promessa de entrega ou disponibilidade sem base efetiva.

### 3.2 Vendedor
"Quero vender" na loja pública: explicação, pré-interesse opcional e caminho para conta + solicitação empresarial. Gestão própria após verificação e aprovação: perfil, equipe e permissões, origem de envio, catálogo, imagens, variantes se aprovadas, preço, saldo, pedidos da sua loja e ocorrências. Cada vendedor vê somente seus dados. Aprovação cadastral não implica ativação de pagamento, frete ou anúncio.

### 3.3 Administração central — prioridade arquitetural
Navegação visível e simples, com dashboard, Vendedores, Catálogo, Produtos, Pedidos, Logística, Financeiro, Conteúdo/Campanhas, Leads, Atendimento, Usuários/Perfis e Configurações. Itens indisponíveis por estágio ficam explicitamente bloqueados; não criar menus falsos.

**Operação de catálogo pelo painel, sem código:**
1. Criar/editar/desativar departamentos e categorias; definir ordem, filtros, atributos e políticas por categoria.
2. Criar produto ou importar planilha CSV/XLSX pelo fluxo validado; vincular vendedor aprovado, categoria, SKU, descrição, ficha técnica, variantes quando aplicáveis, peso e medidas.
3. Arrastar/selecionar fotos próprias/licenciadas, ordenar capa e galeria, registrar texto alternativo; validar extensão, MIME real, tamanho e dimensões, gerar derivados otimizados, impedir arquivo executável, armazenar com ACL apropriada.
4. Definir preço em centavos, moeda, preço promocional com vigência e histórico quando aplicável, estoque e origem de envio. Mostrar revisão de campos obrigatórios.
5. Salvar rascunho → pré-visualizar o que o cliente verá → enviar para revisão → aprovar/rejeitar com motivo → publicar/despublicar por ação explícita. Opcional agendamento em fase posterior.
6. Registrar usuário, data, alteração e motivo; reversão/arquivamento sem exclusão silenciosa de histórico; impedir divulgação de ofertas de vendedor suspenso.
7. Alteração em lote de preços/estoque e importação com prévia de erros, arquivo de retorno, idempotência e limites. Não permitir que uma planilha contorne regras de autorização ou moderação.

**Papéis:** administrador central (governança), operador de catálogo (edita, pode ou não publicar conforme permissão), atendimento (leitura restrita), proprietário/gestor da loja (apenas sua empresa), editor da loja (ofertas próprias submetidas à revisão). Aprovação sensível exige MFA administrativo e trilha; não hardcode de permissões de negócio no frontend. O servidor aplica as regras.

**Configuração administrável:** categorias, atributos, banners e páginas institucionais, flags de publicação, regras de aprovação, parâmetros logísticos, comissão contratual por acordo e canais de suporte. Chaves financeiras, credenciais e políticas de segurança são geridas em ambiente/secret manager, nunca em formulário público ou código versionado.

### 3.4 Pedidos e financeiro
Pedido técnico e estoque reservável com invariantes existentes devem ser integrados ao checkout seguro, mas feature flags seguem OFF antes da homologação. Cotação é por vendedor; orçamento expira e revalida endereço/itens. Pagamento segue contrato 1:1 por vendedor/subpedido (sem prometer cobrança 1:N). PSP com contas autorizadas, webhook assinado, idempotência, reconciliação e estados de falha/refundo. Comissões/taxas e responsabilidades fiscais exigem aprovação de negócio/jurídico. Nunca considerar retorno do navegador como prova de pagamento.

## 4. Entregas verticais FE + BE + ADMIN

| ID | Pacote | Frontend | Backend/admin/dados | Critério de aceite |
|---|---|---|---|---|
| LC-00 | Proposta e arquitetura | Mapa completo das telas e navegação; demo coerente | contratos, papéis, regras e critérios documentados | escopo aprovado e nenhuma afirmação comercial inventada |
| LC-01 | Catálogo gerenciável | produtos, galeria e formulário admin | CRUD categoria/produto, mídia segura, preço/estoque, draft/review/publish, auditoria | operador publica item elegível sem editar código; guest não vê rascunhos |
| LC-02 | Vendedores | landing e inscrição, portal claro | lead opcional, seller onboarding/review, status e equipes; convites seguros | autorização por empresa; aprovação comercial separada |
| LC-03 | Comprador | vitrine, busca, conta, sacola, endereço | catálogo vivo, BFF, sessão, ownership, estoque | regressão FE-01 a FE-05 e dados reais de servidor |
| LC-04 | Preparação e logística | entrega por vendedor e seleção de serviço | origem, CEP, peso/dimensões, provedor de cotação, expiração/revalidação | frete real ou falha explícita; sem inventar valor |
| LC-05 | Checkout e estoque | resumo final e estados de confirmação/erro | snapshots, reserva concorrente, idempotência e subpedidos | compra consistente sem duplicidade/oversell |
| LC-06 | Pagamentos | método disponível e retorno de status | PSP sandbox/real com contrato de marketplace, webhook e conciliação | pagamento comprovado no servidor, refund/falha testados |
| LC-07 | Pós-venda | meus pedidos e rastreio/solicitações | status, eventos, expedição, cancelamento, devolução, notificações | isolamento comprador/vendedor, histórico auditável |
| LC-08 | Operação administrativa | dashboard simples, filtros, fila de moderação e suporte | RBAC/MFA, financeiro, relatórios e logs | operação completa sem acesso direto ao banco |
| LC-09 | Homologação completa | roteiro E2E desktop/mobile | testes de integração, concorrência, segurança, privacidade, infra, backups e rollback | aceite formal de cada jornada |
| LC-10 | Catálogo real e go-live | vitrine com fotos/preços reais | carga controlada, revisão e flags, monitoramento e liberação | ao menos um seller real plenamente habilitado; pedidos/pagamentos testados |

LC-01 e LC-02 começam cedo e não esperam LC-06; LC-03 já possui base integrada. LC-04/05/06 são caminho crítico para vender. LC-09 precede LC-10. Cada pacote deve ter PR vertical próprio e não depender apenas de arquivos estáticos.

## 5. Definição concreta da apresentação para aprovação

A demonstração final deve oferecer roteiro com personas sintéticas, sem copiar pessoas reais:
1. Visitante encontra produto, filtra e abre galeria.
2. Comprador cria conta de teste, salva item, cadastra endereço, vê cotações sandbox e percorre checkout; pagamento sandbox, webhooks e resultado exibidos claramente como TESTE.
3. Interessado solicita cadastro de loja; admin recebe, revisa e concede/rejeita com motivo; seller não consegue vender antes dos gates.
4. Seller cadastra produto com imagens DEMO, preço e saldo e solicita publicação; admin modera e publica; vitrine reflete resultado.
5. Comprador gera pedido sandbox; seller visualiza só seu subpedido; admin acompanha status e auditoria.
6. Simular indisponibilidade, falha de frete, falha de pagamento, cancelamento, item suspenso e tentativa de acesso cruzado.
7. Demonstrar versão mobile, acessibilidade, velocidade, erros e estado vazio.

**A aprovação é do produto completo em homologação.** O GitHub Pages é apoio visual e NÃO demonstra persistência, login, estoque, frete ou pagamento reais.

## 6. Carga real posterior à homologação (sem editar código)

**Preparar produção fechada:** banco vazio de DEMO, categorias e configurações reais; SSL, e-mail, backups e administração de acesso testados. Marketplace checkout OFF durante carga.

**Operação sugerida:** administrador cria/convida loja real → valida documentação e contrato → seller registra origem de envio → admin/seller cadastra produtos, fotos, SKU, preço, medidas e estoque → mostra prévia → modera e habilita anúncio → valida frete/PSP por seller → publica na vitrine. A aprovação comercial não é aprovação automática de todas as ofertas. Lotes importados geram relatório de erros, sem publicação cega.

**Checklist do produto publicável:** nome real, categoria, vendedor habilitado, imagem de capa e galeria autorizadas, descrição e atributos, SKU único no escopo, preço válido, estoque coerente, peso e dimensões conforme frete, endereço de origem, política da categoria e revisão concluída. Sem o conjunto, fica como rascunho ou pendente.

## 7. Ambiente, implantação e segurança

- Desenvolvimento/local: DEMO, testes automáticos, provedores fake/sandbox.
- Homologação privada: Laravel+DB, e-mail teste, sandbox de PSP/frete, contas sintéticas; não executar seeder sintético fora de local/testing (se necessário, usar fixtures HML controladas e explicitamente sinalizadas, com governança).
- Produção fechada para carga comercial: contas e dados reais autorizados, sem exposição do catálogo até checagem final; arquivos persistentes e backups.
- Produção aberta: feature flags comerciais ligadas APENAS após gates de lançamento.

O repositório já possui FE-04/FE-05, BE-05 de drafts/reservas, BFF/SSO e pré-checks Hostinger SSH/DB. A FE-06+BE-06 está em PR #31. A equipe de vendedores está no PR #23. Isso NÃO significa deploy Laravel pronto. Revisar capacidade da hospedagem para filas, cron, armazenagem de imagens, e-mail, transações e monitoramento; não confundir Pages com servidor.

Manter MFA admin, segregação por vendedor, CSRF, rate limiting, logs sem dados sensíveis, armazenamento privado de documentos, proteção de arquivos, auditoria, snapshots de pedido, backups externos/restauração e plano de rollback. Políticas de privacidade/termos/devolução e comissões precisam de revisão responsável antes do go-live.

## 8. Decisões pendentes com impacto técnico

- PSP/contrato de marketplace, taxas, split e responsabilidade por estorno.
- Região de atendimento, integração logística, origem por seller, política de frete/troca/devolução.
- Comissão e política de repasse; operação fiscal.
- Quem aprova produto/preço e quem pode publicar: operador central, vendedor ou fluxo híbrido (recomendação de MVP: seller propõe, central aprova/publica).
- Variações SKU (tamanho/cor) e categorias restritas para MVP.
- Conteúdo institucional real, fotos autorizadas, canal de atendimento e política de comunicação.
- Meta de catálogo e lojas para soft launch, baseada em capacidade real, não inventada neste plano.

## 9. Aceite por área e definição de pronto

**Produto/Design:** jornada completa e linguagem mobile/desktop aprovada.
**Frontend:** navegação responsiva, acessibilidade, estados de erro/espera/vazio e nenhum CTA enganoso.
**Backend/Dados:** invariantes de estoque/preço, autorização/tenancy, migração e testes; import/export seguros.
**Operação/Admin:** operador realiza fluxo de cadastro→revisão→publicação→despublicação sem Git/SQL.
**Financeiro/Logística:** contratos e transações sandbox testados; depois testes controlados reais.
**Segurança/Privacidade:** MFA, logs, retenção, documentos, backups/restauração e resposta a incidente.
**Infra:** HML/produção separados, deploy, SSL, filas, cron, observabilidade e rollback.
**Direção do projeto:** aceite registrado e autorização explícita para carga real e, separadamente, para abertura comercial.

## 10. Estado inicial e próximo bloco

- FE-04/05 integradas à main; checkout comercial OFF.
- FE-06 + BE-06 PR #31 (revisar/mesclar após checagens); PR #23 convites de seller.
- **Prioridade imediata: LC-00 + LC-01 (admin de catálogo com foto, preço e publicar) em paralelo à conclusão de FE-06/BE-06 e definição do contrato de frete/PSP.**
- O plano antigo de "site de pré-lançamento apenas para captação" fica expressamente substituído. A landing de lojistas continua no site final, mas não define o escopo do lançamento.
