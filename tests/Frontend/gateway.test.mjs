import test from 'node:test';
import assert from 'node:assert/strict';
import {APP_ORIGIN,ENTRY_URL,gatewayFor,appIsReady} from '../../public/brand/gateway-policy.js';
import {guardedNavigation,navigate,installShell,cleanShellCaches,SHELL_PATH,SHELL_DIGEST,CACHE_NAME,CACHE_PREFIX} from '../../public/rp-gateway-sw.js';
import {mountGateway} from '../../public/brand/gateway-navigation.js';
const svg='<svg xmlns="http://www.w3.org/2000/svg" width="2" height="2" viewBox="0 0 2 2"></svg>';
const image=()=>new Response(svg,{headers:{'Content-Type':'image/svg+xml'}});
const navigation=(path='/profile',extra={})=>({url:APP_ORIGIN+path,method:'GET',mode:'navigate',...extra});

test('transporta rutas y filtros en el fragmento de la entrada fija',()=>{
    const target=APP_ORIGIN+'/clubs/9?date=2026-11-28&sports%5B0%5D=padel#court-3';
    const entry=new URL(gatewayFor(target));assert.equal(entry.origin,new URL(ENTRY_URL).origin);assert.equal(entry.search,'');
    assert.equal(new URLSearchParams(entry.hash.slice(1)).get('next'),'/clubs/9?date=2026-11-28&sports%5B0%5D=padel#court-3');
});
test('no transporta destinos externos, archivos, APIs ni rutas de operaciones',()=>{
    for(const url of ['https://externo.test/profile','javascript:alert(1)',APP_ORIGIN+'/api/clubs',APP_ORIGIN+'/brand/ready',APP_ORIGIN+'/users/1/avatar',APP_ORIGIN+'/logout',APP_ORIGIN+'/bookings',APP_ORIGIN+'/checkout/a/pay'])assert.equal(gatewayFor(url),null,url);
});
test('la imagen de preparación se comprueba sin cookies y sin caché',async()=>{
    let actual;assert.equal(await appIsReady({fetcher:async(...args)=>{actual=args;return image();},now:()=>123}),true);
    assert.match(actual[0],/\/brand\/ready\?_\=123$/);assert.equal(actual[1].credentials,'omit');assert.equal(actual[1].cache,'no-store');assert.equal(actual[1].redirect,'error');
});
test('HTML de Render, errores, imágenes incorrectas y redirecciones no son preparación',async()=>{
    for(const response of [new Response('Render',{status:503}),new Response('<html>Render</html>'),new Response('<svg width="1" height="1"/>',{headers:{'Content-Type':'image/svg+xml'}}),Object.assign(image(),{customRedirect:true})]) {
        const candidate=response.customRedirect?{ok:true,redirected:true,headers:response.headers}:response;
        assert.equal(await appIsReady({fetcher:async()=>candidate}),false);
    }
});
test('una consulta bloqueada vence incluso si el adaptador no atiende abort',async()=>{
    let signal,job,cleared;
    const timers={set(fn){job=fn;return 7;},clear(id){cleared=id;}};
    const pending=appIsReady({timers,fetcher:(_url,init)=>{signal=init.signal;return new Promise(()=>{});}});
    job();assert.equal(await pending,false);assert.equal(signal.aborted,true);assert.equal(cleared,7);
});
test('solo las navegaciones GET conocidas entran en el guardián',()=>{
    assert.equal(guardedNavigation(navigation()),true);
    for(const extra of [{method:'POST'},{method:'PATCH'},{method:'DELETE'},{mode:'cors'},{url:APP_ORIGIN+'/api/clubs'},{url:'https://externo.test/profile'}])assert.equal(guardedNavigation(navigation('/profile',extra)),false);
});
class CacheStore {
    constructor(){this.stores=new Map();this.puts=[];this.deleted=[];}
    async open(name){if(!this.stores.has(name))this.stores.set(name,new Map());const store=this.stores.get(name);return {put:async(key,response)=>{this.puts.push([name,key]);store.set(key,response.clone());},match:async key=>store.get(key)?.clone()};}
    async keys(){return [...this.stores.keys()];}
    async delete(name){this.deleted.push(name);return this.stores.delete(name);}
}
async function shellCache(){const cacheStorage=new CacheStore(),cache=await cacheStorage.open(CACHE_NAME);await cache.put(APP_ORIGIN+SHELL_PATH,new Response('escena pública',{headers:{'Content-Type':'text/html','X-RP-Public-Loader':SHELL_DIGEST}}));return cacheStorage;}
test('la escena aparece sin consultar el servidor ni esperar el timeout anterior',async()=>{
    const cacheStorage=await shellCache();let calls=0;
    const response=await navigate(navigation('/profile'),{cacheStorage,fetcher:()=>{calls++;return new Promise(()=>{});}});
    assert.equal(await response.text(),'escena pública');assert.equal(calls,0);assert.match(response.headers.get('Cache-Control'),/no-store/);
});
test('sin escena instalada se usa la entrada estática sin una espera previa',async()=>{
    let calls=0;const response=await navigate(navigation('/profile'),{cacheStorage:new CacheStore(),fetcher:async()=>{calls++;return new Response('privado');}});
    assert.equal(response.status,302);assert.equal(new URL(response.headers.get('Location')).origin,new URL(ENTRY_URL).origin);assert.equal(calls,0);
});
test('después de ready pasa una vez la petición original y nunca guarda su respuesta',async()=>{
    const request=navigation('/profile?rp_ready=1'),expected=new Response('privado'),cacheStorage=await shellCache(),seen=[];
    const response=await navigate(request,{cacheStorage,fetcher:async r=>{seen.push(r);return expected;}});
    assert.equal(response,expected);assert.deepEqual(seen,[request]);assert.equal(cacheStorage.puts.length,1);assert.equal(guardedNavigation(request),false);
});
test('un error real de Laravel después de ready se conserva sin bucles',async()=>{
    const expected=new Response('Error propio de Laravel',{status:503});
    assert.equal(await navigate(navigation('/profile?rp_ready=1'),{fetcher:async()=>expected}),expected);
});
test('POST se conserva sin comprobación ni repetición del cuerpo',async()=>{
    const request=navigation('/bookings',{method:'POST'}),expected=new Response('enviado');let calls=0;
    assert.equal(await navigate(request,{cacheStorage:{open(){throw Error('No acceder al caché');}},fetcher:async r=>{assert.equal(r,request);calls++;return expected;}}),expected);assert.equal(calls,1);
});
test('si el navegador rechaza el caché se ofrece la entrada estática',async()=>{
    const response=await navigate(navigation(),{cacheStorage:{async open(){throw Error('Sin espacio');}}});assert.equal(response.status,302);
});

class Events {
    constructor(){this.handlers=new Map();}
    addEventListener(n,fn){const set=this.handlers.get(n)||new Set();set.add(fn);this.handlers.set(n,set);}
    removeEventListener(n,fn){this.handlers.get(n)?.delete(fn);}
    emit(type,data={}){const event={type,isTrusted:true,button:0,defaultPrevented:false,preventDefault(){this.defaultPrevented=true;},...data};for(const fn of this.handlers.get(type)||[])fn(event);return event;}
}
function fixture({workers=false,foreign=false,local=false}={}) {
    const doc=new Events(),redirects=[],registrations=[];
    const env={location:{origin:local?'http://127.0.0.1:8000':APP_ORIGIN,pathname:'/',search:'',href:(local?'http://127.0.0.1:8000':APP_ORIGIN)+'/',assign:url=>redirects.push(url)},navigator:{},FormData:class{constructor(form){return form.values||[];}}};
    const worker={scriptURL:APP_ORIGIN+'/rp-gateway-sw.js?loader=20a'};
    if(workers)env.navigator.serviceWorker={controller:worker,getRegistration:async()=>foreign?{active:{scriptURL:APP_ORIGIN+'/other-sw.js'}}:null,register:async(...args)=>{registrations.push(args);return {active:worker};}};
    const link=(href,extra={})=>({href,target:'',hasAttribute:()=>false,...extra});
    const click=(href,extra={})=>doc.emit('click',{target:{closest:()=>link(href)},...extra});
    return {doc,env,redirects,registrations,link,click};
}
test('sin worker el enlace normal pasa por la entrada conservando su destino',async()=>{
    const s=fixture(),binding=mountGateway(s.doc,s.env);await binding.ready;s.click(APP_ORIGIN+'/profile');assert.equal(s.redirects[0],gatewayFor(APP_ORIGIN+'/profile'));binding.destroy();
});
test('respeta anclas, botones modificadores, descargas y enlaces externos',async()=>{
    const s=fixture(),binding=mountGateway(s.doc,s.env);await binding.ready;
    s.click(APP_ORIGIN+'/#map');s.click(APP_ORIGIN+'/profile',{ctrlKey:true});s.click('https://externo.test/profile');s.click(APP_ORIGIN+'/profile',{defaultPrevented:true});s.click(APP_ORIGIN+'/profile',{isTrusted:false});
    s.doc.emit('click',{target:{closest:()=>s.link(APP_ORIGIN+'/profile',{target:'_blank'})}});
    s.doc.emit('click',{target:{closest:()=>s.link(APP_ORIGIN+'/profile',{hasAttribute:()=>true})}});assert.deepEqual(s.redirects,[]);binding.destroy();
});
test('el formulario GET conserva campos repetidos y no altera formularios POST',async()=>{
    const s=fixture(),binding=mountGateway(s.doc,s.env);await binding.ready;
    const form={method:'get',action:APP_ORIGIN+'/encuentra-partido',values:[['sports[]','padel'],['sports[]','tenis'],['budget','15']]};
    s.doc.emit('submit',{target:form});assert.equal(s.redirects.length,1);const next=new URLSearchParams(new URL(s.redirects[0]).hash.slice(1)).get('next');assert.deepEqual(new URL(next,APP_ORIGIN).searchParams.getAll('sports[]'),['padel','tenis']);
    s.doc.emit('submit',{target:{...form,method:'post'}});assert.equal(s.redirects.length,1);binding.destroy();
});
test('con worker propio controla recargas y conserva la navegación nativa',async()=>{
    const s=fixture({workers:true}),binding=mountGateway(s.doc,s.env);assert.equal(await binding.ready,true);s.click(APP_ORIGIN+'/profile');assert.deepEqual(s.redirects,[]);assert.deepEqual(s.registrations[0],['/rp-gateway-sw.js?loader=20a',{scope:'/',type:'module',updateViaCache:'none'}]);binding.destroy();
});
test('no reemplaza otro worker ni registra la protección en desarrollo local',async()=>{
    const s=fixture({workers:true,foreign:true}),binding=mountGateway(s.doc,s.env);assert.equal(await binding.ready,false);assert.equal(s.registrations.length,0);binding.destroy();
    const local=fixture({workers:true,local:true});assert.equal(mountGateway(local.doc,local.env),null);assert.equal(local.registrations.length,0);
});
test('montar dos veces no duplica listeners y destruir los libera',async()=>{
    const s=fixture(),binding=mountGateway(s.doc,s.env);assert.equal(mountGateway(s.doc,s.env),binding);assert.equal(s.doc.handlers.get('click').size,1);binding.destroy();assert.equal(s.doc.handlers.get('click').size,0);assert.equal(s.doc.handlers.get('submit').size,0);
});


test('instalar descarga solo el HTML público y verifica su huella antes de guardarlo',async()=>{
    const {readFile}=await import('node:fs/promises');const bytes=await readFile(new URL('../../public/brand/navigation-shell.html',import.meta.url)),cacheStorage=new CacheStore();let actual;
    await installShell({cacheStorage,fetcher:async(...args)=>{actual=args;return new Response(bytes,{headers:{'Content-Type':'text/html','Set-Cookie':'ignorar=1'}});}});
    assert.equal(new URL(actual[0]).pathname,SHELL_PATH);assert.equal(actual[1].credentials,'omit');assert.equal(actual[1].cache,'reload');assert.equal(actual[1].redirect,'error');
    assert.deepEqual(cacheStorage.puts,[[CACHE_NAME,APP_ORIGIN+SHELL_PATH]]);
    const cached=await (await cacheStorage.open(CACHE_NAME)).match(APP_ORIGIN+SHELL_PATH);assert.equal(cached.headers.has('Set-Cookie'),false);
});
test('HTML de cuenta, errores o una escena de otra versión no entran en el caché',async()=>{
    for(const response of [new Response('cuenta privada',{headers:{'Content-Type':'text/html'}}),new Response('Render',{status:503}),new Response('script',{headers:{'Content-Type':'application/javascript'}})]) {
        const cacheStorage=new CacheStore();await assert.rejects(installShell({cacheStorage,fetcher:async()=>response}));assert.deepEqual(cacheStorage.puts,[]);
    }
});
test('limpiar versiones anteriores no toca otros cachés de la aplicación',async()=>{
    const cacheStorage=new CacheStore();for(const name of [CACHE_PREFIX+'old',CACHE_NAME,'otra-aplicacion'])await cacheStorage.open(name);
    await cleanShellCaches(cacheStorage);assert.deepEqual(cacheStorage.deleted,[CACHE_PREFIX+'old']);assert.deepEqual(await cacheStorage.keys(),[CACHE_NAME,'otra-aplicacion']);
});
test('un worker antiguo todavía activo usa el respaldo hasta completar la actualización',async()=>{
    const s=fixture({workers:true});s.env.navigator.serviceWorker.controller={scriptURL:APP_ORIGIN+'/rp-gateway-sw.js'};
    const binding=mountGateway(s.doc,s.env);await binding.ready;s.click(APP_ORIGIN+'/profile');assert.equal(s.redirects[0],gatewayFor(APP_ORIGIN+'/profile'));binding.destroy();
});
