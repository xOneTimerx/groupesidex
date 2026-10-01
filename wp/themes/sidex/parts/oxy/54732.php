<?php
/**
 * Gabarit « !Page - Développement durable » (ex-Oxygen 54732), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<?php /* En-tête */ ?><section id="section-2-54732" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Wrapper */ ?><div id="div_block-3-54732" class="ct-div-block c-columns-3-1 c-columns-m-1 c-columns-gap-l"><?php /* Content */ ?><div id="div_block-4-54732" class="ct-div-block c-max-width-960 c-full-width c-margin-bottom-l"><h1 id="code_block-5-54732" class="ct-code-block c-tagline c-text-light c-margin-bottom-l"><?php echo get_entete_field("entete_sous_titre"); ?></h1><h2 id="code_block-6-54732" class="ct-code-block c-heading-light title-reveal c-h1 c-margin-bottom-m"><?php echo get_entete_field("entete_titre"); ?></h2><div id="code_block-7-54732" class="ct-code-block c-text-light c-margin-bottom-l"><?php echo get_entete_field("entete_contenu"); ?></div><a id="link_button-12-54732" class="ct-link-button c-btn-main c-btn-l c-transition" href="<?php echo esc_attr(sx_fn('get_field', 'entete_btn_lien')); ?>" target="_self" role="button"><span id="span-13-54732" class="ct-span"><?php echo sx_kses(sx_fn('get_field', 'entete_btn_texte')); ?></span></a></div></div><div id="div_block-10-54732" class="ct-div-block"><img id="image-11-54732" alt="bounce-logo" src="/wp-content/uploads/logo-sidex-blanc-bounce.svg" class="ct-image bounce2"/></div></div></section><?php /* Bloc - GES */ ?><section id="section-15-54732" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Wrapper */ ?><div id="div_block-16-54732" class="ct-div-block c-columns-3 c-columns-m-1 c-columns-gap-xxl"><?php /* Bloc 1 */ ?><div id="div_block-17-54732" class="ct-div-block c-center c-owl-m"><img id="image-91-54732" alt="feuille verte" src="/wp-content/uploads/feuille-verte.svg" class="ct-image"/><div id="div_block-82-54732" class="ct-div-block"><div id="code_block-83-54732" class="ct-code-block c-h1"><div class="sidex-co2-depuis-2006"></div>

<script>
document.addEventListener("DOMContentLoaded", function () {

  const valeurInitiale = 55354;
  const tauxParMinute = 0.006508;
  const dateReference = new Date("2025-12-31T23:59:00");

  function calculerValeur() {

    const maintenant = new Date();
    const diffMinutes = (maintenant - dateReference) / 60000;

    return valeurInitiale + (diffMinutes * tauxParMinute);

  }

  function updateCounter() {

    const valeur = calculerValeur();

    const texte = valeur
      .toFixed(5)
      .replace(".", ",");

    document
      .querySelectorAll(".sidex-co2-depuis-2006")
      .forEach(el => {

        el.textContent = texte;

      });

  }

  updateCounter();

  setInterval(updateCounter, 1000);

});
</script></div></div><div id="code_block-20-54732" class="ct-code-block"><?= the_field("contenu_bloc_1"); ?></div><?php /* auto */ ?><div id="div_block-110-54732" class="ct-div-block"><div id="code_block-111-54732" class="ct-code-block">
<div class="sidex-voitures-depuis-2006"></div>

<script>
document.addEventListener("DOMContentLoaded", function () {

  const tonnesInitiales = 55354;
  const tauxTonnesParMinute = 0.006508;
  const voituresParTonne = 13224 / 58775;
  const dateReference = new Date("2025-12-31T23:59:00");

  function calculerTonnes() {
    const maintenant = new Date();
    const diffMinutes = (maintenant - dateReference) / 60000;
    return tonnesInitiales + (diffMinutes * tauxTonnesParMinute);
  }

  function updateCounter() {
    const voitures = calculerTonnes() * voituresParTonne;

    document.querySelectorAll(".sidex-voitures-depuis-2006").forEach(el => {
      el.textContent = voitures.toFixed(5).replace(".", ",");
    });
  }

  updateCounter();
  setInterval(updateCounter, 1000);

});
</script></div></div></div><?php /* Bloc 2 */ ?><div id="div_block-27-54732" class="ct-div-block c-center c-owl-m"><img id="image-93-54732" alt="feuille verte" src="/wp-content/uploads/feuille-verte.svg" class="ct-image"/><div id="div_block-85-54732" class="ct-div-block"><div id="code_block-86-54732" class="ct-code-block c-h1"><div id="sidex-co2-cette-annee">0</div>

<script>
(function(){

  const tauxParMinute = 0.006508;
  const dateReference = new Date("2026-01-01T00:00:00");

  function calculerValeur() {
    const maintenant = new Date();
    const diffMinutes = (maintenant - dateReference) / 60000;
    return diffMinutes * tauxParMinute;
  }

  function updateCounter() {
    document.getElementById("sidex-co2-cette-annee").textContent =
      calculerValeur().toLocaleString("fr-CA", {
        minimumFractionDigits: 5,
        maximumFractionDigits: 5
      });
  }

  setInterval(updateCounter, 1000);
  updateCounter();

})();
</script></div></div><div id="code_block-28-54732" class="ct-code-block"><?= the_field("contenu_bloc_2"); ?></div><?php /* auto */ ?><div id="div_block-102-54732" class="ct-div-block"><div id="code_block-105-54732" class="ct-code-block">
<div class="sidex-voitures-depuis-2006"></div>

<script>
document.addEventListener("DOMContentLoaded", function () {

  const tonnesInitiales = 55354;
  const tauxTonnesParMinute = 0.006508;
  const voituresParTonne = 13224 / 58775;
  const dateReference = new Date("2025-12-31T23:59:00");

  function calculerTonnes() {
    const maintenant = new Date();
    const diffMinutes = (maintenant - dateReference) / 60000;
    return tonnesInitiales + (diffMinutes * tauxTonnesParMinute);
  }

  function updateCounter() {
    const voitures = calculerTonnes() * voituresParTonne;

    document.querySelectorAll(".sidex-voitures-depuis-2006").forEach(el => {
      el.textContent = voitures.toFixed(5).replace(".", ",");
    });
  }

  updateCounter();
  setInterval(updateCounter, 1000);

});
</script></div></div></div><?php /* Bloc 3 */ ?><div id="div_block-29-54732" class="ct-div-block c-center c-owl-m"><img id="image-95-54732" alt="feuille verte" src="/wp-content/uploads/feuille-verte.svg" class="ct-image"/><div id="div_block-88-54732" class="ct-div-block"><div id="code_block-89-54732" class="ct-code-block c-h1"><div id="sidex-co2-cette-semaine">0</div>

<script>
(function(){

  const tauxParMinute = 0.006508;

  function getDebutSemaine() {
    const maintenant = new Date();
    const jour = maintenant.getDay();
    const difference = jour === 0 ? 6 : jour - 1;

    const debutSemaine = new Date(maintenant);
    debutSemaine.setDate(maintenant.getDate() - difference);
    debutSemaine.setHours(0, 0, 0, 0);

    return debutSemaine;
  }

  function calculerValeur() {
    const maintenant = new Date();
    const debutSemaine = getDebutSemaine();
    const diffMinutes = (maintenant - debutSemaine) / 60000;

    return diffMinutes * tauxParMinute;
  }

  function updateCounter() {
    document.getElementById("sidex-co2-cette-semaine").textContent =
      calculerValeur().toLocaleString("fr-CA", {
        minimumFractionDigits: 5,
        maximumFractionDigits: 5
      });
  }

  setInterval(updateCounter, 1000);
  updateCounter();

})();
</script></div></div><div id="code_block-30-54732" class="ct-code-block"><?= the_field("contenu_bloc_3"); ?></div><?php /* auto */ ?><div id="div_block-113-54732" class="ct-div-block"><div id="code_block-114-54732" class="ct-code-block">
<div class="sidex-voitures-depuis-2006"></div>

<script>
document.addEventListener("DOMContentLoaded", function () {

  const tonnesInitiales = 55354;
  const tauxTonnesParMinute = 0.006508;
  const voituresParTonne = 13224 / 58775;
  const dateReference = new Date("2025-12-31T23:59:00");

  function calculerTonnes() {
    const maintenant = new Date();
    const diffMinutes = (maintenant - dateReference) / 60000;
    return tonnesInitiales + (diffMinutes * tauxTonnesParMinute);
  }

  function updateCounter() {
    const voitures = calculerTonnes() * voituresParTonne;

    document.querySelectorAll(".sidex-voitures-depuis-2006").forEach(el => {
      el.textContent = voitures.toFixed(5).replace(".", ",");
    });
  }

  updateCounter();
  setInterval(updateCounter, 1000);

});
</script></div></div></div></div></div></section><?php /* Bloc - CO2 */ ?><section id="section-21-54732" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Wrapper */ ?><div id="div_block-22-54732" class="ct-div-block c-columns-3 c-columns-m-1 c-columns-gap-xxl"><h4 id="code_block-23-54732" class="ct-code-block c-h3 c-heading-dark title-reveal"><?php the_field("titre_bloc_co2"); ?></h4><?php /* Bloc 1 */ ?><div id="div_block-24-54732" class="ct-div-block c-bg-light c-padding-l c-owl-m" data-aos="fade-up" data-aos-once="true"><?php /* Img */ ?><div id="div_block-26-54732" class="ct-div-block img-rounded"></div><div id="code_block-31-54732" class="ct-code-block c-bold"><?= the_field("bloc_co2_contenu_bloc_1"); ?></div></div><?php /* Bloc 2 */ ?><div id="div_block-32-54732" class="ct-div-block c-bg-light c-padding-l c-owl-m" data-aos="fade-up" data-aos-once="true"><?php /* Img */ ?><div id="div_block-33-54732" class="ct-div-block img-rounded"></div><div id="code_block-34-54732" class="ct-code-block c-bold"><?= the_field("bloc_co2_contenu_bloc_2"); ?></div></div></div></div></section><?php /* Bloc - Mesurez votre impact */ ?><section id="section-35-54732" class="ct-section c-owl-l"><div class="ct-section-inner-wrap"><div id="code_block-36-54732" class="ct-code-block"><script>
document.addEventListener("DOMContentLoaded", function () {

  const TONNE_CO2_PAR_M2 = 0.0283;
  const PI2_VERS_M2 = 0.092903;

  const input = document.getElementById("sidex-pi2");
  const resultat = document.getElementById("sidex-resultat");

  if (!input || !resultat) return;

  function calculer() {
    const pi2 = parseFloat(input.value.replace(",", "."));

    if (!pi2 || pi2 <= 0) {
      resultat.textContent = "Total";
      return;
    }

    const tonnesCO2 = pi2 * PI2_VERS_M2 * TONNE_CO2_PAR_M2;

    resultat.textContent = tonnesCO2.toLocaleString("fr-CA", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  input.addEventListener("input", calculer);
  calculer();

});
</script></div><?php /* Rangée */ ?><div id="div_block-47-54732" class="ct-div-block c-center c-full-width c-center-self c-owl-m c-padding-m c-max-width-960"><h2 id="code_block-48-54732" class="ct-code-block c-tagline c-text-light"><?php the_field("bloc_mesurez_impact_sous_titre"); ?></h2><h3 id="code_block-49-54732" class="ct-code-block c-h3 title-reveal c-heading-light"><?php the_field("bloc_mesurez_impact_titre"); ?></h3></div><?php /* Wrapper */ ?><div id="div_block-44-54732" class="ct-div-block c-max-width-960 c-center-self c-inline c-columns-gap-l"><?php /* En pieds */ ?><div id="div_block-37-54732" class="ct-div-block sidex-calc-input c-owl-s"><div id="code_block-38-54732" class="ct-code-block"><div class="sidex-calc-wrap">

  <input
    type="text"
    id="sidex-pi2"
    class="sidex-input"
    placeholder="0"
  >

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

  const TONNE_CO2_PAR_M2 = 0.0283;
  const PI2_VERS_M2 = 0.092903;

  const input =
    document.getElementById("sidex-pi2");

  const resultat =
    document.getElementById("sidex-resultat");

  function calculer() {

    const pi2 =
      parseFloat(input.value);

    if (!pi2 || pi2 <= 0) {

      resultat.textContent =
        "Total";

      return;

    }

    const tonnesCO2 =
      pi2 *
      PI2_VERS_M2 *
      TONNE_CO2_PAR_M2;

    resultat.textContent =
      tonnesCO2.toLocaleString(
        "fr-CA",
        {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        }
      );

  }

  input.addEventListener(
    "input",
    calculer
  );

});
</script></div><div id="text_block-39-54732" class="ct-text-block c-text-light"><span id="span-55-54732" class="ct-span"><?php echo sx_kses(sx_fn('get_field', 'libelle_valeur_en_pieds')); ?></span></div></div><?php /* Impact réel */ ?><div id="div_block-41-54732" class="ct-div-block c-owl-s"><div id="sidex-resultat" class="ct-div-block c-padding-xs"></div><div id="text_block-43-54732" class="ct-text-block c-text-light"><span id="span-57-54732" class="ct-span"><?php echo sx_kses(sx_fn('get_field', 'libelle_impact_reel')); ?></span></div></div></div><a id="link_button-52-54732" class="ct-link-button c-btn-main c-btn-l c-transition c-center-self" href="<?php echo esc_attr(sx_fn('get_field', 'bloc_mesurez_impact_btn_lien')); ?>" target="_self" role="button"><span id="span-53-54732" class="ct-span"><?php echo sx_kses(sx_fn('get_field', 'bloc_mesurez_impact_btn_texte')); ?></span></a></div></section><?php /* Blocs inversés */ ?><section id="section-59-54732" class="ct-section"><div class="ct-section-inner-wrap"><div id="_dynamic_list-60-54732" class="oxy-dynamic-list repeater-alternate-xl c-owl-xxl"><?php $sx_i1 = 0; if (have_rows('field_6a286a0f7d3fb', sx_row_owner('field_6a286a0f7d3fb'))) : while (have_rows('field_6a286a0f7d3fb', sx_row_owner('field_6a286a0f7d3fb'))) : the_row(); $sx_i1++; ?><div id="div_block-61-54732-<?php echo (int) $sx_i1; ?>" data-id="div_block-61-54732" class="ct-div-block c-columns-2 c-columns-m-1 c-columns-gap-xl c-bg-light c-padding-xl c-inline"><?php /* Content */ ?><div id="div_block-63-54732-<?php echo (int) $sx_i1; ?>" data-id="div_block-63-54732" class="ct-div-block c-owl-l"><h3 id="code_block-65-54732-<?php echo (int) $sx_i1; ?>" data-id="code_block-65-54732" class="ct-code-block c-tagline"><?= the_sub_field("sous_titre"); ?></h3><h4 id="code_block-67-54732-<?php echo (int) $sx_i1; ?>" data-id="code_block-67-54732" class="ct-code-block c-h3 c-heading-dark title-reveal"><?= the_sub_field("titre"); ?></h4><div id="code_block-70-54732-<?php echo (int) $sx_i1; ?>" data-id="code_block-70-54732" class="ct-code-block"><?= the_sub_field("contenu"); ?></div></div><img id="image-101-54732-<?php echo (int) $sx_i1; ?>" data-id="image-101-54732" alt="Durabilite Sidex" src="<?php echo esc_url(sx_sub('image')); ?>" class="ct-image"/></div><?php endwhile; endif; ?></div></div></section><?php /* = CTA */ ?><?php sx_oxy_part(149); ?>
