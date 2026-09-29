# SonarQube Cloud — integração do Salada Mix 2.0

Escolha: **Cloud + GitHub Actions**, sem instalar SonarQube Server na Hostinger.
Os testes existentes continuam em `.github/workflows/quality.yml`. A análise estática fica em `.github/workflows/sonarqube.yml` e usa PCOV/PHPUnit + MySQL 8.4 para produzir relatório Clover.

## Ativação única pelo proprietário do GitHub/SonarQube

1. Abra https://sonarcloud.io/ e entre com **GitHub**. Autorize a aplicação SonarQube a acessar o repositório `gustavolmarquesg4f-dotcom/Salada-Mix` e importe-o como projeto.
2. Selecione **CI-based analysis/GitHub Actions**. Se o projeto já existir com **Automatic Analysis**, desative-a em Project Administration → Analysis Method: as duas modalidades não podem rodar juntas.
3. Identificadores públicos do projeto existente, confirmados pelo proprietário: organization key `gustavolmarquesg4f-dotcom` e project key `gustavolmarquesg4f-dotcom_Salada-Mix`. Estão versionados no workflow; não é necessário criar Repository Variables para eles.
4. Em https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix/settings/secrets/actions crie o **Repository secret** `SONAR_TOKEN` com o token gerado pela SonarQube. Não coloque o token em arquivos, comentários, PRs, mensagens ou capturas.
5. O workflow contém `SONAR_ORGANIZATION` e `SONAR_PROJECT_KEY` como identificadores públicos; **somente** `SONAR_TOKEN` é um secret. Não inclua tokens no código.
6. Acione o workflow **SonarQube Cloud** em Actions → Run workflow (main), ou faça novo push/PR. Confira no log que o scanner executou. Sem essas configurações o workflow **falha explicitamente** no preflight, com indicação de qual variável está ausente. Um check verde somente é possível após executar scanner e receber Quality Gate aprovado (espera de até 300 segundos).
7. Quando a análise realmente gerar o check da SonarQube no GitHub, configure a proteção da main (Settings → Rules → Rulesets ou Branch protection) para exigir esse status e o CI atual. Verifique o nome real do check antes de torná-lo obrigatório.

## Escopo e critério

- Lê `app`, `bootstrap`, `config`, `database`, `routes`, `resources` e `preview`; analisa PHP, JS, CSS, Blade e configurações conforme suporte da plataforma.
- Marca `tests` e `preview/tests` como testes. Cópias dos CSS/SVG oficiais em preview são excluídas para não duplicar linhas e achados.
- Importa `coverage/sonar-clover.xml` para cobertura do PHP aplicativo. O JS/CSS/Blade continua em análise estática, mas não entra no percentual até testes frontend com LCOV. Não se deve interpretar essa métrica como cobertura de todo o marketplace.
- Adotar Quality Gate em **New Code**; corrigir novas falhas, riscos e vulnerabilidades antes de integrar PRs. Revisar findings de segurança, não fechar como falso positivo sem evidência.
- Banco: a ferramenta analisa migrations como código, mas não substitui testes MySQL reais, revisão de permissões, privacidade, backups ou pentest.
- A preview do GitHub Pages continua estática; a análise não implanta nem ativa compras.

## Alternativa de infraestrutura

SonarQube Server self-hosted exige operação própria, atualizações, monitoramento, backups e memória. Evitar colocá-lo no mesmo VPS de uma loja comercial neste estágio. Se mais tarde houver requisito de dados sob controle próprio, avaliar uma instância separada e a edição/licença necessária para análise de PR.

Referências: https://docs.sonarsource.com/sonarqube-cloud/getting-started/github e https://github.com/SonarSource/sonarqube-scan-action
