const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const vm = require("node:vm");
const dir=path.resolve(__dirname,"..");
const html=fs.readFileSync(path.join(dir,"index.html"),"utf8");
const code=fs.readFileSync(path.join(dir,"assets/app.js"),"utf8");
assert.match(html,/id="app"/);
assert.match(html,/assets\/style.css/);
assert.match(html,/assets\/app.js/);

const elements=new Map();
const element=id=>{
 if(!elements.has(id))elements.set(id,{
   innerHTML:"",textContent:"",value:"",hidden:false,handlers:new Map(),
   classList:{add(){},remove(){},toggle(){},contains(){return false}},
   setAttribute(){},addEventListener(event,callback){this.handlers.set(event,callback)}
 });
 return elements.get(id);
};
const navLinks=[];
const ctx=vm.createContext({
 document:{getElementById:element,querySelectorAll:()=>navLinks,title:""},
 window:{addEventListener(event,cb){this[event]=cb},scrollTo(){}},
 location:{hash:"#/"},
 Intl,URLSearchParams,Math,Number,String,Object,Array,Set,FormData,
 clearTimeout(){},setTimeout(){return 1}
});
vm.runInContext(code,ctx,{filename:"preview/assets/app.js"});
const display=()=>element("app").innerHTML;
const go=hash=>{ctx.location.hash=hash;ctx.window.hashchange()};
assert.match(display(),/Um universo de escolhas/);
assert.match(display(),/Sérum facial/);
go("#/category/beleza");
assert.match(display(),/Sérum facial/);
assert.doesNotMatch(display(),/Câmera compacta/);
go("#/product/fone-bluetooth");
assert.match(display(),/Adicionar ao carrinho da prévia/);
assert.match(display(),/checkout/);
go("#/cart");
assert.match(display(),/carrinho está vazio/);
go("#/join");
assert.match(display(),/Solicitação de cadastro/);
assert.match(display(),/Não envie dados reais/);
go("#/seller");
assert.match(display(),/Portal do vendedor|PORTAL DO VENDEDOR/);
go("#/admin");
assert.match(display(),/Central Salada Mix/);
assert.match(display(),/ilustrativo/);
console.log("PREVIEW_SMOKE_OK: home, category, product, cart, onboarding, seller, admin.");

