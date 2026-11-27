/* Sonidos opcionales de la interfaz. El contexto solo se crea/reanuda dentro
   de un gesto; guardar la preferencia no autoriza una reproducción automática. */
const RECIPES = Object.freeze({
    clic:{notes:[[1320,0,.035]], volume:.07, type:'triangle'},
    tecla:{notes:[[880,0,.022]], volume:.035, type:'square'},
    hover:{notes:[[1760,0,.025]], volume:.025, type:'sine'},
    ok:{notes:[[523,0,.09],[784,.085,.14]], volume:.10, type:'sine'},
    error:{notes:[[233,0,.13],[175,.1,.18]], volume:.11, type:'sine'},
});
const defaultTimers = {set:(...args)=>globalThis.setTimeout(...args), clear:(...args)=>globalThis.clearTimeout(...args)};

export function createSoundController({Context, storage, onChange=()=>{}, onNotice=()=>{}, timers=defaultTimers, now=()=>performance.now()}={}) {
    let wanted=false, enabled=false, unlocked=false, destroyed=false, context=null, master=null, pending=null, generation=0, lastSound=-Infinity;
    const supported=typeof Context==='function', voices=new Map();
    try {wanted=storage?.getItem('rp-sonido')==='on';} catch { /* Preferencia opcional. */ }
    enabled=wanted; // Una elección ya confirmada se conserva entre páginas.
    const save=value=>{try {storage?.setItem('rp-sonido',value?'on':'off');} catch { /* El audio no depende del almacenamiento. */ }};
    // enabled refleja la elección; active indica si se puede reproducir ahora.
    const snapshot=()=>({supported,wanted,enabled:!destroyed&&supported&&enabled,active:!destroyed&&wanted&&unlocked&&context?.state==='running'&&!pending,
        phase:!supported?'unsupported':pending?'starting':!destroyed&&wanted&&unlocked&&context?.state==='running'?'on':wanted?'armed':'off'});
    const render=()=>onChange(snapshot());
    const silence=()=>{
        try {master?.gain.setValueAtTime(0,context.currentTime);} catch { /* Contexto cerrado. */ }
        for(const [voice,gain] of [...voices]) {try {voice.stop();} catch {} try {voice.disconnect();gain?.disconnect();} catch {} voices.delete(voice);}
    };
    const stopWaiting=()=>{if(pending) pending.finish(false);};
    const stateChanged=()=>{
        if(context?.state!=='running') {unlocked=false;silence();}
        if(!wanted) silence();
        render();
    };
    const ensureContext=()=>{
        if(context?.state==='closed') {
            context.removeEventListener?.('statechange',stateChanged);try {master?.disconnect();} catch {} context=null;master=null;
        }
        if(!context) {
            const fresh=new Context();
            try {
                const gain=fresh.createGain();gain.gain.setValueAtTime(0,fresh.currentTime);gain.connect(fresh.destination);
                context=fresh;master=gain;context.addEventListener?.('statechange',stateChanged);
            } catch(error) {try {Promise.resolve(fresh.close?.()).catch(()=>{});} catch {} throw error;}
        }
        return context;
    };
    const play=(name, interval=45)=>{
        if(!snapshot().active || !Object.hasOwn(RECIPES,name) || voices.size>=12 || now()-lastSound<interval) return false;
        const recipe=RECIPES[name], time=context.currentTime;
        try {
            for(const [hz,delay,duration] of recipe.notes) {
                const oscillator=context.createOscillator(),gain=context.createGain();
                oscillator.type=recipe.type;oscillator.frequency.value=hz;
                gain.gain.setValueAtTime(.0001,time+delay);
                gain.gain.exponentialRampToValueAtTime(recipe.volume,time+delay+.008);
                gain.gain.exponentialRampToValueAtTime(.0001,time+delay+duration);
                oscillator.connect(gain);gain.connect(master);voices.set(oscillator,gain);
                oscillator.onended=()=>{voices.delete(oscillator);oscillator.disconnect();gain.disconnect();};
                oscillator.start(time+delay);oscillator.stop(time+delay+duration+.02);
            }
            lastSound=now();return true;
        } catch {silence();wanted=false;enabled=false;unlocked=false;save(false);render();onNotice('error');return false;}
    };
    const activate=(tone='ok')=>{
        if(!supported||destroyed) return Promise.resolve(false);
        if(pending) return pending.promise; // No se acumulan tonos durante una reanudación.
        if(snapshot().active) {play(tone);return Promise.resolve(true);}
        wanted=true;unlocked=false;
        const ticket=++generation;
        let current;
        try {current=ensureContext();} catch {wanted=false;enabled=false;save(false);render();onNotice('error');return Promise.resolve(false);}
        let resolve, timeout=null;
        const promise=new Promise(done=>{resolve=done;});
        const finish=success=>{
            if(pending?.ticket!==ticket) return;
            timers.clear(timeout);current.removeEventListener?.('statechange',check);pending=null;
            let accepted=success&&ticket===generation&&!destroyed&&wanted&&current.state==='running';
            if(accepted) {try {master.gain.setValueAtTime(.8,current.currentTime);} catch {accepted=false;}}
            if(accepted) {
                unlocked=true;enabled=true;save(true);render();accepted=play(tone,0);if(accepted) onNotice('on');
            } else if(ticket===generation&&!destroyed) {
                wanted=false;enabled=false;unlocked=false;save(false);silence();render();onNotice('error');
            }
            resolve(accepted);
        };
        let resumeFinished=false;
        const check=()=>{if(resumeFinished&&current.state==='running') finish(true);};
        pending={promise,finish,ticket};
        current.addEventListener?.('statechange',check);
        timeout=timers.set(()=>finish(current.state==='running'),2500);
        render();
        try {
            // Se ejecuta ahora, antes de cualquier await: conserva el gesto del toque.
            const resumed=current.resume();
            // Un único frame silencioso ayuda a los motores móviles antiguos.
            if(typeof current.createBuffer==='function'&&typeof current.createBufferSource==='function') {
                const prime=current.createBufferSource();prime.buffer=current.createBuffer(1,1,current.sampleRate||44100);
                prime.connect(master);voices.set(prime,null);prime.onended=()=>{voices.delete(prime);prime.disconnect();};prime.start();
            }
            Promise.resolve(resumed).then(()=>{resumeFinished=true;check();},()=>finish(false));
        } catch {finish(false);}
        return promise;
    };
    const deactivate=()=>{
        generation++;wanted=false;enabled=false;unlocked=false;stopWaiting();silence();save(false);render();onNotice('off');
        try {Promise.resolve(context?.suspend()).catch(()=>{});} catch { /* Ya queda silenciado por la ganancia. */ }
    };
    const pause=()=>{
        generation++;unlocked=false;stopWaiting();silence();render();
        try {Promise.resolve(context?.suspend()).catch(()=>{});} catch {}
    };
    const destroy=()=>{
        generation++;destroyed=true;unlocked=false;stopWaiting();silence();
        context?.removeEventListener?.('statechange',stateChanged);
        try {Promise.resolve(context?.close()).catch(()=>{});} catch {}
    };
    render();
    return {snapshot,activate,play,pause,destroy,toggle:()=>pending||snapshot().enabled?(deactivate(),Promise.resolve(false)):activate('ok'),
        gesture:tone=>wanted?activate(tone):Promise.resolve(false)};
}

const bindings=new WeakMap();
export function mountSounds(doc=document, env=globalThis) {
    if(bindings.has(doc)) return bindings.get(doc);
    const button=doc.getElementById('sound-toggle');if(!button) return null;
    const divider=doc.querySelector('[data-sound-divider]'),status=doc.getElementById('sound-status');let noticeTimer=null;
    const notice=kind=>{
        if(!status) return;
        globalThis.clearTimeout(noticeTimer);
        status.textContent=kind==='on'?'Sonidos activados.':kind==='off'?'Sonidos desactivados.':'No se pudo activar el sonido. Vuelve a tocar el botón.';
        status.hidden=false;status.dataset.kind=kind;
        noticeTimer=globalThis.setTimeout(()=>{status.hidden=true;},kind==='error'?5000:2500);
    };
    const paint=state=>{
        button.hidden=!state.supported;if(divider) divider.hidden=!state.supported;
        button.setAttribute('aria-pressed',String(state.enabled));button.setAttribute('aria-busy',String(state.phase==='starting'));
        const title=state.enabled?'Desactivar sonidos':state.phase==='starting'?'Cancelar activación del sonido':'Activar sonidos';
        button.setAttribute('aria-label',title);button.title=state.phase==='armed'?'Sonidos activados; se reanudarán al tocar la página':title;
        button.querySelector('.snd-on')?.toggleAttribute('hidden',!state.enabled);
        button.querySelector('.snd-off')?.toggleAttribute('hidden',state.enabled);
    };
    let storage;try {storage=env.localStorage;} catch {}
    const controller=createSoundController({Context:env.AudioContext||env.webkitAudioContext,storage,onChange:paint,onNotice:notice});
    const toggle=event=>{if(event.isTrusted===false) return;void controller.toggle();};
    const click=event=>{
        if(event.isTrusted===false||event.target?.closest?.('#sound-toggle')) return;
        if(event.target?.closest?.('button,a,label,summary,[role="button"],input,select,textarea')) void controller.gesture('clic');
    };
    const hover=event=>{if(event.isTrusted!==false&&event.pointerType==='mouse'&&event.target?.closest?.('.btn,.main-nav a')) controller.play('hover',120);};
    const key=event=>{
        const field=event.target;
        if(event.isTrusted===false||event.repeat||!field?.matches?.('input,textarea')||field.type==='password'||field.name?.includes('password')||field.closest('[data-payment-form]')) return;
        if(event.key==='Enter') void controller.gesture('clic');
        else if(event.key?.length===1||event.key==='Backspace') controller.play('tecla');
    };
    const hidden=()=>{if(doc.hidden) controller.pause();};
    const pagehide=()=>controller.pause();
    const destroy=()=>{
        button.removeEventListener('click',toggle);doc.removeEventListener('click',click);doc.removeEventListener('pointerover',hover);doc.removeEventListener('keydown',key);doc.removeEventListener('visibilitychange',hidden);env.removeEventListener?.('pagehide',pagehide);
        globalThis.clearTimeout(noticeTimer);controller.destroy();bindings.delete(doc);
    };
    button.addEventListener('click',toggle);doc.addEventListener('click',click);doc.addEventListener('pointerover',hover);doc.addEventListener('keydown',key);doc.addEventListener('visibilitychange',hidden);env.addEventListener?.('pagehide',pagehide);
    const binding={controller,destroy};bindings.set(doc,binding);return binding;
}
if(typeof document!=='undefined') mountSounds(document);
