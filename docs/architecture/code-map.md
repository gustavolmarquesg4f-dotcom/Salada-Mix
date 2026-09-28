# Mapa do código — Salada Mix 2.0

| Percurso | Arquivo/pasta | O que já existe |
|---|---|---|
| Vitrine | `resources/views/storefront/` | Página inicial, categorias e detalhe da oferta |
| Conta | `resources/views/auth/`, `app/Http/Controllers/Auth/` | Cadastro, login, verificação de e-mail e recuperação |
| Vendedor | `resources/views/seller/`, `app/Http/Controllers/Seller/` | Inscrição empresarial, painel e oferta |
| Plataforma | `resources/views/admin/`, `app/Http/Controllers/Admin/` | Aprovação de vendedores e moderação |
| Seller | `app/Domain/Seller/` | Inscrição e análise administrativa |
| Catálogo | `app/Domain/Catalog/` | Proposição/revisão de ofertas e consulta pública |
| Banco | `database/migrations/` | Usuários, empresas, categorias, ofertas e estoque |
| Testes | `tests/Feature/` | Fluxos de autenticação, seller, moderação e segregação |
| Pipeline | `.github/workflows/quality.yml` | Migrations MySQL, testes, rotas e build |
| Deploy | `infrastructure/` e `docs/runbooks/` | Modelo de implantação, não execução no VPS |

A aplicação é uma base de desenvolvimento. Não há checkout financeiro real nem publicação na Hostinger nesta etapa.

## Acompanhar mudanças

- Código: https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix
- Histórico: https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix/commits/main/
- PRs: https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix/pulls
- CI: https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix/actions
