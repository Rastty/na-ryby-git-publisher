<?php
/**
 * Render-only family fishing-stay choices for the historical article 59271.
 * Exact public source identity, frozen rendered prefix, approved partner URLs.
 * No stored WordPress writes, new cookies, redirects or synthetic click events.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Family_Stays_59271_V1 {
    const ID = 59271;
    const SLUG = 'rodinna-dovolena-u-vody-kam-na-ryby-s-detmi';
    const URL = 'https://na-ryby.eu/rodinna-dovolena-u-vody-kam-na-ryby-s-detmi/';
    const MODIFIED_GMT = '2023-08-23 08:26:35';
    // SHA256 of the first 512 bytes of the public WP REST rendered content on
    // 18 Nov 2026, before this module. This is NOT a claim about raw WP SHA.
    const RENDERED_PREFIX_BYTES = 512;
    const RENDERED_PREFIX_SHA256 = '02228a68098a012f31a5df6c96f82ea9231b2d8fd9fd1ea392929a08d750b04a';
    const ANCHOR = '<p>Zde je pár tipů, kam se vydat na ryby se svými dětmi:</p>';
    const MARKER = 'data-nry-family-stays="v1"';
    const HPORTAL_LIPNO = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F';
    const HPORTAL_SLAPY = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Frabyne%2Fpenzion-janata%2F';
    const STAYS = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', __CLASS__ . '::render', PHP_INT_MAX);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', __CLASS__ . '::enqueue', 28);
        }
    }

    private static function eligible($loop = true) {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::ID
            || ($loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
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
            && strpos($post->post_content, 'Kam na ryby s dětmi?') !== false
            && strpos($post->post_content, 'Rybolov na řece Vltavě') !== false
            && strpos($post->post_content, 'Rybářská osada Dolní Vltavice') !== false;
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-family-stays-59271-v1',
            plugins_url('assets/nry-family-stays-59271-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function offer($id, $eyebrow, $name, $description, $url, $cta) {
        return '<article data-nry-family-offer="'.$id.'">'
            .'<p class="nry-family-stays__place">'.$eyebrow.'</p>'
            .'<h3>'.$name.'</h3><p>'.$description.'</p>'
            .'<p class="nry-family-stays__action"><a class="nry-family-stays__cta"'
            .' href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'"'
            .' target="_blank" rel="sponsored nofollow noopener">'.$cta.'</a></p>'
            .'</article>';
    }

    public static function block() {
        return '<section class="nry-family-stays" '.self::MARKER.' aria-label="Rodinné pobyty u vody">'
            .'<p class="nry-family-stays__eyebrow">Rodinné pobyty u vody · konkrétní možnosti</p>'
            .'<h2>Na ryby s dětmi? Podívejte se rovnou na ubytování</h2>'
            .'<p>Začněte místem, kde chcete trávit čas s rodinou. Vybrali jsme dvě konkrétní možnosti u Slap a Lipna; '
            .'rybaření se zde uvádí jako okolní aktivita, <strong>není tím potvrzena povolenka ani lov v ceně</strong>.</p>'
            .'<div class="nry-family-stays__grid">'
            .self::offer('lipno-kohoutu', 'Lipno · Horní Planá', 'Pension U Kohoutů',
                'Hportal uvádí rodinné pokoje a rybaření mezi aktivitami v okolí. '
                .'Před rezervací ověřte volný pokoj, vzdálenost k vybranému revíru a podmínky lovu.',
                self::HPORTAL_LIPNO, 'Zobrazit ubytování u Lipna')
            .self::offer('slapy-janata', 'Slapy · Nová Rabyně', 'Penzion Janata',
                'Hportal uvádí polohu vedle pláže, vodní sporty a rybaření. '
                .'Ověřte vhodnost konkrétního pokoje pro děti i pravidla místního revíru.',
                self::HPORTAL_SLAPY, 'Zobrazit ubytování u Slap')
            .'</div>'
            .'<p class="nry-family-stays__more">Chcete rybářský pobyt i v jiné lokalitě? '
            .'<a href="'.self::STAYS.'">Prohlédnout další konkrétní nabídky pobytů</a>.</p>'
            .'<p class="nry-family-stays__note"><small>Partnerské odkazy · ověřeno 18. 11. 2026. '
            .'Dostupnost a ceny se mění. Na svazových vodách ověřte rybářský lístek, '
            .'povolenku, pravidla pro děti a bezpečnost u vody; u soukromých vod podmínky správce.</small></p>'
            .'</section>';
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $matches) === false) return null;
        return array_map(function($url) { return html_entity_decode($url, ENT_QUOTES, 'UTF-8'); }, $matches[2]);
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 2000
            || hash('sha256', substr($html, 0, self::RENDERED_PREFIX_BYTES)) !== self::RENDERED_PREFIX_SHA256
            || substr_count($html, self::ANCHOR) !== 1
            || substr_count($html, '<h3>Rybolov na řece Vltavě</h3>') !== 1
            || substr_count($html, '<h3>Rybářská osada Dolní Vltavice</h3>') !== 1
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::HPORTAL_LIPNO) !== false
            || strpos($html, self::HPORTAL_SLAPY) !== false
            || strpos($html, self::STAYS) !== false) return $html;
        $old = self::hrefs($html);
        if ($old === null) return $html;
        $added = array(self::HPORTAL_LIPNO, self::HPORTAL_SLAPY, self::STAYS);
        foreach ($added as $url) {
            if (in_array($url, $old, true)) return $html;
        }
        $changed = str_replace(self::ANCHOR, self::block() . "\n" . self::ANCHOR, $html);
        $new = self::hrefs($changed);
        $preserved = array_values(array_filter($new ?? array(), function($url) use($added) {
            return !in_array($url, $added, true);
        }));
        $new_counts = $new === null ? array() : array_count_values($new);
        return ($new !== null && $preserved === $old
            && count($new) === count($old) + 3
            && substr_count($changed, self::MARKER) === 1
            && substr_count($changed, 'data-nry-family-offer=') === 2
            && ($new_counts[self::HPORTAL_LIPNO] ?? 0) === 1
            && ($new_counts[self::HPORTAL_SLAPY] ?? 0) === 1
            && ($new_counts[self::STAYS] ?? 0) === 1)
            ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Family_Stays_59271_V1::boot();
