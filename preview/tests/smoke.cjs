const assert=require("node:assert/strict");
const fs=require("node:fs");
const path=require("node:path");
const vm=require("node:vm");
const root=path.resolve(__dirname,"../..");
const read=p=>fs.readFileSync(path.join(root,p),"utf8");
const html=read("preview/index.html");
const js=read("preview/assets/app.js");
assert.match(html,/id="conteudo"/);
assert.match(html,/sm-search/);
assert.match(html,/assets\/salada\/salada-mix-logo\.svg/);
assert.match(html,/PRÉVIA VISUAL FE-03/);
assert.equal(read("preview/assets/salada-foundation.css"),read("resources/css/salada-foundation.css"));
assert.equal(read("preview/assets/salada-catalog.css"),read("resources/css/salada-catalog.css"));
assert.equal(read("preview/assets/salada-visual-demo.css"),read("resources/css/salada-visual-demo.css"));
assert.equal(read("preview/assets/salada/salada-mix-logo.svg"),read("public/assets/salada/salada-mix-logo.svg"));
const elements=new Map();
function element(id){
 if(!elements.has(id))elements.set(id,{
  innerHTML:"",textContent:"",value:"",dataset:{},listeners:new Map(),
  classList:{add(){},remove(){}},
  addEventListener(event,handler){this.listeners.set(event,handler)},
  scrollIntoView(){}
 });
 return elements.get(id);
}
const ctx=vm.createContext({
 document:{
  getElementById:element,querySelector(){return null},title:""
 },
 window:{
  addEventListener(event,callback){this[event]=callback},
  scrollTo(){}
 },
 location:{hash:"#/"},
 Intl,URLSearchParams,Math,Number,String,Object,Array,Set,FormData,
 encodeURIComponent,decodeURIComponent
});
vm.runInContext(js,ctx,{filename:"preview/assets/app.js"});
const display=()=>element("conteudo").innerHTML;
const go=hash=>{ctx.location.hash=hash;ctx.window.hashchange();};
assert.match(display(),/Tudo o que você ama, em um só lugar/);
assert.match(display(),/sm-demo-image/);
assert.match(display(),/sm-editorial-hero/);
assert.match(display(),/Sérum facial vitamina C \(DEMO\)/);
assert.match(display(),/sm-home-hero/);
go("#/buscar");
assert.match(display(),/Refinar busca/);
assert.match(display(),/9 ofertas disponíveis/);
go("#/buscar?q=vitamina");
assert.match(display(),/Sérum facial vitamina C/);
assert.doesNotMatch(display(),/Mouse ergonômico sem fio/);
go("#/categorias/beleza-e-cuidados");
assert.match(display(),/3 ofertas disponíveis/);
assert.doesNotMatch(display(),/Câmera compacta \(DEMO\)/);
go("#/buscar?min_price=100,00&sort=price_asc");
assert.match(display(),/Fone Bluetooth sem fio/);
assert.doesNotMatch(display(),/Sérum facial vitamina C \(DEMO\)/);
go("#/ofertas/demo-home-light");
assert.match(display(),/Luminária de mesa \(DEMO\)/);
assert.match(display(),/sm-detail-image/);
assert.match(display(),/O checkout está desativado/);
assert.match(display(),/104,90/);
go("#/vender/cadastro");assert.match(display(),/Simular solicitação/);
assert.match(display(),/não recebe nem armazena dados/);
go("#/vendedor");assert.match(display(),/MixTech \(DEMO\)/);
go("#/admin");assert.match(display(),/nenhuma decisão é persistida/);
go("#/conta");assert.match(display(),/não executa autenticação/);
console.log("FE03_PREVIEW_OK: exact CSS/logo, home, catalog, filters, category, offer, seller, admin, account.");
