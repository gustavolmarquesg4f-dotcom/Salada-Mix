# Backlog executável — Pré-lançamento Salada Mix

Este quadro é sequência de execução, não cronograma com datas inventadas. Cada cartão deve gerar PR vertical com frontend, backend, migração/contrato, testes e revisão. Origem: docs/roadmap/prelancamento-captacao.md.

| ID | Entrega | Dependência | Evidência de aceite |
| --- | --- | --- | --- |
| PL-01 | Home pré-lançamento + página Seja Parceiro | copy e marca | conteúdo real, CTAs funcionais, responsivo e sem "comprar agora" |
| PL-02 | Seller Lead API + formulário real | PL-01, DB, aviso revisado | validação, rate limit, honeypot, dedup, CSRF, persistência, confirmação |
| PL-03 | Admin pipeline de leads | PL-02 | MFA, ACL, lista/filtro/etapas, trilha, nenhum PII público |
| PL-04 | Convite → cadastro formal | PL-03 | vínculo lead-seller, CNPJ validado, aprovação independente |
| PL-05 | Lista de compradores | PL-02, aviso e email | opt-in separado, verificação, descadastro, sem spam |
| PL-06 | SEO/analytics e conteúdo | PL-01 | sitemap, meta/OG, métricas por fonte, sem PII |
| PL-07 | Deploy/monitoramento/backup | PL-02/03 e ambiente | URL real, SSL, SMTP, DB, rollback, restauração, checkout off |
| PL-08 | Rotina de captação e kit lojista | PL-01/03 | responsável, FAQ/script, registro dos contatos, indicadores |

## Sequência de lançamento

M1 — Apresentação institucional navegável (sem coleta se backend ainda não estiver publicado).
M2 — Captação real de lojistas + painel protegido + infraestrutura.
M3 — Conversão de interessados e comunicação para compradores.
M4 — Liberação comercial apenas depois dos critérios de docs/runbooks/go-live.md e evolução de frete/pagamentos.

## Regras de cada PR

1. Não misturar conteúdo DEMO do catálogo com a landing pública.
2. Rota pública de captação só é aceita com validação, privacidade, proteção e teste de persistência.
3. Tela administrativa tem autorização, MFA e auditoria; teste para outro usuário/loja.
4. Não habilitar checkout/drafts sem autorização e homologação próprias.
5. Atualizar documentação, migração, testes, CSS e prévia estática onde possível, distinguindo visual de sistema real.
