/* Accessibilité (audit du 16 oct. 2026). */
(function () {
	/* Déclencheurs des panneaux (div/span role="button", inc/html.php) : Entrée et Espace font comme un clic. */
	document.addEventListener('keydown', function (e) {
		var el = e.target;
		if ((e.key === 'Enter' || e.key === ' ') && el.matches && el.matches('div[role="button"], span[role="button"]')) {
			e.preventDefault();
			el.click();
		}
	});

	/* Lien d'évitement : le défilement doux d'Oxygen (oxygen-inline.js) annule le saut d'ancre ; on donne le focus au contenu. */
	document.addEventListener('click', function (e) {
		var main = e.target.closest && e.target.closest('.sx-skip-link') && document.getElementById('main');
		if (main) {
			main.focus({ preventScroll: true });
		}
	});

	/* Carrousels Flickity : les cellules masquées (aria-hidden) ne doivent pas garder de liens atteignables au clavier. */
	var FOCUSABLE = 'a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])';
	function sync(cell) {
		var hidden = cell.getAttribute('aria-hidden') === 'true';
		cell.querySelectorAll(hidden ? FOCUSABLE : '[data-sx-tabindex]').forEach(function (el) {
			if (hidden) {
				el.setAttribute('data-sx-tabindex', el.getAttribute('tabindex') || '');
				el.setAttribute('tabindex', '-1');
			} else {
				var t = el.getAttribute('data-sx-tabindex');
				t ? el.setAttribute('tabindex', t) : el.removeAttribute('tabindex');
				el.removeAttribute('data-sx-tabindex');
			}
		});
	}
	new MutationObserver(function (records) {
		records.forEach(function (r) {
			if (r.target.parentNode && r.target.parentNode.classList && r.target.parentNode.classList.contains('flickity-slider')) {
				sync(r.target);
			}
		});
	}).observe(document.documentElement, { subtree: true, attributes: true, attributeFilter: ['aria-hidden'] });
	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.flickity-slider > [aria-hidden]').forEach(sync);
	});

	/* Onglets Skeletabs (pages GEO) : sous 992 px ils deviennent un accordéon mais gardent role="tab" sans tablist,
	   et tabindex="-1" sur les sections fermées (inatteignables au clavier). En mode accordéon : boutons ordinaires
	   avec aria-expanded, panneaux en region ; en mode onglets : rôles d'origine. L'état vient de la classe
	   oxy-dynamic-tabs_active (Skeletabs ne relit jamais les attributs ARIA). */
	function tabs(inner) {
		var accordion = inner.classList.contains('oxy-dynamic-tabs_mode-accordion');
		inner.querySelectorAll('.oxy-dynamic-tabs_tab').forEach(function (tab) {
			var panel = document.getElementById(tab.getAttribute('aria-controls'));
			var open = tab.classList.contains('oxy-dynamic-tabs_active');
			if (accordion) {
				tab.removeAttribute('role');
				tab.removeAttribute('aria-selected');
				tab.setAttribute('aria-expanded', open ? 'true' : 'false');
				tab.setAttribute('tabindex', '0');
				panel && panel.setAttribute('role', 'region');
			} else if (tab.getAttribute('role') !== 'tab') {
				tab.setAttribute('role', 'tab');
				tab.setAttribute('aria-selected', open ? 'true' : 'false');
				tab.setAttribute('tabindex', open ? '0' : '-1');
				tab.removeAttribute('aria-expanded');
				panel && panel.setAttribute('role', 'tabpanel');
			}
		});
	}
	new MutationObserver(function (records) {
		records.forEach(function (r) {
			var inner = r.target.closest && r.target.closest('.oxy-dynamic-tabs_inner');
			inner && tabs(inner);
		});
	}).observe(document.documentElement, { subtree: true, attributes: true, attributeFilter: ['class', 'aria-selected'] });
})();
