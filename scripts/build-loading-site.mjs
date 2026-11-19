/** Сборка отдельной статической точки входа. Её файлы доступны, пока Laravel спит. */
import { cp, mkdir, readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = fileURLToPath(new URL('../', import.meta.url));
const output = path.join(root, 'loading-site', 'dist');
const config = JSON.parse(await readFile(path.join(root, 'loading-site', 'config.json'), 'utf8'));
const destination = new URL(config.destination);
if (destination.protocol !== 'https:') throw new Error('El destino debe usar HTTPS.');
const escapeAttribute = (value) => value.replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
let scene = await readFile(path.join(root, 'resources', 'views', 'partials', 'intro.blade.php'), 'utf8');
scene = scene.replace(/\{\{--[\s\S]*?--\}\}/g, '')
    .replace('data-mode="page"', `data-mode="gateway" data-destination="${escapeAttribute(destination.href)}"`);
if (!scene.includes('data-mode="gateway"') || /\{\{|@include|@extends/.test(scene)) {
    throw new Error('La escena contiene una plantilla que no se puede publicar como HTML estático.');
}
const template = await readFile(path.join(root, 'loading-site', 'template.html'), 'utf8');
if (!template.includes('<!-- RP_SCENE -->')) throw new Error('Falta el punto de inserción del cargador.');
await mkdir(path.join(output, 'brand'), { recursive: true });
for (const name of ['loading.css', 'loading.js', 'manrope-800.woff2']) {
    await cp(path.join(root, 'public', 'brand', name), path.join(output, 'brand', name));
}
await writeFile(path.join(output, 'index.html'), template
    .replaceAll('__RP_DESTINATION__', escapeAttribute(destination.href))
    .replace('<!-- RP_SCENE -->', scene));
console.log('Entrada estática preparada en loading-site/dist');
