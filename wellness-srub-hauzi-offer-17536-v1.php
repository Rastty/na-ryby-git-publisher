<?php
/**
 * Exact-source-armed same-property Hauzi offer for wellness srub #17536.
 * Prevents outdated Agoda promises and unverified bait-boat/fishing claims.
 * No stored WP writes, merchant requests, or fabricated clicks.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Wellness_Srub_Hauzi_Offer_17536_V1 {
    const ID = 17536;
    const SLUG = 'chata-rybarsky-wellness-srub';
    const URL = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/';
    const MODIFIED_GMT = '2023-04-11 13:48:48';
    // Raw WP source is independently proven: #45004031871, 3030 bytes, exact SHA.
    const VERIFIED_RAW_SHA256 = '0f31f6da4e9a4f0a2330226b99ba9054136d1085005f39d303502d34655d5137';
    const MARKER = 'data-nry-wellness-srub-offer="v1"';
    const HAUZI_LISTING = 'https://www.hauzi.cz/rybarsky-srub';
    const HAUZI_AFFILIATE = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Frybarsky-srub';
    const OWNER_LISTING = 'https://www.e-chalupy.cz/lancov-vranovska-prehrada-ubytovani-pronajmuti-rybarsky-srub-o922';
    const INTRO = '<p><strong>Chata Rybářský wellness srub &#8211; Vranovská přehrada</strong>';
    const LEGACY_START = '<div class="agoda-booking-box" style="background:';
    const LEGACY_END = "* Garantovaná nejnižší cena a možnost zrušení zdarma u většiny objektů.</div>\n</div>\n";
    const OLD_BOAT = 'Pro rybáře loď na zavážení nástrah a návnad(stále prokrmovaná místa).';
    const OLD_AGODA = 'anrdoezrs.net/click-8287231-15735403';
    const OLD_CTA = 'Zobrazit nabídky na nejlepší ubytování';
    const OLD_CLAIM = 'Garantovaná nejnižší cena';

    public static function armed() {
        return strlen(self::VERIFIED_RAW_SHA256) === 64
            && (bool)preg_match('/^[a-f0-9]{64}$/D', self::VERIFIED_RAW_SHA256);
    }

    public static function boot() {
        if (!self::armed()) return; // Any missing or malformed SHA disables hooks.
        if (function_exists('add_filter')) {
            add_filter('the_content', __CLASS__.'::render', PHP_INT_MAX);
        }
        if (function_exists('add_action')) {
            add_action('wp_enqueue_scripts', __CLASS__.'::enqueue', 28);
            add_action('template_redirect', __CLASS__.'::startBuffer', 25);
        }
    }

    private static function eligible() {
        if (!self::armed() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || !function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $post = get_post(self::ID);
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($post->post_content ?? null)
            && hash_equals(self::VERIFIED_RAW_SHA256, hash('sha256', $post->post_content));
    }

    public static function enqueue() {
        if (!self::eligible() || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-wellness-srub-hauzi-17536-v1',
            plugins_url('assets/nry-wellness-srub-hauzi-17536-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function cta($label) {
        return '<a class="nry-wellness__cta" href="'
            .htmlspecialchars(self::HAUZI_AFFILIATE, ENT_QUOTES, 'UTF-8')
            .'" target="_blank" rel="sponsored nofollow noopener">'.$label.'</a>';
    }

    public static function feature() {
        return '<section class="nry-wellness" '.self::MARKER
            .' aria-label="Aktuální možnosti pobytu ve Wellness srubu">'
            .'<p class="nry-wellness__eyebrow">Vranovská přehrada · wellness · rybaření</p>'
            .'<h2>Wellness srub s koupacím sudem a saunou: ověřte si pobyt</h2>'
            .'<p>Konkrétní srub v oblasti Lančova a Jazovické zátoky nabízí podle ubytovacího portálu '
            .'<strong>3 ložnice až pro 7 hostů, finskou saunu a koupací sud</strong>. '
            .'Dostupnost, vybavení i aktuální cenu je nutné potvrdit u ubytovatele.</p>'
            .'<div class="nry-wellness__warning"><strong>Pozor pro rybáře:</strong> Podle aktuálního popisu '
            .'objektu může nižší hladina Vranovské přehrady po jaru rybolov výrazně omezit. '
            .'Nespoléhejte na historický slib o stále zakrmených místech ani na dostupnost lodě; '
            .'před rezervací si ověřte skutečný stav vody, přístup k revíru a povolenku.</div>'
            .'<div class="nry-wellness__actions">'.self::cta('Zjistit termín Wellness srubu na Hauzi')
            .'<a class="nry-wellness__secondary" href="'.self::OWNER_LISTING
            .'" target="_blank" rel="noopener">Další informace o srubu</a></div>'
            .'<p class="nry-wellness__fine">Partnerský odkaz Hauzi. Odkaz neznamená potvrzení volného termínu, '
            .'vhodného rybářského místa ani zahrnutou povolenku.</p>'
            .'</section>';
    }

    public static function footer() {
        return '<aside class="nry-wellness__bottom" aria-label="Ověření dostupnosti Wellness srubu">'
            .'<h3>Zajímá vás Wellness srub u Vranova?</h3>'
            .'<p>Před rezervací porovnejte aktuální dostupnost, cenu a možnosti rybaření vzhledem k hladině přehrady.</p>'
            .self::cta('Ověřit konkrétní srub na Hauzi')
            .'<p><small>Partnerský odkaz; bez garance ceny, storna nebo možnosti lovu.</small></p>'
            .'</aside>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 1800
            || strpos($html, self::MARKER) !== false
            || substr_count($html, self::INTRO) !== 1
            || substr_count($html, self::LEGACY_START) !== 1
            || substr_count($html, self::LEGACY_END) !== 1
            || substr_count($html, self::OLD_BOAT) !== 1
            || substr_count($html, self::OLD_AGODA) !== 1
            || substr_count($html, self::OLD_CTA) !== 1
            || substr_count($html, self::OLD_CLAIM) !== 1
            || strpos($html, self::HAUZI_LISTING) !== false
            || strpos($html, 'tracking.affiliateport.eu/click?o=1990') !== false) return $html;
        $intro = strpos($html, self::INTRO);
        $start = strpos($html, self::LEGACY_START);
        $tail = strpos($html, self::LEGACY_END, $start);
        if ($intro === false || $start === false || $tail === false
            || $start <= $intro || $start - $intro > 9500) return $html;
        $end = $tail + strlen(self::LEGACY_END);
        $oldBox = substr($html, $start, $end - $start);
        if (strlen($oldBox) < 900 || strlen($oldBox) > 2500
            || substr_count($oldBox, self::OLD_AGODA) !== 1) return $html;
        $withoutBox = substr($html, 0, $start).self::footer().substr($html, $end);
        $changed = str_replace(self::OLD_BOAT,
            'Starší popis zmiňoval loď na zavážení a zakrmená místa, jejich aktuální dostupnost ale není ověřena.',
            $withoutBox, $countBoat);
        $changed = substr_replace($changed, self::feature(), $intro, 0);
        $safeHref = htmlspecialchars(self::HAUZI_AFFILIATE, ENT_QUOTES, 'UTF-8');
        return $countBoat === 1
            && substr_count($changed, self::MARKER) === 1
            && substr_count($changed, $safeHref) === 2
            && substr_count($changed, 'rel="sponsored nofollow noopener"') === 2
            && substr_count($changed, self::OWNER_LISTING) === 1
            && strpos($changed, self::OLD_AGODA) === false
            && strpos($changed, self::OLD_CLAIM) === false
            && strpos($changed, self::OLD_BOAT) === false
            ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }

    public static function startBuffer() {
        if (!self::eligible() || headers_sent()
            || (function_exists('is_feed') && is_feed())) return;
        ob_start(array(__CLASS__, 'transformDocument'));
    }

    public static function transformDocument($document, $flags = 0) {
        if (!is_string($document) || strlen($document) < 20000
            || strpos($document, self::MARKER) !== false
            || substr_count($document, self::INTRO) !== 1
            || substr_count($document, self::LEGACY_START) !== 1
            || substr_count($document, self::LEGACY_END) !== 1) return $document;
        $start = strpos($document, self::INTRO);
        $tail = strpos($document, self::LEGACY_END, $start);
        if ($start === false || $tail === false || $tail <= $start) return $document;
        $end = $tail + strlen(self::LEGACY_END);
        $original = substr($document, $start, $end - $start);
        $changed = self::transform($original);
        if ($changed === $original) return $document;
        $result = substr($document, 0, $start).$changed.substr($document, $end);
        return substr_count($result, self::MARKER) === 1
            && substr_count($result, htmlspecialchars(self::HAUZI_AFFILIATE, ENT_QUOTES, 'UTF-8')) === 2
            ? $result : $document;
    }
}
NRY_Wellness_Srub_Hauzi_Offer_17536_V1::boot();
