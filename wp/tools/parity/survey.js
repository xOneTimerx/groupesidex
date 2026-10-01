// Relevé de styles calculés d'une page : un enregistrement par élément visible porteur
// d'un id Oxygen, d'un texte propre, d'une image ou d'un lien. Sortie JSON.
//   node survey.js <url> <largeur> <sortie.json> [--hide=<sélecteur>]
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const [,, url, width = '1440', out = 'survey.json', ...rest] = process.argv;
const hide = (rest.find(a => a.startsWith('--hide=')) || '').slice(7);

(async () => {
  const b = await puppeteer.launch({ executablePath: '/home/tacti/.local/bin/chromium', headless: 'new', args: ['--no-sandbox'] });
  const p = await b.newPage();
  await p.setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36');
  await p.setViewport({ width: +width, height: 1000 });
  await p.goto(url, { waitUntil: 'networkidle2', timeout: 90000 });
  await p.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 700) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 120)); }
    window.scrollTo(0, 0); await document.fonts.ready;
  });
  if (hide) { await p.addStyleTag({ content: `${hide}{display:none!important}` }); }
  await new Promise(r => setTimeout(r, 800));
  const rows = await p.evaluate(() => {
    const keep = ['font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing', 'text-transform', 'color',
      'background-color', 'background-image', 'padding', 'margin', 'border', 'border-radius', 'display', 'gap',
      'justify-content', 'align-items', 'max-width', 'position', 'text-align', 'text-decoration-line', 'box-shadow', 'opacity', 'object-fit', 'grid-template-columns', 'flex-direction'];
    const out = [];
    const own = el => [...el.childNodes].filter(n => n.nodeType === 3).map(n => n.textContent).join('').replace(/\s+/g, ' ').trim();
    for (const el of document.querySelectorAll('body *')) {
      if (['SCRIPT', 'STYLE', 'NOSCRIPT', 'LINK', 'META', 'PATH', 'svg'].includes(el.tagName)) continue;
      const r = el.getBoundingClientRect();
      if (r.width < 1 || r.height < 1) continue;
      const cs = getComputedStyle(el);
      if (cs.visibility === 'hidden' || cs.display === 'none') continue;
      const text = own(el);
      const isOxy = /^(_?[a-z_-]+-\d+-\d+(-\d+)?)$/.test(el.id || '');
      if (!isOxy && !text && !['IMG', 'A', 'VIDEO', 'BUTTON', 'LI', 'UL', 'NAV', 'HEADER', 'FOOTER', 'SECTION', 'MAIN', 'svg'].includes(el.tagName)) continue;
      const s = {};
      for (const k of keep) { const v = cs.getPropertyValue(k); if (v && !['none', 'normal', '0px', 'rgba(0, 0, 0, 0)', 'auto', 'static', 'start', '1'].includes(v)) s[k] = v; }
      out.push({ tag: el.tagName.toLowerCase(), id: el.id || '', cls: (el.className && el.className.baseVal === undefined ? el.className : '').toString().slice(0, 90),
        x: Math.round(r.x), y: Math.round(r.y + scrollY), w: Math.round(r.width), h: Math.round(r.height),
        text: text.slice(0, 60), href: el.getAttribute('href') || '', src: (el.currentSrc || el.getAttribute('src') || '').split('/').pop().slice(0, 60), s });
    }
    return { height: Math.max(document.documentElement.scrollHeight, document.body.scrollHeight), rows: out };
  });
  fs.writeFileSync(out, JSON.stringify(rows));
  console.log(`${rows.rows.length} éléments, hauteur ${rows.height}px → ${out}`);
  await b.close();
})().catch(e => { console.error(e.message); process.exit(1); });
