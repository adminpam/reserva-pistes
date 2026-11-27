import test from 'node:test';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
import {hasEmailDomainExtension, formIdentity, fieldMessage, collectIssues, fieldsForError, chooseServerForm, enhanceValidation} from '../../public/ui/form-validation.js';

// El adaptador DOM permite comprobar ámbito, eventos y accesibilidad sin dependencias.
// Las flags de ValidityState se suministran explícitamente: no simula un navegador.
class Element {
    constructor(tag='div') {this.tagName=tag.toUpperCase();this.attrs=new Map();this.children=[];this.hidden=false;this.value='';this.type='text';this.name='';this.dataset={};this.labels=[];this.validity={valid:true};this.required=false;this.minLength=-1;this.maxLength=-1;this.disabled=false;this.checked=false;this._text='';}
    get willValidate(){return !this.disabled && !['hidden','button','submit'].includes(this.type);}
    setAttribute(k,v){this.attrs.set(k,String(v));}
    getAttribute(k){return this.attrs.get(k)??null;}
    removeAttribute(k){this.attrs.delete(k);}
    hasAttribute(k){return this.attrs.has(k);}
    append(...nodes){for(const n of nodes){n.parent=this;this.children.push(n);if(n.tagName==='INPUT'&&this.elements&&!this.elements.includes(n))this.elements.push(n);}}
    prepend(n){n.parent=this;this.children.unshift(n);}
    after(n){const p=this.parent;if(!p)throw Error('Sin padre');n.parent=p;p.children.splice(p.children.indexOf(this)+1,0,n);}
    remove(){if(this.parent)this.parent.children=this.parent.children.filter(n=>n!==this);}
    replaceChildren(...nodes){this.children=[];this._text='';this.append(...nodes);}
    set textContent(v){this._text=String(v);this.children=[];}
    get textContent(){return this._text+this.children.map(n=>n.textContent).join('');}
    matches(selectors){return selectors.split(',').some(s=>s.startsWith('.')?String(this.className||'').split(' ').includes(s.slice(1)):s==='[hidden]'?this.hidden:s==='[role="group"]'?this.getAttribute('role')==='group':s.toUpperCase()===this.tagName);}
    closest(selector){let n=this;while(n){if(n.matches(selector))return n;n=n.parent;}return null;}
    focus(){this.focused=true;}
    scrollIntoView(){this.scrolled=true;}
}
function input(name,value='',extra={}){const x=new Element('input');Object.assign(x,{name,value},extra);return x;}
function form(fields,action='/register',extra={}){
    const f=new Element('form');f.action='https://example.test'+action;f.method='post';f.noValidate=false;f.elements=fields;
    for(const field of fields){field.form=f;f.append(field);}Object.assign(f,extra);return f;
}
function documentOf(forms,payload){
    const handlers=new Map(),fallback=new Element(),state=new Element('script');fallback.setAttribute('tabindex','-1');state.textContent=JSON.stringify(payload||{});
    const doc={forms,baseURI:'https://example.test/',documentElement:{classList:{contains:()=>false}},createElement:tag=>new Element(tag),getElementById:id=>id==='rp-validation-state'?state:null,querySelector:s=>s==='[data-validation-fallback]'?fallback:null,
        addEventListener(name,fn,capture){const list=handlers.get(name)||[];list.push({fn,capture});handlers.set(name,list);},
        emit(name,target,extra={}){const e={type:name,target,defaultPrevented:false,stopped:false,preventDefault(){this.defaultPrevented=true;},stopImmediatePropagation(){this.stopped=true;},...extra};for(const h of handlers.get(name)||[]){h.fn(e);if(e.stopped)break;}return e;}};
    for(const f of forms){f.ownerDocument=doc;for(const x of f.elements)x.ownerDocument=doc;}
    return {doc,fallback,handlers};
}
function errorMessage(field,fields=[field],extra={}){return fieldMessage(field,form(fields,'/register',extra));}

test('una contraseña de acceso antigua y corta sigue pudiendo llegar al servidor',()=>{
    const field=input('password','corta');assert.equal(errorMessage(field),'');
});
test('la confirmación se compara aunque el ojo haya mostrado los campos como texto',()=>{
    const p=input('password','Correcta16!',{type:'text'}),c=input('password_confirmation','Diferente16!',{type:'text'});
    assert.match(errorMessage(c,[p,c]),/no coinciden/);c.value=p.value;assert.equal(errorMessage(c,[p,c]),'');
});
test('la longitud de una contraseña nueva se comprueba por caracteres y por bytes',()=>{
    const p=input('password','😀😀😀😀'),c=input('password_confirmation',p.value);
    assert.match(errorMessage(p,[p,c]),/al menos 8/);p.value='á'.repeat(40);assert.match(errorMessage(p,[p,c]),/demasiado larga/);
    p.value='Correcta16!';assert.equal(errorMessage(p,[p,c]),'');
});
test('nombres con espacios y datos numéricos inválidos reciben mensajes legibles',()=>{
    assert.match(errorMessage(input('name','   ',{required:true})),/Completa/);
    assert.match(errorMessage(input('price_euros','',{validity:{badInput:true}})),/número válido/);
    assert.match(errorMessage(input('price_euros','1.123',{validity:{stepMismatch:true}})),/dos decimales/);
});
test('los límites y el correo usan las flags nativas sin mensajes ingleses',()=>{
    assert.equal(errorMessage(input('email','mal',{type:'email',validity:{typeMismatch:true}})),'Introduce un correo electrónico válido.');
    assert.match(errorMessage(input('open_hour','24',{max:'23',validity:{rangeOverflow:true}})),/23/);
    assert.match(errorMessage(input('name','abcdef',{maxLength:4})),/4 caracteres/);
});
test('cierre igual o anterior a la apertura se rechaza sin enviar',()=>{
    const a=input('open_hour','20'),b=input('close_hour','19');assert.match(errorMessage(b,[a,b]),/posterior/);
    b.value='20';assert.match(errorMessage(b,[a,b]),/posterior/);b.value='24';assert.equal(errorMessage(b,[a,b]),'');
});
test('el rango de nivel solo se exige cuando el partido es abierto',()=>{
    const min=input('min_level','4'),max=input('max_level','2'),open=input('is_open','1',{type:'checkbox',checked:true});
    assert.match(errorMessage(max,[min,max,open]),/igual o superior/);open.checked=false;assert.equal(errorMessage(max,[min,max,open]),'');
});
test('solo se aceptan los dos números ficticios normalizando espacios y guiones',()=>{
    const n=input('payment[number]','4242-4242 4242 4242');const f=form([n],'/checkout/abc/pay');f.setAttribute('data-payment-form','');
    assert.equal(fieldMessage(n,f),'');n.value='4000 0000 0000 0002';assert.equal(fieldMessage(n,f),'');n.value='4111 1111 1111 1111';assert.match(fieldMessage(n,f),/ficticias/);
});
test('titular, CVC y caducidad muestran la corrección de prueba',()=>{
    const h=input('payment[holder]','Otra persona'),c=input('payment[cvc]','444'),d=input('payment[expiry]','12-30',{validity:{patternMismatch:true}});
    const f=form([h,c,d],'/checkout/abc/pay');f.setAttribute('data-payment-form','');
    assert.match(fieldMessage(h,f),/Persona Demo/);assert.match(fieldMessage(c,f),/123/);assert.match(fieldMessage(d,f),/MM\/AA/);
});
test('el grupo de horas tiene un solo error y omite controles deshabilitados u ocultos',()=>{
    const a=input('hour','10',{type:'radio',validity:{valueMissing:true}}),b=input('hour','11',{type:'radio',validity:{valueMissing:true}}),disabled=input('name','',{disabled:true,required:true}),hidden=input('other','',{required:true});
    const f=form([a,b,disabled,hidden]);hidden.hidden=true;const issues=collectIssues(f);assert.equal(issues.size,1);assert.equal(issues.get('hour'),'Elige una hora disponible.');
});
test('las claves con puntos encuentran niveles, pagos y el grupo de deportes',()=>{
    const level=input('levels[7]','3',{type:'radio'}),expiry=input('payment[expiry]','12/30'),sport=input('sports[]','tenis',{type:'checkbox'});const f=form([level,expiry,sport]);
    assert.deepEqual(fieldsForError(f,'levels.7'),[level]);assert.deepEqual(fieldsForError(f,'payment.expiry'),[expiry]);assert.deepEqual(fieldsForError(f,'sports.0'),[sport]);assert.deepEqual(fieldsForError(f,'sports'),[sport]);
});
test('dos pistas con la misma ruta reciben identidades diferentes sin datos secretos',()=>{
    const a=form([input('court_id','7',{type:'hidden'}),input('password','Secreto!')],'/bookings');
    const b=form([input('court_id','8',{type:'hidden'})],'/bookings');documentOf([a,b]);
    assert.notEqual(formIdentity(a),formIdentity(b));assert.doesNotMatch(formIdentity(a),/Secreto/);
});
test('un error de servidor pertenece a la pista enviada y nunca a las demás',()=>{
    const a=form([input('court_id','7',{type:'hidden'}),input('hour','10',{type:'radio'})],'/bookings'),b=form([input('court_id','8',{type:'hidden'}),input('hour','10',{type:'radio'})],'/bookings');documentOf([a,b]);
    assert.equal(chooseServerForm([a,b],{hour:['Ocupada']},formIdentity(b)),b);
    assert.equal(chooseServerForm([a,b],{hour:['Ocupada']},null),null);
    assert.equal(chooseServerForm([a,b],{hour:['Ocupada']},'inventada'),null);
});
test('un envío incorrecto bloquea los siguientes manejadores y enfoca el primer error',()=>{
    const n=input('name','',{required:true}),f=form([n]),{doc}=documentOf([f]);const binding=enhanceValidation(doc);let locked=false;
    doc.addEventListener('submit',()=>locked=true);const event=doc.emit('submit',f);
    assert.equal(event.defaultPrevented,true);assert.equal(locked,false);assert.equal(n.focused,true);assert.equal(n.getAttribute('aria-invalid'),'true');assert.equal(binding.states.get(f).summary.hidden,false);
    n.value='Nombre de prueba';doc.emit('input',n);assert.equal(n.getAttribute('aria-invalid'),null);
    assert.equal(doc.emit('submit',f).defaultPrevented,false);assert.equal(locked,true);
});
test('los errores de servidor se muestran como texto y conservan aria-describedby previo',()=>{
    const email=input('email','correo@example.test');email.setAttribute('aria-describedby','ayuda-correo');const f=form([email]);const {doc,fallback}=documentOf([f],{errors:{email:['<img src=x onerror=alert(1)>']}});
    const binding=enhanceValidation(doc);assert.equal(fallback.hidden,true);assert.match(email.getAttribute('aria-describedby'),/^ayuda-correo rp-error-/);
    const message=binding.states.get(f).nodes[0];assert.equal(message.textContent,'<img src=x onerror=alert(1)>');assert.equal(message.children.length,0);
    doc.emit('input',email);assert.equal(email.getAttribute('aria-describedby'),'ayuda-correo');assert.equal(email.getAttribute('aria-invalid'),null);
});
test('los errores de negocio quedan visibles en el formulario correcto aunque no tengan campo',()=>{
    const field=input('hour','10',{type:'radio'}),f=form([field],'/bookings');const {doc}=documentOf([f]);
    const identity=formIdentity(f);doc.getElementById('rp-validation-state').textContent=JSON.stringify({form:identity,errors:{checkout_key:['Recarga la ficha.']}});
    const b=enhanceValidation(doc);assert.match(b.states.get(f).summary.textContent,/Recarga la ficha/);assert.equal(b.states.get(f).summary.focused,true);
});
test('una identidad manipulada o ambigua mantiene el aviso HTML de respaldo',()=>{
    const a=form([input('role','player')],'/admin/users/1'),b=form([input('role','manager')],'/admin/users/2');const {doc,fallback}=documentOf([a,b],{errors:{role:['Rol incorrecto.']}});
    enhanceValidation(doc);assert.equal(fallback.hidden,false);assert.equal(fallback.focused,true);assert.equal(a.elements[0].getAttribute('aria-invalid'),null);
});
test('reiniciar limpia los mensajes propios y repetir la mejora no duplica controles',()=>{
    const n=input('name','',{required:true}),f=form([n]),{doc}=documentOf([f]);const b=enhanceValidation(doc);assert.equal(enhanceValidation(doc),b);
    assert.equal(f.elements.filter(x=>x.name==='_validation_form').length,1);doc.emit('submit',f);doc.emit('reset',f);assert.equal(n.getAttribute('aria-invalid'),null);assert.equal(b.states.get(f).summary.hidden,true);
});
test('los filtros GET no reciben marcadores y la foto automática queda fuera de la mejora',()=>{
    const search=form([input('q','')],'/admin/clubs',{method:'get'}),avatar=form([input('avatar','')],'/profile/avatar');avatar.setAttribute('data-avatar-autosave','');const {doc}=documentOf([search,avatar]);
    enhanceValidation(doc);assert.equal(search.elements.some(x=>x.name==='_validation_form'),false);assert.equal(avatar.noValidate,false);assert.equal(avatar.children.length,1);
});
test('formnovalidate se conserva para acciones que omiten la comprobación local',()=>{
    const f=form([input('name','',{required:true})]),{doc}=documentOf([f]);enhanceValidation(doc);assert.equal(doc.emit('submit',f,{submitter:{formNoValidate:true}}).defaultPrevented,false);
});
test('una acción sin campos editables recibe su error de negocio en su propia tarjeta',()=>{
    const f=form([], '/matches/7/join'),{doc}=documentOf([f]);doc.getElementById('rp-validation-state').textContent=JSON.stringify({form:formIdentity(f),errors:{match:['No quedan plazas.']}});
    const b=enhanceValidation(doc);assert.match(b.states.get(f).summary.textContent,/No quedan plazas/);
});
test('el formulario de salir no se modifica',()=>{
    const f=form([], '/logout'),{doc}=documentOf([f]);enhanceValidation(doc);assert.equal(f.noValidate,false);assert.equal(f.children.length,0);
});
test('el error queda fuera del contenedor del icono para no desplazar el ojo',()=>{
    const password=input('password','',{required:true}),outer=new Element(),icon=new Element(),wrapper=new Element('span');
    outer.className='field';icon.className='field-icon';wrapper.className='rp-password-wrap';
    const f=form([]);f.elements.push(password);password.form=f;f.append(outer);outer.append(icon);icon.append(wrapper);wrapper.append(password);
    const {doc}=documentOf([f]);const binding=enhanceValidation(doc);doc.emit('submit',f);
    const error=binding.states.get(f).nodes[0];assert.equal(error.parent,outer);assert.equal(icon.children.length,1);
});

test('el registro exige dominio con extensión usando la misma tabla que el servidor',()=>{
    const examples=JSON.parse(readFileSync(new URL('../Fixtures/registration-email-domains.json',import.meta.url),'utf8'));
    for(const email of examples.valid) assert.equal(hasEmailDomainExtension(email),true,email);
    for(const email of examples.invalid) assert.equal(hasEmailDomainExtension(email),false,email);
    assert.equal(hasEmailDomainExtension('cuenta@example.test\n'),false);
});
test('el dominio sin extensión produce una corrección clara solo al registrar una cuenta',()=>{
    const email=input('email','david@reservapistes',{type:'email'}),confirmation=input('password_confirmation','');
    assert.match(errorMessage(email,[email,confirmation]),/dominio y extensión/);
    email.value='david@reservapistes.test';assert.equal(errorMessage(email,[email,confirmation]),'');
    email.value='david@reservapistes';assert.equal(errorMessage(email,[email]),'');
});
test('al pulsar enviar no aparece un error de blur que desplace el botón antes del clic',()=>{
    const n=input('name','',{required:true}),f=form([n]),{doc}=documentOf([f]);const b=enhanceValidation(doc);
    doc.emit('focusout',n,{relatedTarget:{form:f,type:'submit'}});assert.equal(b.states.get(f).summary.hidden,true);
    const event=doc.emit('submit',f);assert.equal(event.defaultPrevented,true);assert.equal(n.focused,true);
});
