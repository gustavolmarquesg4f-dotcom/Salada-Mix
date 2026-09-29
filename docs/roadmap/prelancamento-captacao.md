# Salada Mix 2.0 — Plano de pré-lançamento e captação

Status: proposta de execução registrada em 29/09/2026. Este arquivo é planejamento, não publicação do site nem autorização para transações comerciais.

## 1. Decisão de produto

Lançar a presença pública antes da abertura comercial, com duas experiências distintas:

- Site público de pré-lançamento: marca, proposta de valor, departamentos, perguntas frequentes, captação de interessados e canal de contato. Sem catálogo fictício apresentado como oferta vendável; sem preço, desconto, prazo, avaliações ou promessa não verificada.
- Homologação privada do marketplace Laravel: autenticação, cadastro empresarial, catálogo, sacola, reservas técnicas e checkout em desenvolvimento. Sem pagamentos reais e sem dados reais misturados ao catálogo DEMO.

O objetivo imediato é encontrar, qualificar e acompanhar as primeiras lojas parceiras. A lista de compradores interessados vem depois da captação de lojistas, mas pode compartilhar a estrutura de campanhas e consentimento. Nenhuma função de pré-lançamento depende de concluir frete ou PSP.

O site público usa a identidade visual existente: branco predominante, jade para CTAs, menta de apoio e coral limitado a pequenos destaques. Manter o manual visual FE-03 como referência.

## 2. Públicos e promessas honestas

### Lojista prospectivo
Pergunta: o que é Salada Mix, para quem serve, o que precisa para entrar, como funciona a seleção e qual o próximo passo?

CTA principal: "Quero ser uma loja parceira". Conteúdo: proposta multidepartamentos, processo de interesse → contato → convite → cadastro completo → análise/habilitação. Não afirmar comissões, abrangência de entrega, pagamentos, data de lançamento ou alcance de público antes de decisão e homologação.

### Futuro comprador
Pergunta: como acompanhar o lançamento e descobrir os departamentos?
CTA secundário: "Quero acompanhar o lançamento", condicionado à validação de aviso de privacidade e comunicação. Nenhum carrinho comercial nem oferta fictícia no site público.

### Equipe interna
Precisa de lista de leads protegida, histórico de contato, origem da campanha, filtros, atribuição e transição para onboarding formal.

## 3. Mapa do site público (MVP)

1. / — landing de pré-lançamento, cabeçalho de marca, hero com CTA para lojistas e CTA secundário para interessados, departamentos como inspiração, como funciona, perguntas frequentes e rodapé.
2. /seja-parceiro — benefícios não promissórios, etapas, público-alvo, formulário de interesse e dúvidas de vendedores.
3. /sobre — o que é o marketplace multidepartamentos, proposta e estágio real.
4. /em-breve — formulário de interesse para futuros compradores, opcional na primeira publicação.
5. /contato — canal oficial, sem inventar telefone ou endereço.
6. /privacidade — conteúdo jurídico revisado, responsável e canal de solicitações, antes de ativar formulário com coleta real.

Preservar as rotas existentes de catálogo e login na aplicação de homologação. No modo público pré-lançamento, menus e CTAs não encaminham visitantes para sacola, login ou checkout não operacional. Indicador visível: "Estamos formando nossa rede de lojas. Compras ainda não disponíveis."

## 4. Funil de captação de lojas

Visitante → lead recebido → contato pendente → contatado → qualificado → convidado a cadastrar → cadastro formal enviado → análise → parceria habilitada quando os gates forem concluídos. Outros estados possíveis: recusado, sem interesse, inválido ou solicitado exclusão.

### Formulário de interesse inicial
Obrigatórios: nome, nome da loja/negócio, e-mail, cidade, UF, segmento de atuação, aceite informado do aviso de privacidade.
Opcionais: WhatsApp/telefone, Instagram/site e breve descrição. Marketing e novidades com escolha separada, desmarcada por padrão. Fonte/UTM capturados por campos controlados, sem enviar credenciais ou documento sensível.

Não pedir CNPJ ou documentação no primeiro contato. O fluxo formal de vendedor existente, com usuário verificado e validação do CNPJ, permanece separado e é oferecido apenas após a qualificação.

### Resposta ao envio
Mostrar mensagem neutra de recebimento e próximos passos; não prometer aprovação ou prazo. Enviar confirmação transacional se o serviço de e-mail estiver homologado. Não mostrar lead público nem incluir dados de outros interessados.

## 5. Arquitetura e dados

Usar o Laravel existente, MariaDB/MySQL no ambiente público e uma tabela dedicada a leads, separada de sellers/seller_memberships.

Tabela prevista seller_leads:
- id ULID, kind (seller|buyer), name, business_name, email_normalized, phone nullable, city, state, segment, site_or_social nullable;
- source, campaign, medium, referrer_host opcional e limitados, consent_notice_version, marketing_opt_in, marketing_opt_in_at nullable;
- status, assigned_to nullable, last_contact_at nullable, internal_notes privativas, created_at, updated_at;
- índices para kind/status/created_at e busca por e-mail normalizado; chave de deduplicação apropriada para evitar múltiplos envios na mesma campanha sem apagar histórico operacional.

Criar histórico de atividades (status, ator, data, observação resumida) para trilha de atendimento. Não registrar dados de formulários em logs de aplicação ou analytics. Definir retenção e procedimento de exclusão após validação jurídica.

Rotas propostas:
- GET /seja-parceiro (público);
- POST /interesses/lojista (guest e autenticado, CSRF, throttle e honeypot, validação Laravel);
- GET /em-breve e POST /interesses/comprador apenas quando habilitado;
- GET /admin/interessados, GET /admin/interessados/{id}, PATCH /admin/interessados/{id} (auth, verified, can:review-sellers, admin.mfa);
- ação de convite e vínculo com cadastro formal em entrega posterior; não criar conta automaticamente nem aprovar empresa a partir de lead.

BFF/JSON somente se necessário à interface: autenticação same-origin e autorização das rotas administrativas. Nunca colocar e-mails e telefones em resposta pública.

## 6. Segurança e privacidade

CSRF, validação no servidor, limitação de taxa por IP/contato, campo-armadilha contra bots, deduplicação, limite de tamanho e mensagens de erro que não enumerem leads. CAPTCHA adaptativo se a proteção inicial não for suficiente. Sanitização de texto em HTML e exportação CSV (inclusive prevenção de fórmulas). Admin protegido pelo MFA já existente. Backups, HTTPS e secrets exclusivos do ambiente público.

Antes de abrir captação real: revisar aviso de privacidade, finalidade, retenção, contato de titulares e política de comunicação. Consentimento promocional, quando utilizado, é separado do aviso necessário ao processamento do interesse; incluir descadastro nas comunicações. Nenhuma chave privada no GitHub Pages.

## 7. Painel de aquisição

Visão interna com contadores recebidos/pendentes/contatados/qualificados/convidados, listagem paginada e busca, filtros por segmento/cidade/origem/status, atribuição de responsável, registro de interação e transição controlada de status. Exportação apenas para papel autorizado, com trilha de auditoria e dados mínimos. Nenhuma informação de lead aparece no painel de outra loja.

Indicadores sem metas inventadas:
- visitas únicas por origem (analytics que respeite escolha e aviso aplicável);
- envios válidos e taxa visita → interesse;
- percentual de leads contatados e qualificados;
- convites enviados, cadastros formais iniciados e enviados;
- vendedores aprovados e efetivamente habilitados;
- tempo entre interesse e primeiro contato, e volume de descadastros.

Definir meta numérica somente depois de observar dados reais e capacidade de atendimento.

## 8. Publicação e operação

O endereço definitivo será o domínio já adquirido; não apontar DNS nem alterar e-mail corporativo até revisão do ambiente. GitHub Pages serve somente prévia estática. Formulários reais exigem Laravel com banco, SMTP/serviço transacional, HTTPS, migrations, backups e monitoramento.

Pré-requisitos: PHP e MariaDB compatíveis, caminho público seguro para public do Laravel, APP_ENV=production, APP_DEBUG=false, chave e segredos fora do Git, sessão/cookie/CSRF testados, certificados, e-mail real e fluxo de recuperação, migrations sem limpar tabelas, rollback de release. Não executar MarketplaceDemoSeeder em produção. No modo pré-lançamento, feature flag de captação pública explícita e MARKETPLACE_CHECKOUT_ENABLED=false, MARKETPLACE_ORDER_DRAFTS_ENABLED=false. O pré-flight SSH/DB da Hostinger confirma conexão, não deploy.

Permitir visualização do design no Pages separadamente. O domínio principal deve abrir a landing real e seus formulários apenas após a validação completa. Não usar formulário "de mentirinha" para capturar contatos no Pages.

## 9. Pacotes de entrega integrados (ordem e gates)

### PL-01 — Posicionamento e conteúdo (FE + negócio)
Aprovar mensagem principal, segmentos, nomes de rotas, perguntas frequentes, CTAs e fotos licenciadas. Preparar textos de "pré-lançamento" sem ofertas e benefícios não comprovados.
Aceite: revisão de copy, mobile/desktop, links e nenhuma promessa de compra disponível.

### PL-02 — Página pública de parceiros (FE + BE)
Criar landing /seja-parceiro e formulário validado, migration seller_leads, ação de captação e resposta transacional.
Aceite: envio válido persiste exatamente um lead, duplicidade previsível, falha não perde dados silenciosamente, testes de CSRF/throttle/validação e nenhuma empresa aprovada automaticamente.

### PL-03 — Painel de leads (FE + BE)
Listagem autorizada, filtros, detalhe, histórico, mudança de status, atribuição e auditoria.
Aceite: não-admin negado, MFA exigido, acesso apenas interno, listagem paginada, regressão de isolamento e CSV seguro se exportação implementada.

### PL-04 — Conversão para cadastro formal (FE + BE)
Convite seguro com identificação de lead, fluxo para conta verificada + formulário empresarial já existente; feedback de situação no painel.
Aceite: lead não vira seller aprovado sem revisão; CNPJ continua validado; convite não expõe outros leads.

### PL-05 — Futuros compradores e comunicação (FE + BE)
Lista opcional de interessados, dupla verificação de e-mail quando aplicável, preferências, descadastro e segmentação básica.
Aceite: política aprovada, não enviar marketing a quem não aderiu, e-mails transacionais vs promocionais segregados.

### PL-06 — SEO, conteúdo e medição (FE + integração)
Metadados, sitemap, robots, Open Graph, acessibilidade, analytics sem PII, Search Console e UTMs limitados.
Aceite: indexação de páginas públicas corretas, sem indexar painel/homologação, nenhum identificador pessoal nas URLs de campanha.

### PL-07 — Publicação controlada (DevOps + FE/BE)
Deploy de homologação, smoke dos formulários e e-mails, backups com restauração validada, domínio/HTTPS do site público e monitoramento.
Aceite: canário com dados de teste, rollback definido, sem logs pessoais, checkout desligado, página pública sem produtos fictícios tratáveis como venda.

### PL-08 — Operação comercial de pré-lançamento (negócio + produto)
Definir quem atende os leads, mensagem inicial, registro de retornos, critérios de qualificação e rotina de acompanhamento, com material para abordagem de lojistas.
Aceite: responsável atribuído, caixa de contato monitorada, status atualizado e métricas revisáveis.

PL-01/PL-02 são a prioridade imediata; PL-03 antes da campanha de divulgação. PL-04/PL-05/PL-06 avançam sem bloquear as entregas de logística FE-06+BE-06. PL-07 antecede qualquer formulário público real. PL-08 antecede campanhas em volume.

## 10. Critérios de lançamento de pré-operação

Checklist binário:
- [ ] Identidade, conteúdo e CTAs aprovados.
- [ ] Captura persistente em banco no domínio real; confirmação e erro tratados.
- [ ] Política/aviso de privacidade e contato revisados.
- [ ] Proteção anti-spam, autorização administrativa e MFA testados.
- [ ] E-mail transacional e canal de resposta operacionais.
- [ ] Painel de leads utilizável por pessoa responsável.
- [ ] HTTPS, backups, restauração, monitoramento e rollback testados.
- [ ] Produtos DEMO fora do ambiente público; checkout e pagamentos desligados.
- [ ] Testes PHP/MySQL/MariaDB e build/preview/Sonar aprovados.
- [ ] Smoke real desktop e mobile, com envio de teste e remoção do registro.

Aprovação de pré-lançamento não equivale a aprovação de operação de marketplace. Frete, pagamento, conciliação, devoluções, contratos e gates do docs/runbooks/go-live.md continuam necessários para vender.

## 11. Dependências e decisões registradas

- FE-04 e FE-05 já estão integradas à main.
- FE-06+BE-06 permanece no PR #31, separado da captação; não bloquear lançamento institucional por checkout inacabado.
- Equipe do vendedor permanece no PR #23; pode apoiar o onboarding formal, não é requisito da captura inicial.
- SSH e DB Hostinger possuem pré-validação de conectividade, mas não há afirmação de deploy funcional.
- Informações comerciais (comissão, datas, regiões, PSP e transportadora) ficam "a definir", sem inventar termos.
- Logo, fotos e textos oficiais devem ser aprovados; não divulgar fictícios como reais.

Responsabilidades sugeridas: Produto/negócio aprova mensagem e critérios de lojas; Design/FE publica experiência; BE cuida da captura/CRM; Infra cuida do ambiente; Jurídico revisa privacidade/contratos; Operação atende leads. Uma mesma pessoa pode acumular papéis, mas cada gate tem dono.
