# Isolamento de vendedores

1. Identificador de empresa provém de contexto autenticado e vínculo ativo; jamais confiar apenas em seller_id enviado pelo navegador.
2. Todo dado privado de vendedor exige autorização por recurso e filtro server-side; scopes de Eloquent são defesa adicional, não limite único.
3. Chaves compostas/FKs devem impedir cruzamento de empresas em itens de pedido.
4. Documentos empresariais ficam em armazenamento privado; URL expira e exige autorização.
5. Casos de teste: empresa A não lê/altera/exporta dados de B; usuário sem vínculo recebe 403/404.
6. Admin tem privilégio mínimo, ações auditadas e MFA antes de produção.
