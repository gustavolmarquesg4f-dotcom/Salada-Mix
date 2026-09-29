# Backlog executivo — Lançamento comercial Salada Mix 2.0

**Fonte:** [Plano mestre v2](plano-lancamento-comercial.md). Substitui o backlog de pré-lançamento isolado. Cada história entrega frontend + backend + admin/dados + testes no mesmo PR sempre que pertinente.

| ID | Prioridade | História / entregável | Dependências | Aceite verificável |
|---|---|---|---|---|
| LC-00.1 | P0 | Aprovar mapa de jornada comprador/seller/admin, estados e visual | marca e escopo | roteiro de demo completo, telas e estados documentados |
| LC-00.2 | P0 | Validar regras comerciais: PSP, frete, comissão, devolução, categoria MVP | negócio/jurídico | decisões assinadas; sem taxa/prazo inventados |
| LC-01.1 | P0 | Dashboard Admin Catálogo, listagem/busca/status | permissão admin/MFA | operador encontra rascunhos/aprovados, mobile e desktop |
| LC-01.2 | P0 | Categorias e atributos administráveis | schema catálogo | criar/editar/desativar sem deploy, slugs/visibilidade consistentes |
| LC-01.3 | P0 | Cadastro/edição de produto, oferta, preço em centavos, estoque/SKU | seller aprovado e categoria | dados validados, autorização por empresa |
| LC-01.4 | P0 | Upload de foto e galeria; derivados; capa; alt text | storage privado/público, política de imagem | tipo/tamanho/MIME conferidos, arquivos não executáveis, vínculo e exclusão segura |
| LC-01.5 | P0 | Preview→submissão→aprovação/rejeição→publicação/despublicação | LC-01.1–4 | rascunho nunca aparece em vitrine; auditoria e justificativas |
| LC-01.6 | P1 | Importação planilha/CSV com validação, preview/erro e idempotência | LC-01.3–5 | lote não burla moderação ou permissões |
| LC-02.1 | P0 | Página pública "Seja vendedor" com informações reais e inscrição | conteúdo aprovado | fluxo claro e CNPJ apenas no cadastro formal |
| LC-02.2 | P0 | Administração de propostas de seller e status, com MFA | cadastro existente | rejeição/aprovação auditável, sem ativar pagamentos automaticamente |
| LC-02.3 | P1 | Convites/roles da equipe de loja | PR #23 | isolamento por empresa |
| LC-03.1 | P0 | Regressão FE-01–05 com catálogo e conta integrados | main atual | testes de sessão e preço/estoque do servidor |
| LC-04.1 | P0 | Integrar FE-06/BE-06 de preparação de compra | PR #31 | endereço próprio e prontidão, sem cobrança |
| LC-04.2 | P0 | Dados de peso/dimensão e origem por seller | LC-01.3 | faltas de dados impedem cotação real |
| LC-04.3 | P0 | Cotação real por vendedor, validade, recálculo e erro | provedor/contrato | sem inventar tarifa ou prazo |
| LC-05.1 | P0 | Checkout final + reserva transacional + idempotência | LC-04 | estoque não negativo, sem dupla reserva |
| LC-06.1 | P0 | Pagamento sandbox por subpedido, webhook e reconciliação | PSP/contrato | não confiar em retorno do navegador |
| LC-06.2 | P0 | Falhas, estorno, expiração e fechamento financeiro | LC-06.1 | evento repetido não duplica efeito |
| LC-07.1 | P0 | Pedidos comprador/vendedor/admin, rastreio e suporte | LC-05/06 | isolamento, estados e auditoria |
| LC-08.1 | P0 | Administração operacional com papéis, conteúdo e dashboards | LC-01/02/07 | operador realiza publicação/atendimento sem SQL/Git |
| LC-09.1 | P0 | Demo E2E completa e aprovação funcional/visual | LC-01–08 | roteiro e aceite por perfil; HML real, dados sintéticos |
| LC-09.2 | P0 | Homologação segurança, concorrência, LGPD, SMTP, PSP/frete, backup/restauração | LC-09.1 | evidências e checklist go-live concluídos |
| LC-10.1 | P0 | Produção fechada, cadastro de sellers/produtos reais pelo admin | LC-09 | fotos, preços, estoque, medidas e origem revisados |
| LC-10.2 | P0 | Soft launch e primeira venda controlada | LC-10.1 | conciliação, expedição, atendimento e rollback prontos |

## Demonstração para aprovação (primeira proposta)
Completa, com dados sintéticos **claramente marcados**, frontend e backend de ponta a ponta, painel administrativo de cadastro/publicação, login, vendedor, catálogo, entrega, pagamento sandbox e pedido. A prévia GitHub Pages é referência visual, não substitui a demonstração Laravel. Não habilitar pagamento real.

## Carga pós-homologação
Criar vendedor real → validar contrato/origem → cadastrar/importar produto e fotos autorizadas → definir preço/estoque/dimensões → preview → aprovar/publicar → validar logística e PSP → abrir vitrine. Sem atualização de código para publicação de itens. Checkout fechado durante carga.

## Fluxo de cada PR
Implementação FE+BE, migrations, autorização, testes unitários/feature/E2E, documentação de tela/contrato, preview quando fizer sentido, quality gate e validação mobile. Registrar bloqueadores externos em separado.
