# Hostinger HML — preflight de conexão com o banco

O fluxo manual `Hostinger HML - validar banco (somente leitura)` foi preparado para verificar a base existente antes de qualquer deploy. Não cria tabelas, não roda migrations nem publica arquivos. O segredo é transmitido via STDIN dentro do túnel SSH verificado.

## Única nova credencial

Em GitHub → Settings → Environments, crie ou abra o ambiente **homologacao**. Em **Environment secrets**, adicione `HML_DB_PASSWORD` com a senha que foi definida na criação do usuário MySQL `u904890479_sm_hml`. Não use uma senha do GitHub/SSH. Não compartilhe o valor em mensagens, logs ou prints. Se esqueceu a senha, altere-a pelo menu do banco no hPanel e atualize o segredo correspondente. Os cinco secrets SSH continuam no repositório.

Depois que este workflow for integrado à main, execute manualmente em Actions → Hostinger HML - validar banco → Run workflow → main. O resultado esperado é `Conexao HML: OK`, a versão MariaDB e a quantidade de tabelas. O teste usa `localhost:3306`, hostname publicado na documentação oficial da Hostinger para bancos de hospedagem web: https://www.hostinger.com/br/support/1583552-como-localizar-os-detalhes-do-seu-banco-de-dados-mysql-na-hostinger/

## Próximos gates para publicar Laravel

- PR MariaDB integrado; Quality e Sonar verdes no commit **main** atual, não apenas em uma branch anterior.
- Gerar e versionar `composer.lock` e `package-lock.json`; builds hoje ainda resolvem dependências sem lock.
- Montar release versionado fora de `public_html`, manter apenas arquivos `public/` publicados; definir index.php com caminhos privados corretos.
- Gravar `.env` somente no diretório privado; APP_KEY persistente, APP_DEBUG=false, APP_ENV=staging, DB_CONNECTION=mariadb, DB_HOST=localhost, SESSION_SECURE_COOKIE=true.
- Restringir homologação (por exemplo, proteção de diretório), bloquear indexação, usar apenas dados de teste e evitar a criação de contas reais sem entrega de e-mail homologada.
- Migrar com backup antes, jamais `migrate:fresh`; manter compra, reserva e PSP desligados.
- Confirmar funcionamento de /up, homepage, login e logs. Produção e domínio principal fora deste fluxo.

A hospedagem web não é VPS e não oferece por si só worker persistente supervisionado nem alta disponibilidade. Checkout comercial continua bloqueado.
