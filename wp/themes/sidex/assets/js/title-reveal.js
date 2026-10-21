/* Révélation des titres (ex-Advanced Scripts 82 + 84 : GSAP SplitText + ScrollTrigger, ≈ 85 Ko), en JS natif.
   Même rendu : chaque ligne du titre (.title-reveal) monte de 100 % de sa hauteur en apparaissant, 0,75 s,
   lignes décalées de 0,25 s, quand le haut du titre entre dans l'écran. Le héros de l'accueil (titres fixes et
   1re diapositive) n'est pas animé : il est le LCP, le texte s'affiche avec le premier rendu. Même structure que SplitText :
   div.lineParent > div.lineChild par ligne. Rien ne bouge si l'utilisateur a demandé moins d'animations. */
(function () {
	if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		return;
	}
	var EASE = 'cubic-bezier(0.23, 1, 0.32, 1)'; // power4.out

	// Mots (texte) et éléments en ligne (gardés entiers) ; <br> force une nouvelle ligne.
	function tokens(el) {
		var out = [];
		Array.prototype.slice.call(el.childNodes).forEach(function (node) {
			if (node.nodeType === 3) {
				node.nodeValue.split(/\s+/).forEach(function (word) {
					if (word) {
						// inline-block, comme SplitText : un mot à trait d'union (Baie-Saint-Paul) ne se coupe pas à la mesure.
						var span = document.createElement('span');
						span.style.display = 'inline-block';
						span.textContent = word;
						span.sxWord = true;
						out.push(span);
					}
				});
			} else if (node.nodeType === 1) {
				out.push(node);
			}
		});
		return out;
	}

	function split(el) {
		var parts = tokens(el);
		el.textContent = '';
		parts.forEach(function (p, i) {
			if (i && p.nodeName !== 'BR' && parts[i - 1].nodeName !== 'BR') {
				el.appendChild(document.createTextNode(' '));
			}
			el.appendChild(p);
		});
		// Regroupement par ligne affichée (même logique que SplitText : position verticale de chaque mot).
		var lines = [], top = null;
		parts.forEach(function (p) {
			if (p.nodeName === 'BR') {
				top = null;
				return;
			}
			var y = p.offsetTop;
			if (top === null || Math.abs(y - top) > 2) {
				lines.push([]);
				top = y;
			}
			lines[lines.length - 1].push(p);
		});
		el.textContent = '';
		return lines.map(function (words) {
			var parent = document.createElement('div');
			var child = document.createElement('div');
			parent.className = 'lineParent';
			child.className = 'lineChild';
			parent.style.cssText = child.style.cssText = 'display:block;position:relative;text-align:inherit';
			words.forEach(function (w, i) {
				if (i) {
					child.appendChild(document.createTextNode(' '));
				}
				child.appendChild(w.sxWord ? document.createTextNode(w.textContent) : w);
			});
			parent.appendChild(child);
			el.appendChild(parent);
			child.style.opacity = '0';
			child.style.visibility = 'hidden';
			child.style.transform = 'translateY(100%)';
			return child;
		});
	}

	function play(lines) {
		lines.forEach(function (line, i) {
			line.style.transition = 'opacity .75s ' + EASE + ' ' + i * 0.25 + 's, transform .75s ' + EASE + ' ' + i * 0.25 + 's';
			line.style.visibility = 'visible';
			line.style.opacity = '1';
			line.style.transform = 'none';
		});
	}

	function init() {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting || e.boundingClientRect.top < 0) {
					io.unobserve(e.target);
					var lines = e.target.sxLines;
					requestAnimationFrame(function () {
						requestAnimationFrame(function () { play(lines); });
					});
				}
			});
		});
		document.querySelectorAll('.title-reveal').forEach(function (title) {
			if (title.closest('.slider-wrapper-content, .slide:first-of-type')) {
				return;
			}
			title.sxLines = split(title);
			io.observe(title);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
