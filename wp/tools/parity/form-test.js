// Test réel du formulaire Fluent Forms 3 (Soumission) sur le staging : node form-test.js <chemin> [jeton-contournement-captcha]
// Sans jeton : on attend le refus reCAPTCHA. Avec jeton (mu-plugin temporaire sur le staging) : envoi complet attendu.
const puppeteer = require('puppeteer-core');
const [,, path = '/soumission/', token] = process.argv;
const base = 'https://stg-groupesidexcom-staging.kinsta.cloud';
(async () => {
  const b = await puppeteer.launch({ executablePath: '/home/tacti/.local/bin/chromium', headless: 'new', args: ['--no-sandbox'] });
  const p = await b.newPage(); await p.setViewport({ width: 1440, height: 1000 });
  if (token) await p.setCookie({ name: 'sx_qa_captcha', value: token, domain: 'stg-groupesidexcom-staging.kinsta.cloud' });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  const ajax = [];
  p.on('response', async r => { if (r.url().includes('admin-ajax.php') && r.request().method() === 'POST') { const t = await r.text().catch(() => ''); if (/fluentform_submit|"result"|errors/.test(t) || /fluentform/.test(r.request().postData() || '')) ajax.push(`${r.status()} ${t.slice(0, 300)}`); } });
  await p.goto(base + path + '?cb=' + Date.now(), { waitUntil: 'networkidle2', timeout: 180000 });
  const sel = 'form.frm-fluent-form[data-form_id="3"]';
  // Panneau « Soumission » de l'en-tête (chemin principal des leads) : on l'ouvre comme un visiteur.
  await p.click('#div_block-161-69 .multi-trigger-soumission'); await new Promise(r => setTimeout(r, 1500));
  const form = (await p.$$(sel)).find(async f => await f.evaluate(x => !!x.offsetParent)) ;
  const ok = await p.evaluate(s => { const f = [...document.querySelectorAll(s)].find(x => x.closest('#-off-canvas-167-69')); if (!f) return 'formulaire introuvable'; f.id ||= 'sx-qa-form'; return f.id; }, sel);
  console.log('formulaire', ok, '| reCAPTCHA rendu :', await p.evaluate(() => !!document.querySelector('iframe[src*="recaptcha"]')));
  const fid = '#' + ok;
  // Parcours réel du formulaire en 3 étapes : remplir les champs visibles, « Étape suivante », puis envoyer.
  const VALS = { datetime: '15/05/2027', input_mask: 'J1X5T5', 'names[first_name]': 'TEST', 'names[last_name]': 'QA Tactik',
    email: 'qa-test@example.com', phone: '8198436286', description: 'TEST QA Tactik Média (staging, mise en ligne du thème) — ne pas traiter.' };
  let nav = null;
  for (let step = 0; step < 4; step++) {
    for (let pass = 0; pass < 3; pass++) {
      await p.evaluate(fid => { for (const s of document.querySelectorAll(fid + ' select')) if (s.offsetParent && !s.value) { s.value = [...s.options].find(o => o.value)?.value; s.dispatchEvent(new Event('change', { bubbles: true })); } }, fid);
      await new Promise(r => setTimeout(r, 400));
    }
    for (const [n, v] of Object.entries(VALS)) {
      const el = await p.$(`${fid} [name="${n}"]`);
      if (!el || !(await el.evaluate(e => !!e.offsetParent && !e.value))) continue;
      if (n === 'datetime') { await el.evaluate(e => e._flatpickr ? e._flatpickr.setDate(new Date(2027, 4, 15), true) : (e.value = '15/05/2027')); continue; }
      await el.evaluate(e => e.scrollIntoView({ block: 'center' })); await el.click().catch(() => el.focus()); await el.type(v, { delay: 20 });
      await el.evaluate(e => e.dispatchEvent(new Event('blur', { bubbles: true })));
    }
    const next = await p.evaluateHandle(fid => [...document.querySelectorAll(fid + ' .ff-btn-next')].find(x => x.offsetParent) || null, fid);
    if (await next.evaluate(x => !!x)) { await next.evaluate(x => x.click()); await new Promise(r => setTimeout(r, 1200)); console.log('étape', step + 1, '→ suivante', await p.evaluate(fid => [...document.querySelectorAll(fid + ' .ff-el-is-error label')].map(l => l.textContent.trim()).join(', ') || 'ok', fid)); continue; }
    await p.evaluate(fid => document.querySelector(fid + ' [type=submit]').scrollIntoView({ block: 'center' }), fid);
    nav = p.waitForNavigation({ timeout: 60000 }).then(() => p.url()).catch(() => null);
    await p.evaluate(fid => document.querySelector(fid + ' [type=submit]').click(), fid);
    break;
  }
  const url = nav && await nav;
  await new Promise(r => setTimeout(r, 2000));
  const msgs = await p.evaluate(fid => [...document.querySelectorAll(fid + ' .error, ' + fid + ' .text-danger, .ff-message-success, .ff-errors-in-stack')].map(e => e.textContent.trim()).filter(Boolean), fid).catch(() => []);
  console.log('URL après envoi :', url || p.url());
  console.log('messages :', JSON.stringify(msgs));
  console.log('champs en erreur :', JSON.stringify(await p.evaluate(fid => [...document.querySelectorAll(fid + ' .ff-el-is-error')].map(g => [...g.querySelectorAll('input,select,textarea')].map(i => `${i.name}=${i.value}|vis:${!!i.offsetParent}|${i.className.slice(0, 40)}`).join(';')), fid).catch(() => [])));
  console.log('AJAX :', ajax.join(' || ') || '(aucun)');
  console.log('erreurs JS :', errs.slice(0, 3).join(' | ') || 'aucune');
  await b.close();
})();
