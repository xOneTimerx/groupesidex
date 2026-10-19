/* Scripts front d'Advanced Scripts (extension désactivée), repris tels quels. jQuery requis. Révélation des titres (82 + 84) : js/title-reveal.js. */

/* ── Advanced Scripts 30 Initialisation de la page ── */
const btnSticky = document.querySelector(".btn-sticky");

// toggle 'scroll to top' based on scroll position
window.addEventListener('scroll', e => {
  //btnSticky.style.display = window.scrollY > 75 ? 'block' : 'none';
  btnSticky.style.display = 'block';
});

/* ── Advanced Scripts 269 Sticky CTA on scroll ── */
// 1. Cache the element once — not on every scroll
const cta = document.querySelector('.btn-sticky');

if (cta) {
  let ticking = false;

  // 2. Use requestAnimationFrame to throttle (replaces debounce/throttle libs)
  window.addEventListener('scroll', function () {
    if (!ticking) {
      requestAnimationFrame(function () {
        // 3. toggle() replaces the if/else
        cta.classList.toggle('is-visible', window.scrollY > 300);
        ticking = false;
      });
      ticking = true;
    }
  // 4. passive: true tells the browser this won't call preventDefault(),
  //    allowing it to optimize scrolling independently
  }, { passive: true });
}

/* ── Advanced Scripts 141 Projets - Tableau (préremplissage de la soumission) ── */
jQuery(".multi-trigger-soumission").click(function() {
    if(jQuery(this).data("essence")) {
        jQuery("[data-name='form_essence']").val(jQuery(this).data("essence"));
        jQuery("[data-name='form_essence_interieur']").val(jQuery(this).data("essence"));
        jQuery("[data-name='form_essence_exterieur']").val(jQuery(this).data("essence"));
    }
    if(jQuery(this).data("modele")) {
        jQuery("[data-name='form_modele']").val(jQuery(this).data("modele"));
        jQuery("[data-name='form_modele_interieur']").val(jQuery(this).data("modele"));
        jQuery("[data-name='form_modele_exterieur']").val(jQuery(this).data("modele"));
    }
});

/* ── Advanced Scripts 144 Glightbox - JS ── */
document.addEventListener("DOMContentLoaded", function loadGlightbox() {
	// GLightbox n'est chargé que sur les gabarits qui ont des nuanciers (inc/oxy-libs.php).
	if (typeof GLightbox === "undefined") return;
	const lightboxProduits = GLightbox({
	    selector: '.glightbox-couleurs',
		moreLength: 0,
	});
	console.log(lightboxProduits);
});

/* ── Advanced Scripts 245 Populer champ Poste Recherché ── */
function updatePoste(typeEmploi) {
	var champEmploi = document.getElementsByName("type_emploi")[0];
	var btnPoste = typeEmploi;
	champEmploi.value = btnPoste.dataset.emploi;
}
