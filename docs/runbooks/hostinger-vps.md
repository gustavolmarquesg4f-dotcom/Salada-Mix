# Hostinger VPS — implantação planejada, NÃO executada

Referência inicial: KVM 2, Ubuntu LTS, Nginx, PHP-FPM, MySQL local e worker em processo supervisionado.
Isso é um ponto único de falha; expansão: banco separado e aplicação redundante.

## Rede
Liberar 80/443 para internet; SSH por chave, com controle de origem. MySQL escuta loopback. Desabilitar root SSH por senha. HTTPS e headers de segurança após validar domínio.

## Aplicação
/public é o único document root; .env pertence à raiz privada. Usar usuário de deploy sem root e permissões mínimas. Composer --no-dev no release. Worker e scheduler sob supervisão. NUNCA usar \`php artisan migrate:fresh\` em produção.

## Backup
Backup de banco consistente, cifrado e enviado para destino externo ao VPS; incluir arquivos privados essenciais. Restaurar regularmente em host de teste. Backup do próprio VPS não substitui exportação externa.

## Procedimento de liberação
Build CI -> homologação -> revisão da migration -> snapshot/backup -> release -> smoke test -> monitoramento. Rollback de código não deve assumir que migration destrutiva seja reversível.

