# Convites de equipe — incremento funcional

- Somente owner ativo do seller pode convidar, cancelar ou revogar membros.
- Convites possuem token aleatório de 256 bits; apenas SHA-256 é persistido, expiração em 48h.
- O usuário precisa entrar com o e-mail convidado e confirmá-lo; não há concessão automática por abertura do link.
- A aceitação é POST com CSRF, transação, lock e consumo único do token.
- Escopo de cada vínculo permanece seller_id; papéis: owner, manager, operations, finance e support.
- Convidado não vira proprietário; owner não pode ser revogado via recurso de equipe.
- Alterações geram audit_log. Convites dependem de MAIL_* configurado.
- Não há recursos para vendas reais neste incremento. A homologação deve incluir testes de acesso cruzado, expiração, duplicidade e revogação.
