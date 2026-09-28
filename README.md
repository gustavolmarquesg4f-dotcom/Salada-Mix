# Salada Mix 2.0 — Marketplace aberto

Projeto **greenfield**. Laravel 13, PHP 8.3+, Blade/Livewire 4, MySQL 8.4, VPS Hostinger.
Nenhum arquivo de código da loja anterior foi incorporado.

## Portais e responsabilidades

- Comprador: vitrine, login e pedidos.
- Vendedor: solicitação de cadastro, equipe, ofertas e subpedidos próprios.
- Administração central: aprovação e governança.
- Domínios: Identity, Seller, Catalog, Inventory, Orders, Payments, Shipping e Privacy.
- Integrações externas sempre por adaptadores; nenhum segredo no cliente.

## Rodar localmente (após instalar PHP, Composer, Node e MySQL)

```bash
cp .env.example .env
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan serve
```

Configure DB_HOST, DB_DATABASE, DB_USERNAME e DB_PASSWORD no .env. Para testes automatizados: `php artisan test`.
A primeira instalação deve gerar `composer.lock` e `package-lock.json` e registrá-los no Git antes de qualquer release; o scaffold publicado ainda não contém lockfiles.

## Status real

Fundação de código e documentação em implantação. **Não habilitar venda real:** checkout, OAuth Mercado Pago, split, frete, LGPD operacional, backup e deploy ainda exigem implementação/homologação. Use somente dados fictícios.

Arquitetura: [visão técnica](docs/architecture/README.md). Critérios de entrada em produção: [checklist](docs/runbooks/go-live.md).
