<?php
/**
 * Exact-property offer for the historical Cyklochata Vranov article #11983.
 * No stored WordPress changes, outbound merchant requests or synthetic clicks.
 * Active only for exact verified original WP SHA from independent read-only proof #45002087016.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Cyklochata_Hauzi_Offer_11983_V1 {
    const ID = 11983;
    const SLUG = 'rybarska-chata-cyklochata-vranovska-prehrada';
    const URL = 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/';
    const MODIFIED_GMT = '2023-04-11 12:57:02';
    // Exact original source was independently verified after main release #358.
    const VERIFIED_RAW_SHA256 = '539d7814d35f2ea52996f3011b24bed44b5c753334b9ad000c21ce547daa19c5';
    const MARKER = 'data-nry-cyklochata-offer="v1"';
    const ANCHOR = 'nry-cyklochata-hauzi-offer-11983';
    const HAUZI_LISTING = 'https://www.hauzi.cz/cyklochata';
    const HAUZI_AFFILIATE = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcyklochata';
    const OFFICIAL = 'https://www.dovolenavranov.cz/cyklochata/';
    const INTRO = '<p><strong>Rybářská chata Cyklochata &#8211; Vranovská přehrada</strong>';
    const LEGACY_START = '<div class="agoda-booking-box" style="background:';
    const LEGACY_END = "* Garantovaná nejnižší cena a možnost zrušení zdarma u většiny objektů.</div>\n</div>\n";
    const LEGACY_LINK = 'anrdoezrs.net/click-8287231-15735403';
    const LEGACY_CTA = 'Zobrazit nabídky na nejlepší ubytování';
    const LEGACY_CLAIM = 'Garantovaná nejnižší cena';

    public static function armed() {
        return strlen(self::VERIFIED_RAW_SHA256) === 64
            && (bool)preg_match('/^[a-f0-9]{64}$/D', self::VERIFIED_RAW_SHA256);
    }

    public static function boot() {
        // If original raw source changes, fail closed without modifying output.
        if (!self::armed()) return;
        if (function_exists('add_filter')) {
            add_filter('the_content', __CLASS__.'::render', PHP_INT_MAX);
        }
        if (function_exists('add_action')) {
            add_action('wp_enqueue_scripts', __CLASS__.'::enqueue', 28);
            add_action('template_redirect', __CLASS__.'::startBuffer', 25);
        }
    }

    private static function eligible() {
        if (!self::armed()
            || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
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
        wp_enqueue_style('nry-cyklochata-hauzi-offer-11983-v1',
            plugins_url('assets/nry-cyklochata-hauzi-offer-11983-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function cta($label) {
        return '<a class="nry-cyklochata__cta" href="'
            .htmlspecialchars(self::HAUZI_AFFILIATE, ENT_QUOTES, 'UTF-8')
            .'" target="_blank" rel="sponsored nofollow noopener">'.$label.'</a>';
    }

    public static function feature() {
        return '<section id="'.self::ANCHOR.'" class="nry-cyklochata" '.self::MARKER
            .' aria-label="Ověření ubytování Cyklochata u Vranovské přehrady">'
            .'<p class="nry-cyklochata__eyebrow">Vranovská přehrada · konkrétní chata · ověření pobytu</p>'
            .'<h2>Cyklochata: zjistěte možnosti pobytu přímo u tohoto objektu</h2>'
            .'<p>Tato nabídka vede na <strong>stejnou Cyklochatu ve Štítarech</strong>, kterou popisuje článek. '
            .'Provozovatel uvádí 7 ložnic, kapacitu až 28 osob a přibližně 170 m od vody. '
            .'Aktuální cenu, volný termín a podmínky pobytu si vždy ověřte před rezervací.</p>'
            .'<div class="nry-cyklochata__actions">'.self::cta('Zjistit termín Cyklochaty na Hauzi')
            .'<a class="nry-cyklochata__secondary" href="'.self::OFFICIAL
            .'" target="_blank" rel="noopener">Informace přímo od provozovatele</a></div>'
            .'<p class="nry-cyklochata__fine">Partnerský odkaz Hauzi. Ubytování samo o sobě nezahrnuje '
            .'rybářskou povolenku ani nezaručuje možnost lovu. Pravidla revíru a přístup k vodě ověřte samostatně.</p>'
            .'</section>';
    }

    public static function footer() {
        return '<aside class="nry-cyklochata__bottom" aria-label="Ověření nabídky Cyklochaty">'
            .'<h3>Chcete si Cyklochatu rezervovat?</h3>'
            .'<p>Ověřte dostupnost a cenu konkrétní chaty. Historické informace v článku nemusí být aktuální.</p>'
            .self::cta('Ověřit konkrétní Cyklochatu')
            .'<p><small>Partnerský odkaz Hauzi · bez záruky nejnižší ceny či storna zdarma.</small></p>'
            .'</aside>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 1500
            || strpos($html, self::MARKER) !== false
            || substr_count($html, self::INTRO) !== 1
            || substr_count($html, self::LEGACY_START) !== 1
            || substr_count($html, self::LEGACY_END) !== 1
            || substr_count($html, self::LEGACY_LINK) !== 1
            || substr_count($html, self::LEGACY_CTA) !== 1
            || substr_count($html, self::LEGACY_CLAIM) !== 1
            || strpos($html, self::HAUZI_LISTING) !== false
            || strpos($html, 'tracking.affiliateport.eu/click?o=1990') !== false) return $html;
        $intro = strpos($html, self::INTRO);
        $start = strpos($html, self::LEGACY_START);
        $endStart = strpos($html, self::LEGACY_END, $start);
        if ($intro === false || $start === false || $endStart === false
            || $start < $intro || $start - $intro > 9000) return $html;
        $end = $endStart + strlen(self::LEGACY_END);
        $oldBox = substr($html, $start, $end - $start);
        if (strlen($oldBox) < 900 || strlen($oldBox) > 2500
            || substr_count($oldBox, self::LEGACY_LINK) !== 1
            || substr_count($oldBox, self::LEGACY_CTA) !== 1) return $html;
        $withoutBox = substr($html, 0, $start).self::footer().substr($html, $end);
        $changed = substr_replace($withoutBox, self::feature(), $intro, 0);
        $safeHref = htmlspecialchars(self::HAUZI_AFFILIATE, ENT_QUOTES, 'UTF-8');
        return substr_count($changed, self::MARKER) === 1
            && substr_count($changed, $safeHref) === 2
            && substr_count($changed, 'rel="sponsored nofollow noopener"') === 2
            && substr_count($changed, self::OFFICIAL) === 1
            && strpos($changed, self::LEGACY_LINK) === false
            && strpos($changed, self::LEGACY_CLAIM) === false
            && strpos($changed, self::LEGACY_CTA) === false
            ? $changed : $html;
    }

    public static function render($html) {
        // tagDiv may render the post outside the main Loop. Queried post ID,
        // verified raw WP SHA and literal HTML anchors are the authorization.
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
NRY_Cyklochata_Hauzi_Offer_11983_V1::boot();
