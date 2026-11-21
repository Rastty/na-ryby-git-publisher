<?php
/**
 * Four concrete editorial choices inside the otherwise abstract private waters guide.
 * First-party-only navigation, not a ranking or verification of current prices.
 * Output-only and exact public WordPress post; original content remains unchanged.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Private_Waters_Concrete_Choice_V1 {
    const SLUG = 'nejlepsi-soukrome-reviry-pro-rybolov-v-cesku';
    const URL = 'https://na-ryby.eu/nejlepsi-soukrome-reviry-pro-rybolov-v-cesku/';
    const MARKER = 'data-nry-private-water-examples="v1"';
    const ANCHOR = 'Podle čeho poznat dobrý soukromý revír';
    const OTHER_HEADING = 'Pro koho dávají soukromé revíry největší smysl';
    const STERKAC = 'https://na-ryby.eu/soukromy-revir-sterkac-zajeci/';
    const RPETY = 'https://na-ryby.eu/soukromy-revir-rpety-hate-stredocesky-kraj/';
    const MRHAL = 'https://na-ryby.eu/soukromy-revir-rybnik-mrhal/';
    const SPINDLER = 'https://na-ryby.eu/rybnik-spindler/';
    const ARCHIVE = 'https://na-ryby.eu/category/soukrome-rybarske-reviry/';

    private static function eligible($content = true) {
        foreach (array('is_single', 'is_admin', 'is_preview', 'home_url',
            'get_queried_object_id', 'get_permalink', 'get_post') as $fn) {
            if (!function_exists($fn)) return false;
        }
        if (!is_single(self::SLUG) || is_admin() || is_preview()
            || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || (function_exists('is_feed') && is_feed())
            || (function_exists('is_customize_preview') && is_customize_preview())
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return false;
        if ($content && (!function_exists('is_main_query') || !is_main_query()
            || !function_exists('in_the_loop') || !in_the_loop())) return false;
        $id = (int) get_queried_object_id();
        if ($id <= 0 || get_permalink($id) !== self::URL) return false;
        $post = get_post($id);
        return is_object($post)
            && (int)($post->ID ?? 0) === $id
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG;
    }

    public static function block() {
        return '<section class="nry-water-examples" '.self::MARKER
            .' aria-labelledby="nry-water-examples-title">'
            .'<p class="nry-water-examples__eyebrow">Konkrétní lokality k porovnání</p>'
            .'<h2 id="nry-water-examples-title">Čtyři soukromé vody, od kterých můžete začít</h2>'
            .'<p>Nejde o žebříček kvality ani aktuální nabídku volných míst. '
            .'Vybrali jsme čtyři různé regiony, ať nemusíte hledat úplně naslepo. '
            .'U každé vody ověřte u správce dnešní podmínky lovu a rezervace.</p>'
            .'<div class="nry-water-examples__grid">'
            .'<article><span>Jižní Morava</span><h3>Štěrkáč Zaječí</h3>'
            .'<p>Podmínky lovu a kontakty na provozovatele.</p>'
            .'<a href="'.self::STERKAC.'">Zjistit pravidla Štěrkáče</a></article>'
            .'<article><span>Střední Čechy</span><h3>Rpety–Hatě</h3>'
            .'<p>Základní orientace v lokalitě a pravidlech.</p>'
            .'<a href="'.self::RPETY.'">Prohlédnout Rpety–Hatě</a></article>'
            .'<article><span>Jižní Čechy</span><h3>Rybník Mrhal</h3>'
            .'<p>Informace o vodě a co ověřit před výpravou.</p>'
            .'<a href="'.self::MRHAL.'">Prohlédnout rybník Mrhal</a></article>'
            .'<article><span>Vysočina</span><h3>Rybník Špindler</h3>'
            .'<p>Starší profil vody; aktuální ceny a provoz ověřte zvlášť.</p>'
            .'<a href="'.self::SPINDLER.'">Prohlédnout rybník Špindler</a></article>'
            .'</div>'
            .'<p class="nry-water-examples__more">Nehodí se vám tyto lokality? '
            .'<a href="'.self::ARCHIVE.'">Projděte soukromé revíry podle regionu</a>.</p>'
            .'<p class="nry-water-examples__note">Redakční výběr příkladů, nikoli placené hodnocení. '
            .'Cena, povolenky, noční lov, rybí obsádka i dostupnost se mohou měnit.</p>'
            .'</section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 2500
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::STERKAC) !== false
            || strpos($html, self::RPETY) !== false
            || strpos($html, self::MRHAL) !== false
            || strpos($html, self::SPINDLER) !== false) return $html;
        $pattern = '~<h2\\b[^>]*>\\s*'.preg_quote(self::ANCHOR, '~').'\\s*</h2>~u';
        $m = array();
        if (preg_match_all($pattern, $html, $m, PREG_OFFSET_CAPTURE) !== 1
            || !isset($m[0][0][1])) return $html;
        if (strpos($html, self::OTHER_HEADING) === false
            || strpos($html, self::OTHER_HEADING) <= $m[0][0][1]) return $html;
        $insertAt = $m[0][0][1];
        $section = self::block();
        if (substr_count($section, self::MARKER) !== 1
            || substr_count($section, '<article>') !== 4) return $html;
        $updated = substr($html, 0, $insertAt).$section.substr($html, $insertAt);
        return substr_count($updated, self::MARKER) === 1 ? $updated : $html;
    }

    public static function render($html) {
        return self::eligible(true) ? self::transform($html) : $html;
    }

    public static function enqueue() {
        if (self::eligible(false) && function_exists('wp_enqueue_style')
            && function_exists('plugins_url')) {
            wp_enqueue_style('nry-private-waters-examples-v1',
                plugins_url('assets/nry-private-waters-examples-v1.css', __FILE__),
                array(), '1.0.0');
        }
    }

    public static function boot() {
        // Only register runtime hooks inside WordPress. Some offline publisher
        // tests stub add_filter() but intentionally have no is_single/get_post.
        if (function_exists('is_single') && function_exists('get_post')
            && function_exists('add_filter') && function_exists('add_action')) {
            add_filter('the_content', array(__CLASS__, 'render'), 92);
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 25);
        }
    }
}
NRY_Private_Waters_Concrete_Choice_V1::boot();
