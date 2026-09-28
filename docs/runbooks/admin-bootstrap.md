# Administrador inicial

**Não** criar endpoint HTTP que transforme um cliente em administrador.

1. Configurar envio de e-mail real em homologação e confirmar a conta do responsável.
2. A partir de sessão SSH autorizada no ambiente correto, executar:
   php artisan platform:promote-admin administrador@example.test --confirm
3. Conferir trilha platform.admin.promoted_by_cli; validar permissão mínima e identidade do operador.
4. No primeiro acesso administrativo, abrir `/seguranca/mfa`, confirmar a senha, adicionar a chave TOTP em um aplicativo autenticador e salvar os oito códigos de recuperação. A administração exige código TOTP em cada sessão.
5. Caso o administrador perca autenticador **e** códigos de recuperação, um operador com acesso SSH autorizado deverá validar a identidade por procedimento externo e executar `php artisan platform:reset-admin-mfa administrador@example.test --confirm`. O comando registra `identity.mfa.reset_by_cli`, redefine a chave e revoga sessões armazenadas no banco. Não existe reset HTTP público.
6. Antes de produção: SMTP real, HTTPS, SESSION_DRIVER=database, SESSION_ENCRYPT=true, backups de APP_KEY, gestão de privilégios e revisão periódica de acessos.
7. Em homologação usar apenas usuários fictícios. Nunca comitar credenciais ou usar papéis privilegiados por seed público.

