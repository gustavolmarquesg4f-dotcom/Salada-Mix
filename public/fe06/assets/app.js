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
const DEMO_PHOTOS={"demo-tech-fone":"photo-1505740420928-5e560c06d30e","demo-tech-mouse":"photo-1527814050087-3793815479db","demo-tech-camera":"photo-1516035069371-29a1b244cc32","demo-belle-serum":"photo-1556228578-0d85b1a4d571","demo-belle-maquiagem":"photo-1596462502278-27bfdc403348","demo-belle-perfume":"photo-1541643600914-78b084683601","demo-home-cafe":"photo-1495474472287-4d71bcdd2085","demo-home-light":"photo-1507473885765-e6ed057f782c","demo-home-bag":"photo-1547949003-9792a18a2601"};
const DEMO_CATEGORIES={"beleza-e-cuidados":"photo-1596462502278-27bfdc403348","moda-e-acessorios":"photo-1547949003-9792a18a2601","tecnologia-e-informatica":"photo-1505740420928-5e560c06d30e","casa-e-decoracao":"photo-1493663284031-b7e3aefcae8c","infantil-e-brinquedos":"photo-1558060370-d644479cb6f7","eletrodomesticos":"photo-1495474472287-4d71bcdd2085"};
const photoUrl=(id,w=520)=>id?'https://images.unsplash.com/'+id+'?auto=format&fit=crop&w='+w+'&q=80':'';
const PREVIEW_CART=new Map([["demo-tech-fone",1],["demo-belle-serum",1]]);
const PREVIEW_FAVORITES=new Set(["demo-belle-serum","demo-home-bag"]);
const ROOT=document.getElementById("conteudo");
const money=cents=>new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(cents/100);
const esc=x=>String(x??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
const category=slug=>CATEGORIES.find(c=>c.slug===slug);
const product=id=>PRODUCTS.find(p=>p.id===id);
const detail=p=>"#/ofertas/"+encodeURIComponent(p.id);
const categoryUrl=slug=>"#/categorias/"+encodeURIComponent(slug);
const note=(text,warning=false)=>'<div class="sm-preview-info'+(warning?" warning":"")+'">'+text+'</div>';
const brandName=p=>esc(category(p.category)?.name||"Departamento");
const version='<div class="sm-preview-kicker"><span class="sm-preview-pill">FE-06 · VISUAL DEMO</span><span>Catálogo de teste (MySQL local) reproduzido nesta página estática</span></div>';
function card(p){
 const photo=photoUrl(DEMO_PHOTOS[p.id]);
 return '<article class="sm-product-card"><a href="'+detail(p)+'" class="sm-product-link" aria-label="Ver oferta: '+esc(p.name)+'"><div class="sm-product-visual sm-product-photo"><img class="sm-demo-image" src="'+photo+'" alt="Imagem ilustrativa: '+esc(p.name)+'" width="480" height="480" loading="lazy" referrerpolicy="no-referrer"><span class="sm-product-demo-badge">DEMO</span></div><div class="sm-product-body"><p class="sm-product-seller">'+esc(p.seller)+'</p><h3 class="sm-product-name">'+esc(p.name)+'</h3><p class="sm-product-price">'+money(p.price)+'</p><p class="sm-product-hint">Ver detalhes →</p></div></a><div class="sm-card-actions"><button type="button" class="sm-action-add" data-demo-action="cart-add" data-id="'+esc(p.id)+'">Adicionar à sacola</button><button type="button" class="sm-action-favorite" data-demo-action="wishlist-toggle" data-id="'+esc(p.id)+'" aria-label="Favoritar '+esc(p.name)+'">'+(PREVIEW_FAVORITES.has(p.id)?'♥':'♡')+'</button></div></article>';
}
function grid(items){
 return items.length?'<div class="sm-offer-grid">'+items.map(card).join("")+'</div>':
 '<div class="sm-empty"><strong>Nenhuma oferta encontrada com esses filtros.</strong><p>Tente outra palavra-chave, remova um filtro ou explore os departamentos disponíveis.</p><a class="sm-btn sm-btn-secondary" href="#/buscar">Limpar filtros</a></div>';
}
function home(){
 const catCards=CATEGORIES.map(c=>{
  const img=DEMO_CATEGORIES[c.slug]?'<img src="'+photoUrl(DEMO_CATEGORIES[c.slug],240)+'" alt="" loading="lazy" referrerpolicy="no-referrer">':'<svg width="27" height="27" aria-hidden="true"><use href="assets/salada/icons.svg#grid"></use></svg>';
  return '<a class="sm-photo-category" href="'+categoryUrl(c.slug)+'"><span class="sm-photo-category-visual">'+img+'</span><strong>'+esc(c.name)+'</strong></a>';
 }).join("");
 const promo=[['beauty','Beleza para sua rotina','beleza-e-cuidados','photo-1596462502278-27bfdc403348'],['technology','Tecnologia para o dia a dia','tecnologia-e-informatica','photo-1505740420928-5e560c06d30e'],['home','Sua casa, seu jeito','casa-e-decoracao','photo-1493663284031-b7e3aefcae8c']];
 return version+'<div class="sm-demo-label" role="status"><strong>SALADA MIX · DEMONSTRAÇÃO</strong><span>Produtos ilustrativos, preços e vendedores sintéticos. Sem compras reais.</span></div>'+
 '<section class="sm-editorial-hero" aria-labelledby="sm-hero-title"><div class="sm-editorial-copy"><span class="sm-eyebrow">SALADA MIX · SEU UNIVERSO DE POSSIBILIDADES</span><h1 id="sm-hero-title">Tudo o que você ama, em um só lugar.</h1><p>Beleza, moda, tecnologia, casa e muito mais. Um marketplace para descobrir produtos de todos os estilos.</p><div class="sm-hero-ctas"><a class="sm-btn sm-btn-primary" href="#/buscar">Explorar produtos →</a><a class="sm-btn sm-btn-secondary" href="#/departamentos">Ver departamentos</a></div></div><div class="sm-editorial-media"><img src="'+photoUrl('photo-1483985988355-763728e1935b',1100)+'" alt="Fotografia ilustrativa de moda" width="560" height="360" referrerpolicy="no-referrer"><span class="sm-hero-sticker">Vários estilos.<br><strong>Um só mix.</strong></span></div></section>'+
 '<section class="sm-market-highlights" aria-label="Informações da plataforma"><span>▦ Muitos departamentos</span><span>⌕ Busque por produtos e lojas</span><span>◇ Empresas sujeitas a aprovação</span></section>'+
 '<section id="departamentos" aria-labelledby="departamentos-titulo"><div class="sm-section-heading"><div><span class="sm-eyebrow">Encontre seu estilo</span><h2 id="departamentos-titulo">Explore os departamentos</h2></div><a href="#/buscar">Ver todos →</a></div><div class="sm-photo-category-grid">'+catCards+'</div></section>'+
 '<section id="ofertas" aria-labelledby="ofertas-titulo"><div class="sm-section-heading"><div><span class="sm-eyebrow">Descubra o seu próximo achado</span><h2 id="ofertas-titulo">Produtos em destaque</h2></div><a href="#/buscar">Explorar catálogo →</a></div><p class="sm-demo-footnote">Produtos ilustrativos de homologação · sem descontos, avaliações ou condições de frete simuladas.</p>'+grid([...PRODUCTS].reverse().slice(0,8))+'</section>'+
 '<section class="sm-promo-grid" aria-label="Inspirações de departamento ilustrativas">'+promo.map(x=>'<a class="sm-promo-tile" href="'+categoryUrl(x[2])+'"><img src="'+photoUrl(x[3],700)+'" alt="" width="450" height="230" loading="lazy" referrerpolicy="no-referrer"><span><strong>'+x[1]+'</strong><small>Descubra produtos →</small></span></a>').join("")+'</section>';
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
 return '<div class="sm-demo-label"><strong>PRODUTO DE DEMONSTRAÇÃO</strong><span>Imagem ilustrativa e dados sintéticos. Não disponível para compra.</span></div>'+
 '<nav class="sm-breadcrumb" aria-label="Caminho de navegação"><a href="#/">Início</a><span>/</span><a href="'+categoryUrl(p.category)+'">'+title+'</a><span>/</span><span aria-current="page">'+esc(p.name)+'</span></nav>'+
 '<article class="sm-detail sm-detail-premium"><div class="sm-detail-gallery"><img class="sm-detail-image" src="'+photoUrl(DEMO_PHOTOS[p.id],720)+'" alt="Imagem ilustrativa: '+esc(p.name)+'" width="720" height="720" referrerpolicy="no-referrer"><p>Imagem meramente ilustrativa, vinculada apenas ao catálogo sintético.</p></div>'+
 '<div class="sm-detail-info"><span class="sm-detail-category">'+title+'</span><p class="sm-eyebrow">Vendido por '+esc(p.seller)+'</p><h1>'+esc(p.name)+'</h1><p class="sm-detail-sku">Referência do vendedor: '+esc(p.sku)+'</p><p class="sm-detail-price">'+money(p.price)+'</p><p class="sm-detail-stock">Disponível para consulta · '+p.stock+' unidades cadastradas (dados fictícios)</p><div class="sm-card-actions sm-card-actions-compact"><button type="button" class="sm-action-add" data-demo-action="cart-add" data-id="'+esc(p.id)+'">Adicionar à sacola</button><button type="button" class="sm-action-favorite" data-demo-action="wishlist-toggle" data-id="'+esc(p.id)+'">♡ Favoritar</button></div><div class="sm-notice info"><strong>O checkout está desativado.</strong> Estamos preparando pagamentos e logística para compras entre diferentes vendedores.</div><a class="sm-btn sm-btn-secondary" href="'+categoryUrl(p.category)+'">Mais neste departamento →</a></div>'+
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
function authScreen(mode){
 const title=mode==="register"?"Crie sua conta":mode==="forgot"?"Esqueceu sua senha?":"Entrar na minha conta";
 const fields=mode==="register"?["Nome completo","E-mail","Crie uma senha","Confirme sua senha"]:mode==="forgot"?["E-mail da conta"]:["E-mail","Senha"];
 const inputs=fields.map(label=>'<label class="sm-static-label">'+label+'<input disabled tabindex="-1" placeholder="'+(label.includes("mail")?"voce@exemplo.com":"Preenchimento disponível no Laravel")+'" aria-label="'+label+' (demonstração)"></label>').join("");
 const action=mode==="register"?"Criar minha conta":mode==="forgot"?"Enviar instruções":"Entrar na minha conta";
 return version+'<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span><span>'+title+'</span></nav>'+
 '<div class="sm-demo-label"><strong>DEMONSTRAÇÃO VISUAL · FE-04</strong><span>Não digite dados reais: esta prévia não executa autenticação nem envia formulários.</span></div>'+
 '<div class="sm-auth-layout"><section class="sm-auth-card"><a class="sm-auth-back" href="#/">← Voltar para a loja</a><span class="sm-eyebrow">Sua conta Salada Mix</span><h1>'+title+'</h1><p class="sm-auth-subtitle">As credenciais são processadas apenas pelo aplicativo Laravel, não pelo GitHub Pages.</p><div class="sm-auth-form">'+inputs+'<button class="sm-btn sm-btn-primary sm-auth-submit" disabled type="button">'+action+' →</button></div><div class="sm-auth-divider"><span>Outras opções</span></div><p class="sm-form-help">Google e GitHub aparecem somente quando o SSO está configurado no servidor Laravel.</p><p class="sm-auth-foot"><a href="#/entrar">Entrar</a> · <a href="#/cadastro">Criar conta</a> · <a href="#/recuperar">Recuperar senha</a></p></section><aside class="sm-auth-aside"><div class="sm-auth-aside-content"><img src="assets/salada/salada-mix-logo.svg" alt="Salada Mix" width="212"><span class="sm-auth-overline">Um universo de possibilidades</span><h2>Seu mix começa aqui.</h2><p>Beleza, moda, tecnologia, casa e muito mais.</p><div class="sm-auth-bubbles"><span>Beleza</span><span>Tecnologia</span><span>Casa</span><span>Moda</span></div></div></aside></div>';
}
function account(){
 return version+'<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span>Minha conta</nav>'+
 '<div class="sm-demo-label"><strong>DEMONSTRAÇÃO VISUAL · FE-04</strong><span>Perfil ilustrativo. O GitHub Pages não executa autenticação nem armazena dados.</span></div>'+
 '<div class="sm-account-heading"><div><span class="sm-eyebrow">Seu espaço no Salada Mix</span><h1>Olá, Cliente Demo!</h1><p>Organize seus dados e acompanhe suas preferências em um só lugar.</p></div><span class="sm-account-avatar" aria-hidden="true">C</span></div>'+
 '<div class="sm-account-notice">Loja em preparação: o checkout e pagamentos reais estão indisponíveis.</div>'+
 '<div class="sm-account-layout"><nav class="sm-account-nav" aria-label="Minha conta"><a href="#/conta" aria-current="page">Meus dados</a><a href="#/enderecos">Endereços</a><a href="#/preparar-compra">Preparar compra</a><a href="#/resumo">Resumo da sacola</a><a href="#/sacola">Minha sacola</a><a href="#/favoritos">Meus favoritos</a><a href="#/entrar">Ver login</a><a href="#/cadastro">Ver cadastro</a></nav>'+
 '<div class="sm-account-panels"><section class="sm-account-panel"><div class="sm-panel-top"><div><span class="sm-eyebrow">Dados pessoais</span><h2>Minhas informações</h2></div><span class="sm-account-chip">Dados sintéticos</span></div><p>Exemplo visual. A atualização real utiliza BFF e sessão protegida.</p><div class="sm-account-form"><label>Nome completo</label><input disabled value="Cliente Demo"><label>E-mail</label><input disabled value="cliente@example.test"><button class="sm-btn sm-btn-primary" disabled>Salvar alterações</button></div></section>'+
 '<section class="sm-account-panel"><div class="sm-panel-top"><div><span class="sm-eyebrow">Proteja seu acesso</span><h2>Login e segurança</h2></div></div><p>Senha, contas vinculadas e alteração segura são operadas pelo backend Laravel.</p><div class="sm-linked-account"><div><strong>Google</strong><small>Exemplo de provedor vinculado</small></div><span class="sm-account-chip">Ilustrativo</span></div></section>'+
 '<section class="sm-account-panel"><h2>Dispositivos conectados</h2><p>Esta funcionalidade consulta sessões reais apenas quando o Laravel usa sessão em banco.</p><button class="sm-btn sm-btn-secondary" disabled>Consultar dispositivos</button></section></div></div>';
}
function addressScreen(){
 return version+'<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span><a href="#/conta">Minha conta</a><span>/</span>Endereços</nav>'+
 '<div class="sm-demo-label"><strong>DEMONSTRAÇÃO VISUAL</strong><span>Endereços sintéticos. Nenhuma informação é enviada ou persistida.</span></div>'+
 '<div class="sm-account-heading"><div><span class="sm-eyebrow">Minha conta</span><h1>Meus endereços</h1><p>Gerencie onde deseja receber suas futuras compras.</p></div></div>'+
 '<div class="sm-address-layout"><section class="sm-account-panel"><h2>Endereços cadastrados</h2><article class="sm-address-item"><div><strong>Casa (DEMO)</strong><span class="sm-account-chip">Principal</span></div><p>Cliente Demo</p><p>Rua Exemplo, 100 · Centro · Cidade/DF · 00000-000</p><button class="sm-destructive" disabled>Excluir endereço</button></article></section><section class="sm-account-panel"><h2>Adicionar endereço</h2><p>Formulário somente visual; o aplicativo Laravel realiza a gravação com validação.</p><div class="sm-account-form">'+["Apelido","Nome de quem recebe","CEP","Rua ou avenida","Número","Bairro","Cidade","UF"].map(x=>'<label>'+x+'</label><input disabled placeholder="Disponível no Laravel" aria-label="'+x+' demonstrativo">').join("")+'<button class="sm-btn sm-btn-primary" disabled>Salvar endereço</button></div></section></div>';
}
function bagScreen(){
 return version+'<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span><a href="#/conta">Minha conta</a><span>/</span>Resumo da sacola</nav>'+
 '<div class="sm-demo-label"><strong>DEMONSTRAÇÃO VISUAL</strong><span>O resumo não corresponde a um pedido e não representa cobrança.</span></div>'+
 '<div class="sm-account-heading"><div><span class="sm-eyebrow">Minha conta</span><h1>Resumo da sacola</h1><p>Itens separados por vendedor para consulta.</p></div></div><div class="sm-summary-list"><section class="sm-account-panel"><h2>MixTech (DEMO)</h2><div class="sm-summary-line"><span>1 × Fone Bluetooth sem fio (DEMO)</span><strong>R$ 129,90</strong></div><p class="sm-summary-note">Frete ainda sem cotação real.</p></section><button class="sm-btn sm-btn-primary" disabled>Pagamento indisponível nesta etapa</button><a class="sm-btn sm-btn-secondary" href="#/enderecos">Gerenciar endereços</a></div>';
}
function cartScreen(){
 const items=[...PREVIEW_CART].map(([id,quantity])=>({p:product(id),quantity})).filter(x=>x.p);
 const groups=new Map();
 for(const line of items){const name=line.p.seller;if(!groups.has(name))groups.set(name,[]);groups.get(name).push(line)}
 const total=items.reduce((sum,x)=>sum+x.p.price*x.quantity,0);
 const block=[...groups].map(([seller,lines])=>'<section class="sm-seller-group"><div class="sm-seller-group-header"><span class="sm-seller-dot" aria-hidden="true"></span><strong>Vendido por '+esc(seller)+'</strong><span>'+lines.length+' produto(s)</span></div>'+lines.map(({p,quantity})=>'<article class="sm-shopping-line"><a class="sm-shopping-thumb" href="'+detail(p)+'"><img src="'+photoUrl(DEMO_PHOTOS[p.id],320)+'" alt="Imagem ilustrativa de '+esc(p.name)+'"><span>DEMO</span></a><div class="sm-shopping-info"><a class="sm-shopping-product" href="'+detail(p)+'">'+esc(p.name)+'</a><p>'+brandName(p)+'</p><span class="sm-shopping-availability">Disponibilidade fictícia</span><button type="button" class="sm-shopping-remove" data-demo-action="cart-remove" data-id="'+esc(p.id)+'">Remover da sacola</button></div><div class="sm-shopping-numbers"><strong>'+money(p.price*quantity)+'</strong><small>'+money(p.price)+' por unidade</small><label class="sr-only" for="qty-'+esc(p.id)+'">Quantidade</label><select class="sm-shopping-qty" id="qty-'+esc(p.id)+'" data-demo-qty="'+esc(p.id)+'">'+Array.from({length:20},(_,i)=>'<option value="'+(i+1)+'" '+(i+1===quantity?'selected':'')+'>'+(i+1)+' unidade(s)</option>').join("")+'</select></div></article>').join("")+'</section>').join("");
 return version+'<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span>Minha sacola</nav><div class="sm-demo-label"><strong>FE-05 · DADOS SINTÉTICOS</strong><span>Interações temporárias nesta página; recarregar restaura os exemplos. Sem conta, pedido ou cobrança.</span></div><div class="sm-shopping-title"><div><span class="sm-eyebrow">Seu mix, suas escolhas</span><h1>Minha sacola <span>('+items.length+')</span></h1><p>Produtos organizados por vendedor.</p></div><a class="sm-btn sm-btn-secondary" href="#/favoritos">♡ Meus favoritos</a></div><div class="sm-shopping-alert"><strong>Somente demonstração visual:</strong> nenhum estoque é reservado. Frete e total final indisponíveis.</div>'+(!items.length?'<section class="sm-shopping-empty"><span class="sm-empty-symbol">◇</span><h2>Sua sacola está esperando seus achados.</h2><p>Explore departamentos e escolha seus produtos.</p><a class="sm-btn sm-btn-primary" href="#/buscar">Explorar produtos →</a></section>':'<div class="sm-shopping-layout"><div class="sm-shopping-sellers">'+block+'</div><aside class="sm-shopping-summary"><span class="sm-eyebrow">Resumo de demonstração</span><h2>Seu mix até agora</h2><div class="sm-shopping-total"><span>Subtotal ilustrativo</span><strong>'+money(total)+'</strong></div><p>Frete: indisponível.</p><p>Total final: indisponível.</p><button disabled class="sm-btn sm-btn-primary sm-shopping-disabled">Finalizar compra indisponível</button><a class="sm-btn sm-btn-secondary" href="#/preparar-compra">Preparar compra e endereço</a><small>Não existe reserva, pedido ou pagamento.</small></aside></div>');
}
function favoritesScreen(){
 const items=[...PREVIEW_FAVORITES].map(product).filter(Boolean);
 return version+'<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span>Favoritos</nav><div class="sm-demo-label"><strong>FE-05 · DADOS SINTÉTICOS</strong><span>Favoritos não são gravados no servidor nesta prévia.</span></div><div class="sm-shopping-title"><div><span class="sm-eyebrow">Seus próximos achados</span><h1>Meus favoritos <span>('+items.length+')</span></h1><p>Uma seleção ilustrativa para avaliar o layout.</p></div><a class="sm-btn sm-btn-secondary" href="#/sacola">Ver minha sacola →</a></div>'+(!items.length?'<section class="sm-shopping-empty"><span class="sm-empty-symbol">♡</span><h2>Seus favoritos vão aparecer aqui.</h2><a class="sm-btn sm-btn-primary" href="#/buscar">Descobrir produtos →</a></section>':'<div class="sm-favorites-grid">'+items.map(p=>'<article class="sm-favorite-card"><a class="sm-favorite-thumb" href="'+detail(p)+'"><img src="'+photoUrl(DEMO_PHOTOS[p.id],360)+'" alt="Imagem ilustrativa: '+esc(p.name)+'"><span>DEMO</span></a><div class="sm-favorite-body"><small>'+esc(p.seller)+'</small><a class="sm-favorite-name" href="'+detail(p)+'">'+esc(p.name)+'</a><strong>'+money(p.price)+'</strong><p>Preço sintético sujeito a alteração.</p><div class="sm-favorite-actions"><button type="button" class="sm-btn sm-btn-primary" data-demo-action="cart-add" data-id="'+esc(p.id)+'">Adicionar à sacola</button><button type="button" class="sm-shopping-remove" data-demo-action="wishlist-toggle" data-id="'+esc(p.id)+'">Remover ♡</button></div></div></article>').join("")+'</div>');
}
function checkoutPrepare(params){
 const choices=[
  {id:"casa",label:"Casa (DEMO)",name:"Cliente Exemplo",street:"Rua Ilustrativa, 100",district:"Centro · Brasília/DF · 70000-000"},
  {id:"trabalho",label:"Trabalho (DEMO)",name:"Cliente Exemplo",street:"Avenida Exemplo, 22",district:"Asa Sul · Brasília/DF · 70000-001"}
 ];
 const selected=choices.find(x=>x.id===params.get("address"))||choices[0];
 const lines=[...PREVIEW_CART].map(([id,quantity])=>({p:product(id),quantity})).filter(x=>x.p);
 const groups=new Map();for(const line of lines){if(!groups.has(line.p.seller))groups.set(line.p.seller,[]);groups.get(line.p.seller).push(line)}
 const total=lines.reduce((sum,x)=>sum+x.p.price*x.quantity,0);
 const addressCards=choices.map(x=>'<a href="#/preparar-compra?address='+x.id+'" class="sm-checkout-address '+(x.id===selected.id?'is-selected':'')+'" '+(x.id===selected.id?'aria-current="true"':'')+'><span class="sm-checkout-radio" aria-hidden="true"></span><span class="sm-checkout-address-content"><strong>'+x.label+'</strong><span>'+x.name+'</span><span>'+x.street+'</span><span>'+x.district+'</span></span><span class="sm-checkout-select-word">'+(x.id===selected.id?'Selecionado':'Selecionar')+'</span></a>').join("");
 const sellerCards=[...groups].map(([seller,items])=>'<div class="sm-checkout-seller"><div class="sm-checkout-seller-head"><span class="sm-seller-dot"></span><strong>'+esc(seller)+'</strong><span>'+items.length+' produto(s)</span></div><div class="sm-checkout-seller-lines">'+items.map(({p,quantity})=>'<div class="sm-checkout-line"><a class="sm-checkout-photo" href="'+detail(p)+'"><img src="'+photoUrl(DEMO_PHOTOS[p.id],160)+'" alt="Imagem ilustrativa: '+esc(p.name)+'"></a><div><a href="'+detail(p)+'">'+esc(p.name)+'</a><small>'+quantity+' × '+money(p.price)+'</small></div><strong>'+money(p.price*quantity)+'</strong></div>').join("")+'</div><div class="sm-checkout-shipping-state"><span class="sm-checkout-indicator is-warning"></span><span>Origem/cotação de frete indisponíveis nesta demonstração.</span></div><div class="sm-checkout-seller-subtotal"><span>Subtotal desta loja</span><strong>'+money(items.reduce((s,x)=>s+x.p.price*x.quantity,0))+'</strong></div></div>').join("");
 return version+'<nav class="sm-breadcrumb"><a href="#/">Início</a><span>/</span><a href="#/sacola">Minha sacola</a><span>/</span>Preparação de compra</nav>'+
 '<div class="sm-demo-label"><strong>FE-06 + BE-06 · DEMONSTRAÇÃO</strong><span>Endereços e produtos fictícios. A aplicação Laravel calcula a prontidão real somente para a conta autenticada.</span></div>'+
 '<div class="sm-checkout-heading"><span class="sm-eyebrow">Entrega integrada FE-06 + BE-06</span><h1>Prepare seu pedido</h1><p>Confira o endereço, os produtos e a situação de cada loja.</p></div>'+
 '<div class="sm-shopping-alert"><strong>Sem transações:</strong> esta tela não cria pedidos, não reserva estoque e não solicita pagamento.</div>'+
 '<ol class="sm-checkout-steps"><li class="is-done"><span>1</span> Sacola</li><li class="is-current" aria-current="step"><span>2</span> Endereço</li><li><span>3</span> Frete</li><li><span>4</span> Pagamento</li></ol>'+
 '<div class="sm-checkout-layout"><div class="sm-checkout-main"><section class="sm-account-panel"><div class="sm-panel-top"><div><span class="sm-eyebrow">Passo 2</span><h2>Endereço de entrega</h2></div><a class="sm-checkout-edit" href="#/enderecos">Ver endereços →</a></div><div class="sm-checkout-addresses">'+addressCards+'</div><p class="sm-form-help">Selecionar outro endereço altera apenas a demonstração.</p></section>'+
 '<section class="sm-account-panel"><div class="sm-panel-top"><div><span class="sm-eyebrow">Passo 3</span><h2>Entrega por vendedor</h2></div><span class="sm-account-chip">Sem cotação real</span></div>'+ (sellerCards||'<div class="sm-checkout-empty"><strong>Sacola vazia.</strong><a class="sm-btn sm-btn-secondary" href="#/buscar">Explorar produtos</a></div>')+'</section></div>'+
 '<aside class="sm-checkout-sidebar"><span class="sm-eyebrow">Resumo da preparação</span><h2>Seu mix</h2><div class="sm-checkout-price"><span>Produtos</span><strong>'+money(total)+'</strong></div><div class="sm-checkout-price"><span>Frete</span><strong>Não cotado</strong></div><div class="sm-checkout-price sm-checkout-grand"><span>Total final</span><strong>Indisponível</strong></div><div class="sm-checkout-readiness"><h3>Pendências desta compra</h3><ul><li>Cotação real de frete ainda não integrada.</li><li>Pagamento não homologado.</li><li>Nenhuma reserva ou pedido é gerado.</li></ul></div><button class="sm-btn sm-btn-primary sm-checkout-lock" disabled>Finalizar compra indisponível</button><a class="sm-btn sm-btn-secondary" href="#/sacola">← Voltar à sacola</a><p>Não representa orçamento, cobrança ou promessa de entrega.</p></aside></div>';
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
 else if(path==="/entrar")html=authScreen("login");
 else if(path==="/cadastro")html=authScreen("register");
 else if(path==="/recuperar")html=authScreen("forgot");
 else if(path==="/enderecos")html=addressScreen();
 else if(path==="/resumo")html=bagScreen();
 else if(path==="/preparar-compra")html=checkoutPrepare(params);
 else if(path==="/sacola")html=cartScreen();
 else if(path==="/favoritos")html=favoritesScreen();
 else html=missing();
 ROOT.innerHTML=html;
 const search=document.getElementById("sm-global-search");if(search)search.value=params.get("q")||"";
 document.title=(path==="/buscar"?"Buscar":path.startsWith("/categorias/")?(category(decodeURIComponent(path.slice(12)))?.name||"Categoria"):path.startsWith("/ofertas/")?(product(decodeURIComponent(path.slice(9)))?.name||"Oferta"):"Salada Mix")+" — Entrega 6 (prévia)";
 const menu=document.querySelector(".sm-mobile-details");if(menu)menu.open=false;
 if(path==="/departamentos"||path==="/ofertas"){const section=document.getElementById(path);if(section)section.scrollIntoView({block:"start"});}
 else if(typeof window.scrollTo==="function")window.scrollTo(0,0);
}
document.getElementById("global-search").addEventListener("submit",event=>{
 event.preventDefault();const input=document.getElementById("sm-global-search");const q=input?input.value.trim():"";
 location.hash="#/buscar"+(q?"?q="+encodeURIComponent(q):"");
 if(!q&&location.hash==="#/buscar")route();
});
ROOT.addEventListener("click",event=>{
 const target=event.target.closest("[data-demo-action]");
 if(!target)return;
 const id=target.dataset.id,action=target.dataset.demoAction;
 if(!product(id))return;
 if(action==="cart-add")PREVIEW_CART.set(id,Math.min(20,(PREVIEW_CART.get(id)||0)+1));
 else if(action==="cart-remove")PREVIEW_CART.delete(id);
 else if(action==="wishlist-toggle"){if(PREVIEW_FAVORITES.has(id))PREVIEW_FAVORITES.delete(id);else PREVIEW_FAVORITES.add(id)}
 route();
});
ROOT.addEventListener("change",event=>{
 const target=event.target.closest("[data-demo-qty]");
 if(!target)return;
 const q=Number(target.value);
 if(Number.isInteger(q)&&q>=1&&q<=20)PREVIEW_CART.set(target.dataset.demoQty,q);
 route();
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
