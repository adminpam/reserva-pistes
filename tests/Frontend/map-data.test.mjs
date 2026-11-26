// Casos de contrato y de seguridad del contenido recibido por el mapa.
import test from 'node:test';
import assert from 'node:assert/strict';
import { normalizeClubs, clubCount, createClubCard } from '../../public/ui/map-data.js';

const origin = 'http://localhost:8000';
const fixture = { id: 1, name: 'Club de prueba', lat: 41.54, lng: 2.1,
    address: 'Dirección de prueba', sports: ['Tenis'], url: '/clubs/1', image_url: '/img/sports/tenis-thumb.jpg' };

test('normaliza el contrato y conserva el destino local', () => {
    const [club] = normalizeClubs([{ ...fixture, lat: '41.54', sports: ['Tenis', 'Tenis', null] }], origin);
    assert.equal(club.lat, 41.54);
    assert.equal(club.url, 'http://localhost:8000/clubs/1');
    assert.deepEqual(club.sports, ['Tenis']);
});
test('rechaza coordenadas incorrectas y enlaces ejecutables o externos', () => {
    const invalid = [{ ...fixture, lat: null }, { ...fixture, lng: 200 }, { ...fixture, lat: 'incorrecto' },
        { ...fixture, url: 'javascript:alert(1)' }, { ...fixture, url: 'https://example.org/clubs/1' }];
    assert.deepEqual(normalizeClubs(invalid, origin), []);
});
test('una imagen externa se sustituye por la ilustración local', () => {
    const [club] = normalizeClubs([{ ...fixture, image_url: 'https://example.org/track.png' }], origin);
    assert.equal(club.image, 'http://localhost:8000/img/club-cover.svg');
});
test('distingue una base vacía de una respuesta con formato incorrecto', () => {
    assert.deepEqual(normalizeClubs([], origin), []);
    assert.throws(() => normalizeClubs({ message: 'error' }, origin));
    assert.equal(clubCount(0), '0 clubes');
    assert.equal(clubCount(1), '1 club');
    assert.equal(clubCount(41), '41 clubes');
});
test('la tarjeta trata nombres y deportes como texto y no como HTML', () => {
    const elements = [];
    const document = { createElement(tag) {
        const element = { tag, children: [], textContent: '', append(...children) { this.children.push(...children); },
            addEventListener() {}, setAttribute(name, value) { this[name] = value; },
            set innerHTML(value) { throw new Error('No debe interpretarse HTML: ' + value); } };
        elements.push(element); return element;
    } };
    const [club] = normalizeClubs([{ ...fixture, name: '<img src=x onerror=alert(1)>', sports: ['<script>alert(1)</script>'] }], origin);
    createClubCard(club, document);
    assert.equal(elements.find((node) => node.tag === 'h3').textContent, '<img src=x onerror=alert(1)>');
    assert.ok(elements.some((node) => node.textContent === '<script>alert(1)</script>'));
    assert.equal(elements.find((node) => node.tag === 'a').href, 'http://localhost:8000/clubs/1');
});


function cardElements(raw) {
    const elements = [];
    const doc = { createElement(tag) {
        const node = { tag, children: [], textContent: '', append(...nodes) { this.children.push(...nodes); },
            setAttribute(name, value) { this[name] = value; }, addEventListener() {},
            set innerHTML(value) { throw new Error('HTML no permitido: ' + value); } };
        elements.push(node); return node;
    } };
    const [club] = normalizeClubs([{ ...fixture, ...raw }], origin);
    createClubCard(club, doc);
    return { elements, club };
}

test('la ficha muestra precio y pistas filtrados y conserva el enlace con deportes', () => {
    const { elements, club } = cardElements({ court_count: 2, min_price_per_hour: 1400,
        city: 'Ciudad de prueba', url: '/clubs/1?sports[]=padel&sports[]=tenis' });
    assert.equal(club.courtCount, 2);
    assert.equal(elements.find((node) => node.className === 'rp-club-card__meta').textContent, '2 pistas');
    assert.match(elements.find((node) => node.tag === 'strong').textContent, /14,00/);
    assert.deepEqual(new URL(elements.find((node) => node.tag === 'a').href).searchParams.getAll('sports[]'), ['padel', 'tenis']);
    const footer = elements.find((node) => node.className === 'rp-club-card__footer');
    assert.equal(footer.children.filter((node) => node.className === 'rp-club-card__price').length, 1);
    assert.equal(footer.children.find((node) => node.tag === 'a').textContent, 'Ver detalles');
});

test('datos de precio o aforo incorrectos no inventan importes ni pistas', () => {
    const { elements, club } = cardElements({ min_price_per_hour: -1, court_count: '2' });
    assert.equal(club.price, null);
    assert.equal(club.courtCount, null);
    assert.ok(elements.some((node) => node.textContent === 'Precio por consultar'));
    assert.equal(elements.some((node) => node.tag === 'strong'), false);
});
