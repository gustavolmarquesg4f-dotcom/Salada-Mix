"use strict";
/* FE-02 static visualization of the real Laravel UI. All data below comes from the
 * local-only MarketplaceDemoSeeder, not an online database. No personal data,
 * authentication, checkout, financial processing, or stock mutation happens here. */
const CATEGORIES=[
 {slug:"beleza-e-cuidados",name:"Beleza e Cuidados"},
 {slug:"tecnologia-e-informatica",name:"Tecnologia e Informática"},
 {slug:"moda-e-acessorios",name:"Moda e Acessórios"},
 {slug:"casa-e-decoracao",name:"Casa e Decoração"},
 {slug:"games",name:"Games"},
 {slug:"infantil-e-brinquedos",name:"Infantil e Brinquedos"},
 {slug:"eletrodomesticos",name:"Eletrodomésticos"},
 {slug:"esporte-e-lazer",name:"Esporte e Lazer"},
 {slug:"papelaria",name:"Papelaria"},
 {slug:"pet-shop",name:"Pet Shop"}
];
const SELLERS=["MixTech (DEMO)","BelleStore (DEMO)","MixCasa (DEMO)"];
const PRODUCTS=[
 {id:"demo-tech-fone",name:"Fone Bluetooth sem fio (DEMO)",category:"tecnologia-e-informatica",seller:SELLERS[0],price:12990,stock:16,sku:"DEMO-TECH-FONE"},
 {id:"demo-tech-mouse",name:"Mouse ergonômico sem fio (DEMO)",category:"tecnologia-e-informatica",seller:SELLERS[0],price:6990,stock:14,sku:"DEMO-TECH-MOUSE"},
 {id:"demo-tech-camera",name:"Câmera compacta (DEMO)",category:"tecnologia-e-informatica",seller:SELLERS[0],price:34900,stock:8,sku:"DEMO-TECH-CAMERA"},
 {id:"demo-belle-serum",name:"Sérum facial vitamina C (DEMO)",category:"beleza-e-cuidados",seller:SELLERS[1],price:4990,stock:18,sku:"DEMO-BELLE-SERUM"},
 {id:"demo-belle-maquiagem",name:"Kit de maquiagem (DEMO)",category:"beleza-e-cuidados",seller:SELLERS[1],price:7990,stock:10,sku:"DEMO-BELLE-MAQUIAGEM"},
 {id:"demo-belle-perfume",name:"Perfume floral 50 ml (DEMO)",category:"beleza-e-cuidados",seller:SELLERS[1],price:13990,stock:12,sku:"DEMO-BELLE-PERFUME"},
 {id:"demo-home-cafe",name:"Cafeteira de vidro 600 ml (DEMO)",category:"casa-e-decoracao",seller:SELLERS[2],price:7850,stock:20,sku:"DEMO-HOME-CAFE"},
 {id:"demo-home-light",name:"Luminária de mesa (DEMO)",category:"casa-e-decoracao",seller:SELLERS[2],price:10490,stock:15,sku:"DEMO-HOME-LIGHT"},
 {id:"demo-home-bag",name:"Bolsa transversal ajustável (DEMO)",category:"moda-e-acessorios",seller:SELLERS[2],price:8990,stock:9,sku:"DEMO-HOME-BAG"}
];
const ROOT=document.getElementById("conteudo");
const money=cents=>new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(cents/100);
const esc=x=>String(x??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
const category=slug=>CATEGORIES.find(c=>c.slug===slug);
const product=id=>PRODUCTS.find(p=>p.id===id);
const detail=p=>"#/ofertas/"+encodeURIComponent(p.id);
const categoryUrl=slug=>"#/categorias/"+encodeURIComponent(slug);
const note=(text,warning=false)=>'<div class="sm-preview-info'+(warning?" warning":"")+'">'+text+'</div>';
const brandName=p=>esc(category(p.category)?.name||"Departamento");
const version='<div class="sm-preview-kicker"><span class="sm-preview-pill">FE-01 + FE-02 · MAIN</span><span>Catálogo de teste (MySQL local) reproduzido nesta página estática</span></div>';
function card(p){
 return '<article class="sm-product-card"><a href="'+detail(p)+'" class="block" aria-label="Ver oferta: '+esc(p.name)+'"><div class="sm-product-visual" aria-hidden="true">'+brandName(p)+'</div><div class="sm-product-body"><p class="sm-product-seller">Vendido por '+esc(p.seller)+'</p><h3 class="sm-product-name">'+esc(p.name)+'</h3><p class="sm-product-price">'+money(p.price)+'</p><p class="sm-product-hint">Consulte os detalhes · compras indisponíveis nesta fase</p></div></a></article>';
}
function grid(items){
 return items.length?'<div class="sm-offer-grid">'+items.map(card).join("")+'</div>':
 '<div class="sm-empty"><strong>Nenhuma oferta encontrada com esses filtros.</strong><p>Tente outra palavra-chave, remova um filtro ou explore os departamentos disponíveis.</p><a class="sm-btn sm-btn-secondary" href="#/buscar">Limpar filtros</a></div>';
}
function home(){
 return version+'<section class="sm-home-hero" aria-labelledby="sm-hero-title"><div><span class="sm-eyebrow">O marketplace de todos os estilos</span><h1 id="sm-hero-title">Seu mix. Seu estilo. Tudo num só lugar.</h1><p>Beleza, moda, tecnologia, casa e muito mais. Estamos reunindo empresas e preparando uma experiência de compra para todos.</p><div class="sm-hero-ctas"><a class="sm-btn sm-btn-primary" href="#/buscar">Explorar produtos</a><a class="sm-btn sm-btn-secondary" href="#/vender/cadastro">Quero vender</a></div></div><div class="sm-hero-art" aria-hidden="true"><span class="sm-hero-circle"></span><img class="sm-hero-logo" src="assets/salada/salada-mix-simbolo.svg" alt=""><span class="sm-spark one"></span><span class="sm-spark two"></span></div></section>'+
 '<section id="departamentos" aria-labelledby="departamentos-titulo"><div class="sm-section-heading"><h2 id="departamentos-titulo">Explore os departamentos</h2><a href="#/buscar">Ver todos os produtos →</a></div><div class="sm-category-grid">'+CATEGORIES.map(c=>'<a class="sm-category-link" href="'+categoryUrl(c.slug)+'">'+esc(c.name)+'</a>').join("")+'</div></section>'+
 '<section id="ofertas" aria-labelledby="ofertas-titulo"><div class="sm-section-heading"><h2 id="ofertas-titulo">Ofertas disponíveis</h2><a href="#/buscar">Explorar catálogo →</a></div>'+grid([...PRODUCTS].reverse().slice(0,8))+'</section>';
}
function priceInput(raw){
 if(!/^[0-9]{1,7}([.,][0-9]{1,2})?$/.test(raw||""))return null;
 const [whole,frac=""]=raw.replace(",",".").split(".");
 return Number(whole)*100+Number(frac.padEnd(2,"0"));
}
function filterProducts(params,fixedCategory){
 const q=(params.get("q")||"").trim().toLocaleLowerCase("pt-BR");
 const cat=fixedCategory||params.get("category")||"";
 const seller=params.get("seller")||"";
 const min=priceInput(params.get("min_price")||""),max=priceInput(params.get("max_price")||"");
 const sort=params.get("sort")||"recent";
 let items=PRODUCTS.filter(p=>(!q||[p.name,p.seller].some(v=>v.toLocaleLowerCase("pt-BR").includes(q)))&&(!cat||p.category===cat)&&(!seller||p.seller===seller)&& (min===null||p.price>=min)&&(max===null||p.price<=max));
 if(sort==="price_asc")items=[...items].sort((a,b)=>a.price-b.price||a.id.localeCompare(b.id));
 else if(sort==="price_desc")items=[...items].sort((a,b)=>b.price-a.price||a.id.localeCompare(b.id));
 else items=[...items].reverse();
 return items;
}
function browse(params,fixedCategory){
 const cat=fixedCategory?category(fixedCategory):null;
 if(fixedCategory&&!cat)return missing();
 const items=filterProducts(params,fixedCategory);
 const selected=(field,value)=>params.get(field)===value?' selected':'';
 const q=esc(params.get("q")||"");
 const title=cat?cat.name:"Encontre o que procura";
 const base=cat?categoryUrl(cat.slug):"#/buscar";
 return '<nav class="sm-breadcrumb" aria-label="Caminho de navegação"><a href="#/">Início</a><span>/</span><span aria-current="page">'+esc(cat?cat.name:"Buscar")+'</span></nav>'+
 '<div class="sm-browse-heading"><div><span class="sm-eyebrow">Explore o seu mix</span><h1>'+esc(title)+'</h1><p>'+items.length+(items.length===1?" oferta disponível":" ofertas disponíveis")+(q?' para "'+q+'"':"")+'.</p></div></div>'+
 note("A busca, os filtros e a ordenação funcionam com os mesmos produtos fictícios do seeder local. Nesta URL não há conexão com MySQL.")+
 '<div class="sm-browse-layout"><aside class="sm-filter-panel" aria-label="Filtros de produtos"><h2>Refinar busca</h2><form id="browse-form" method="get" data-category="'+esc(fixedCategory||"")+'"><label for="sm-filter-q">Buscar por produto ou loja</label><input id="sm-filter-q" name="q" type="search" maxlength="100" value="'+q+'" placeholder="Nome ou palavra-chave">'+
 (cat?"":'<label for="sm-filter-category">Departamento</label><select id="sm-filter-category" name="category"><option value="">Todos os departamentos</option>'+CATEGORIES.map(c=>'<option value="'+esc(c.slug)+'"'+selected("category",c.slug)+'>'+esc(c.name)+'</option>').join("")+'</select>')+
 '<label for="sm-filter-seller">Vendido por</label><select id="sm-filter-seller" name="seller"><option value="">Todas as lojas</option>'+SELLERS.map(s=>'<option value="'+esc(s)+'"'+selected("seller",s)+'>'+esc(s)+'</option>').join("")+'</select>'+
 '<span class="sm-filter-label">Preço (R$)</span><div class="sm-price-fields"><div><label for="sm-min-price">De</label><input id="sm-min-price" name="min_price" inputmode="decimal" type="text" pattern="[0-9]{1,7}([.,][0-9]{1,2})?" maxlength="10" value="'+esc(params.get("min_price")||"")+'" placeholder="0,00"></div><div><label for="sm-max-price">Até</label><input id="sm-max-price" name="max_price" inputmode="decimal" type="text" pattern="[0-9]{1,7}([.,][0-9]{1,2})?" maxlength="10" value="'+esc(params.get("max_price")||"")+'" placeholder="999,00"></div></div>'+
 '<label for="sm-filter-sort">Ordenar por</label><select id="sm-filter-sort" name="sort"><option value="recent"'+(!params.get("sort")||selected("sort","recent")?" selected":"")+'>Mais recentes</option><option value="price_asc"'+selected("sort","price_asc")+'>Menor preço</option><option value="price_desc"'+selected("sort","price_desc")+'>Maior preço</option></select>'+
 '<button class="sm-btn sm-btn-primary sm-filter-submit" type="submit">Aplicar filtros</button><a class="sm-filter-clear" href="'+base+'">Limpar filtros</a></form></aside>'+
 '<section class="sm-browse-results" aria-label="Resultados da busca"><div class="sm-result-bar"><span>Exibindo '+(items.length?1:0)+'–'+items.length+' de '+items.length+'</span><span>Produtos de empresas habilitadas (dados fictícios)</span></div>'+grid(items)+'</section></div>';
}
function offer(id){
 const p=product(id);if(!p)return missing();
 const title=brandName(p);
 return '<nav class="sm-breadcrumb" aria-label="Caminho de navegação"><a href="#/">Início</a><span>/</span><a href="'+categoryUrl(p.category)+'">'+title+'</a><span>/</span><span aria-current="page">'+esc(p.name)+'</span></nav>'+
 '<article class="sm-detail"><div class="sm-detail-gallery"><div class="sm-detail-visual" role="img" aria-label="Imagem do produto ainda não cadastrada">'+title+'</div><p>Imagem ilustrativa indisponível. Mídias reais serão cadastradas em etapa posterior.</p></div>'+
 '<div class="sm-detail-info"><p class="sm-eyebrow">Vendido por '+esc(p.seller)+'</p><h1>'+esc(p.name)+'</h1><p class="sm-detail-sku">Referência do vendedor: '+esc(p.sku)+'</p><p class="sm-detail-price">'+money(p.price)+'</p><p class="sm-detail-stock">Disponível para consulta · '+p.stock+' em estoque (dados fictícios)</p><div class="sm-notice info"><strong>O checkout está desativado.</strong> Estamos preparando o pagamento e o cálculo de frete para compras entre diferentes vendedores.</div><a class="sm-btn sm-btn-secondary" href="'+categoryUrl(p.category)+'">Ver mais neste departamento</a></div>'+
 '<section class="sm-detail-description"><h2>Descrição do produto</h2><p>Produto fictício para validação visual do Salada Mix. Não disponível para compra.</p></section></article>';
}
function join(){
 return version+'<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span>Vender</nav><div class="sm-preview-panel"><span class="sm-eyebrow">Marketplace aberto</span><h1>Faça parte do Salada Mix</h1><p>Envie os dados da empresa para avaliação. A aprovação cadastral não libera automaticamente pagamentos ou publicação de produtos.</p>'+
 note("<strong>Prévia estática:</strong> este formulário não recebe nem armazena dados. Use apenas informações fictícias.",true)+
 '<form id="join-form"><div class="sm-preview-form"><label>Razão social<input required name="legal_name" maxlength="200" placeholder="Empresa Exemplo Ltda"></label><label>Nome fantasia<input required name="trade_name" maxlength="160" placeholder="Minha Loja"></label><label>CNPJ fictício<input required name="cnpj" maxlength="18" placeholder="00.000.000/0000-00"></label><label>E-mail fictício<input required type="email" name="email" placeholder="empresa@example.test"></label></div><div class="sm-preview-actions"><button class="sm-btn sm-btn-primary" type="submit">Simular solicitação</button><a class="sm-preview-link" href="#/vendedor">Ver painel ilustrativo →</a></div></form><div id="join-message" class="sm-preview-info sm-preview-hidden" role="status" style="margin-top:16px"></div></div>';
}
function seller(){
 return version+'<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span>Área do vendedor</nav><div class="sm-preview-panel"><span class="sm-eyebrow">Portal vendedor · demonstração</span><h1>Minha empresa — MixTech (DEMO)</h1><p>O backend Laravel possui cadastro, vínculos e autorização por empresa; esta tela apresenta apenas dados ilustrativos.</p>'+note("Status: aprovação cadastral não equivale à habilitação de vendas. Conexão financeira, logística e moderação são etapas próprias.",true)+
 '<div class="sm-preview-grid"><div class="sm-preview-stat"><strong>3</strong><span>Ofertas fictícias desta loja</span></div><div class="sm-preview-stat"><strong>0</strong><span>Pedidos reais</span></div><div class="sm-preview-stat"><strong>—</strong><span>Recebimentos indisponíveis</span></div></div><h2 style="margin-top:26px">Ofertas da empresa</h2><div style="overflow-x:auto"><table class="sm-preview-table"><thead><tr><th>Produto</th><th>SKU</th><th>Preço</th><th>Exibição</th></tr></thead><tbody>'+PRODUCTS.filter(p=>p.seller===SELLERS[0]).map(p=>'<tr><td>'+esc(p.name)+'</td><td>'+esc(p.sku)+'</td><td>'+money(p.price)+'</td><td>Demo local</td></tr>').join("")+'</tbody></table></div><div class="sm-preview-actions"><a class="sm-btn sm-btn-secondary" href="#/vender/cadastro">Ver inscrição de empresa</a></div></div>';
}
function admin(){
 return version+'<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span>Administração</nav><div class="sm-preview-panel"><span class="sm-eyebrow">Administração · demonstração</span><h1>Governança do marketplace</h1>'+note("A interface real exige login e permissão administrativa. Aqui nenhuma decisão é persistida nem aplicada a empresas.",true)+
 '<div class="sm-preview-grid"><div class="sm-preview-stat"><strong>3</strong><span>Empresas fictícias</span></div><div class="sm-preview-stat"><strong>9</strong><span>Ofertas fictícias</span></div><div class="sm-preview-stat"><strong>0</strong><span>Transações reais</span></div></div><h2 style="margin-top:25px">Empresas ilustrativas</h2><div style="overflow-x:auto"><table class="sm-preview-table"><thead><tr><th>Empresa</th><th>Produtos</th><th>Ambiente</th></tr></thead><tbody>'+SELLERS.map(s=>'<tr><td>'+esc(s)+'</td><td>'+PRODUCTS.filter(p=>p.seller===s).length+'</td><td>Fictício</td></tr>').join("")+'</tbody></table></div></div>';
}
function account(){
 return '<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span>Minha conta</nav><div class="sm-preview-panel"><h1>Acesso à conta</h1>'+note("O GitHub Pages não executa autenticação nem armazena dados. Cadastro e login estão implementados no Laravel, mas precisam de um servidor PHP e configuração de e-mail para homologação.",true)+'<a class="sm-btn sm-btn-secondary" href="#/">Voltar para a loja</a></div>';
}
function missing(){return '<div class="sm-empty"><strong>Conteúdo não encontrado nesta prévia.</strong><a class="sm-btn sm-btn-secondary" href="#/">Voltar ao início</a></div>';}
function route(){
 const raw=(location.hash||"#/").slice(1),path=raw.split("?")[0]||"/",params=new URLSearchParams(raw.includes("?")?raw.slice(raw.indexOf("?")+1):"");
 let html;
 if(path==="/"||path==="/departamentos"||path==="/ofertas")html=home();
 else if(path==="/buscar")html=browse(params,null);
 else if(path.startsWith("/categorias/"))html=browse(params,decodeURIComponent(path.slice(12)));
 else if(path.startsWith("/ofertas/"))html=offer(decodeURIComponent(path.slice(9)));
 else if(path==="/vender/cadastro")html=join();
 else if(path==="/vendedor")html=seller();
 else if(path==="/admin")html=admin();
 else if(path==="/conta")html=account();
 else html=missing();
 ROOT.innerHTML=html;
 const search=document.getElementById("sm-global-search");if(search)search.value=params.get("q")||"";
 document.title=(path==="/buscar"?"Buscar":path.startsWith("/categorias/")?(category(decodeURIComponent(path.slice(12)))?.name||"Categoria"):path.startsWith("/ofertas/")?(product(decodeURIComponent(path.slice(9)))?.name||"Oferta"):"Salada Mix")+" — Entrega 2 (prévia)";
 const menu=document.querySelector(".sm-mobile-details");if(menu)menu.open=false;
 if(path==="/departamentos"||path==="/ofertas"){const section=document.getElementById(path);if(section)section.scrollIntoView({block:"start"});}
 else if(typeof window.scrollTo==="function")window.scrollTo(0,0);
}
document.getElementById("global-search").addEventListener("submit",event=>{
 event.preventDefault();const input=document.getElementById("sm-global-search");const q=input?input.value.trim():"";
 location.hash="#/buscar"+(q?"?q="+encodeURIComponent(q):"");
 if(!q&&location.hash==="#/buscar")route();
});
ROOT.addEventListener("submit",event=>{
 if(event.target.id==="browse-form"){
  event.preventDefault();const data=new FormData(event.target),params=new URLSearchParams();
  for(const [key,value] of data.entries()){if(String(value).trim())params.set(key,String(value).trim());}
  const cat=event.target.dataset.category,base=cat?"#/categorias/"+encodeURIComponent(cat):"#/buscar";
  location.hash=base+(params.toString()?"?"+params.toString():"");
  if(location.hash===base)route();
 }
 if(event.target.id==="join-form"){event.preventDefault();event.target.reset();const msg=document.getElementById("join-message");if(msg){msg.classList.remove("sm-preview-hidden");msg.textContent="Simulação concluída. Nenhum cadastro foi realizado nem informação enviada ao servidor.";}}
});
window.addEventListener("hashchange",route);
route();
