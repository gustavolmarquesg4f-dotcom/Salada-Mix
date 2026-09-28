# GitHub Pages — modo simplificado por branch

## Erro anterior

O workflow 'Publicar prévia do Salada Mix' falhou em `actions/configure-pages@v5` porque a API retornou 404: o site Pages não tinha sido habilitado. Não é erro de HTML/CSS/JS; testes da prévia passaram.

## Solução adotada

Foi criada a branch `gh-pages` **somente** com `index.html`, `assets/style.css`, `assets/app.js` e `.nojekyll` na raiz. Não publicar o código Laravel por Pages.

1. Abra https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix/settings/pages
2. Em Build and deployment → Source, selecione **Deploy from a branch**.
3. Em Branch, escolha **gh-pages** e **/(root)**. Clique **Save**.
4. Acompanhe a publicação em https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix/actions
5. Abra a URL informada pela própria seção Pages; endereço de projeto esperado: https://gustavolmarquesg4f-dotcom.github.io/Salada-Mix/

A integração do GitHub conectada não possui ação de configurações do Pages; ativar a fonte exige uma vez a ação da conta administradora.

## Atualização da prévia

A branch main guarda o código e a fonte visual em preview/. A branch gh-pages guarda só a versão publicada. Para sincronizar, crie na gh-pages um commit com os quatro blobs da pasta preview/ na main, substituindo seu conteúdo; não use deploy que exponha a raiz do monólito. Atualizações feitas por conta autorizada/commit GitHub devem disparar build Pages.

A prévia não permite venda real, não grava dados e não reproduz autenticação. O site Laravel de verdade depende de implantação em VPS.
