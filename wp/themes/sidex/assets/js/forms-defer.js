/* Fluent Forms à la demande (inc/forms-defer.php l'imprime en ligne avec window.sxFormsDefer = { groupe: [URL, …] }).
   Les bibliothèques se chargent quand un formulaire approche de l'écran (≈ 400 px) ou reçoit le focus, le doigt ou
   le clic, puis sont branchées par les chemins de Fluent Forms (reinitExtras, reInitExtras, fluentform_reset). */
(function () {
	var groups = window.sxFormsDefer || {};
	var requested = false;

	function forEachForm(fn) {
		if (window.jQuery) {
			jQuery('form.frm-fluent-form').each(function () {
				fn(jQuery(this));
			});
		}
	}

	// reCAPTCHA v2 : appelé par l'API Google (onload=sxFormsRecaptchaReady) ; Fluent Forms dessine ses widgets.
	window.sxFormsRecaptchaReady = function () {
		if (typeof window.fluentFormApp !== 'function') {
			return;
		}
		forEachForm(function (form) {
			var app = form.find('.g-recaptcha').length ? window.fluentFormApp(form) : null;
			if (app && typeof app.reinitExtras === 'function') {
				app.reinitExtras();
			}
		});
	};

	var ready = {
		// Script en ligne de chaque champ date (DateTime.php) : initPicker() sur « reInitExtras ».
		flatpickr: function () {
			forEachForm(function (form) {
				if (form.find('.ff-el-datepicker').length) {
					form.trigger('reInitExtras');
				}
			});
		},
		// Même réglage que initMask() de form-submission.js, qui a tourné avant le chargement de jquery.mask.
		mask: function () {
			var vars = (window.fluentFormVars && window.fluentFormVars.input_mask_vars) || {};
			if (!window.jQuery || !jQuery.fn.mask) {
				return;
			}
			jQuery('input[data-mask]').each(function () {
				var el = jQuery(this);
				var opts = {
					clearIfNotMatch: vars.clearIfNotMatch,
					translation: {
						'*': { pattern: /[0-9a-zA-Z]/ },
						0: { pattern: /\d/ },
						9: { pattern: /\d/, optional: true },
						'#': { pattern: /\d/, recursive: true },
						A: { pattern: /[a-zA-Z0-9]/ },
						S: { pattern: /[a-zA-Z]/ }
					}
				};
				if (el.attr('data-mask-reverse')) {
					opts.reverse = true;
				}
				if (el.attr('data-clear-if-not-match')) {
					opts.clearIfNotMatch = true;
				}
				if (el.attr('data-mask')) {
					el.mask(el.attr('data-mask'), opts);
				}
			});
		},
		// fluentform-advanced.js relance l'initialisation des champs fichier sur « fluentform_reset » (comme après un envoi).
		upload: function () {
			var pending = window.jQuery && jQuery('form.frm-fluent-form input[type="file"]').filter(function () {
				return !jQuery(this).data('blueimp-fileupload');
			});
			if (pending && pending.length) {
				jQuery(document.body).trigger('fluentform_reset');
			}
		}
	};

	// Les scripts d'un groupe s'exécutent dans l'ordre (dépendances de jQuery File Upload).
	function loadGroup(urls, done) {
		var i = 0;
		(function next() {
			if (i >= urls.length) {
				return done && done();
			}
			var s = document.createElement('script');
			s.src = urls[i++];
			s.async = false;
			s.onload = next;
			s.onerror = function () {
				if (window.console) {
					console.error('sidex: échec du chargement de ' + s.src);
				}
			};
			document.body.appendChild(s);
		})();
	}

	function load() {
		if (requested) {
			return;
		}
		requested = true;
		Object.keys(groups).forEach(function (name) {
			loadGroup(groups[name], ready[name] && function () {
				// Après l'initialisation de Fluent Forms et des scripts en ligne des champs (jQuery ready).
				jQuery(ready[name]);
			});
		});
	}

	function start() {
		var forms = Array.prototype.slice.call(document.querySelectorAll('form.frm-fluent-form'));
		if (!forms.length) {
			return;
		}
		['focusin', 'pointerdown', 'touchstart'].forEach(function (type) {
			document.addEventListener(type, function (e) {
				if (forms.some(function (f) { return f.contains(e.target); })) {
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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
