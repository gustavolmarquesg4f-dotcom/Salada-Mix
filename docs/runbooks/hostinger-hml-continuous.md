# Deploy contínuo Hostinger — homologação Salada Mix

O workflow `.github/workflows/hostinger-hml-cd.yml` faz deploy de **cada commit aprovado da main** por GitHub Actions; também aceita execução manual. Só inicia pacote e SSH depois que os runs de push `Qualidade Salada Mix` e `SonarQube Cloud` estão verdes no MESMO SHA. Não publica PRs nem branches draft.

Destino exclusivo: `https://ivory-rook-276202.hostingersite.com`. A hospedagem é **web compartilhada**, não VPS; não há garantia de worker persistente/alta disponibilidade. O domínio `saladamixltda.com.br` não é alterado.

## Layout e sequência

- Código privado: `$HOME/apps/salada-mix-hml/current`; release novo em `releases/<SHA>`; anterior em `releases/previous-<oldSHA>-<runID>`.
- Variáveis persistentes: `shared/.env` em modo 0600, APP_KEY/DB_PASSWORD_B64 preservados; não copiar senha para o runner. `shared/storage` guarda sessões, cache, uploads e logs; na primeira atualização migra o storage atual para symlink compartilhado.
- Publicação de frontend: assets versionados `public/build` e `public/assets`; só `public/` e index especial são servidos no document root existente. Manifest do Vite compilado com `npm ci`; Composer usa lock e `--no-dev`. Nenhum `.env` no webroot.
- Antes de migrations, efetua dump local MariaDB em `backups/release-<runID>/database.sql` e cópia do webroot antigo. Se o cliente `mariadb-dump`/`mysqldump` não existir ou o backup falhar, **aborta sem migrar**. Os arquivos de backup são privados (0600).
- Usa `flock` e concurrency do Actions. Entra em manutenção durante migrações e troca de release; sem promessa de deploy zero-downtime. Aplica migrations **incrementais** (nunca `migrate:fresh`), mantém só categorias anteriores e não roda seed a cada release.
- Troca `current`, publica arquivos, executa `artisan up`. Smoke valida /up, /, /entrar, logo, asset do manifest, robots/noindex e que .env/vendor/logs não são HTTP 200. Também testa URLs sem query para detectar cache antigo. Se falhar: webroot e versão anterior voltam; **o banco não sofre rollback automático** (migrations devem ser aditivas/compatíveis com o código anterior). O SQL de backup fica preservado para recuperação acompanhada.
- Confirma SHA em `deployed_commit.txt` só após HTTP aprovado e remove o marcador `.pending-release`.

## Requisitos permanentes

Secrets SSH do repositório: `HML_SSH_HOST`, `HML_SSH_PORT`, `HML_SSH_USER`, `HML_SSH_PRIVATE_KEY`, `HML_SSH_KNOWN_HOSTS`. Secret de banco `HML_DB_PASSWORD` é usado só no bootstrap inicial; atualizações reutilizam o `.env` privado. Ambiente GitHub `homologacao` deve permitir apenas `main`.

O objetivo desta pipeline é homologar aplicação e interface. `APP_ENV=staging`, `APP_DEBUG=false`, `MARKETPLACE_CHECKOUT_ENABLED=false`, `MARKETPLACE_ORDER_DRAFTS_ENABLED=false`, `PAYMENTS_PROVIDER=none`, SSO desligado e `MAIL_MAILER=log` devem continuar até integração e testes específicos. Robots e noindex **não substituem** controle de acesso por senha no ambiente de homologação.

## Primeira instalação e diagnóstico

Os workflows antigos `hostinger-hml-first-deploy.yml` e `hostinger-hml-activate.yml` são históricos de uso único; não os reexecute sobre a aplicação já publicada. Diante de erro de pipeline, primeiro leia o job. Se o dump faltar ou a base não for compatível, mantenha a versão anterior e corrija a causa antes de novo deploy. Não apague `current`, `shared` ou a base HML.

As entregas ainda draft (checkout/logística) ou PRs antigos de convites só entram em `main` após revisão e checks; a pipeline não deve publicar trabalho não aprovado.
