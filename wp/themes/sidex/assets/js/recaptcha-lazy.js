/* reCAPTCHA de Fluent Forms à la demande (inc/assets.php l'imprime en ligne à la place de google-recaptcha-js).
   L'API Google se charge quand un formulaire qui contient un reCAPTCHA approche de l'écran ou reçoit le focus,
   le doigt ou le clic ; sxRecaptchaReady demande alors à Fluent Forms de dessiner ses widgets. */
(function () {
	var src = document.currentScript && document.currentScript.getAttribute('data-src');
	var requested = false;

	window.sxRecaptchaReady = function () {
		if (!window.jQuery || typeof window.fluentFormApp !== 'function') {
			return;
		}
		jQuery('form.frm-fluent-form').each(function () {
			var form = jQuery(this);
			var app = form.find('.g-recaptcha').length ? window.fluentFormApp(form) : null;
			if (app && typeof app.reinitExtras === 'function') {
				app.reinitExtras();
			}
		});
	};

	function load() {
		if (requested || !src) {
			return;
		}
		requested = true;
		var s = document.createElement('script');
		s.src = src;
		s.async = true;
		document.head.appendChild(s);
	}

	function start() {
		var forms = [];
		document.querySelectorAll('.g-recaptcha').forEach(function (el) {
			forms.push(el.closest('form') || el);
		});
		if (!forms.length) {
			return;
		}
		['focusin', 'pointerdown', 'touchstart'].forEach(function (type) {
			document.addEventListener(type, function (e) {
				if (e.target.closest && forms.some(function (f) { return f.contains(e.target); })) {
					load();
				}
			}, { passive: true, capture: true });
		});
		if (!('IntersectionObserver' in window)) {
			load();
			return;
		}
		var io = new IntersectionObserver(function (entries) {
			if (entries.some(function (e) { return e.isIntersecting; })) {
				io.disconnect();
				load();
			}
		}, { rootMargin: '400px 0px' });
		forms.forEach(function (f) { io.observe(f); });
	}

	// Après l'initialisation de Fluent Forms (scripts différés, exécutés avant DOMContentLoaded).
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
