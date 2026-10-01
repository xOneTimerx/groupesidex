// Tests fonctionnels Sidex : chaque test tourne sur le thème ET sur ?oxygen=1 ; on compare les résultats.
//   node sidex-func.js [base] [motif]
const puppeteer = require('puppeteer-core');
const base = process.argv[2] || 'https://stg-groupesidexcom-staging.kinsta.cloud';
const only = new RegExp(process.argv[3] || '.');
const sleep = ms => new Promise(r => setTimeout(r, ms));
const vis = (p, sel) => p.evaluate(s => { const e = document.querySelector(s); if (!e) return 'absent'; const r = e.getBoundingClientRect(); const cs = getComputedStyle(e); return (r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && +cs.opacity > 0.05) ? 'visible' : 'caché'; }, sel);

const TESTS = [
  ['Sous-menu Revêtements au survol (bureau)', '/', 1440, async p => { await p.hover('#-pro-menu-31-69 .menu-item-has-children > a'); await sleep(700); return vis(p, '#-pro-menu-31-69 .menu-item-has-children .sub-menu'); }],
  ['Panneau Soumission (bouton en-tête)', '/', 1440, async p => { await p.click('#div_block-161-69 .multi-trigger-soumission'); await sleep(900); return vis(p, '#-off-canvas-167-69-inner'); }],
  ['Panneau Soumission : formulaire présent', '/', 1440, async p => p.evaluate(() => !!document.querySelector('#-off-canvas-167-69 form.frm-fluent-form'))],
  ['Menu mobile (hamburger)', '/', 390, async p => { await p.click('#fancy_icon-96-69'); await sleep(900); return vis(p, '#-off-canvas-194-69-inner'); }],
  ['Menu mobile : sous-menu coulissant', '/', 390, async p => { await p.click('#fancy_icon-96-69'); await sleep(900); const b = await p.$('#-horizontal-slide-menu-199-69 .mm-btn_next, #-horizontal-slide-menu-199-69 .mm-listitem__btn'); if (!b) return 'pas de bouton'; await b.click(); await sleep(700); return p.evaluate(() => document.querySelectorAll('#-horizontal-slide-menu-199-69 .mm-panel_opened, #-horizontal-slide-menu-199-69 .mm-panel--opened').length); }],
  ['En-tête collant après défilement', '/', 1440, async p => { await p.evaluate(() => window.scrollTo(0, 1500)); await sleep(800); return p.evaluate(() => document.querySelector('#_header-5-69').className.includes('oxy-sticky-header-active')); }],
  ['CTA mobile collant visible après défilement', '/', 390, async p => { await p.evaluate(() => window.scrollTo(0, 1500)); await sleep(800); return vis(p, '#div_block-206-69'); }],
  ['Carrousel de l\'accueil initialisé', '/', 1440, async p => p.evaluate(() => document.querySelectorAll('.flickity-enabled').length)],
  ['Héros : diapositive suivante', '/', 1440, async p => { const a = await p.evaluate(() => document.querySelector('.slide-count')?.textContent); await sleep(8200); const b = await p.evaluate(() => document.querySelector('.slide-count')?.textContent); return `${a}→${b}`; }],
  ['Réalisations : grille + facettes', '/projets/', 1440, async p => p.evaluate(() => ({ cartes: document.querySelectorAll('.oxy-dynamic-list > .ct-div-block, .wpgb-card').length, facettes: document.querySelectorAll('.wpgb-facet').length }))],
  ['Réalisations : toggle des filtres', '/projets/', 1440, async p => { const before = await p.evaluate(() => document.querySelector('#_toggle-242-1582')?.className); await p.click('#_toggle-242-1582'); await sleep(600); const after = await p.evaluate(() => document.querySelector('#_toggle-242-1582')?.className); return before !== after; }],
  ['Réalisations : filtre « Intérieur »', '/projets/', 1440, async p => { const count = () => p.evaluate(() => document.querySelectorAll('#_dynamic_list-3-1582 > .ct-div-block').length); const n0 = await count(); const cbs = await p.$$('#-wpgb-facet-163-1582 .wpgb-checkbox'); if (cbs.length < 2) return 'pas de case'; await cbs[1].click(); await sleep(5000); const n1 = await count(); const first = await p.evaluate(() => document.querySelector('#_dynamic_list-3-1582 > .ct-div-block')?.textContent.trim().slice(0, 40)); return `${n0}→${n1} ${first}`; }],
  ['Page GEO : onglets dynamiques', '/bardeau-cedre/', 1440, async p => { const tabs = await p.$$('.oxy-dynamic-tabs_tab'); if (tabs.length < 2) return 'pas d\'onglets'; await tabs[1].click(); await sleep(800); return p.evaluate(() => [...document.querySelectorAll('.oxy-dynamic-tabs_panel')].map(x => getComputedStyle(x).display !== 'none' && x.getBoundingClientRect().height > 0).join(',')); }],
  ['Page GEO : accordéon FAQ', '/bardeau-cedre/', 1440, async p => { const h = await p.$$('.oxy-pro-accordion_header'); if (h.length < 2) return 'pas d\'accordéon'; await h[1].click(); await sleep(800); return p.evaluate(() => [...document.querySelectorAll('.oxy-pro-accordion_item')].slice(0, 3).map(x => x.querySelector('.oxy-pro-accordion_header').getAttribute('aria-expanded')).join(',')); }],
  ['Billet : table des matières remplie', '/revetement-de-bois-sans-entretien/', 1440, async p => p.evaluate(() => document.querySelectorAll('.oxy-table-of-contents a').length)],
  ['Billet : temps de lecture', '/revetement-de-bois-sans-entretien/', 1440, async p => p.evaluate(() => document.querySelector('.oxy-reading-time')?.textContent.trim())],
  ['Réalisation : galerie + visionneuse', '/projets/vertendre-100-de-la-reserve/', 1440, async p => { const a = await p.$('.oxy-gallery-item'); if (!a) return 'pas de galerie'; await a.click(); await sleep(1200); return vis(p, '.pswp--open, .pswp.pswp--visible'); }],
  ['Documents : lightbox', '/documents/', 1440, async p => { const a = await p.$('.oxy-lightbox_link'); if (!a) return 'pas de lightbox'; await a.click(); await sleep(1500); return vis(p, '.fancybox__container, .fancybox-container'); }],
  ['À propos : carrousel', '/a-propos/', 1440, async p => p.evaluate(() => document.querySelectorAll('.flickity-enabled').length)],
  ['Carrières : liste des postes dans le formulaire', '/carrieres/', 1440, async p => p.evaluate(() => document.querySelectorAll('select[name=type_emploi] option').length)],
  ['Anglais : menu principal traduit', '/en/', 1440, async p => p.evaluate(() => document.querySelector('#-pro-menu-31-69 a')?.textContent.trim())],
  ['Anglais : sélecteur de langue → FR', '/en/', 1440, async p => p.evaluate(() => [...document.querySelectorAll('#-pro-menu-166-69 a')].map(a => a.getAttribute('href')).find(h => h && !h.includes('/en/')) || 'aucun')],
  ['Ancre interne : défilement', '/bardeau-cedre/', 1440, async p => { const a = await p.$('a[href^="#"]:not([href="#"])'); if (!a) return 'pas d\'ancre'; await a.click(); await sleep(1500); return p.evaluate(() => Math.round(scrollY) > 200); }],
];

(async () => {
  const b = await puppeteer.launch({ executablePath: '/home/tacti/.local/bin/chromium', headless: 'new', args: ['--no-sandbox'] });
  let same = 0, n = 0;
  for (const [name, path, width, fn] of TESTS) {
    if (!only.test(name)) continue;
    const out = [];
    for (const variant of ['', 'oxygen=1']) {
      const p = await b.newPage();
      const errors = [];
      p.on('pageerror', e => errors.push(e.message.split('\n')[0].slice(0, 120)));
      await p.setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36');
      await p.setViewport({ width, height: 900 });
      // Référence : ?oxygen=1 sur le même site, ou un autre site (REF_BASE, ex. le site en ligne) une fois Oxygen désactivé.
      const url = variant ? (process.env.REF_BASE ? process.env.REF_BASE + path : base + path + (path.includes('?') ? '&' : '?') + variant) : base + path;
      try {
        await p.goto(url, { waitUntil: 'networkidle2', timeout: 150000 });
        await sleep(1200);
        out.push({ r: JSON.stringify(await fn(p)), e: errors });
      } catch (e) { out.push({ r: 'ERREUR ' + e.message.split('\n')[0].slice(0, 100), e: errors }); }
      await p.close();
    }
    n++; const ok = out[0].r === out[1].r; same += ok;
    console.log(`${ok ? 'OK ' : '≠  '} ${name} — thème ${out[0].r} / ${process.env.REF_BASE ? 'en ligne' : 'Oxygen'} ${out[1].r}${out[0].e.length ? '  [erreurs JS thème : ' + out[0].e.join(' ; ') + ']' : ''}`);
  }
  console.log(`\n${same}/${n} comportements identiques`);
  await b.close();
})();
