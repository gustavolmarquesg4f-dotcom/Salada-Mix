"use strict";

// DEMO ONLY: data is fabricated and held in browser memory. No real orders, accounts,
// approval, inventory adjustment or payment occurs on GitHub Pages.
const categories=[
 {id:"tecnologia",name:"Tecnologia",emoji:"🎧",bg:"#e5eeff"},
 {id:"beleza",name:"Beleza",emoji:"✨",bg:"#fae6eb"},
 {id:"moda",name:"Moda",emoji:"👜",bg:"#fae9d6"},
 {id:"casa",name:"Casa & decoração",emoji:"🛋️",bg:"#e4f2e8"},
 {id:"games",name:"Games",emoji:"🎮",bg:"#eee5fa"},
 {id:"infantil",name:"Infantil",emoji:"🧸",bg:"#fff0d7"}
];
const photo=id=>"https://images.unsplash.com/"+id+"?auto=format&fit=crop&w=700&q=78";
const products=[
 {id:"fone-bluetooth",name:"Fone Bluetooth sem fio com case de carregamento",cat:"tecnologia",price:129.90,old:169.90,seller:"MixTech",image:photo("photo-1505740420928-5e560c06d30e"),emoji:"🎧",tag:"OFERTA",rating:"4,8"},
 {id:"serum-facial",name:"Sérum facial hidratante com vitamina C — 30 ml",cat:"beleza",price:49.90,old:69.90,seller:"BelleStore",image:photo("photo-1608248543803-ba4f8c70ae0b"),emoji:"✨",tag:"MAIS VENDIDO",rating:"4,9"},
 {id:"bolsa-transversal",name:"Bolsa transversal feminina com alça ajustável",cat:"moda",price:89.90,old:119.90,seller:"UrbanLab",image:photo("photo-1548036328-c9fa89d128fa"),emoji:"👜",tag:"NOVO",rating:"4,7"},
 {id:"cafeteira",name:"Cafeteira de vidro com prensa francesa 600 ml",cat:"casa",price:78.50,old:99.90,seller:"CasaRara",image:photo("photo-1495474472287-4d71bcdd2085"),emoji:"☕",tag:"ESCOLHA",rating:"4,8"},
 {id:"controle",name:"Controle gamer sem fio para PC e console",cat:"games",price:199.00,old:249.90,seller:"MixTech",image:photo("photo-1606144042614-b2417e99c4e3"),emoji:"🎮",tag:"OFERTA",rating:"4,7"},
 {id:"perfume",name:"Perfume eau de parfum floral feminino 50 ml",cat:"beleza",price:139.90,old:179.90,seller:"BelleStore",image:photo("photo-1541643600914-78b084683601"),emoji:"🌸",tag:"OFERTA",rating:"4,9"},
 {id:"relogio",name:"Relógio minimalista com pulseira ajustável",cat:"moda",price:119.90,old:159.90,seller:"UrbanLab",image:photo("photo-1523275335684-37898b6baf30"),emoji:"⌚",tag:"NOVO",rating:"4,7"},
 {id:"camera",name:"Câmera compacta para registrar seus momentos",cat:"tecnologia",price:349.00,old:429.00,seller:"MixTech",image:photo("photo-1516035069371-29a1b244cc32"),emoji:"📷",tag:"OFERTA",rating:"4,8"},
 {id:"poltrona",name:"Poltrona de apoio para sala e quarto",cat:"casa",price:499.90,old:629.90,seller:"CasaRara",image:photo("photo-1567538096630-e0c55bd6374c"),emoji:"🛋️",tag:"NOVO",rating:"4,6"},
 {id:"maquiagem",name:"Kit básico de maquiagem para uso diário",cat:"beleza",price:79.90,old:99.90,seller:"BelleStore",image:photo("photo-1596462502278-27bfdc403348"),emoji:"💄",tag:"ESCOLHA",rating:"4,9"},
 {id:"luminaria",name:"Luminária decorativa de mesa, design moderno",cat:"casa",price:104.90,old:139.90,seller:"CasaRara",image:photo("photo-1507473885765-e6ed057f782c"),emoji:"💡",tag:"OFERTA",rating:"4,7"},
 {id:"tenis",name:"Tênis casual leve para todos os dias",cat:"moda",price:159.90,old:209.90,seller:"UrbanLab",image:photo("photo-1542291026-7eec264c27ff"),emoji:"👟",tag:"OFERTA",rating:"4,8"}
];
const sellers=[
 {name:"MixTech",cat:"Tecnologia",status:"Ativa",items:4},
 {name:"BelleStore",cat:"Beleza",status:"Ativa",items:3},
 {name:"UrbanLab",cat:"Moda",status:"Ativa",items:3},
 {name:"CasaRara",cat:"Casa",status:"Ativa",items:3},
 {name:"Nova Parceira",cat:"Diversos",status:"Pendente",items:0}
];
const cart=Object.create(null),favorite=new Set();let sortMode="featured",sellerTab="overview",toastTimer;
const app=document.getElementById("app");
const money=v=>new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(v);
const h=v=>String(v??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
const findProduct=id=>products.find(p=>p.id===id);
const getCategory=id=>categories.find(c=>c.id===id);
const img=p=>'<img src="'+h(p.image)+'" alt="'+h(p.name)+'" loading="lazy" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'grid\'"><span class="fallback" style="display:none">'+h(p.emoji)+'</span>';
function toast(message){const node=document.getElementById("toast");node.textContent=message;node.classList.add("show");clearTimeout(toastTimer);toastTimer=setTimeout(()=>node.classList.remove("show"),3400);}
function btn(text,to,kind){return '<a class="btn '+(kind||"")+'" href="#'+to+'">'+text+'</a>';}
function demo(message){return '<div class="demo"><span><b>↗ Modo demonstração.</b> '+(message||"Esta interface usa dados fictícios e não grava no banco de dados.")+'</span><a class="text-link" href="https://github.com/gustavolmarquesg4f-dotcom/Salada-Mix" target="_blank" rel="noopener noreferrer">Código no GitHub ↗</a></div>';}
function card(p){
 return '<article class="product"><div class="product-pic"><a href="#/product/'+h(p.id)+'" aria-label="Ver '+h(p.name)+'">'+img(p)+'</a><span class="tag">'+h(p.tag)+'</span><button class="favorite '+(favorite.has(p.id)?"active":"")+'" data-fav="'+h(p.id)+'" aria-label="Favoritar '+h(p.name)+'">'+(favorite.has(p.id)?"♥":"♡")+'</button></div><div class="product-body"><div class="seller-label">Vendido por '+h(p.seller)+'</div><a href="#/product/'+h(p.id)+'"><h3>'+h(p.name)+'</h3></a><div class="rating">★★★★★ <span>'+h(p.rating)+' · Demo</span></div><div><span class="price">'+money(p.price)+'</span><span class="old-price">'+money(p.old)+'</span></div><div class="installments">Até 3x sem juros (ilustrativo)</div><div class="product-foot"><span class="ship">✧ Frete a calcular</span><button class="add" data-add="'+h(p.id)+'" aria-label="Adicionar ao carrinho">+</button></div></div></article>';
}
function productGrid(items){return items.length?'<div class="products">'+items.map(card).join("")+'</div>':'<div class="empty"><div style="font-size:38px">⌕</div><b>Nenhum produto nesta busca</b><p>Experimente outra categoria ou termo.</p>'+btn("Ver todos","/")+'</div>';}
function productSection(title,desc,items,link){
 return '<div class="section-heading"><div><h2>'+h(title)+'</h2><p>'+h(desc)+'</p></div>'+(link?'<a class="text-link" href="#'+link+'">Ver todos →</a>':"")+'</div>'+productGrid(items);
}
function home(){
 return '<section class="hero"><div class="hero-primary"><span class="pill">SALADA MIX 2.0 · NOVA EXPERIÊNCIA</span><h1>Um universo de escolhas. Do seu jeito.</h1><p>Beleza, tecnologia, moda, casa e muito mais — tudo em um marketplace pensado para descobrir coisas incríveis.</p>'+btn("Explorar ofertas →","/category/beleza","light")+'<span class="hero-decor">✦</span></div><div class="hero-side"><span class="pill orange">VENDA NO SALADA MIX</span><h2>Sua loja merece ser descoberta.</h2><p>Cadastro aberto para empresas. Análise e aprovação pela plataforma.</p><a class="text-link" href="#/join">Saiba como participar →</a></div></section>'+
 '<section class="benefits"><div class="benefit"><span class="benefit-icon">✧</span><div><b>Muitas categorias</b><small>Para todas as rotinas</small></div></div><div class="benefit"><span class="benefit-icon">▣</span><div><b>Empresas verificadas</b><small>Modelo com aprovação</small></div></div><div class="benefit"><span class="benefit-icon">◇</span><div><b>Ofertas por vendedor</b><small>Várias lojas em um lugar</small></div></div><div class="benefit"><span class="benefit-icon">♡</span><div><b>Feito para você</b><small>Experiência responsiva</small></div></div></section>'+
 '<section>'+productSection("Explore por departamento","Uma mistura de tudo o que você ama",[],null).replace('<div class="empty"><div style="font-size:38px">⌕</div><b>Nenhum produto nesta busca</b><p>Experimente outra categoria ou termo.</p>'+btn("Ver todos","/")+'</div>',"")+
 '<div class="categories">'+categories.map(c=>'<a class="category" href="#/category/'+c.id+'"><span class="category-icon" style="background:'+c.bg+'">'+c.emoji+'</span><strong>'+h(c.name)+'</strong></a>').join("")+'</div></section>'+
 '<section>'+productSection("Escolhas para descobrir","Produtos ilustrativos — prévia de navegação",products.slice(0,8),"/search")+'</section>'+
 '<div class="promo"><div><span class="pill orange">MARKETPLACE ABERTO</span><h2>Tem uma empresa? Faça parte dessa mistura.</h2><p>O cadastro público passa por avaliação antes de qualquer publicação.</p></div>'+btn("Conhecer o portal do vendedor","/seller","orange")+'</div>'+
 '<section>'+productSection("Para completar sua lista","Mais ideias para casa, tecnologia e estilo",products.slice(8),"/search")+'</section>';
}
function listing(cat,term){
 let list=products.filter(p=>(!cat||p.cat===cat)&&(!term||[p.name,p.seller,p.cat].some(t=>t.toLowerCase().includes(term.toLowerCase()))));
 if(sortMode==="price-low")list=list.slice().sort((a,b)=>a.price-b.price);
 if(sortMode==="price-high")list=list.slice().sort((a,b)=>b.price-a.price);
 const c=getCategory(cat);
 const title=c?c.name:term?'Resultados para “'+term+'”':'Todas as ofertas';
 return '<div class="crumb"><a href="#/">Início</a> / '+h(title)+'</div><section class="listing-head"><div><h1>'+h(title)+'</h1><p>'+list.length+' produtos demonstrativos</p></div><label class="sr-only" for="sort">Ordenar por</label><select class="sort" id="sort"><option value="featured"'+(sortMode==="featured"?" selected":"")+'>Mais relevantes</option><option value="price-low"'+(sortMode==="price-low"?" selected":"")+'>Menor preço</option><option value="price-high"'+(sortMode==="price-high"?" selected":"")+'>Maior preço</option></select></section>'+productGrid(list);
}
function productPage(p){
 if(!p)return '<div class="empty"><b>Produto não encontrado</b>'+btn("Voltar à loja","/")+'</div>';
 const c=getCategory(p.cat);
 return '<div class="crumb"><a href="#/">Início</a> / <a href="#/category/'+p.cat+'">'+h(c?c.name:p.cat)+'</a> / '+h(p.name)+'</div>'+
 '<div class="detail"><div class="detail-pic">'+img(p)+'</div><section class="detail-info"><span class="pill">Vendido por '+h(p.seller)+'</span><h1>'+h(p.name)+'</h1><div class="rating">★★★★★ <span>'+h(p.rating)+' · Avaliação ilustrativa</span></div><div class="divider"></div><p><b>Sobre este item</b></p><p>Esta descrição é demonstrativa. O produto ilustra a experiência da vitrine, o detalhamento e a separação por vendedor que serão conectados ao catálogo Laravel.</p><p>Categoria: '+h(c?c.name:p.cat)+' · Referência: '+h(p.id)+'</p></section>'+
 '<aside class="detail-buy"><span class="pill orange">PRÉVIA VISUAL</span><div style="margin-top:16px"><span class="price">'+money(p.price)+'</span> <span class="old-price">'+money(p.old)+'</span></div><p>Até 3x sem juros (ilustrativo)</p><div class="divider"></div><p>✧ Frete e prazo serão calculados por vendedor na versão comercial.</p><p>Loja: <b>'+h(p.seller)+'</b></p><button class="btn block" data-add="'+h(p.id)+'">Adicionar ao carrinho da prévia</button><p class="notice">Não é possível pagar ou enviar pedidos aqui. O fluxo financeiro Laravel/Mercado Pago ainda não está homologado.</p></aside></div>';
}
function cartPage(){
 const lines=Object.keys(cart).filter(id=>cart[id]>0).map(id=>({product:findProduct(id),qty:cart[id]})).filter(x=>x.product);
 const total=lines.reduce((s,x)=>s+x.product.price*x.qty,0);
 return '<div class="crumb"><a href="#/">Início</a> / Carrinho de demonstração</div><div class="dash-head"><div><h1>Meu carrinho</h1><p>Compra multivendedor representada apenas na interface.</p></div></div>'+demo("Os itens ficam somente na memória desta página; não há conta, estoque reservado ou pedido.")+
 (!lines.length?'<div class="empty"><div style="font-size:45px">♧</div><b>Seu carrinho está vazio</b><p>Explore a vitrine e adicione seus favoritos.</p>'+btn("Explorar ofertas","/")+'</div>':
 '<div class="cart-layout"><div class="panel" style="margin-top:0"><h2>'+lines.length+' produto(s)</h2>'+
 lines.map(x=>'<div class="cart-row"><img src="'+h(x.product.image)+'" alt=""><div><a href="#/product/'+x.product.id+'"><h3>'+h(x.product.name)+'</h3></a><p>Vendedor: '+h(x.product.seller)+'</p><div class="qty"><button data-qty="'+x.product.id+'" data-delta="-1" aria-label="Diminuir">−</button><b>'+x.qty+'</b><button data-qty="'+x.product.id+'" data-delta="1" aria-label="Aumentar">+</button></div></div><b>'+money(x.product.price*x.qty)+'</b></div>').join("")+
 '</div><aside class="panel" style="margin-top:0"><h2>Resumo demonstrativo</h2><div style="display:flex;justify-content:space-between"><span>Subtotal</span><b>'+money(total)+'</b></div><div class="divider"></div><p class="notice">Não há cobrança nem finalização. No marketplace real, cada vendedor terá pagamento e frete próprios até uma solução 1:N ser homologada.</p><button class="btn block" type="button" disabled>Finalizar compra (indisponível)</button></aside></div>');
}
function sellerPage(){
 const mine=products.filter(p=>p.seller==="MixTech");
 return demo("Painel com dados de exemplo. As regras reais de empresa, autorização e aprovação estão no backend Laravel.")+
 '<div class="dash-head"><div><span class="pill">PORTAL DO VENDEDOR · DEMO</span><h1>Olá, MixTech 👋</h1><p>Resumo de uma empresa ilustrativa cadastrada no Salada Mix.</p></div>'+btn("Cadastrar produto fictício","/seller/new")+'</div>'+
 '<div class="tabs"><button class="tab '+(sellerTab==="overview"?"active":"")+'" data-tab="overview">Visão geral</button><button class="tab '+(sellerTab==="catalog"?"active":"")+'" data-tab="catalog">Meus produtos</button><button class="tab '+(sellerTab==="orders"?"active":"")+'" data-tab="orders">Pedidos</button></div>'+
 (sellerTab==="overview"?'<div class="stats"><div class="stat"><small>Vendas demonstrativas</small><b>R$ 0,00</b><small>Sem pedidos reais</small></div><div class="stat"><small>Ofertas ilustrativas</small><b>'+mine.length+'</b><small>Catálogo da prévia</small></div><div class="stat"><small>Novas mensagens</small><b>0</b><small>Canal ainda não integrado</small></div><div class="stat"><small>Status empresarial</small><b style="font-size:22px;color:#11714c">Aprovada</b><small>Habilitação financeira pendente</small></div></div><section class="panel"><h2>Próximas etapas de habilitação</h2><div class="notice">Aprovação cadastral ≠ empresa ativa para receber vendas. Conexão financeira, logística e catálogo precisam estar homologados.</div></section>':
 sellerTab==="catalog"?'<section class="panel"><h2>Ofertas da loja</h2>'+tableRows(mine.concat(products.filter(p=>p.demoAdded)))+'</section>':
 '<section class="panel"><h2>Pedidos</h2><div class="empty"><b>Nenhum pedido real</b><p>Este painel é somente uma prévia visual.</p></div></section>');
}
function tableRows(items){
 return '<div class="table-wrap"><table class="data-table"><thead><tr><th>Produto</th><th>SKU</th><th>Preço</th><th>Status</th></tr></thead><tbody>'+
 items.map(p=>'<tr><td>'+h(p.name)+'</td><td>'+h(p.id)+'</td><td>'+money(p.price)+'</td><td><span class="status '+(p.demoAdded?"":"good")+'">'+(p.demoAdded?"Aprovada (demo)":"Exemplo")+'</span></td></tr>').join("")+'</tbody></table></div>';
}
function sellerNew(){
 return '<div class="crumb"><a href="#/seller">Painel vendedor</a> / Novo produto</div>'+demo("Cadastro simulado: preenchimento e moderação não enviam nada ao banco.")+
 '<div class="dash-head"><div><h1>Enviar novo produto</h1><p>Na aplicação Laravel, só gestores da empresa aprovada poderão criar ofertas.</p></div></div><form id="new-offer-form" class="form-card"><div class="form-grid"><div class="field"><label for="new-name">Nome do produto</label><input id="new-name" name="name" required maxlength="95" placeholder="Ex.: Mouse sem fio"></div><div class="field"><label for="new-sku">SKU</label><input id="new-sku" name="sku" required maxlength="25" placeholder="MOUSE-01"></div><div class="field"><label for="new-cat">Categoria</label><select id="new-cat" name="category">'+categories.map(c=>'<option value="'+c.id+'">'+h(c.name)+'</option>').join("")+'</select></div><div class="field"><label for="new-price">Preço (R$)</label><input id="new-price" name="price" required min="1" max="99999" type="number" step=".01" placeholder="129.90"></div></div><div class="field"><label for="new-desc">Descrição</label><textarea id="new-desc" name="description" rows="3" placeholder="Descreva o produto demonstrativo"></textarea></div><button class="btn">Enviar produto (simulação)</button></form>';
}
function joinPage(){
 return '<div class="dash-head"><div><span class="pill">MARKETPLACE ABERTO</span><h1>Venda no Salada Mix</h1><p>Um espaço para empresas de diferentes categorias.</p></div></div>'+demo("A inscrição abaixo é uma demonstração de interface. Não envia dados nem cria empresa.")+
 '<div class="hero" style="min-height:230px"><div class="hero-primary" style="padding:30px"><h1 style="font-size:34px">Sua loja dentro de uma experiência maior.</h1><p>Cadastre sua empresa, acompanhe a análise e prepare seu catálogo. Na versão real, apenas empresas aprovadas poderão avançar.</p></div><div class="hero-side"><h2>Como funciona?</h2><p>① Solicite o cadastro<br>② Aguarde análise<br>③ Configure sua loja<br>④ Habilite as integrações</p></div></div>'+
 '<div class="section-heading"><div><h2>Solicitação de cadastro</h2><p>Veja o formulário previsto para o onboarding.</p></div></div><form id="join-form" class="form-card"><div class="form-grid"><div class="field"><label for="legal">Razão social</label><input id="legal" required placeholder="Empresa Exemplo Ltda"></div><div class="field"><label for="trading">Nome fantasia</label><input id="trading" required placeholder="Minha Loja"></div><div class="field"><label for="cnpj">CNPJ</label><input id="cnpj" required placeholder="00.000.000/0000-00"></div><div class="field"><label for="email">E-mail comercial</label><input id="email" required type="email" placeholder="comercial@empresa.com.br"></div></div><p class="notice">Não envie dados reais neste formulário. Utilize informações fictícias para experimentar a interface.</p><button type="submit" class="btn">Simular envio para análise</button></form>';
}
function adminPage(){
 const pending=products.filter(p=>p.demoPending);
 return demo("Painel de moderação ilustrativo. Mudanças ficam somente no navegador e não concedem permissões reais.")+
 '<div class="dash-head"><div><span class="pill">ADMINISTRAÇÃO DA PLATAFORMA</span><h1>Central Salada Mix</h1><p>Aprovação de empresas, ofertas e governança do marketplace.</p></div>'+btn("Abrir documentação ↗","/admin","outline")+'</div>'+
 '<div class="stats"><div class="stat"><small>Empresas (demo)</small><b>'+sellers.length+'</b><small>Cadastro aberto</small></div><div class="stat"><small>Empresas pendentes</small><b>'+sellers.filter(s=>s.status==="Pendente").length+'</b><small>Aguardando análise</small></div><div class="stat"><small>Ofertas demonstrativas</small><b>'+products.length+'</b><small>Sem comércio real</small></div><div class="stat"><small>Vendas processadas</small><b>0</b><small>Checkout desligado</small></div></div>'+
 '<section class="panel"><h2>Solicitações de empresas</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>Empresa</th><th>Categoria</th><th>Status</th><th>Ação demonstrativa</th></tr></thead><tbody>'+
 sellers.map((s,i)=>'<tr><td><b>'+h(s.name)+'</b></td><td>'+h(s.cat)+'</td><td><span class="status '+(s.status==="Ativa"?"good":"")+'">'+h(s.status)+'</span></td><td>'+(s.status==="Pendente"?'<button class="btn sm" data-approve-seller="'+i+'">Simular aprovação</button>':'<span style="color:#708481">—</span>')+'</td></tr>').join("")+'</tbody></table></div></section>'+
 '<section class="panel"><h2>Ofertas em análise</h2>'+(pending.length?'<div class="table-wrap"><table class="data-table"><thead><tr><th>Produto</th><th>Empresa</th><th>Status</th><th>Ação</th></tr></thead><tbody>'+pending.map(p=>'<tr><td>'+h(p.name)+'</td><td>'+h(p.seller)+'</td><td><span class="status">Pendente</span></td><td><button class="btn sm" data-approve-product="'+h(p.id)+'">Aprovar (demo)</button></td></tr>').join("")+'</tbody></table></div>':'<div class="notice">Nenhuma oferta nova pendente na demonstração.</div>')+'</section>'+
 '<section class="panel"><h2>Controles necessários antes do lançamento</h2><div class="notice">MFA administrativo, habilitação do PSP, políticas comerciais e fiscais, moderação, LGPD operacional, backups externos e testes de recuperação permanecem bloqueadores de produção.</div></section>';
}
function route(){
 const full=(location.hash||"#/").slice(1);
 const path=full.split("?")[0]||"/";
 const qs=new URLSearchParams(full.includes("?")?full.slice(full.indexOf("?")+1):"");
 let output;
 if(path==="/")output=home();
 else if(path.startsWith("/category/"))output=listing(path.slice("/category/".length),"");
 else if(path==="/search")output=listing(null,qs.get("q")||"");
 else if(path.startsWith("/product/"))output=productPage(findProduct(path.slice("/product/".length)));
 else if(path==="/cart")output=cartPage();
 else if(path==="/seller")output=sellerPage();
 else if(path==="/seller/new")output=sellerNew();
 else if(path==="/join")output=joinPage();
 else if(path==="/admin")output=adminPage();
 else output='<div class="empty"><b>Página não encontrada</b>'+btn("Voltar para o início","/")+'</div>';
 app.innerHTML=output;
 document.title=(path==="/?"?"Loja":path==="/seller"?"Vendedor":path==="/admin"?"Administração":"Salada Mix")+" | Prévia do marketplace";
 document.querySelectorAll(".nav-list a").forEach(el=>el.classList.toggle("active",el.getAttribute("href")==="#"+path));
 document.getElementById("nav-strip").classList.remove("open");
 document.getElementById("mobile-menu").setAttribute("aria-expanded","false");
 updateCount();
 window.scrollTo({top:0,behavior:"auto"});
}
function updateCount(){const count=Object.values(cart).reduce((a,n)=>a+n,0),el=document.getElementById("cart-count");el.textContent=count;el.hidden=count===0;}
document.getElementById("search-form").addEventListener("submit",e=>{e.preventDefault();const q=document.getElementById("search-input").value.trim();sortMode="featured";location.hash=q?"/search?q="+encodeURIComponent(q):"/search";if(location.hash==="#/search")route();});
document.getElementById("mobile-menu").addEventListener("click",()=>{const nav=document.getElementById("nav-strip");nav.classList.toggle("open");document.getElementById("mobile-menu").setAttribute("aria-expanded",nav.classList.contains("open")?"true":"false");});
app.addEventListener("click",e=>{
 const add=e.target.closest("[data-add]");if(add){const id=add.dataset.add;cart[id]=(cart[id]||0)+1;updateCount();toast("Produto adicionado ao carrinho de demonstração.");return;}
 const fav=e.target.closest("[data-fav]");if(fav){const id=fav.dataset.fav;favorite.has(id)?favorite.delete(id):favorite.add(id);fav.classList.toggle("active");fav.textContent=favorite.has(id)?"♥":"♡";toast("Preferência registrada apenas nesta prévia.");return;}
 const quantity=e.target.closest("[data-qty]");if(quantity){const id=quantity.dataset.qty;cart[id]=Math.max(0,(cart[id]||0)+Number(quantity.dataset.delta));if(!cart[id])delete cart[id];route();return;}
 const tab=e.target.closest("[data-tab]");if(tab){sellerTab=tab.dataset.tab;route();return;}
 const seller=e.target.closest("[data-approve-seller]");if(seller){sellers[Number(seller.dataset.approveSeller)].status="Aprovada";toast("Aprovação ilustrativa aplicada na prévia, sem modificar Laravel.");route();return;}
 const product=e.target.closest("[data-approve-product]");if(product){const p=findProduct(product.dataset.approveProduct);if(p){p.demoPending=false;p.demoAdded=true;toast("Oferta aprovada somente na prévia. Ainda não é uma venda real.");route();}return;}
});
app.addEventListener("change",e=>{if(e.target.id==="sort"){sortMode=e.target.value;route();}});
app.addEventListener("submit",e=>{
 if(e.target.id==="join-form"){e.preventDefault();e.target.reset();toast("Simulação concluída. Nenhum dado foi enviado ou armazenado.");return;}
 if(e.target.id==="new-offer-form"){
 e.preventDefault();
 const data=new FormData(e.target);
 const id="demo-"+Math.random().toString(36).slice(2,8);
 const p={id,name:String(data.get("name")),cat:String(data.get("category")),price:Number(data.get("price")),old:Number(data.get("price"))*1.15,seller:"MixTech",image:"",emoji:getCategory(String(data.get("category"))).emoji,tag:"DEMO",rating:"—",demoPending:true};
 products.push(p);toast("Produto fictício enviado para moderação ilustrativa.");location.hash="/admin";return;
 }
});
window.addEventListener("hashchange",route);
route();
