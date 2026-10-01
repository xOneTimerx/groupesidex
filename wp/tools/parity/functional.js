// Tests fonctionnels du thème Lorendo sur l'accueil : node functional.js <url>
const puppeteer = require('puppeteer-core');
const url = process.argv[2];
const results = [];
const ok = (name, pass, info = '') => results.push(`${pass ? '✓' : '✗'} ${name}${info ? ' — ' + info : ''}`);

(async () => {
  const b = await puppeteer.launch({ executablePath: '/home/tacti/.local/bin/chromium', headless: 'new', args: ['--no-sandbox', '--autoplay-policy=no-user-gesture-required'] });
  const p = await b.newPage();
  const errors = [];
  p.on('pageerror', (e) => errors.push(e.message));
  p.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
  p.on('requestfailed', (r) => { if (!/crisp|google|facebook|cleantalk/.test(r.url()) && !/ABORTED/.test(r.failure()?.errorText || '')) errors.push('requête échouée ' + r.url()); });

  // Desktop
  await p.setViewport({ width: 1440, height: 1000 });
  await p.goto(url + '?cb=' + Date.now(), { waitUntil: 'networkidle2' });
  ok('rendu par le thème', await p.$eval('body', () => !!document.querySelector('link[href*="themes/lorendo/assets/app.css"]')));
  ok('aucun CSS/JS Oxygen chargé', await p.evaluate(() => ![...document.querySelectorAll('link[rel=stylesheet],script[src]')].some(e => /plugins\/(oxygen|oxy-ninja|oxyextras|oxy-toolbox)|uploads\/oxygen/.test(e.href || e.src))));
  ok('un seul h1', await p.evaluate(() => document.querySelectorAll('h1').length === 1), await p.evaluate(() => document.querySelector('h1')?.textContent.trim()));
  ok('titre de page (Rank Math)', await p.evaluate(() => document.title.length > 10), await p.title());
  ok('meta description', await p.evaluate(() => !!document.querySelector('meta[name="description"]')?.content));
  ok('JSON-LD présent', await p.evaluate(() => document.querySelectorAll('script[type="application/ld+json"]').length > 0));

  // Sous-menu au survol
  const parent = await p.$('.lo-nav > li:has(.lo-submenu) > a');
  await parent.hover();
  await new Promise(r => setTimeout(r, 600));
  ok('sous-menu visible au survol', await p.evaluate(() => [...document.querySelectorAll('.lo-nav .lo-submenu')].some(s => getComputedStyle(s).visibility === 'visible')));

  // Carrousel
  const before = await p.$eval('[data-hero-track]', t => t.style.transform || 'translateX(0%)');
  await p.mouse.move(700, 2000); // hors du héros
  await p.evaluate(() => window.scrollTo(0, 3000));
  await new Promise(r => setTimeout(r, 5600));
  const after = await p.$eval('[data-hero-track]', t => t.style.transform);
  ok('carrousel avance seul (5 s)', before !== after, `${before} → ${after}`);
  await p.click('[data-hero-dot="2"]').catch(() => {});
  await new Promise(r => setTimeout(r, 800));
  ok('point de navigation', await p.$eval('[data-hero-dot="2"]', d => d.getAttribute('aria-current') === 'true'));

  // Vidéo
  await p.evaluate(() => document.querySelector('[data-video]').scrollIntoView());
  await p.click('[data-video]');
  await new Promise(r => setTimeout(r, 1500));
  ok('lecteur vidéo ouvert', await p.$eval('[data-video-dialog]', d => d.open));
  ok('vidéo chargée', await p.$eval('[data-video-dialog] video', v => !!v.currentSrc && v.readyState > 0), await p.$eval('[data-video-dialog] video', v => v.currentSrc.split('/').pop()));
  await p.keyboard.press('Escape');
  await new Promise(r => setTimeout(r, 300));
  ok('Échap ferme la vidéo', await p.$eval('[data-video-dialog]', d => !d.open));

  // En-tête collant
  await p.evaluate(() => window.scrollTo(0, 1500));
  await new Promise(r => setTimeout(r, 300));
  ok('en-tête collant', await p.evaluate(() => { const h = document.querySelector('[data-sticky-header]'); return h.classList.contains('is-stuck') && h.getBoundingClientRect().bottom > 90; }));

  // Mobile
  await p.setViewport({ width: 390, height: 844 });
  await p.goto(url + '?cb=' + Date.now(), { waitUntil: 'networkidle2' });
  await p.click('[data-offcanvas-open]');
  await new Promise(r => setTimeout(r, 600));
  ok('menu mobile ouvert', await p.$eval('[data-offcanvas]', o => o.classList.contains('is-open')));
  const drill = await p.$('.lo-drill__panel.is-active [data-drill-to]');
  if (drill) {
    const label = await drill.evaluate(e => e.textContent.trim());
    await drill.click();
    await new Promise(r => setTimeout(r, 300));
    ok('tiroir de sous-menu', await p.evaluate(() => document.querySelector('.lo-drill__panel.is-active').id !== 'lo-drill-root'), label);
    await p.click('.lo-drill__panel.is-active .lo-drill__title');
    await new Promise(r => setTimeout(r, 300));
    ok('retour au menu', await p.evaluate(() => document.querySelector('.lo-drill__panel.is-active').id === 'lo-drill-root'));
  }
  await p.keyboard.press('Escape');
  await new Promise(r => setTimeout(r, 500));
  ok('Échap ferme le menu', await p.$eval('[data-offcanvas]', o => !o.classList.contains('is-open')));
  ok('pas de débordement horizontal', await p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), await p.evaluate(() => `${document.documentElement.scrollWidth}px`));

  ok('aucune erreur console', errors.length === 0, errors.slice(0, 5).join(' | '));
  console.log(results.join('\n'));
  await b.close();
})().catch(e => { console.error(e); process.exit(1); });
