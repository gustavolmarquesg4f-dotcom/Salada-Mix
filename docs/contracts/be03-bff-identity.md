# BE-03 — API Gateway/BFF e identidade

## Padrão arquitetural

Uma entrada HTTP HTTPS da mesma origem em `/bff/v1` dentro do monólito Laravel. Não existe segundo processo PHP nem microserviço dedicado neste estágio; o BFF agrega serviços de domínio sem acesso direto do cliente às tabelas. O Nginx encaminha ao `public/index.php`, e o grupo **web** do Laravel fornece sessão HttpOnly, cookie SameSite, criptografia configurável, CSRF e autenticação.

O prefixo `/api/v1/catalog` é público e read-only. `/bff/v1` fornece representação específica para a interface; cliente deve enviar `Accept: application/json` e, em PUT/PATCH/POST/DELETE, o token CSRF de `<meta name="csrf-token">` no header `X-CSRF-TOKEN`, junto de `credentials: same-origin`. **Nunca** colocar API key/segredo de PSP no navegador, nem usar tokens de acesso no localStorage.

## Contratos públicos

| Método | URL | Retorno |
| --- | --- | --- |
| GET | /bff/v1/storefront | categories, offers, features.checkout_enabled=false |
| GET | /bff/v1/catalog/categories | categorias ativas |
| GET | /bff/v1/catalog/offers | filtro/ordenação/paginação da API read-only |
| GET | /bff/v1/catalog/offers/{offer} | oferta elegível; 404 para removida |
| GET | /bff/v1/auth/me | authenticated, email_verified, admin_mfa_required, user limitado |

## Login e conta (mesma sessão do Laravel)

| Método | URL | Dados |
| --- | --- | --- |
| POST | /bff/v1/auth/register | name, email, password, password_confirmation |
| POST | /bff/v1/auth/login | email, password |
| POST | /bff/v1/auth/logout | sem payload |
| POST | /bff/v1/auth/forgot-password | email; retorno uniforme para conta existente ou não |
| POST | /bff/v1/auth/reset-password | token, email, password, password_confirmation |
| POST | /bff/v1/auth/verification-email | reenvio com sessão |
| GET | /bff/v1/account | dados pessoais limitados |
| PATCH | /bff/v1/account | name; mudança de email exige current_password e nova verificação |
| PUT | /bff/v1/account/password | current_password, password, password_confirmation |
| GET | /bff/v1/account/sessions | sessões recentes, apenas se SESSION_DRIVER=database |
| DELETE | /bff/v1/account/sessions/{fingerprint} | current_password; não expõe ID real de sessão |

Verificação de e-mail ocorre no link assinado `/email/verify/{id}/{hash}` do próprio Laravel. Contas novas não recebem papel de administrador. Admin só é concedido por comando CLI restrito.

## Segundo fator administrativo

Todas as rotas `/admin/*` e `/bff/v1/admin/*` exigem `can:review-sellers` **e** `admin.mfa` após `auth` e `verified`. Enrolamento exige senha atual; segredo TOTP criptografado; confirmação por seis dígitos, tolerância de 30 segundos; desafio com passo único ou código de recuperação descartável com hash. Acessos sem configuração ou desafio recebem 403 JSON e URL da página `/seguranca/mfa` (ou redirect em HTML).

GET /bff/v1/auth/mfa/ retorna estado; POST /enroll (current_password), /confirm (code), /challenge (code OU recovery_code) e /recovery-codes (current_password, code). O usuário deve guardar os códigos de recuperação no momento da geração. `APP_KEY` e backups criptografados são críticos para recuperar os segredos. Recuperação operacional quando todos os fatores forem perdidos: comando CLI `platform:reset-admin-mfa email --confirm`, após verificação externa da identidade, com auditoria e revogação das sessões do banco.

SSO via Google/Microsoft/IdP externo **não está ativado**: exige escolha do provedor, credenciais OAuth/OIDC, URIs de retorno, políticas de vinculação de contas e testes de segurança. Não confundir sessão unificada do monólito com SSO federado.

## Comprador, vendedor e administração

| Método | URL | Condições |
| --- | --- | --- |
| GET | /bff/v1/buyer | conta, cart, wishlist e empresas do próprio usuário |
| GET | /bff/v1/checkout/preview | resumo por vendedor somente leitura; frete e total final nulos |
| GET/PUT/DELETE | /bff/v1/cart/{offer?} | sessão verificada, preço servidor, sem reserva |
| GET/PUT/DELETE | /bff/v1/wishlist/{offer?} | sessão verificada, ofertas públicas |
| GET/POST | /bff/v1/addresses | CRUD de endereço, POST validado |
| PATCH/DELETE | /bff/v1/addresses/{address} | ownership por user_id; erro 404 cruzado |
| POST | /bff/v1/sellers | cadastrar empresa com CNPJ validado |
| GET | /bff/v1/sellers/{seller}/origins | origens da própria empresa; escrita permanece nas rotas HTML de BE-03A |
| GET | /bff/v1/sellers/{seller} | membro ativo da própria empresa |
| POST | /bff/v1/sellers/{seller}/offers | owner/manager de empresa aprovada |
| GET | /bff/v1/admin | resumo limitado e segundo fator verificado |
| GET | /bff/v1/admin/sellers, /bff/v1/admin/offers | moderação privativa, paginação |
| POST | /bff/v1/admin/sellers/{seller}/decision | decision approved/rejected, reason obrigatório se rejeitado |
| POST | /bff/v1/admin/offers/{offer}/decision | idem, decisão auditada |

Endereços reutilizam o schema e as validações de BE-03A (#22), com `recipient_name`, `neighborhood` e limite de 10 por comprador. Não fazem consulta automática a CEP nem cotação; só normalizam/armazenam campos informados pelo usuário. Default é único por usuário graças ao lock da linha do comprador. Informações de endereço NÃO são públicas.

## Erros e contratos de segurança

- 401 sem sessão, 403 não verificado/sem papel/sem segundo fator, 404 para objeto alheio não revelado, 422 para dados inválidos, 429 para rate limiting. Todos os retornos BFF têm `Cache-Control: no-store, private`.
- Não aceitar seller_id, user_id, preço ou status em payload como fonte de autoridade. Domain actions filtram tenant e status.
- Checkout permanece **false**. Não há pedidos, reservas, recebimentos, cotação de frete ou SSO externo nesta entrega.
- Email real deve ser configurado via SMTP/serviço transacional; `MAIL_MAILER=log` é SOMENTE desenvolvimento. `APP_URL`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, HTTPS, backups e revisão LGPD são exigências de homologação.
- A prévia GitHub Pages não chama o BFF e não demonstra login real; o BFF deve ser implantado em VPS com PHP/MySQL para uso real.

## Testes

Executar `php artisan migrate --force`, `php artisan test`, `php artisan route:list` e `npm run build` em MySQL 8.4. Conferir especialmente criação/duplicidade de conta, login, verificação, redefinição, TOTP/códigos de recuperação, tenant, endereços e CSRF no navegador homologado.

