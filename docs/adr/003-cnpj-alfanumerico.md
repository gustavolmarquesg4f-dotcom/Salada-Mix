# ADR-003 — CNPJ alfanumérico

Status: aceito em 2026-09-28.
A Receita Federal passou a emitir CNPJs com letras nas 12 primeiras posições em julho de 2026. CNPJs antigos permanecem válidos.
Persistir como CHAR(14), com normalização para A-Z/0-9 e dois dígitos verificadores numéricos. Validar módulo 11 usando ASCII - 48.
Casos: 11.222.333/0001-81 e 00.000.000/E08G-12. Cadastro comercial não substitui consulta cadastral oficial nem KYC do PSP.
Fonte: https://www.gov.br/receitafederal/pt-br/acesso-a-informacao/acoes-e-programas/programas-e-atividades/cnpj-alfanumerico

