/* Contrato de datos del mapa. Valida coordenadas y enlaces antes de construir
   contenido DOM; los nombres recibidos nunca se interpretan como HTML. */
export function normalizeClubs(payload, origin) {
    if (!Array.isArray(payload)) throw new Error('Respuesta de clubes no válida.');
    const base = new URL(origin);
    const localUrl = (value, fallback = null) => {
        if (typeof value !== 'string' || !value.trim()) return fallback;
        try {
            const url = new URL(value, base);
            return ['http:', 'https:'].includes(url.protocol) && url.origin === base.origin ? url.href : fallback;
        } catch { return fallback; }
    };
    const result = [];
    const seen = new Set();
    for (const raw of payload) {
        if (!raw || typeof raw !== 'object' || typeof raw.name !== 'string' || !raw.name.trim()) continue;
        if (raw.lat === null || raw.lat === undefined || raw.lat === '' || raw.lng === null || raw.lng === undefined || raw.lng === '') continue;
        const lat = Number(raw.lat), lng = Number(raw.lng);
        const url = localUrl(raw.url);
        if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180 || !url) continue;
        if (seen.has(url)) continue;
        seen.add(url);
        result.push({
            price: Number.isInteger(raw.min_price_per_hour) && raw.min_price_per_hour >= 0 ? raw.min_price_per_hour : null,
            id: String(raw.id ?? url), name: raw.name.trim().slice(0, 180),
            address: typeof raw.address === 'string' ? raw.address.slice(0, 240) : '',
            city: typeof raw.city === 'string' ? raw.city.slice(0, 100) : '', lat, lng, url,
            sports: [...new Set((Array.isArray(raw.sports) ? raw.sports : []).filter((name) => typeof name === 'string' && name.trim()).map((name) => name.trim().slice(0, 70)))],
            image: localUrl(raw.image_url, new URL('/img/club-cover.svg', base).href),
            imageDescription: typeof raw.image_description === 'string' ? raw.image_description.slice(0, 180) : 'Ilustración de un club deportivo',
        });
    }
    return result;
}

export function clubCount(count) { return count === 1 ? '1 club' : `${count} clubes`; }

export function createClubCard(club, doc = document) {
    const node = (tag, className, text) => {
        const element = doc.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    };
    const article = node('article', 'rp-club-card');
    const figure = node('figure');
    const image = node('img');
    image.src = club.image;
    image.alt = club.imageDescription;
    image.width = 84; image.height = 90;
    image.addEventListener('error', () => {
        image.src = new URL('/img/club-cover.svg', club.url).href;
    }, { once: true });
    figure.append(image, node('figcaption', '', 'Ilustración'));
    const content = node('div');
    content.append(node('h3', '', club.name));
    content.append(node('p', '', club.address || club.city || 'Dirección no disponible'));
    const sports = node('div', 'rp-club-card__sports');
    for (const sport of club.sports) sports.append(node('span', 'rp-club-card__sport', sport));
    content.append(sports);
    const link = node('a', 'rp-club-card__link', 'Ver detalles');
    link.href = club.url;
    link.setAttribute('aria-label', `Ver detalles de ${club.name}`);
    const arrow = node('span', '', '→');
    arrow.setAttribute('aria-hidden', 'true');
    link.append(arrow);
    content.append(link);
    article.append(figure, content);
    return article;
}
