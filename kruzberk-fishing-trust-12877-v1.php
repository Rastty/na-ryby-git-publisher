<?php
/**
 * One source-locked, frontend-only safety clarification for the Kružberk stay
 * guide. No WordPress content write, affiliate rewrite or new tracking.
 * Official sources: obec Kružberk and ČRS RIS, checked 19 Nov 2026.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Kruzberk_Fishing_Trust_12877_V1 {
    const ID = 12877;
    const SLUG = 'rybarska-chata-kruzberk-rybarina-moravskoslezsky-kraj';
    const URL = 'https://na-ryby.eu/rybarska-chata-kruzberk-rybarina-moravskoslezsky-kraj/';
    const MODIFIED_GMT = '2026-04-14 23:33:21';
    // Independent public, GET-only WordPress raw proof from PR #275.
    const RAW_SHA256 = 'e52ed59429977b7173d628c07eaac7bbd7b5e030eb5a72bb26f8ffcc682b85b8';
    // First 512 bytes of anonymous WP REST content.rendered on 19 Nov 2026.
    const PREFIX_SHA256 = '1d36323ddcfb6d1a6f3ad25c4de8612d71114ee534e96d36dae1c3ccb573664e';
    const ANCHOR = '<h1>Rybářská chata Kružberk: u Moravice, co čekat na místě a pro koho dává smysl</h1>';
    const MARKER = 'data-nry-kruzberk-trust="v1"';
    const OBEC = 'https://www.kruzberk.cz/turisticke-informace/kruzberska-prehrada/';
    const CRS = 'https://ris.rybsvaz.cz/karta-reviru/-/473054-moravice-4';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', __CLASS__.'::render', PHP_INT_MAX);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', __CLASS__.'::enqueue', 28);
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
            && hash_equals(self::RAW_SHA256, hash('sha256', $post->post_content));
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-kruzberk-trust-12877-v1',
            plugins_url('assets/nry-kruzberk-trust-12877-v1.css', __FILE__), array(), '1.0.0');
    }

    public static function block() {
        return '<section class="nry-kruzberk-trust-v1" '.self::MARKER.' aria-label="Ověřená pravidla rybolovu u Kružberku">'
            .'<p class="nry-kruzberk-trust-v1__eyebrow">Důležité před rezervací a rybolovem</p>'
            .'<h2>V samotné přehradě Kružberk se rybařit nesmí</h2>'
            .'<p>Vodárenská nádrž Kružberk není místem povoleného rybolovu. '
            .'Oficiální stránky obce na to výslovně upozorňují.</p>'
            .'<p>Pstruhový revír <strong>473&nbsp;054 Moravice&nbsp;4</strong> je jiný, vymezený úsek řeky: '
            .'od silničního mostu v Zálužné po tabuli u limnigrafu přibližně 300&nbsp;m pod hrází. '
            .'Přítoky tohoto revíru jsou chovné a lov ryb je v nich zakázán.</p>'
            .'<p>Poloha chaty u vody sama o sobě nezaručuje lovné místo ani oprávnění k rybolovu. '
            .'Před cestou ověřte konkrétní úsek, aktuální podmínky a potřebnou povolenku.</p>'
            .'<div class="nry-kruzberk-trust-v1__links">'
            .'<a href="'.self::OBEC.'" rel="noopener" target="_blank">Pravidla přehrady na webu obce</a>'
            .'<a href="'.self::CRS.'" rel="noopener" target="_blank">Mapa a podmínky revíru Moravice 4 (ČRS)</a>'
            .'</div><p class="nry-kruzberk-trust-v1__date"><small>Ověřeno 19. 11. 2026; před lovem zkontrolujte aktuální soupis revírů.</small></p>'
            .'</section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 10000
            || hash('sha256', substr($html, 0, 512)) !== self::PREFIX_SHA256
            || substr_count($html, self::ANCHOR) !== 1
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::OBEC) !== false
            || strpos($html, self::CRS) !== false) return $html;
        $updated = str_replace(self::ANCHOR, self::ANCHOR."\n".self::block(), $html, $replacements);
        return $replacements === 1
            && substr_count($updated, self::MARKER) === 1
            && substr_count($updated, self::OBEC) === 1
            && substr_count($updated, self::CRS) === 1
            ? $updated : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Kruzberk_Fishing_Trust_12877_V1::boot();
