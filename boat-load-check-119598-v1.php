<?php
/**
 * Exact-source, render-only boat payload check for one existing buyer guide.
 * No stored content mutation, merchant routing, visitor storage or external calls.
 * Rollback: remove this file's require from the main plugin and redeploy.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Boat_Load_Check_119598_V1 {
    const ID = 119598;
    const SLUG = 'nejlepsi-nafukovaci-cluny-pro-rybare';
    const MODIFIED = '2026-05-15 12:17:30';
    const MARKER = 'data-nry-boat-load="v1"';
    const CJ = 'click-100927011-12347575';
    const ANCHOR = '<h2 style="font-size: 30px; line-height: 1.3; margin: 36px 0 16px 0; color: #0f172a;">Nosnost: proč nestačí počítat jen lidi</h2>';

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
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $matches) === false) return null;
        return $matches[2];
    }

    private static function form() {
        return '<section '.self::MARKER.' aria-label="Kontrola celkové zátěže rybářského člunu"'
            .' style="border:2px solid #0369a1;border-radius:12px;background:#f0f9ff;padding:18px;margin:20px 0;color:#0c4a6e">'
            .'<h3 style="margin:0 0 8px;color:#075985;font-size:21px">Vejde se vaše sestava do nosnosti člunu?</h3>'
            .'<p>Sečtěte hmotnost lidí, motoru, baterie a všech věcí, které povezete. Do prvního pole přepište'
            .' <strong>nejvyšší doporučenou celkovou zátěž</strong> přímo ze štítku nebo návodu konkrétního člunu.'
            .' Nezaměňujte ji s hmotností samotného člunu ani s limitem jen pro osoby.</p>'
            .'<form data-nry-boat-load-form="v1" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end">'
            .'<label>Maximální celková zátěž (kg)<input name="capacity" type="number" min="1" max="2500" step="0.5" required'
            .' placeholder="Údaj výrobce" inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<label>Všichni lidé dohromady (kg)<input name="people" type="number" min="1" max="1000" step="0.5" required'
            .' placeholder="Součet hmotností" inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<label>Motor a držák (kg)<input name="motor" type="number" min="0" max="800" step="0.5" required value="0"'
            .' inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<label>Baterie (kg)<input name="battery" type="number" min="0" max="800" step="0.5" required value="0"'
            .' inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<label>Rybářská výbava (kg)<input name="gear" type="number" min="0" max="800" step="0.5" required value="0"'
            .' inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<label>Ostatní náklad (kg)<input name="other" type="number" min="0" max="800" step="0.5" required value="0"'
            .' inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<button type="submit" style="cursor:pointer;border:0;border-radius:8px;padding:11px 15px;background:#0369a1;color:#fff;font-weight:bold">Spočítat zátěž</button>'
            .'</form><output data-nry-boat-load-result="v1" role="status" aria-live="polite"'
            .' style="display:block;font-size:17px;font-weight:bold;margin:14px 0 7px">Vyplňte údaje ze štítku a vaši sestavu.</output>'
            .'<p style="font-size:14px;margin:8px 0 0"><strong>Důležité:</strong> I když součet nepřekročí limit,'
            .' nejde o potvrzení bezpečné plavby. Samostatně ověřte maximální počet osob, povolený motor,'
            .' rozložení zátěže, doporučení výrobce, počasí, záchranné vesty a pravidla revíru.'
            .' Údaje a dostupnost konkrétního člunu vždy ověřte před nákupem.</p></section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 18000
            || strpos($html, self::MARKER) !== false
            || substr_count($html, self::ANCHOR) !== 1
            || substr_count($html, self::CJ) !== 13
            || strpos($html, 'Rychlý orientační výpočet zátěže') === false) return $html;
        $before = self::hrefs($html);
        if ($before === null || count($before) < 13) return $html;
        $next = str_replace(self::ANCHOR, self::ANCHOR.self::form(), $html);
        $after = self::hrefs($next);
        return ($before === $after && substr_count($next, self::MARKER) === 1) ? $next : $html;
    }

    public static function render($html) { return self::eligible() ? self::transform($html) : $html; }

    public static function enqueue() {
        if (self::eligible() && function_exists('wp_enqueue_script') && function_exists('plugins_url')) {
            wp_enqueue_script('nry-boat-load-v1', plugins_url('assets/nry-boat-load-v1.js', __FILE__),
                array(), '1.0.0', true);
        }
    }

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_singular')) {
            add_filter('the_content', array(__CLASS__, 'render'), 79);
        }
        if (function_exists('add_action') && function_exists('is_singular')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'));
        }
    }
}
NRY_Boat_Load_Check_119598_V1::boot();
