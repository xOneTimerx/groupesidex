// Captures de référence Oxygen (animations figées) : node refshots.js <refshots.json> <dossier> [largeurs]
// Pour chaque page et largeur : <nom>-<largeur>.png pleine page + <nom>-<largeur>.json (textes, boîtes, styles).
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const [,, list, outDir = 'out/ref', widths = '390,768,1024,1440,1920'] = process.argv;
const pages = JSON.parse(fs.readFileSync(list, 'utf8'));
fs.mkdirSync(outDir, { recursive: true });
const freezeCss = `*,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto!important}
[data-aos]{opacity:1!important;transform:none!important}.lineChild,.lineParent{transform:none!important;opacity:1!important}
#crisp-chatbox,.crisp-client,.cky-consent-container,.cky-overlay,#cookie-law-info-bar,.cmplz-cookiebanner{display:none!important}`;

async function shot(browser, name, url, width) {
  const p = await browser.newPage();
  await p.setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36');
  await p.setViewport({ width: +width, height: 1000 });
  await p.goto(url, { waitUntil: 'networkidle2', timeout: 180000 });
  await p.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 150)); }
    window.scrollTo(0, 0); await document.fonts.ready;
  });
  await new Promise(r => setTimeout(r, 2500));
  await p.evaluate(() => {
    // Les animations liées à ScrollTrigger restent en pause tant qu'elles ne sont pas déclenchées : on les termine.
    if (window.ScrollTrigger) { ScrollTrigger.getAll().forEach(t => { if (t.animation) t.animation.progress(1); }); }
    if (window.gsap) { gsap.globalTimeline.progress(1); gsap.globalTimeline.pause(); }
    for (let i = 1; i < 99999; i++) clearInterval(i); // arrête le carrousel du héros (setInterval 7,5 s)
    document.querySelectorAll('img[loading="lazy"]').forEach(img => { img.loading = 'eager'; });
  });
  await p.addStyleTag({ content: freezeCss });
  await p.evaluate(async () => { await Promise.all([...document.images].map(i => i.complete ? null : new Promise(r => { i.onload = i.onerror = r; setTimeout(r, 8000); }))); });
  await new Promise(r => setTimeout(r, 500));
  const data = await p.evaluate(() => {
    const own = el => [...el.childNodes].filter(n => n.nodeType === 3).map(n => n.textContent).join('').replace(/\s+/g, ' ').trim();
    const rows = [];
    for (const el of document.querySelectorAll('body *')) {
      if (el.closest('.oxy-off-canvas,[aria-hidden="true"]')) continue;
      const r = el.getBoundingClientRect(); if (r.width < 1 || r.height < 1) continue;
      const cs = getComputedStyle(el); if (cs.visibility === 'hidden' || cs.display === 'none' || +cs.opacity === 0) continue;
      const text = own(el), isImg = el.tagName === 'IMG'; if (text.length < 2 && !isImg) continue;
      rows.push({ tag: el.tagName.toLowerCase(), text: isImg ? 'IMG:' + (el.currentSrc || el.src).split('/').pop().split('?')[0] : text.slice(0, 70),
        x: Math.round(r.x), y: Math.round(r.y + scrollY), w: Math.round(r.width), h: Math.round(r.height),
        fs: cs.fontSize, fw: cs.fontWeight, ff: cs.fontFamily, lh: cs.lineHeight, c: cs.color, ls: cs.letterSpacing, tt: cs.textTransform });
    }
    return { url: location.href, width: innerWidth, height: document.documentElement.scrollHeight, rows };
  });
  fs.writeFileSync(`${outDir}/${name}-${width}.json`, JSON.stringify(data));
  await p.screenshot({ path: `${outDir}/${name}-${width}.png`, fullPage: true, captureBeyondViewport: true });
  await p.close();
  console.log(name, width, data.height);
}

(async () => {
  const b = await puppeteer.launch({ protocolTimeout: 240000, executablePath: '/home/tacti/.local/bin/chromium', headless: 'new', args: ['--no-sandbox', '--disable-gpu', '--hide-scrollbars'] });
  const jobs = Object.entries(pages).flatMap(([n, u]) => widths.split(',').map(w => [n, u, w]));
  const run = async () => { for (let j; (j = jobs.shift());) { try { await shot(b, ...j); } catch (e) { console.error('ÉCHEC', j[0], j[2], e.message); } } };
  await Promise.all([run(), run()]);
  await b.close();
})();
