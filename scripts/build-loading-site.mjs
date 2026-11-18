/** Construye una entrada estática independiente de Laravel. Reutiliza los
 * archivos públicos para que ambos cargadores mantengan el mismo diseño. */
import { cp, mkdir, readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = fileURLToPath(new URL('../', import.meta.url));
const output = path.join(root, 'loading-site', 'dist');
await mkdir(path.join(output, 'brand'), { recursive: true });
for (const name of ['loading.css', 'loading.js', 'manrope-800.woff2']) {
    await cp(path.join(root, 'public', 'brand', name), path.join(output, 'brand', name));
}
const config = JSON.parse(await readFile(path.join(root, 'loading-site', 'config.json'), 'utf8'));
const destination = new URL(config.destination);
if (destination.protocol !== 'https:') throw new Error('El destino debe usar HTTPS.');
const escapeAttribute = (text) => text.replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;');
const scene = (await readFile(path.join(root, 'resources', 'views', 'partials', 'intro.blade.php'), 'utf8'))
    .replace(/\{\{--[\s\S]*?--\}\}/g, '')
    .replace('data-mode="page"', `data-mode="gateway" data-destination="${escapeAttribute(destination.href)}"`);
const template = await readFile(path.join(root, 'loading-site', 'template.html'), 'utf8');
await writeFile(path.join(output, 'index.html'), template.replace('<!-- RP_SCENE -->', scene));
console.log('Entrada estática preparada en loading-site/dist');
