/** Construye una escena pública autónoma; no renderiza Laravel ni lee .env/BD. */
import {readFile,writeFile,mkdir} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
import {APP_ORIGIN} from '../public/brand/gateway-policy.js';

export async function buildNavigationShell(root=fileURLToPath(new URL('../',import.meta.url))) {
    const text=async name=>(await readFile(path.join(root,name),'utf8')).replaceAll('\r\n','\n');
    let scene=(await text('resources/views/partials/intro.blade.php')).replace(/\{\{--[\s\S]*?--\}\}/g,'')
        .replace('data-mode="page"',`data-mode="gateway" data-return="current" data-destination="${APP_ORIGIN}/"`);
    if (!scene.includes('data-return="current"') || scene.includes('{{') || scene.includes('{!!') || /@[A-Za-z_]/.test(scene)) throw new Error('La escena necesita HTML público sin directivas Blade.');
    let css=await text('public/brand/loading.css');
    const font=await readFile(path.join(root,'public/brand/manrope-800.woff2'));
    if (!css.includes("url('./manrope-800.woff2')")) throw new Error('No se reconoce la fuente local del cargador.');
    css=css.replace("url('./manrope-800.woff2')",`url(data:font/woff2;base64,${font.toString('base64')})`);
    const script=await text('public/brand/loading.js');
    if (/<\/script/i.test(script) || /<\/style/i.test(css)) throw new Error('Contenido no admitido en la escena autónoma.');
    const html=`<!doctype html>\n<html lang="es" class="rp-standalone"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#00372e"><title>Reserva Pistes — preparando las pistas</title><style>body{margin:0;background:#00372e}\n${css}</style></head><body>\n${scene}\n<noscript><style>.rp-loader{display:none!important}body{padding:24px;color:white;font-family:system-ui}</style><p>Activa JavaScript para comprobar la carga.</p><a style="color:#ffb433" href="${APP_ORIGIN}/?rp_ready=1">Abrir Reserva Pistes</a></noscript>\n<script>${script}</script></body></html>\n`;
    const digest=createHash('sha256').update(html).digest('hex');
    const workerPath=path.join(root,'public/rp-gateway-sw.js'),worker=await text('public/rp-gateway-sw.js');
    const marker=/^export const SHELL_DIGEST = '(?:[a-f0-9]{64}|__GENERATED_SHELL_SHA256__)';$/m;
    if (!marker.test(worker)) throw new Error('Falta la huella del cargador en el worker.');
    await mkdir(path.join(root,'public/brand'),{recursive:true});
    await writeFile(path.join(root,'public/brand/navigation-shell.html'),html);
    await writeFile(workerPath,worker.replace(marker,`export const SHELL_DIGEST = '${digest}';`));
    return {digest,bytes:Buffer.byteLength(html)};
}
if(process.argv[1]&&path.resolve(process.argv[1])===fileURLToPath(import.meta.url)) {
    const result=await buildNavigationShell();console.log(`Escena pública preparada: ${result.bytes} bytes.`);
}
