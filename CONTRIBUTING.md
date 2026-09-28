# Contribuição — Salada Mix 2.0

O Salada Mix é um monólito modular. **Branches são por entrega, não por camada fixa**: frontend, backend e migrations de uma funcionalidade seguem juntos.

## Fluxo Git

1. Atualize `main`: `git fetch origin && git switch main && git pull --ff-only origin main`.
2. Crie uma branch curta de trabalho, como `feature/seller-team`, `feature/catalog-search`, `fix/stock-race` ou `docs/vscode-workflow`.
3. Abra Pull Request para `main` com descrição, critérios de aceitação e relação com a issue.
4. Execute `php artisan test`, `php artisan route:list` e `npm run build`. O CI também executa migrations no MySQL 8.4.
5. Faça merge somente após CI aprovado e revisão; use squash para manter um histórico legível.

**Não usar branches permanentes chamadas `front`, `back` ou `banco`**: elas geram entregas incompletas e conflitos de integração. O isolamento é feito por diretórios e limites de domínio.

## Responsabilidades

- `resources/views/`, `resources/css/`, `resources/js/`: apresentação.
- `app/Http/`: entrada HTTP, validação e autorização.
- `app/Domain/`: regras de negócio, casos de uso e contratos.
- `app/Models/`: modelos de persistência.
- `database/migrations/`: alterações de schema versionadas **junto da feature**.
- `tests/`: cenários positivos, negativos e isolamento entre vendedores.
- `infrastructure/`: exemplos operacionais, não credenciais de produção.

Não comite `.env`, chaves, tokens, backups ou documentos pessoais. Cadastro aprovado não libera venda; checkout depende da integração financeira homologada. Antes de produção são necessários MFA, lockfiles, backup externo e os demais controles do [checklist de entrada em produção](docs/runbooks/go-live.md).
