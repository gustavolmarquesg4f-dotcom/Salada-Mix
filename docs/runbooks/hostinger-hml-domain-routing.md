# HML — verificar associação domínio/webroot

Um primeiro deploy gerou o Laravel em `$HOME/apps/salada-mix-hml/current` e executou as migrations. O smoke test HTTP retornou 404; o rollback restaurou somente `default.php`, **não** apagou Laravel privado nem desfez o schema. Portanto, não reexecutar o fluxo de primeira instalação: ele deve abortar corretamente.

Workflow de diagnóstico: testa leitura HTTP normal e roteamento forçado ao IP SSH (quando IP), utilizando arquivo temporário não sensível em **apenas** `ivory-rook-276202.hostingersite.com/public_html`. Limpa o arquivo ao final e não toca em produção, bancos, chaves, .env nem ativos reais.

Se ambos os caminhos da prova retornarem 404, o problema está na associação do domínio ao document root ou no apontamento do servidor web. Verificar em hPanel → site temporário → Arquivos → Contas FTP qual é a raiz informada; também conferir a pré-visualização, domínio conectado, SSL e status de publicação. Não alterar DNS ou domínio principal sem validar a causa. Este workflow não publica app e só deve ser usado para diagnóstico.
