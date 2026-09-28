# Salada Mix 2.0 — Marketplace aberto

Projeto **greenfield**. Laravel 13, PHP 8.3+, Blade/Livewire 4, MySQL 8.4, VPS Hostinger.
Nenhum arquivo de código da loja anterior foi incorporado.

## Portais e responsabilidades

- Comprador: vitrine, login e pedidos.
- Vendedor: solicitação de cadastro, equipe, ofertas e subpedidos próprios.
- Administração central: aprovação e governança.
- Domínios: Identity, Seller, Catalog, Inventory, Orders, Payments, Shipping e Privacy.
- Integrações externas sempre por adaptadores; nenhum segredo no cliente.

## Prévia navegável no GitHub Pages

O diretório [preview/](preview/) contém a **prévia da Entrega 2**, usando a identidade e o CSS oficiais, com home, busca, filtros, detalhe de produto e painéis informativos de vendedor e administrador. Os dados são fictícios, alinhados ao seeder de desenvolvimento. **Não executa Laravel, MySQL nem pagamentos.**

Para ativar pela primeira vez, abra **Settings → Pages → Build and deployment → Source: Deploy from a branch** e selecione **gh-pages / (root)**, depois Save. O branch [gh-pages](https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix/tree/gh-pages) contém somente os arquivos públicos de visualização; a aplicação Laravel fica na main. As próximas versões da prévia serão sincronizadas ao gh-pages após revisão. A URL esperada é `https://gustavolmarquesg4f-dotcom.github.io/Salada-Mix/`. Veja [runbook](docs/runbooks/pages-preview.md).

## Rodar localmente (após instalar PHP, Composer, Node e MySQL)

```bash
cp .env.example .env
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan db:seed
php artisan serve
```

Configure DB_HOST, DB_DATABASE, DB_USERNAME e DB_PASSWORD no .env. Para testes automatizados: `php artisan test`.
A primeira instalação deve gerar `composer.lock` e `package-lock.json` e registrá-los no Git antes de qualquer release; o scaffold publicado ainda não contém lockfiles.


## Acompanhar o projeto no VS Code

Repositório público: https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix

```bash
git clone https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix.git
cd Salada-Mix
code .
git fetch origin
git switch main
git pull --ff-only origin main
```

Se a pasta já está clonada, execute somente os últimos três comandos dentro dela. No VS Code, ative **Git: Autofetch** para identificar novos commits; use **Git: Pull** para trazer os arquivos ao disco. Para inspecionar código sem instalar nada, abra [github.dev](https://github.dev/gustavolmarquesg4f-dotcom/Salada-Mix).

O [mapa do código](docs/architecture/code-map.md) mostra onde ficam frontend, backend e migrations. Antes de contribuir, leia [CONTRIBUTING.md](CONTRIBUTING.md).

## SonarQube Cloud — qualidade contínua

A integração com SonarQube Cloud está preparada em [sonarqube.yml](.github/workflows/sonarqube.yml) e [sonar-project.properties](sonar-project.properties). Ela ainda depende da autorização da conta SonarQube/GitHub e da configuração segura de `SONAR_TOKEN`, `SONAR_ORGANIZATION` e `SONAR_PROJECT_KEY`. Um job sem essas configurações **não representa análise ou Quality Gate aprovado**. Consulte o [runbook de ativação](docs/runbooks/sonarqube-cloud.md).

## Backend BFF e identidade

Entrada same-origin em `/bff/v1`: catálogo, conta, vendedor, administração, carrinho, favoritos e endereços. O login usa sessão do Laravel, e a área administrativa exige TOTP. Veja o [contrato BE-03](docs/contracts/be03-bff-identity.md). A prévia do GitHub Pages continua estática e não executa autenticação real. Checkout, pagamento e frete permanecem bloqueados.

## Status real

Fundação de código com onboarding, catálogo inicial, moderação e estoque de cadastro implantada. **Não habilitar venda real:** checkout, OAuth Mercado Pago, split, frete, LGPD operacional, backup e deploy ainda exigem implementação/homologação. Use somente dados fictícios.

Arquitetura: [visão técnica](docs/architecture/README.md). Critérios de entrada em produção: [checklist](docs/runbooks/go-live.md).
