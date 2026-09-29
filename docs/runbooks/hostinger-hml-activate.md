# HML — reativação da primeira publicação privada

O primeiro deploy construiu a aplicação e aplicou migrations, mas o smoke test HTTP falhou. O rollback restaurou somente a página pública; `current`, a chave de aplicação e o banco permaneceram preservados. Diagnóstico posterior comprovou que `public_html` estava em modo 750; a permissão correta 755 foi validada com HTTP 200 em todos os IPs do domínio.

Este workflow retoma **a instalação já existente**. Ele não faz novo install de dependências, não executa seed nem migrations, não gira APP_KEY e não modifica o domínio principal. Exige PR CI + Sonar verdes no exato commit de main. Preflight prova arquivos estáticos e PHP em cada endereço resolvido do domínio, evitando expor a aplicação em domínio mal mapeado.

O ativador verifica `APP_ENV=staging`, checkout/rascunhos/pagamentos desligados e banco acessível; copia somente os arquivos de public, usa o front controller de caminhos privados e instala noindex/robots. O rollback automático de smoke HTTP restaura `default.php` e remove apenas recursos publicados, sem desfazer o schema.

Esta rotina é para ativação **uma única vez**. Após sucesso, use um futuro pipeline de releases versionados para novas mudanças de código; não volte a executar `first-deploy.sh`.

## Permissões no webroot

`cp -a source/. webroot/` preserva o modo da pasta de origem também no destino. O pacote foi extraído sob umask 027 e `public/` pode ficar em 750. O ativador e o rollback corrigem explicitamente **apenas** a pasta pública da HML para 755 após a cópia. Isto evita que arquivos estáticos e rotas retornem 404 por falta de travessia de diretório. Esta correção não altera permissões da aplicação privada nem do domínio principal.
