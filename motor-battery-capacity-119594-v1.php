<?php
/**
 * Render-only battery capacity estimator for one existing electric-motor guide.
 * No post_content write, affiliate rewrite, outbound request or visitor storage.
 * Rollback: remove the require from na-ryby-git-publisher.php.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Motor_Battery_Capacity_119594_V1 {
    const ID = 119594;
    const SLUG = 'nejlepsi-lodni-elektromotory-pro-rybare';
    const MODIFIED = '2026-05-15 10:37:45';
    const MARKER = 'data-nry-motor-battery="v1"';
    const CJ = 'click-100927011-12347575';
    const ANCHOR = '<h3 style="font-size: 24px; margin: 24px 0 10px 0; color: #0f172a;">Jakou kapacitu baterie vybrat?</h3>';

    public static function eligible() {
        if (!function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_post') || !function_exists('get_permalink')
            || get_permalink(self::ID) !== 'https://na-ryby.eu/'.self::SLUG.'/') return false;
        $post = get_post(self::ID);
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post' && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED;
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $found) === false) return null;
        return $found[2];
    }

    private static function calculator() {
        return '<section '.self::MARKER.' aria-label="Orientační výpočet kapacity baterie"'
            .' style="border:2px solid #0f766e;border-radius:12px;padding:18px;margin:18px 0 26px;background:#f0fdfa;color:#134e4a">'
            .'<h4 style="margin:0 0 8px;font-size:21px;color:#115e59">Spočítejte si orientační kapacitu baterie</h4>'
            .'<p>Nejdřív zjistěte odběr <strong>konkrétního motoru v ampérech (A)</strong> při plánovaném provozu.'
            .' Tah 55 lb sám o sobě odběr neudává. Pokud znáte jen maximální odběr, výsledek bude konzervativní.</p>'
            .'<form data-nry-motor-battery-form="v1" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end">'
            .'<label>Odběr motoru (A)<input name="current" type="number" min="1" max="150" step="0.5" required placeholder="Podle výrobce"'
            .' inputmode="decimal" style="display:block;width:100%;box-sizing:border-box;padding:9px;margin-top:5px"></label>'
            .'<label>Doba chodu (hodiny)<input name="hours" type="number" min="0.5" max="12" step="0.5" required value="2"'
            .' inputmode="decimal" style="display:block;width:100%;box-sizing:border-box;padding:9px;margin-top:5px"></label>'
            .'<label>Využitelná část baterie (%)<input name="usable" type="number" min="10" max="100" step="5" required value="70"'
            .' inputmode="numeric" style="display:block;width:100%;box-sizing:border-box;padding:9px;margin-top:5px"></label>'
            .'<label>Bezpečnostní rezerva (%)<input name="reserve" type="number" min="0" max="100" step="5" required value="30"'
            .' inputmode="numeric" style="display:block;width:100%;box-sizing:border-box;padding:9px;margin-top:5px"></label>'
            .'<button type="submit" style="cursor:pointer;border:0;border-radius:8px;padding:11px 15px;background:#0f766e;color:#fff;font-weight:bold">Vypočítat Ah</button>'
            .'</form><output data-nry-motor-battery-result="v1" role="status" aria-live="polite"'
            .' style="display:block;font-weight:bold;margin:13px 0 8px">Zadejte skutečný odběr motoru a stiskněte Vypočítat.</output>'
            .'<p style="font-size:14px;margin:8px 0 0">Výchozích 70 % využitelné kapacity a 30 % rezervy jsou pouze ilustrační hodnoty.'
            .' Skutečně povolené vybití a proudový limit baterie určuje výrobce. Výpočet Ah = A × h × (1 + rezerva) / využitelný podíl'
            .' nezohledňuje proudové špičky, stáří baterie, chlad ani ztráty při velkém odběru.'
            .' Napětí motoru a baterie musí souhlasit; jistič, kabeláž a BMS volte dle pokynů výrobců.</p></section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 18000
            || strpos($html, self::MARKER) !== false
            || substr_count($html, self::ANCHOR) !== 1
            || substr_count($html, self::CJ) !== 13
            || strpos($html, 'Baterie k elektromotoru: olovo, AGM nebo LiFePO4?') === false) return $html;
        $before = self::hrefs($html);
        if ($before === null || count($before) < 13) return $html;
        $next = str_replace(self::ANCHOR, self::ANCHOR.self::calculator(), $html);
        $after = self::hrefs($next);
        return ($after === $before && substr_count($next, self::MARKER) === 1) ? $next : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }

    public static function enqueue() {
        if (self::eligible() && function_exists('wp_enqueue_script') && function_exists('plugins_url')) {
            wp_enqueue_script('nry-motor-battery-v1',
                plugins_url('assets/nry-motor-battery-v1.js', __FILE__), array(), '1.0.0', true);
        }
    }

    public static function boot() {
        // WP query APIs must exist; offline publisher/KSES test stubs may
        // implement add_filter but cannot register array callbacks safely.
        if (function_exists('add_filter') && function_exists('is_singular')) {
            add_filter('the_content', array(__CLASS__, 'render'), 78);
        }
        if (function_exists('add_action') && function_exists('is_singular')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'));
        }
    }
}
NRY_Motor_Battery_Capacity_119594_V1::boot();
