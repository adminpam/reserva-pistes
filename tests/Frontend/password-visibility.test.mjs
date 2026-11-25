import test from 'node:test';
import assert from 'node:assert/strict';
import {setPasswordVisibility, enhancePasswords} from '../../public/ui/password-visibility.js';
function field() {
    const handlers = {}, attrs = {};
    const input = {type:'password', value:'Contraseña Ficticia', name:'password', selectionStart:3, selectionEnd:7, dataset:{}, id:'secret', labels:[{textContent:'Nueva contraseña'}],
        setSelectionRange(a,b){this.selectionStart=a;this.selectionEnd=b;}, focus(){this.focused=true;}, before(wrapper){this.wrapper=wrapper;}, form:{addEventListener:(name,fn)=>handlers[name]=fn}};
    input.ownerDocument = {createElement:tag=>({tag,dataset:{},children:[],append(...nodes){this.children.push(...nodes)},setAttribute:(name,value)=>attrs[name]=value,getAttribute:name=>attrs[name],addEventListener:(name,fn)=>handlers[name]=fn})};
    return {input,handlers,attrs,root:{querySelectorAll:()=>[input]}};
}
test('mostrar y ocultar conserva valor, nombre y selección',()=>{
 const s=field(), [binding]=enhancePasswords(s.root);
 s.handlers.click();assert.equal(s.input.type,'text');assert.equal(s.attrs['aria-pressed'],'true');
 assert.equal(s.input.value,'Contraseña Ficticia');assert.equal(s.input.name,'password');assert.equal(s.input.selectionStart,3);assert.equal(s.input.selectionEnd,7);
 s.handlers.click();assert.equal(s.input.type,'password');assert.equal(binding.button.type,'button');assert.equal(s.input.focused,true);
});
test('etiqueta accesible describe el campo correcto',()=>{
 const s=field();enhancePasswords(s.root);assert.equal(s.attrs['aria-controls'],'secret');assert.equal(s.attrs['aria-label'],'Mostrar nueva contraseña');
 s.handlers.click();assert.equal(s.attrs['aria-label'],'Ocultar nueva contraseña');
});
test('enviar o restablecer el formulario oculta sin alterar su contenido',()=>{
 const s=field();enhancePasswords(s.root);s.handlers.click();s.handlers.submit();assert.equal(s.input.type,'password');
 s.handlers.click();s.handlers.reset();assert.equal(s.input.type,'password');assert.equal(s.input.value,'Contraseña Ficticia');
});
test('repetir la mejora no duplica botones',()=>{
 const s=field();assert.equal(enhancePasswords(s.root).length,1);assert.equal(enhancePasswords(s.root).length,0);
});
test('las contraseñas de campos diferentes se muestran por separado',()=>{
 const a=field(),b=field();enhancePasswords(a.root);enhancePasswords(b.root);a.handlers.click();assert.equal(a.input.type,'text');assert.equal(b.input.type,'password');
});
test('un teclado que no permite restaurar selección sigue pudiendo ocultar',()=>{
 const s=field();s.input.setSelectionRange=()=>{throw new Error('unsupported')};const [binding]=enhancePasswords(s.root);setPasswordVisibility(s.input,binding.button,true);binding.hide();assert.equal(s.input.type,'password');
});
