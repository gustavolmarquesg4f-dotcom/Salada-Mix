# FE-04 — Autenticação e experiência do comprador

**Base:** `main` após FE-03 e BE-04 BFF/SSO. **Branch:** `feature/frontend-buyer-account-fe04`.

## Implementação
- Reestiliza login, cadastro, recuperar senha, redefinir senha e verificação de e-mail nas rotas web Laravel existentes.
- Mantém autenticação de sessão Laravel, CSRF, validação, e-mail verificado e rate limiting; não duplica nem substitui credenciais.
- Opções SSO Google/GitHub visíveis somente quando configuradas e habilitadas. O servidor mantém o controle do fluxo OAuth/SSO.
- Minha conta com dados pessoais, login por senha, contas conectadas e consulta às sessões via BFF same-origin.
- Edição do nome/e-mail via PATCH `bff.account.update`; mudança de senha via PUT `bff.account.password`; conta SSO-only usa `bff.auth.password.establish`.
- JavaScript não armazena senha/token em localStorage ou cookies adicionais; usa a sessão HttpOnly existente, CSRF e credenciais same-origin.
- Após alterar e-mail, exige confirmação do endereço pela rota de verificação existente.
- Endereços mantêm rotas server-rendered, validação backend e isolamento por usuário.
- Resumo da sacola é somente leitura. Sem pedidos/pagamentos/frete reais.
- Comportamentos de erro 401/403/419/422 apresentados com texto e `aria-live`. Botões BFF permanecem inativos sem JS.
- Prévia GitHub Pages será estática: demonstra telas sem receber credenciais nem simular autenticação real.

## Fora do escopo
- Transações comerciais, checkout e cotação de frete; LGPD jurídico definitivo; página operacional de histórico de pedidos; desvinculação de SSO pela interface; redefinir segundo fator.
- Nenhum dado do usuário real será copiado para a prévia estática.

## Aceite
1. `php artisan test --filter=FrontendBuyerAccountTest` e toda a suíte passam.
2. `npm run build` e `node preview/tests/smoke.cjs` passam.
3. Validar responsividade e estados de formulário, recuperação e segurança.
4. Verificar que a mudança de e-mail requer autenticação recente e devolve à verificação.
5. SSO e MFA continuam no backend. Checkout permanece desligado.
