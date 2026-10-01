// Parité Oxygen ↔ thème : même URL rendue deux fois (A = ?oxygen=1, B = thème), animations figées.
//   node parity.js <url> <largeur> <dossier-sortie> [--shots]
// Écrit a.json / b.json (textes + boîtes + styles) et, avec --shots, a.png / b.png pleine page.
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const [,, url, width = '1440', outDir = 'out/parity', ...flags] = process.argv;
const shots = flags.includes('--shots');
fs.mkdirSync(outDir, { recursive: true });

const freezeCss = `*,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto!important}
.lo-reveal,.lo-reveal img{transform:none!important}.lo-clip-reveal,.imagefx1{clip-path:none!important}
[data-aos]{opacity:1!important;transform:none!important}
#crisp-chatbox,.crisp-client,#cookie-law-info-bar,.cky-consent-container{display:none!important}`;

async function render(browser, target, file) {
  const p = await browser.newPage();
  await p.setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36');
  await p.setViewport({ width: +width, height: 1000 });
  await p.goto(target, { waitUntil: 'networkidle2', timeout: 120000 });
  await p.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 150)); }
    window.scrollTo(0, 0);
    await document.fonts.ready;
  });
  await new Promise(r => setTimeout(r, 2000)); // fin des timelines GSAP déclenchées
  await p.addStyleTag({ content: freezeCss });
  await p.evaluate(() => {
    // Figer les carrousels sur la première diapositive.
    const fl = document.querySelector('.flickity-enabled');
    if (fl && window.Flickity) { const f = Flickity.data(fl); f.stopPlayer?.(); f.pausePlayer?.(); f.select(0, false, true); }
    document.querySelector('[data-hero]')?.dispatchEvent(new Event('mouseenter'));
    const track = document.querySelector('[data-hero-track]');
    if (track) track.style.transform = 'translateX(0)';
    document.querySelectorAll('.lo-marquee__track,.track').forEach(t => { t.style.transform = 'none'; });
    document.querySelectorAll('[style*="translate"]').forEach(el => { if (el.closest('.section-img-reveal-ltr,.section-img-reveal-rtl')) el.style.transform = 'none'; });
  });
  await p.evaluate(async () => {
    document.querySelectorAll('img[loading="lazy"]').forEach(img => { img.loading = 'eager'; });
    await Promise.all([...document.images].map(img => img.complete ? null : new Promise(r => { img.onload = img.onerror = r; setTimeout(r, 8000); })));
  });
  await new Promise(r => setTimeout(r, 400));
  const data = await p.evaluate(() => {
    const own = el => [...el.childNodes].filter(n => n.nodeType === 3).map(n => n.textContent).join('').replace(/\s+/g, ' ').trim();
    const rows = [];
    for (const el of document.querySelectorAll('body *')) {
      if (el.closest('#lo-offcanvas,.oxy-off-canvas,[aria-hidden="true"],.lo-marquee,.marquee')) continue;
      const r = el.getBoundingClientRect();
      if (r.width < 1 || r.height < 1) continue;
      const cs = getComputedStyle(el);
      if (cs.visibility === 'hidden' || cs.display === 'none' || +cs.opacity === 0) continue;
      const text = own(el);
      const isImg = el.tagName === 'IMG';
      if (text.length < 2 && !isImg) continue;
      if (r.x > window.innerWidth || r.right < 0) continue;
      rows.push({ tag: el.tagName.toLowerCase(), text: isImg ? 'IMG:' + (el.currentSrc || el.src).split('/').pop().split('?')[0].replace(/-\d+x\d+(?=\.)/, '') : text.slice(0, 70),
        x: Math.round(r.x), y: Math.round(r.y + scrollY), w: Math.round(r.width), h: Math.round(r.height),
        fs: cs.fontSize, fw: cs.fontWeight, lh: cs.lineHeight, c: cs.color, ls: cs.letterSpacing, tt: cs.textTransform });
    }
    return { height: Math.max(document.documentElement.scrollHeight, document.body.scrollHeight), rows };
  });
  fs.writeFileSync(`${outDir}/${file}.json`, JSON.stringify(data));
  if (shots) await p.screenshot({ path: `${outDir}/${file}.png`, fullPage: true });
  await p.close();
  return data.height;
}

(async () => {
  const b = await puppeteer.launch({ executablePath: '/home/tacti/.local/bin/chromium', headless: 'new', args: ['--no-sandbox', '--hide-scrollbars'] });
  const sep = url.includes('?') ? '&' : '?';
  const ha = await render(b, `${url}${sep}oxygen=1&cb=${Date.now()}`, 'a');
  const hb = await render(b, `${url}${sep}cb=${Date.now()}`, 'b');
  console.log(`${width}px — Oxygen ${ha}px / thème ${hb}px (écart ${hb - ha})`);
  await b.close();
})().catch(e => { console.error(e.message); process.exit(1); });
