// Chargement différé de Fluent Forms (inc/forms-defer.php) sur le staging : node forms-defer-check.js <chemin> [mobile|desktop] [scroll]
// Vérifie qu'aucune bibliothèque différée (reCAPTCHA, flatpickr, jquery.mask, jQuery File Upload) ne part au chargement,
// puis qu'à l'ouverture du panneau Soumission (ou au défilement avec « scroll ») le widget reCAPTCHA, le calendrier,
// les champs fichier et les masques sont branchés, sans erreur JS. Lecture seule : rien n'est envoyé.
const puppeteer = require('puppeteer-core');
const [,, path = '/', mode = 'desktop', action = 'panel'] = process.argv;
const base = 'https://stg-groupesidexcom-staging.kinsta.cloud';
const DEFERRED = /recaptcha|flatpickr\.min\.js|jquery\.mask|jQuery-File-Upload/;
(async () => {
  const b = await puppeteer.launch({ executablePath: '/home/tacti/.local/bin/chromium', headless: 'new', args: ['--no-sandbox'] });
  const p = await b.newPage();
  if (mode === 'mobile') await p.emulate(puppeteer.KnownDevices['Pixel 5']); else await p.setViewport({ width: 1440, height: 1000 });
  const reqs = []; p.on('request', r => { if (DEFERRED.test(r.url())) reqs.push(r.url().replace(/\?.*/, '').split('/').pop()); });
  const errs = []; p.on('pageerror', e => errs.push(e.message)); p.on('console', m => { if (m.type() === 'error') errs.push('console: ' + m.text().slice(0, 160)); });
  await p.goto(base + path + '?cb=' + Date.now(), { waitUntil: 'networkidle2', timeout: 180000 });
  await new Promise(r => setTimeout(r, 3000));
  const state = () => p.evaluate(() => ({
    recaptcha: document.querySelectorAll('.g-recaptcha[data-g-recaptcha_widget_id]').length + '/' + document.querySelectorAll('.g-recaptcha').length,
    flatpickr: [...document.querySelectorAll('.ff-el-datepicker')].filter(e => e._flatpickr).length + '/' + document.querySelectorAll('.ff-el-datepicker').length,
    upload: [...document.querySelectorAll('form.frm-fluent-form input[type=file]')].filter(e => jQuery(e).data('blueimp-fileupload')).length + '/' + document.querySelectorAll('form.frm-fluent-form input[type=file]').length,
    mask: typeof jQuery.fn.mask,
  }));
  const forms = await p.evaluate(() => [...document.querySelectorAll('form.frm-fluent-form')].map(f => f.id + (f.closest('[id^="-off-canvas"]') ? ' (panneau)' : ' (page)')));
  console.log(mode, path, '| formulaires :', forms.join(', '));
  console.log('  au chargement :', reqs.length ? reqs.join(', ') : 'aucune bibliothèque différée', JSON.stringify(await state()));
  if (action === 'scroll') {
    await p.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 400) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 120)); } });
  } else {
    await p.evaluate(() => [...document.querySelectorAll('.multi-trigger-soumission')].find(x => x.offsetParent)?.click());
  }
  await new Promise(r => setTimeout(r, 4000));
  console.log('  après', action === 'scroll' ? 'défilement :' : 'ouverture du panneau :', reqs.length, 'requêtes', JSON.stringify(await state()));
  console.log('  erreurs JS :', errs.join(' | ') || 'aucune');
  await b.close();
})();
