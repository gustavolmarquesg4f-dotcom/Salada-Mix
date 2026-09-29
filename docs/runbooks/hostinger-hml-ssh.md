# Hostinger HML — conexão GitHub Actions por SSH

Este primeiro workflow **não faz deploy**. Ele verifica o acesso SSH da conta Hostinger e a existência da pasta de homologação. Não altera `public_html`, o domínio de produção, arquivos, banco ou credenciais.

## Autorização inicial

1. No Windows, gere uma chave exclusiva para esta automação com `ssh-keygen -t ed25519 -a 100 -f "$env:USERPROFILE\.ssh\salada_mix_hml_actions" -C "salada-mix-hml-actions"`. Não reutilize sua chave pessoal. Caso o arquivo já exista, não o sobrescreva.
2. Para o fluxo desassistido, a chave de deploy deve ser dedicada e sem passphrase; o segredo criptografado do GitHub guarda sua chave privada. Nunca coloque a chave no repositório ou em mensagens.
3. Em Hostinger → Sites → site HML → Avançado → Acesso SSH → Chaves SSH, adicione o conteúdo completo de `salada_mix_hml_actions.pub` (apenas a chave **pública**).
4. Em GitHub → repositório → Settings → Secrets and variables → Actions, cadastre os 5 **repository secrets**:
   - `HML_SSH_HOST`: host/IP indicado pelo hPanel;
   - `HML_SSH_PORT`: porta indicada pelo hPanel;
   - `HML_SSH_USER`: usuário SSH indicado pelo hPanel;
   - `HML_SSH_PRIVATE_KEY`: conteúdo integral da chave privada, incluindo BEGIN e END;
   - `HML_SSH_KNOWN_HOSTS`: linha de host key previamente verificada no seu computador, na forma `[host]:port` (ou hashed). Não confie cegamente em `ssh-keyscan`: valide a fingerprint por um canal confiável.
5. Rode `Actions → Hostinger HML - validar SSH (somente leitura) → Run workflow → main`. A configuração `environment: homologacao` identifica o destino.
6. Acompanhe o resultado. Um check verde significa **apenas** conexão SSH e diretório validado, não deploy Laravel.

## Restrições e próxima fase

Não passar segredos via chat, screenshot ou arquivo versionado. O acesso SSH na hospedagem compartilhada é limitado à conta, e não concede root ou processos permanentes.

Antes do workflow real de deploy: validar migrations no MariaDB 11.8.9, gerar arquivos de lock, separar aplicação privada de `public_html`, configurar `.env` e banco **somente no servidor**, garantir SSL, backups e teste de rollback. Checkout e pagamentos ficam desabilitados.
