import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';
import {createSoundController,mountSounds} from '../../public/ui/sounds.js';
const deferred=()=>{let resolve,reject;const promise=new Promise((a,b)=>{resolve=a;reject=b;});return {promise,resolve,reject};};
const tick=()=>new Promise(resolve=>setImmediate(resolve));
function timers(){let serial=0;const tasks=new Map();return {tasks,set(fn,ms){tasks.set(++serial,{fn,ms});return serial;},clear(id){tasks.delete(id);},fire(){for(const [id,job] of [...tasks]){tasks.delete(id);job.fn();}}};}
function scenario(t,options={}) {
    const contexts=[],changes=[],notices=[],writes=[],clock=timers(),log=[];let time=100;
    const storage={getItem(){if(options.storageError)throw Error('Sin almacenamiento');return options.stored||'off';},setItem(k,v){if(options.storageError)throw Error('Sin almacenamiento');writes.push([k,v]);}};
    class Context {
        constructor(){if(options.constructorError)throw Error('No disponible');this.state=options.state||'suspended';this.currentTime=1;this.sampleRate=44100;this.destination={};this.handlers=new Set();this.resumptions=[];this.suspensions=[];this.gains=[];this.oscillators=[];this.sources=[];contexts.push(this);log.push('create');}
        addEventListener(name,fn){assert.equal(name,'statechange');this.handlers.add(fn);}
        removeEventListener(name,fn){this.handlers.delete(fn);}
        emit(state){this.state=state;for(const fn of [...this.handlers])fn();}
        resume(){log.push('resume');const job=deferred();this.resumptions.push(job);if(options.resume==='reject')job.reject(Error('No permitido'));if(options.resume==='immediate'){this.emit('running');job.resolve();}return job.promise;}
        suspend(){if(options.deferSuspend){const job=deferred();this.suspensions.push(job);return job.promise;}this.emit('suspended');return Promise.resolve();}
        close(){this.emit('closed');return Promise.resolve();}
        createGain(){if(options.failFirstGain&&contexts.length===1)throw Error('Sin recursos');const param={value:1,setValueAtTime(v){this.value=v;},ramps:[],exponentialRampToValueAtTime(value,time){this.ramps.push([value,time]);}};const gain={gain:param,connect(){},disconnect(){this.disconnected=true;}};this.gains.push(gain);return gain;}
        createOscillator(){const oscillator={frequency:{value:0},connect(){},disconnect(){this.disconnected=true;},start(){this.started=true;},stop(){this.stopped=true;}};this.oscillators.push(oscillator);return oscillator;}
        createBuffer(){return {};}
        createBufferSource(){log.push('prime');const source={connect(){},disconnect(){this.disconnected=true;},start(){this.started=true;},stop(){this.stopped=true;}};this.sources.push(source);return source;}
    }
    const controller=createSoundController({Context:options.unsupported?undefined:Context,storage,timers:clock,now:()=>time,onChange:s=>changes.push(s),onNotice:s=>notices.push(s)});
    t.after(()=>controller.destroy());
    return {controller,contexts,changes,notices,writes,clock,log,Context,storage,advance:ms=>time+=ms,
        run(index=0,context=contexts.at(-1)){context.emit('running');context.resumptions[index]?.resolve();},
        reject(index=0){contexts.at(-1).resumptions[index].reject(Error('Bloqueado'));}};
}

test('sin WebAudio no se crea contexto ni se permite activar',async t=>{
    const s=scenario(t,{unsupported:true});assert.equal(s.controller.snapshot().phase,'unsupported');assert.equal(await s.controller.toggle(),false);assert.equal(s.contexts.length,0);
});
test('el estado inicial apagado no crea audio ni reproduce al cargar',t=>{
    const s=scenario(t);assert.equal(s.controller.snapshot().active,false);assert.equal(s.contexts.length,0);assert.equal(s.controller.play('ok'),false);
});
test('la preferencia guardada espera un gesto y no crea contexto en la carga',t=>{
    const s=scenario(t,{stored:'on'});assert.equal(s.controller.snapshot().phase,'armed');assert.equal(s.contexts.length,0);assert.equal(s.controller.play('hover'),false);
});
test('crear, reanudar y preparar el frame silencioso ocurren dentro del gesto original',async t=>{
    const s=scenario(t),result=s.controller.toggle();assert.deepEqual(s.log,['create','resume','prime']);assert.equal(s.controller.snapshot().phase,'starting');assert.equal(s.controller.snapshot().active,false);assert.equal(s.contexts[0].oscillators.length,0);
    s.run();assert.equal(await result,true);assert.equal(s.controller.snapshot().active,true);assert.equal(s.contexts[0].oscillators.length,1);assert.deepEqual(s.writes.at(-1),['rp-sonido','on']);
});
test('un contexto ya iniciado también confirma la activación una sola vez',async t=>{
    const s=scenario(t,{state:'running',resume:'immediate'});assert.equal(await s.controller.toggle(),true);assert.equal(s.log.includes('resume'),true);assert.equal(s.contexts[0].oscillators.length,1);assert.equal(s.clock.tasks.size,0);
});
test('un rechazo de resume vuelve a apagado y permite reintentar',async t=>{
    const s=scenario(t),first=s.controller.toggle();s.reject();assert.equal(await first,false);assert.equal(s.controller.snapshot().phase,'off');assert.deepEqual(s.notices,['error']);assert.equal(s.controller.snapshot().enabled,false);
    const second=s.controller.toggle();s.run(1);assert.equal(await second,true);
});
test('una reanudación que no termina vence sin dejar el botón activado',async t=>{
    const s=scenario(t),result=s.controller.toggle();s.clock.fire();assert.equal(await result,false);assert.equal(s.controller.snapshot().active,false);assert.equal(s.contexts[0].gains[0].gain.value,0);assert.equal(s.contexts[0].oscillators.length,0);
    s.run();await tick();assert.equal(s.controller.snapshot().active,false);assert.equal(s.contexts[0].oscillators.length,0);
});
test('apagar mientras espera cancela el intento y descarta el sonido tardío',async t=>{
    const s=scenario(t),result=s.controller.toggle();await s.controller.toggle();assert.equal(await result,false);s.run();await tick();assert.equal(s.controller.snapshot().phase,'off');assert.equal(s.contexts[0].gains[0].gain.value,0);assert.equal(s.contexts[0].oscillators.length,0);assert.equal(s.clock.tasks.size,0);
});
test('muchos gestos durante el arranque no acumulan notas ni reanudaciones',async t=>{
    const s=scenario(t),first=s.controller.toggle();assert.equal(s.controller.gesture('clic'),first);assert.equal(s.controller.gesture('tecla'),first);assert.equal(s.contexts[0].resumptions.length,1);s.run();await first;assert.equal(s.contexts[0].oscillators.length,1);
});
test('desactivar silencia la ganancia, detiene y desconecta las notas',async t=>{
    const s=scenario(t),result=s.controller.toggle();s.run();await result;await s.controller.toggle();const c=s.contexts[0];assert.equal(s.controller.snapshot().active,false);assert.equal(c.gains[0].gain.value,0);assert.ok(c.oscillators.every(n=>n.stopped&&n.disconnected));assert.ok(c.gains.slice(1).every(n=>n.disconnected));assert.deepEqual(s.writes.at(-1),['rp-sonido','off']);
});
test('una interrupción móvil espera el siguiente toque y reanuda dentro de él',async t=>{
    const s=scenario(t),first=s.controller.toggle();s.run();await first;const c=s.contexts[0];c.emit('interrupted');assert.equal(s.controller.snapshot().phase,'armed');assert.equal(c.resumptions.length,1);s.advance(100);const next=s.controller.gesture('clic');assert.equal(c.resumptions.length,2);s.run(1);assert.equal(await next,true);assert.equal(c.oscillators.length,2);
});
test('ocultar la página cancela un intento pendiente y mantiene la preferencia para otro gesto',async t=>{
    const s=scenario(t,{stored:'on'}),first=s.controller.gesture('clic');s.controller.pause();assert.equal(await first,false);s.run();await tick();assert.equal(s.controller.snapshot().phase,'armed');assert.equal(s.contexts[0].oscillators.length,0);
});
test('un contexto cerrado se sustituye solo al recibir el siguiente gesto',async t=>{
    const s=scenario(t),first=s.controller.toggle();s.run();await first;s.contexts[0].emit('closed');assert.equal(s.contexts.length,1);const next=s.controller.gesture('clic');assert.equal(s.contexts.length,2);s.run();assert.equal(await next,true);
});
test('el resultado de un intento anterior no duplica una activación nueva',async t=>{
    const s=scenario(t),old=s.controller.toggle();await s.controller.toggle();const current=s.controller.toggle();s.run(0);await tick();assert.equal(s.controller.snapshot().active,false);assert.equal(await old,false);s.run(1);assert.equal(await current,true);assert.equal(s.contexts[0].oscillators.length,1);
});
test('el almacenamiento bloqueado no impide activar ni desactivar',async t=>{
    const s=scenario(t,{storageError:true}),result=s.controller.toggle();s.run();assert.equal(await result,true);await s.controller.toggle();assert.equal(s.controller.snapshot().active,false);
});
test('un fallo de recursos no reutiliza un contexto construido a medias',async t=>{
    const s=scenario(t,{failFirstGain:true});assert.equal(await s.controller.toggle(),false);assert.equal(s.contexts[0].state,'closed');const result=s.controller.toggle();s.run();assert.equal(await result,true);assert.equal(s.contexts.length,2);
});
test('los tonos desconocidos y las ráfagas no crean nodos adicionales',async t=>{
    const s=scenario(t),result=s.controller.toggle();s.run();await result;const count=s.contexts[0].oscillators.length;assert.equal(s.controller.play('__proto__'),false);assert.equal(s.controller.play('clic'),false);assert.equal(s.contexts[0].oscillators.length,count);s.advance(100);assert.equal(s.controller.play('clic'),true);
});
test('destruir cancela esperas y descarta respuestas posteriores',async t=>{
    const s=scenario(t),result=s.controller.toggle();s.controller.destroy();s.run();assert.equal(await result,false);assert.equal(s.controller.snapshot().active,false);assert.equal(s.contexts[0].oscillators.length,0);assert.equal(s.clock.tasks.size,0);
});
test('los temporizadores predeterminados conservan el receptor global del navegador',async t=>{
    const s=scenario(t),source=readFileSync(new URL('../../public/ui/sounds.js',import.meta.url),'utf8').replace(/^export /gm,'');
    const realm=vm.createContext({Context:s.Context,performance:{now:()=>100}});
    vm.runInContext(`'use strict';var tasks=new Map(),next=0;function setTimeout(fn,ms){if(this!==globalThis)throw new TypeError('Illegal invocation');tasks.set(++next,fn);return next;}function clearTimeout(id){if(this!==globalThis)throw new TypeError('Illegal invocation');tasks.delete(id);}`,realm);
    vm.runInContext(source+'\nglobalThis.sound=createSoundController({Context});',realm);t.after(()=>realm.sound.destroy());const result=realm.sound.toggle();s.run();assert.equal(await result,true);assert.equal(vm.runInContext('tasks.size',realm),0);
});

class Events {
    constructor(){this.handlers=new Map();}
    addEventListener(n,fn){const set=this.handlers.get(n)||new Set();set.add(fn);this.handlers.set(n,set);}
    removeEventListener(n,fn){this.handlers.get(n)?.delete(fn);}
    emit(n,extra={}){for(const fn of this.handlers.get(n)||[])fn({isTrusted:true,...extra});}
}
function ui(t,options={}) {
    const s=scenario(t,options),button=new Events(),attrs=new Map(),icons={};button.setAttribute=(n,v)=>attrs.set(n,v);button.querySelector=selector=>({toggleAttribute:(n,v)=>icons[selector]=v});
    const status={hidden:true,dataset:{}},divider={hidden:true},doc=new Events(),env=new Events();doc.hidden=false;doc.getElementById=id=>id==='sound-toggle'?button:status;doc.querySelector=()=>divider;env.AudioContext=options.unsupported?undefined:s.Context;env.localStorage=s.storage;
    const binding=mountSounds(doc,env);t.after(()=>binding.destroy());return {...s,button,attrs,icons,status,divider,doc,env,binding};
}
test('el botón solo muestra activación confirmada y no recibe un segundo clic delegado',async t=>{
    const s=ui(t);s.button.emit('click');assert.equal(s.attrs.get('aria-pressed'),'false');assert.equal(s.attrs.get('aria-busy'),'true');s.doc.emit('click',{target:{closest:()=>s.button}});assert.equal(s.contexts[0].resumptions.length,1);s.run();await tick();assert.equal(s.attrs.get('aria-pressed'),'true');assert.equal(s.icons['.snd-on'],false);assert.equal(s.status.textContent,'Sonidos activados.');s.button.emit('click');assert.equal(s.attrs.get('aria-pressed'),'false');
});
test('un navegador sin audio oculta botón y separador',t=>{
    const s=ui(t,{unsupported:true});assert.equal(s.button.hidden,true);assert.equal(s.divider.hidden,true);assert.equal(s.contexts.length,0);
});
test('el montaje repetido conserva un solo listener y destruir lo retira',t=>{
    const s=ui(t);assert.equal(mountSounds(s.doc,s.env),s.binding);assert.equal(s.button.handlers.get('click').size,1);s.binding.destroy();assert.equal(s.button.handlers.get('click').size,0);assert.equal(s.doc.handlers.get('click').size,0);
});
test('la preferencia no activa el audio con hover táctil ni eventos artificiales',t=>{
    const s=ui(t,{stored:'on'});s.button.emit('click',{isTrusted:false});s.doc.emit('pointerover',{pointerType:'touch',target:{closest:()=>true}});assert.equal(s.contexts.length,0);assert.equal(s.attrs.get('aria-pressed'),'true');assert.equal(s.binding.controller.snapshot().active,false);
});
test('las teclas de contraseña y pago no producen sonidos de tecleo',async t=>{
    const s=ui(t);s.button.emit('click');s.run();await tick();const count=s.contexts[0].oscillators.length;
    for(const field of [{type:'password',name:'password',closest:()=>false},{type:'text',name:'password',closest:()=>false},{type:'text',name:'payment[number]',closest:()=>true}])s.doc.emit('keydown',{key:'a',target:{matches:()=>true,...field}});
    assert.equal(s.contexts[0].oscillators.length,count);
});

test('reactivar antes de terminar suspend respeta la última decisión y espera su resume',async t=>{
    const s=scenario(t,{deferSuspend:true}),first=s.controller.toggle();s.run();await first;await s.controller.toggle();
    const last=s.controller.toggle(),c=s.contexts[0];assert.equal(c.resumptions.length,2);assert.equal(s.controller.snapshot().active,false);
    c.emit('suspended');c.suspensions[0].resolve();s.run(1);assert.equal(await last,true);assert.equal(s.controller.snapshot().active,true);assert.equal(c.oscillators.length,2);
});


test('navegar conserva el icono activado sin iniciar audio en el nuevo documento',async t=>{
    const first=ui(t);first.button.emit('click');first.run();await tick();
    assert.deepEqual(first.writes.at(-1),['rp-sonido','on']);first.env.emit('pagehide');
    const next=ui(t,{stored:first.writes.at(-1)[1]});
    assert.equal(next.attrs.get('aria-pressed'),'true');assert.equal(next.attrs.get('aria-label'),'Desactivar sonidos');
    assert.equal(next.icons['.snd-on'],false);assert.equal(next.icons['.snd-off'],true);
    assert.equal(next.binding.controller.snapshot().active,false);assert.equal(next.contexts.length,0);
});
test('apagar el icono guardado antes del primer gesto no crea contexto ni reproduce después',async t=>{
    const s=ui(t,{stored:'on'});s.button.emit('click');
    assert.equal(s.attrs.get('aria-pressed'),'false');assert.equal(s.icons['.snd-on'],true);
    assert.equal(s.attrs.get('aria-label'),'Activar sonidos');assert.deepEqual(s.writes.at(-1),['rp-sonido','off']);
    s.doc.emit('click',{target:{closest:selector=>selector==='#sound-toggle'?null:true}});await tick();
    assert.equal(s.contexts.length,0);assert.equal(s.binding.controller.snapshot().enabled,false);
});
test('ocultar, interrumpir y reanudar mantienen el icono hasta apagarlo expresamente',async t=>{
    const s=ui(t);s.button.emit('click');s.run();await tick();const c=s.contexts[0];
    const selected=()=>{assert.equal(s.attrs.get('aria-pressed'),'true');assert.equal(s.icons['.snd-on'],false);assert.equal(s.attrs.get('aria-label'),'Desactivar sonidos');};
    selected();s.doc.hidden=true;s.doc.emit('visibilitychange');selected();assert.equal(s.binding.controller.snapshot().active,false);
    s.env.emit('pagehide');selected();s.doc.hidden=false;
    const resume=()=>s.doc.emit('click',{target:{closest:selector=>selector==='#sound-toggle'?null:true}});
    resume();selected();assert.equal(s.attrs.get('aria-busy'),'true');s.run(1);await tick();selected();assert.equal(s.binding.controller.snapshot().active,true);
    c.emit('interrupted');selected();assert.equal(s.binding.controller.snapshot().active,false);
    resume();selected();s.run(2);await tick();selected();
    s.button.emit('click');assert.equal(s.attrs.get('aria-pressed'),'false');assert.equal(s.binding.controller.snapshot().active,false);
});


test('los avisos se muestran solo al cambiar la elección con el altavoz',async t=>{
    const s=scenario(t),enabled=s.controller.toggle();s.run();assert.equal(await enabled,true);assert.deepEqual(s.notices,['on']);
    s.controller.pause();const resumed=s.controller.gesture();s.run(1);assert.equal(await resumed,true);assert.deepEqual(s.notices,['on']);
    await s.controller.toggle();assert.deepEqual(s.notices,['on','off']);
    const next=scenario(t,{stored:'on'}),first=next.controller.gesture();next.run();assert.equal(await first,true);assert.deepEqual(next.notices,[]);
});
test('un clic que reanuda en otra página no abre un aviso de activación',async t=>{
    const s=ui(t,{stored:'on'});assert.equal(s.status.hidden,true);
    s.doc.emit('click',{target:{closest:selector=>selector==='#sound-toggle'?null:true}});s.run();await tick();
    assert.equal(s.binding.controller.snapshot().active,true);assert.equal(s.status.hidden,true);assert.equal(s.status.textContent,undefined);
    s.button.emit('click');assert.equal(s.status.textContent,'Sonidos desactivados.');
    s.button.emit('click');s.run(1);await tick();assert.equal(s.status.textContent,'Sonidos activados.');
});
test('pasar el ratón, tocar sin clic y escribir en campos no produce sonidos',async t=>{
    const s=ui(t,{stored:'on'});s.doc.emit('click',{target:{closest:selector=>selector==='#sound-toggle'?null:true}});s.run();await tick();
    const before=s.contexts[0].oscillators.length;
    await new Promise(resolve=>setTimeout(resolve,130));
    const target={matches:()=>true,closest:()=>true,type:'text',name:'name'};
    for(const pointerType of ['mouse','touch','pen'])s.doc.emit('pointerover',{pointerType,target});
    s.doc.emit('pointerdown',{pointerType:'touch',target});s.doc.emit('keydown',{key:'a',target});s.doc.emit('keydown',{key:'Enter',target});
    assert.equal(s.contexts[0].oscillators.length,before);assert.equal(s.status.hidden,true);
});
test('activar, reanudar y clicar usan el mismo tono y nivel de salida',async t=>{
    const enabled=scenario(t),start=enabled.controller.toggle();enabled.run();await start;
    const next=scenario(t,{stored:'on'}),resumed=next.controller.gesture('ok');next.run();await resumed;
    next.advance(100);await next.controller.gesture('error');
    const signature=(c,index)=>({type:c.oscillators[index].type,hz:c.oscillators[index].frequency.value,master:c.gains[0].gain.value,envelope:c.gains[index+1].gain.ramps});
    const reference=signature(enabled.contexts[0],0);
    assert.deepEqual(signature(next.contexts[0],0),reference);assert.deepEqual(signature(next.contexts[0],1),reference);
    assert.equal(reference.master,.8);assert.equal(reference.envelope[0][0],.07);assert.equal(enabled.contexts[0].oscillators.length,1);assert.equal(next.contexts[0].oscillators.length,2);
});
test('un fallo real al reanudar mantiene el aviso de error y apaga la preferencia',async t=>{
    const s=scenario(t,{stored:'on'}),resume=s.controller.gesture();s.reject();assert.equal(await resume,false);
    assert.deepEqual(s.notices,['error']);assert.equal(s.controller.snapshot().enabled,false);assert.deepEqual(s.writes.at(-1),['rp-sonido','off']);
});
