import test from 'node:test';
import assert from 'node:assert/strict';
import {mkdtemp,readFile,writeFile,mkdir,rm} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {tmpdir} from 'node:os';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {buildNavigationShell} from '../../scripts/build-navigation-shell.mjs';
import {SHELL_DIGEST} from '../../public/rp-gateway-sw.js';
const project=fileURLToPath(new URL('../../',import.meta.url));
const sources=['resources/views/partials/intro.blade.php','public/brand/loading.css','public/brand/loading.js','public/brand/manrope-800.woff2','public/rp-gateway-sw.js'];
async function fixture(t,crlf=false){const root=await mkdtemp(path.join(tmpdir(),'rp-shell-'));t.after(()=>rm(root,{recursive:true,force:true}));for(const name of sources){const target=path.join(root,name);await mkdir(path.dirname(target),{recursive:true});const bytes=await readFile(path.join(project,name));await writeFile(target,crlf&&!name.endsWith('.woff2')?bytes.toString().replaceAll('\n','\r\n'):bytes);}return root;}

test('la escena publicada es autónoma y coincide con la huella del worker',async()=>{
    const bytes=await readFile(path.join(project,'public/brand/navigation-shell.html')),html=bytes.toString();
    assert.equal(createHash('sha256').update(bytes).digest('hex'),SHELL_DIGEST);
    assert.match(html,/data-return="current"/);assert.match(html,/data:font\/woff2;base64,/);
    assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=/i);assert.doesNotMatch(html,/\{\{|@include|@extends/);
});
test('reconstruir es determinista y no necesita PHP, configuración privada ni datos de cuentas',async t=>{
    const root=await fixture(t);await writeFile(path.join(root,'.env'),'PRIVADO_NO_INCLUIR=1');
    const first=await buildNavigationShell(root),before=await readFile(path.join(root,'public/brand/navigation-shell.html'));
    const second=await buildNavigationShell(root),after=await readFile(path.join(root,'public/brand/navigation-shell.html'));
    assert.deepEqual(first,second);assert.deepEqual(before,after);assert.equal(first.digest,SHELL_DIGEST);assert.doesNotMatch(after.toString(),/PRIVADO_NO_INCLUIR/);
});
test('Windows y Linux generan exactamente la misma escena',async t=>{
    const root=await fixture(t,true);assert.equal((await buildNavigationShell(root)).digest,SHELL_DIGEST);
});
test('una plantilla dinámica o contenido incompatible falla antes de publicar la escena',async t=>{
    const root=await fixture(t);const scene=path.join(root,'resources/views/partials/intro.blade.php');await writeFile(scene,(await readFile(scene,'utf8'))+'\n{{ auth()->user()->email }}');
    await assert.rejects(buildNavigationShell(root),/HTML público/);await assert.rejects(readFile(path.join(root,'public/brand/navigation-shell.html')));
});
test('cambiar la escena invalida su versión y actualiza la huella del worker',async t=>{
    const root=await fixture(t),cssPath=path.join(root,'public/brand/loading.css');
    await writeFile(cssPath,(await readFile(cssPath,'utf8'))+'\n.rp-loader{color:#fafafa}\n');
    const changed=await buildNavigationShell(root);assert.notEqual(changed.digest,SHELL_DIGEST);
    const worker=await readFile(path.join(root,'public/rp-gateway-sw.js'),'utf8');assert.ok(worker.includes("SHELL_DIGEST = '"+changed.digest+"'"));
});
