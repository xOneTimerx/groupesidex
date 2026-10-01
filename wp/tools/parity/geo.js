// Géométrie de sélecteurs à plusieurs largeurs : node geo.js <url> <largeurs,séparées> <sel1> <sel2>...
const puppeteer = require('puppeteer-core');
const [,, url, widths, ...sels] = process.argv;
(async () => {
  const b = await puppeteer.launch({ executablePath: '/home/tacti/.local/bin/chromium', headless: 'new', args: ['--no-sandbox'] });
  const p = await b.newPage();
  for (const w of widths.split(',')) {
    await p.setViewport({ width: +w, height: 1000 });
    await p.goto(url, { waitUntil: 'networkidle2', timeout: 90000 });
    const r = await p.evaluate((sels) => sels.map(s => { const e = document.querySelector(s); if (!e) return `${s}: -`;
      const b = e.getBoundingClientRect(), cs = getComputedStyle(e);
      return `${s}: x=${Math.round(b.x)} y=${Math.round(b.y+scrollY)} w=${Math.round(b.width)} h=${Math.round(b.height)} fs=${cs.fontSize} lh=${cs.lineHeight} p=${cs.padding} m=${cs.margin} maxw=${cs.maxWidth} disp=${cs.display}`; }), sels);
    console.log(`--- ${w}px\n` + r.join('\n'));
  }
  await b.close();
})();
