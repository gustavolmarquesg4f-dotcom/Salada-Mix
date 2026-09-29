# Salada Mix — primeira publicação da HML Hostinger

O primeiro release acontece após merge em main, somente se os dois checks dessa MESMA revisão (Qualidade Salada Mix e SonarQube Cloud) ficarem verdes. O workflow também pode ser acionado manualmente na branch main. Não publica no domínio principal.

## Layout

- Código privado: $HOME/apps/salada-mix-hml/current
- Ambiente sensível persistente: $HOME/apps/salada-mix-hml/shared/.env (0600)
- Pacote temporário: $HOME/apps/salada-mix-hml/incoming/release.tar.gz
- Webroot existente: $HOME/domains/ivory-rook-276202.hostingersite.com/public_html
- Backup da página inicial do provedor: $HOME/apps/salada-mix-hml/backups/initial-public-html/default.php

A senha HML_DB_PASSWORD é enviada exclusivamente por STDIN através do canal SSH, sem ser registrada. DB_PASSWORD_B64 é apenas uma representação para evitar falhas de parsing do arquivo dotenv; NÃO é criptografia. O arquivo com essa informação fica fora da raiz pública.

A instalação exige banco totalmente vazio e public_html contendo apenas default.php. Se qualquer condição for diferente, aborta sem sobrescrever conteúdo desconhecido. O APP_KEY é aleatório e preservado mesmo após tentativas malsucedidas. Backend Laravel 13 + vendor ficam fora de public_html; somente os recursos da pasta public e o front controller com caminhos privados são servidos pelo Apache.

## Verificações e limitações

O workflow compila PHP/Node a partir de composer.lock/package-lock.json, aplica migrations normais e executa somente DatabaseSeeder (categorias; sem empresas, usuários ou compras sintéticas). Ativa APP_ENV=staging, APP_DEBUG=false, SESSION_ENCRYPT/SECURE_COOKIE, MAIL_MAILER=log, QUEUE_CONNECTION=sync. Os recursos de pagamento, rascunho de pedido, reservas comerciais e SSO ficam desligados. Define robots Disallow e cabeçalho noindex. Verifica HTTPS, /up, /, /entrar e que /.env não é servido. Se smoke falhar, restaura a landing page original; a migration permanece no banco privado para diagnóstico e não é revertida automaticamente.

Este workflow só serve para a PRIMEIRA instalação; se a pasta current existir, não atualiza nem a remove. Os releases seguintes exigirão um workflow distinto com backup e rollback de aplicação e estratégias de migration compatível. Nunca executar migrate:fresh.

## Antes de permitir usuários reais

Configurar e testar SMTP transacional, autenticação, logs, política LGPD, backup externo e restauração, proteção de homologação e monitoramento. A hospedagem compartilhada não é VPS e não garante processos 24x7 nem alta disponibilidade. Não ativar pagamentos, webhooks financeiros ou vendas.
