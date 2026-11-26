import assert from 'node:assert/strict';
import test from 'node:test';

// Se ejecuta el módulo real con DOM/Leaflet simulados; no es una prueba visual.
test('el mapa conserva filtros y ficha, descarta respuestas antiguas y restaura historial y reintentos', async (t) => {
    const pending = [], events = new Map(), frames = new Map();
    let frameId = 0;
    class Element {
        constructor() { this.listeners={}; this.children=[]; this.attrs={}; this.dataset={}; this.hidden=false; this.style={setProperty(){}}; this.classList={add(){},remove(){},toggle(){}}; this.clientHeight=650; this.scrollHeight=160; }
        addEventListener(name, fn) { (this.listeners[name]??=[]).push(fn); }
        fire(name, target=this) { this.listeners[name]?.forEach(fn=>fn({target})); }
        append(...nodes) { this.children.push(...nodes); }
        replaceChildren(...nodes) { this.children=nodes; }
        setAttribute(k,v) { this.attrs[k]=v; }
        getAttribute(k) { return this.attrs[k]; }
        removeAttribute(k) { delete this.attrs[k]; }
        querySelector(k) { return this.refs?.[k] ?? null; }
        querySelectorAll(k) { return this.lists?.[k] ?? []; }
        getBoundingClientRect() { return {height:64}; }
        contains() { return false; }
        focus() {}
        remove() {}
    }
    const el=()=>new Element();
    const options=['padel','tenis','badminton'].map(value=>Object.assign(el(),{value,checked:false}));
    const group=el(),clear=el(),guide=el(),caption=el(),summary=el();group.refs={'[data-sport-clear]':clear,'[data-sport-guide]':guide,'[data-sport-caption]':caption,'[data-sport-summary]':summary};group.lists={'[data-sport-option]':options};
    const frame=el(),panel=el(),handle=el(),list=el(),selection=el(),mapButton=el(),hint=el(),selectedCard=el(),clubList=el();
    handle.parentElement=el();selection.refs={'[data-selected-card]':selectedCard};
    const root=el();
    root.refs={'[data-sport-filter="map"]':group,'[data-map-state]':el(),'[data-map-state-title]':el(),'[data-map-state-text]':el(),'[data-map-retry]':el(),'[data-map-message]':el(),'[data-selected-card]':selectedCard,'[data-mobile-count]':el(),'[data-mobile-club-list]':clubList,'[data-club-sort]':el(),'[data-club-drawer]':panel,'[data-drawer-handle]':handle,'[data-drawer-list]':list,'[data-mobile-selection]':selection,'[data-drawer-map]':mapButton,'[data-drawer-action]':hint,'.rp-sports':el(),'.rp-map-frame':frame,'#rp-club-map':el(),'[data-selection-close]':el()};
    const mapEl=root.refs['#rp-club-map'];mapEl.dataset={clubsUrl:'/api/clubs',tilesUrl:'/tiles'};
    const count=el();
    const document={readyState:'complete',activeElement:null,getElementById:id=>id==='rp-club-map'?mapEl:count,querySelector:()=>root,querySelectorAll:()=>[],createElement:()=>el(),createTextNode:text=>({textContent:text})};
    const media={matches:true,addEventListener(){},removeEventListener(){}};
    const location={href:'http://localhost:8000/',origin:'http://localhost:8000'};
    const history={pushes:[],pushState(state,title,url){location.href=String(url);this.pushes.push(String(url));},replaceState(state,title,url){location.href=String(url);}};
    const win={location,history,matchMedia:q=>q.includes('max-width')?media:{...media,matches:false},setTimeout,clearTimeout,requestAnimationFrame:fn=>{frames.set(++frameId,fn);return frameId;},cancelAnimationFrame:id=>frames.delete(id),addEventListener:(name,fn)=>events.set(name,fn),removeEventListener(){},visualViewport:null};
    const markers=[];const map={closePopup(){},invalidateSize(){},setView(){return this;},panInside(){},fitBounds(){}};
    win.L={map:()=>map,control:{zoom:()=>({addTo(){}})},tileLayer:()=>({addTo(){return this;},on(){}}),layerGroup:()=>({addTo(){return this;},clearLayers(){markers.length=0;}}),divIcon:x=>x,latLngBounds:x=>x,marker:(coords,opts)=>{const m={coords,opts,getElement:()=>({querySelector:()=>el()}),on(name,fn){this.click=fn;},addTo(){}};markers.push(m);return m;}};
    Object.assign(globalThis,{window:win,document,cancelAnimationFrame:win.cancelAnimationFrame,requestAnimationFrame:win.requestAnimationFrame,fetch:(url,opts)=>new Promise(resolve=>pending.push({url:new URL(url),opts,resolve}))});
    t.after(() => events.get('pagehide')?.({ persisted: false }));
    await import(new URL('../../public/ui/club-explorer.js', import.meta.url));
    const flush=()=>new Promise(resolve=>setImmediate(resolve));
    const answer=(request,name)=>request.resolve({ok:true,json:async()=>[{id:1,name,lat:41.5,lng:2.1,sports:['Pádel'],url:'/clubs/1'+request.url.search,image_url:'/img/club-cover.svg',min_price_per_hour:1500}]});
    const toggle=slug=>{const input=options.find(x=>x.value===slug);input.checked=!input.checked;group.fire('change',input);};
    assert.equal(pending.length,1);
    assert.equal(guide.hidden,false);
    assert.equal(pending[0].url.searchParams.size,0);
    answer(pending[0],'Club inicial'); await flush();
    assert.equal(count.textContent,'1 club');
    toggle('tenis'); const old=pending[1];
    toggle('padel'); const newest=pending[2];
    assert.deepEqual(newest.url.searchParams.getAll('sports[]'),['padel','tenis']);
    assert.equal(newest.url.searchParams.has('sport'),false);
    assert.equal(old.opts.signal.aborted,true);
    assert.equal(guide.hidden,true);
    answer(newest,'Club actualizado'); await flush();
    answer(old,'Respuesta atrasada'); await flush();
    assert.equal(clubList.children[0].children[1].children[0].textContent,'Club actualizado');
    assert.equal(markers.length,1);
    markers[0].click();
    assert.equal(root.dataset.drawerState,'peek');
    assert.equal(selection.hidden,false);
    const card=selectedCard.children[0];
    const link=card.children[2].children.find(x=>x.className==='rp-club-card__link');
    assert.deepEqual(new URL(link.href).searchParams.getAll('sports[]'),['padel','tenis']);
    assert.equal(new URL(location.href).searchParams.getAll('sports[]').length,2);
    const pushes=history.pushes.length;
    location.href='http://localhost:8000/?sports[]=tenis';events.get('popstate')();
    assert.equal(history.pushes.length,pushes);
    assert.equal(options.find(x=>x.value==='tenis').checked,true);
    assert.equal(options.find(x=>x.value==='padel').checked,false);
    assert.deepEqual(pending[3].url.searchParams.getAll('sports[]'),['tenis']);
    answer(pending[3],'Club al volver');await flush();
    clear.fire('click');
    assert.equal(pending[4].url.searchParams.size,0);
    assert.equal(guide.hidden,false);
    assert.equal(clear.hidden,true);
    answer(pending[4],'Catálogo completo');await flush();
    // Recuperar la página desde bfcache vuelve a leer la URL y consulta datos actuales.
    location.href='http://localhost:8000/?sports[0]=padel';events.get('pageshow')({persisted:true});
    assert.deepEqual(pending[5].url.searchParams.getAll('sports[]'),['padel']);
    answer(pending[5],'Actualizado tras caché');await flush();
    // Fallo real del API: Reintentar usa la selección vigente.
    toggle('badminton');pending[6].resolve({ok:false});await flush();
    assert.equal(count.textContent,'Clubes no disponibles');
    root.refs['[data-map-retry]'].fire('click');
    assert.deepEqual(pending[7].url.searchParams.getAll('sports[]'),['badminton','padel']);
    answer(pending[7],'Recuperado');await flush();
    assert.equal(count.textContent,'1 club');
    // Un resultado vacío conserva los filtros y permite limpiar sin perder la ayuda.
    toggle('tenis'); pending[8].resolve({ok:true,json:async()=>[]}); await flush();
    assert.equal(count.textContent,'0 clubes');
    assert.equal(root.refs['[data-map-state-title]'].textContent,'Ningún club reúne todos estos deportes');
    assert.equal(guide.hidden,true);
    assert.equal(clear.hidden,false);
    clear.fire('click');
    assert.equal(pending[9].url.searchParams.size,0);
    assert.equal(guide.hidden,false);
    answer(pending[9],'Catálogo recuperado'); await flush();
    assert.equal(count.textContent,'1 club');
    events.get('pagehide')({persisted:false});

});
