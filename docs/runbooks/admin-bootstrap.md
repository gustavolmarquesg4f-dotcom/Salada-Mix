# Administrador inicial

**Não** criar endpoint HTTP que transforme um cliente em administrador.

1. Configurar envio de e-mail real em homologação e confirmar a conta do responsável.
2. A partir de sessão SSH autorizada no ambiente correto, executar:
   php artisan platform:promote-admin administrador@example.test --confirm
3. Conferir trilha platform.admin.promoted_by_cli; validar permissão mínima e identidade do operador.
4. Antes de produção, implementar MFA, gestão de privilégios e revisão de acesso da administração.
5. Em homologação usar apenas usuários fictícios. Nunca comitar credenciais ou usar papéis privilegiados por seed público.

