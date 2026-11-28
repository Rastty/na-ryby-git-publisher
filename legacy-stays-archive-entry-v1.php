<?php
/**
 * First-choice route out of the historic 2017 /ubytovani-2/ landing.
 *
 * Exact published WordPress page only, GET frontend only. No stored content,
 * redirects, affiliate substitutions or network requests. Old listings
 * remain byte-for-byte identical. Remove its require_once to roll back.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Legacy_Stays_Archive_Entry_V1 {
    const PAGE_URL = 'https://na-ryby.eu/ubytovani-2/';
    const SLUG = 'ubytovani-2';
    const H1 = 'Ubytování v ČR';
    const FIRST_RESULT = 'Rekreační dům Harvánek';
    const MARKER = 'data-nry-legacy-stays-entry="v1"';
    const GUIDE = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const SLAPY = 'https://na-ryby.eu/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/';
    const LIPNO = 'https://na-ryby.eu/rybareni-s-ubytovanim-jihocesky-kraj/';

    const VRANOV = 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/';
    const WELLNESS = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/';
    const PRIVATE_WATER = 'https://na-ryby.eu/nejlepsi-soukrome-reviry-s-ubytovanim-v-ceske-republice-zazitek-ktery-stoji-za-to/';

    private static function eligible() {
        foreach (array('is_page','is_admin','is_preview','home_url',
            'get_queried_object_id','get_post','get_permalink') as $name) {
            if (!function_exists($name)) return false;
        }
        if (!is_page(self::SLUG) || is_admin() || is_preview()
            || (function_exists('is_feed') && is_feed())
            || (function_exists('is_paged') && is_paged())
            || (function_exists('wp_doing_ajax') && wp_doing_ajax())
            || (function_exists('is_customize_preview') && is_customize_preview())
            || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return false;
        $id = (int) get_queried_object_id();
        if ($id <= 0 || get_permalink($id) !== self::PAGE_URL) return false;
        $post = get_post($id);
        return is_object($post)
            && (int)($post->ID ?? 0) === $id
            && ($post->post_type ?? '') === 'page'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG;
    }

    public static function block() {
        return '<section class="nry-legacy-stays-entry" '.self::MARKER
            .' aria-label="Vybrat rybářský pobyt">'
            .'<p class="nry-legacy-stays-entry__eyebrow">Rybářská dovolená</p>'
            .'<h2>Kam na ryby s ubytováním?</h2>'
            .'<p>Vyberte podle oblasti nebo typu výpravy. Každá karta vás zavede na konkrétní nabídku nebo rozhodovací průvodce, kde ověříte další podmínky.</p>'

            .'<div data-nry-stay-planner="v1" class="nry-stay-planner" hidden>'
            .'<h3>Naplánujte si vlastní rybářský pobyt</h3>'
            .'<p>Vyberte oblast a typ výpravy. Rozpočet vyplňte podle sebe; nepracujeme s neověřenými cenami ubytování.</p>'
            .'<div class="nry-stay-planner__fields">'
            .'<label>Oblast<select data-nry-plan="region"><option value="any">Zatím nerozhodnuto</option><option value="slapy">Slapy a střední Čechy</option><option value="lipno">Lipno a jižní Čechy</option><option value="vranov">Vranovská přehrada</option></select></label>'
            .'<label>Typ pobytu<select data-nry-plan="type"><option value="any">Libovolný</option><option value="family">Rodina</option><option value="group">Rybářská parta</option><option value="wellness">Wellness a pohodlí</option><option value="private">Soukromý revír</option></select></label>'
            .'<label>Počet osob<input data-nry-plan="people" type="number" inputmode="numeric" min="1" max="30" step="1" value="2"></label>'
            .'<label>Počet nocí<input data-nry-plan="nights" type="number" inputmode="numeric" min="1" max="60" step="1" value="2"></label>'
            .'<label>Celkový rozpočet (Kč)<input data-nry-plan="budget" type="number" inputmode="numeric" min="0" max="1000000" step="1" value="8000"></label>'
            .'<label>Doprava, lov, jídlo a další náklady (Kč)<input data-nry-plan="extras" type="number" inputmode="numeric" min="0" max="1000000" step="1" value="2000"></label></div>'
            .'<div class="nry-stay-planner__result" role="status" aria-live="polite" aria-atomic="true">'
            .'<p data-nry-plan-output="recommendation"></p><p data-nry-plan-output="total"></p><p data-nry-plan-output="perperson"></p></div>'
            .'<p class="nry-stay-planner__hint">Výsledek je váš limit, ne cena ani dostupnost konkrétní nabídky. Zvýrazněná karta je tip, kde začít; ostatní možnosti zůstávají dostupné.</p></div>'
            .'<nav class="nry-legacy-stays-entry__choices" aria-label="Možnosti výběru pobytu">'
            .'<a href="'.self::GUIDE.'"><strong>Porovnat rybářské pobyty</strong>'
            .'<span>Konkrétní nabídky a na co dát pozor</span></a>'
            .'<a href="'.self::SLAPY.'"><strong>Slapy a střední Čechy</strong>'
            .'<span>Pobyt u přehrady s možností výprav k vodě</span></a>'
            .'<a href="'.self::LIPNO.'"><strong>Lipno a jižní Čechy</strong>'
            .'<span>Porovnejte zázemí u Lipna a cestu k lovišti</span></a>'
            .'<a href="'.self::VRANOV.'"><strong>Chata pro partu u Vranova</strong>'
            .'<span>Štítary · konkrétní objekt; ověřte kapacitu a termín.</span></a>'
            .'<a href="'.self::WELLNESS.'"><strong>Wellness a rybaření</strong>'
            .'<span>Lančov · srub se saunou; ověřte stav vody a přístup.</span></a>'
            .'<a href="'.self::PRIVATE_WATER.'"><strong>Soukromý revír s ubytováním</strong>'
            .'<span>Nejprve vyberte provozovatele vody a ověřte lovné místo.</span></a>'
            .'</nav><p class="nry-legacy-stays-entry__note">'
            .'Ubytování samo o sobě nezahrnuje oprávnění rybařit. '
            .'Ceny, dostupnost a pravidla revíru ověřte u poskytovatele.</p>'
            .'</section>';
    }

    /** Insert only after one exact public H1, ahead of the historical results. */
    public static function place_after_title($html) {
        if (!is_string($html) || strlen($html) < 5000
            || strpos($html, self::MARKER) !== false) return $html;
        // Live WordPress title is visually correct but the raw H1 text is
        // entity/spacing encoded. Compare the *exact* decoded title, never a
        // fuzzy title fragment or an arbitrary H1 containing nested markup.
        $pattern = '~<h1\\b[^>]{0,500}>([\\s\\S]{0,2000}?)</h1>~iu';
        $matches = array();
        if (preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE) !== 1
            || !isset($matches[0][0][0], $matches[0][0][1], $matches[1][0][0])) return $html;
        $rawTitle = trim($matches[1][0][0]);
        // Exact live TagDiv page title on 21 Nov: a *single bare span* around
        // the approved heading. Permit this observed wrapper only; arbitrary
        // nested markup, attributes, links and script tags still fail closed.
        $spanTitle = array();
        if (preg_match('~\\A<span>([^<>]{1,500})</span>\\z~u', $rawTitle, $spanTitle) === 1) {
            $rawTitle = $spanTitle[1];
        }
        if (strpos($rawTitle, '<') !== false || strpos($rawTitle, '>') !== false) return $html;
        $decodedTitle = html_entity_decode($rawTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalTitle = preg_replace('~[\\s\\x{00A0}\\x{202F}]+~u', ' ', $decodedTitle);
        if (!is_string($normalTitle) || trim($normalTitle) !== self::H1) return $html;
        $heading = $matches[0][0][0];
        $titleEnd = $matches[0][0][1] + strlen($heading);
        $firstResult = strpos($html, self::FIRST_RESULT, $titleEnd);
        if ($firstResult === false || $firstResult <= $titleEnd) return $html;
        $block = self::block();
        if (substr_count($block, self::MARKER) !== 1) return $html;
        $changed = substr($html, 0, $titleEnd) . $block . substr($html, $titleEnd);
        if (substr_count($changed, self::MARKER) !== 1
            || strpos($changed, $block) !== $titleEnd
            || strpos($changed, self::FIRST_RESULT, $titleEnd) <= $titleEnd) return $html;
        return $changed;
    }

    public static function buffer() {
        if (self::eligible() && function_exists('ob_start')) {
            ob_start(array(__CLASS__, 'place_after_title'));
        }
    }

    public static function css() {
        if (self::eligible() && function_exists('wp_enqueue_style')
            && function_exists('plugins_url')) {
            wp_enqueue_style('nry-legacy-stays-entry-v1',
                plugins_url('assets/nry-legacy-stays-entry-v1.css', __FILE__),
                array(), '1.2.0');
        }
        // Existing exact-page eligibility also guards the independent read-only
        // click observer. The JS itself requires the unique live panel + 6 URLs.
        if (self::eligible() && function_exists('wp_enqueue_script')
            && function_exists('plugins_url')) {
            wp_enqueue_script('nry-archive-stay-click-v1',
                plugins_url('assets/nry-archive-stay-click-v1.js', __FILE__),
                array(), '1.2.0', true);
        }
    }

    public static function boot() {
        if (function_exists('add_action')) {
            add_action('template_redirect', array(__CLASS__, 'buffer'), 1);
            add_action('wp_enqueue_scripts', array(__CLASS__, 'css'), 25);
        }
    }
}
NRY_Legacy_Stays_Archive_Entry_V1::boot();
