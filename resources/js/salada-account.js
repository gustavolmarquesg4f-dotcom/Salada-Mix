/* FE-04 same-origin customer-account UI. Never persist credentials in browser storage. */
const accountForms=document.querySelectorAll('form[data-sm-bff]');
const token=document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const message=(node,text,error=false)=>{if(!node)return;node.hidden=false;node.classList.toggle('is-error',error);node.textContent=text;};
const csrfHeaders=()=>({'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token||''});
async function parseResponse(response){
 if(response.status===419)throw new Error('Sua sessão expirou. Atualize a página e entre novamente.');
 if(response.status===401||response.status===403)throw new Error('Acesso não autorizado. Verifique o login e a confirmação do e-mail.');
 let body=null;
 try{body=await response.json()}catch(_){throw new Error('Não foi possível processar a resposta do servidor.');}
 if(!response.ok){
   const errors=body?.errors ? Object.values(body.errors).flat().filter(x=>typeof x==='string') : [];
   throw new Error(errors.length?errors.join(' '):(body?.message||'Não foi possível salvar as alterações.'));
 }
 return body;
}
if(token){
 for(const form of accountForms){
  const button=form.querySelector('button[type="submit"]'),feedback=form.querySelector('.sm-bff-feedback');
  if(button)button.disabled=false;
  form.addEventListener('submit',async event=>{
   event.preventDefault();
   if(!form.reportValidity())return;
   if(button)button.disabled=true;
   message(feedback,'Salvando alterações…');
   const data=Object.fromEntries(new FormData(form).entries());
   const oldEmail=form.dataset.initialEmail;
   try{
    const response=await fetch(form.dataset.url,{method:form.dataset.method||'PATCH',credentials:'same-origin',headers:csrfHeaders(),body:JSON.stringify(data),redirect:'follow'});
    const result=await parseResponse(response);
    form.querySelectorAll('input[type="password"]').forEach(x=>x.value='');
    message(feedback,result.message||'Alterações salvas.');
    if(oldEmail&&data.email&&oldEmail.trim().toLowerCase()!==String(data.email).trim().toLowerCase()){
      message(feedback,'E-mail alterado. Confirme o novo endereço pela mensagem enviada.');
      if(form.dataset.nextOnEmail)window.location.assign(form.dataset.nextOnEmail);
    }
   }catch(error){message(feedback,error.message||'Erro ao salvar.',true)}
   finally{if(button)button.disabled=false}
  });
 }
 const sessionButton=document.querySelector('[data-sm-session-load]');
 if(sessionButton){
  sessionButton.disabled=false;
  sessionButton.addEventListener('click',async()=>{
   const feedback=document.getElementById('sm-session-feedback'),list=document.getElementById('sm-session-list');
   sessionButton.disabled=true;message(feedback,'Consultando sessões…');list.replaceChildren();
   try{
    const response=await fetch(sessionButton.dataset.url,{credentials:'same-origin',headers:{'Accept':'application/json'}});
    const data=(await parseResponse(response)).data;
    if(!Array.isArray(data)||!data.length){message(feedback,'Nenhuma sessão encontrada.');return}
    feedback.hidden=true;
    for(const row of data){
     const div=document.createElement('div'),label=document.createElement('strong'),device=document.createElement('span'),when=document.createElement('span');
     div.className='sm-session-row';
     label.textContent=row.current?'Este dispositivo':'Outro dispositivo';
     device.textContent=row.device||'Dispositivo não identificado';
     when.textContent='Última atividade: '+(row.last_active_at?new Date(row.last_active_at).toLocaleString('pt-BR'):'não informada');
     div.append(label,device,when);list.append(div);
    }
   }catch(error){message(feedback,error.message||'Não foi possível consultar as sessões.',true)}
   finally{sessionButton.disabled=false}
  });
 }
}
