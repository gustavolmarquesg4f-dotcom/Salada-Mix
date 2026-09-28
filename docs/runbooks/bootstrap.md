# Ambiente local — desenvolvimento

Pré-requisitos: PHP 8.3+, Composer 2, Node 22+, Docker/Compose (ou MySQL 8.4 local).
O repositório inclui o esqueleto oficial Laravel 13 e código novo da plataforma.

1. \`cp .env.example .env\`
2. \`docker compose -f compose.dev.yaml up -d db\` (senha de desenvolvimento definida no compose).
3. Ajuste DB_PASSWORD e DB_DATABASE no .env para os valores do compose.
4. \`composer install\` — gera composer.lock na primeira instalação; revise e comite o lock.
5. \`php artisan key:generate\`
6. \`npm install && npm run build\` — revise e comite package-lock.json.
7. \`php artisan migrate\`
8. \`php artisan serve\` — em outro terminal \`npm run dev\` se desejar hot reload.
9. \`php artisan test\`.

Em ambiente local MAIL_MAILER=log registra notificações no log; configure um provedor transacional antes de testar confirmação de e-mail ponta a ponta com entrega real.
Nunca copie dados de produção para o desenvolvimento. Nunca comite .env, dumps ou tokens de PSP.

