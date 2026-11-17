<?php
/**
 * Source-bound, render-only buying help and factual corrections on the line Bible.
 * No WordPress content, metadata, affiliate destination or link-target writes.
 * Rollback: remove this require from na-ryby-git-publisher.php.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Line_Bible_Fast_Choice_115678_V1 {
    const PAGE_ID = 115678;
    const SLUG = 'vlascova-bible-kompletni-pruvodce-vyberem-vlasce-a-snury';
    const MODIFIED = '2026-03-05 17:50:34';
    const MARKER = 'data-nry-line-fast-choice="v1"';
    const PARTNER = 'https://www.jdoqocy.com/click-8287231-12347575?url=';
    const WINDING = 'https://na-ryby.eu/jak-spravne-podmotat-navijak-setrete-snuru-a-zvyste-dosah-nahozu/';

    public static function boot() {
        if (function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), 76);
        }
        if (function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 29);
        }
    }

    private static function eligible($in_loop = true) {
        if (!function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::PAGE_ID
            || !function_exists('is_page') || !is_page(self::PAGE_ID)
            || !function_exists('is_singular') || !is_singular('page')
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || ($in_loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink')
            || get_permalink(self::PAGE_ID) !== 'https://na-ryby.eu/'.self::SLUG.'/'
            || !function_exists('get_post')) return false;
        $p = get_post(self::PAGE_ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::PAGE_ID
            && ($p->post_type ?? '') === 'page'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED;
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-line-fast-choice-v1',
            plugins_url('assets/nry-line-fast-choice-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $m) === false) return null;
        return $m[2];
    }

    public static function original_affiliates() {
        return array(
            self::PARTNER.'https%3A%2F%2Fwww.chytapust.cz%2FVlasce-snury%2FShimano-Vlasec-Technium-Tribal-1100m',
            self::PARTNER.'https%3A%2F%2Fwww.chytapust.cz%2FKatran-Vlasec-Synapse-Eclipse-1000m',
            self::PARTNER.'https%3A%2F%2Fwww.chytapust.cz%2FVlasce-snury%2FTrabucco-Vlasec-T-Force-XPS-Feeder-Plus-250m',
            self::PARTNER.'https%3A%2F%2Fwww.chytapust.cz%2FVlasce-snury%2F',
        );
    }

    public static function replacements() {
        return array(
            '<h2>1. Monofil vs. Pletená šňůra: Kdy použít co?</h2>'
                => '<h2 id="nry-line-materials">1. Monofil vs. Pletená šňůra: Kdy použít co?</h2>',
            '<h2>2. Petrovy tipy na top vlasce 2026</h2>'
                => '<h2 id="nry-line-products">2. Příklady vlasců podle použití – ověřte variantu a dostupnost</h2>',
            '<td>Nulová &#8211; okamžitý kontakt</td>'
                => '<td>Velmi malá průtažnost – citlivější kontakt; záleží na konkrétní šňůře</td>',
            '<td>1-2 sezóny (degraduje UV zářením)</td>'
                => '<td>Kontrolujte oděr, paměť a pevnost; při poškození vyměňte</td>',
            '<td>3-5 sezón (velmi trvanlivá)</td>'
                => '<td>Životnost záleží na oděru, použití a skladování; kontrolujte pravidelně</td>',
            '<td>Nízká (skoro neviditelný)</td>'
                => '<td>Viditelnost záleží na barvě, průměru a podmínkách; není neviditelný</td>',
            '<td>Vysoká (neprůhledná)</td>'
                => '<td>Viditelnost záleží na barvě, průměru a světle; porovnejte konkrétní model</td>',
            '<div class="product-price-tag">899 Kč</div>'
                => '<div class="product-price-tag">Cenu ověřte v obchodě</div>',
            '<div class="product-price-tag">649 Kč</div>'
                => '<div class="product-price-tag">Cenu ověřte v obchodě</div>',
            '<div class="product-price-tag">249 Kč</div>'
                => '<div class="product-price-tag">Cenu ověřte v obchodě</div>',
            'Fluo barva pro viditelnost v noci, ale neviditelný u dna.'
                => 'Před výběrem zkontrolujte barvu konkrétní varianty a její viditelnost v podmínkách lovu.',
            '<p>Nejširší výběr vlasců a šňůr s dárky zdarma najdete u našeho partnera.</p>'
                => '<p>U partnera porovnejte aktuální nabídku, délku návinu, průměr a dostupnost.</p>',
        );
    }

    private static function choice() {
        return '<section class="nry-line-fast-choice" '.self::MARKER
            .' aria-label="Rychlá volba vlasce a šňůry">'
            .'<p class="nry-line-fast-choice__eyebrow">Praktický výběr za půl minuty</p>'
            .'<h2>Jaký vlasec nebo šňůru zvolit pro svůj způsob lovu?</h2>'
            .'<div class="nry-line-fast-choice__grid">'
            .'<div><strong>Kapři, běžný odhoz</strong><p>Začněte kvalitním monofilem. Průměr vybírejte podle vzdálenosti, překážek a celého systému včetně šokového návazce.</p></div>'
            .'<div><strong>Feeder</strong><p>Monofil pomáhá tlumit výpady; šňůra může zvýšit citlivost na dálku. Seřiďte brzdu a zohledněte tuhost prutu.</p></div>'
            .'<div><strong>Přívlač</strong><p>Pletená šňůra nabídne citlivost; podle vody a překážek zvažte vhodný koncový návazec. Zkontrolujte uzel i brzdu.</p></div>'
            .'<div><strong>Plavaná a krátká vzdálenost</strong><p>Monofil je často jednoduchá a odpouštějící volba. U jemné sestavy rozhoduje i nosnost návazce.</p></div>'
            .'</div>'
            .'<p class="nry-line-fast-choice__warning"><strong>Pozor na překážky:</strong> samotný nápis „pletenka“ nezaručuje odolnost proti kamenům a škeblím. Kontrolujte poslední metry, uzly a místní podmínky.</p>'
            .'<nav aria-label="Další kroky výběru"><a href="#nry-line-materials">Porovnat materiály</a>'
            .' <a href="#nry-line-products">Zobrazit příklady produktů</a>'
            .' <a href="'.self::WINDING.'">Jak správně navinout šňůru</a></nav>'
            .'<p class="nry-line-fast-choice__disclosure">Níže uvedené produkty jsou příklady, nikoli doložené pořadí z nezávislého testu. Před nákupem ověřte průměr, návin, cenu a skladovost.</p>'
            .'</section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 12000
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::WINDING) !== false
            || substr_count($html, self::PARTNER) !== 4
            || substr_count($html, 'KOUPIT NA CHYTAPUST.CZ') !== 3
            || substr_count($html, '<div class="ryby-bible-container">') !== 1) return $html;
        $before=self::hrefs($html);
        if ($before === null) return $html;
        foreach (self::original_affiliates() as $href) {
            if (substr_count($html, 'href="'.$href.'"') !== 1) return $html;
        }
        $next=$html;
        foreach (self::replacements() as $old=>$new) {
            if (substr_count($next, $old) !== 1) return $html;
            $next=str_replace($old, $new, $next);
        }
        $next=str_replace('KOUPIT NA CHYTAPUST.CZ', 'OVĚŘIT VARIANTU A DOSTUPNOST', $next);
        $at='<h2 id="nry-line-materials">1. Monofil vs. Pletená šňůra: Kdy použít co?</h2>';
        if (substr_count($next, $at) !== 1) return $html;
        $next=str_replace($at, self::choice()."\n".$at, $next);
        $after=self::hrefs($next);
        if ($after === null || count($after) !== count($before)+3) return $html;
        $prior=array_values(array_filter($after, function($href) {
            return !in_array($href, array(self::WINDING, '#nry-line-materials', '#nry-line-products'), true);
        }));
        return $prior === $before && substr_count($next, self::MARKER) === 1 ? $next : $html;
    }

    public static function render($content) {
        return self::eligible() ? self::transform($content) : $content;
    }
}
NRY_Line_Bible_Fast_Choice_115678_V1::boot();
