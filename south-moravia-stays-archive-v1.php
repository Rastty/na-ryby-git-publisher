<?php
/**
 * South Moravia fishing-stays 2017 list -> two already public Vranov decisions.
 * Exact single-published-post only; output-only, preserving all old HTML/links.
 * No new merchant destinations, WP DB writes, prices, tracking or clicks.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_South_Moravia_Stays_Archive_V1 {
    const SLUG = 'rybareni-s-ubytovanim-jihomoravsky-kraj';
    const URL = 'https://na-ryby.eu/rybareni-s-ubytovanim-jihomoravsky-kraj/';
    const TITLE = 'Rybaření s ubytováním – Jihomoravský kraj';
    const MARKER = 'data-nry-jm-archive-stays="v1"';
    const CYCLO = 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/';
    const SRUB = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/';
    const HUB = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';

    /** WP may store a plain hyphen while wptexturize renders the observed en dash. */
    public static function title_allowed($value) {
        if (!is_string($value)) return false;
        $text = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return $text === self::TITLE
            || $text === 'Rybaření s ubytováním - Jihomoravský kraj';
    }

    private static function eligible() {
        foreach (array('is_single','is_singular','get_queried_object_id',
            'is_admin','is_preview','home_url','get_permalink','get_post') as $f) {
            if (!function_exists($f)) return false;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || !is_single(self::SLUG) || !is_singular('post')
            || is_admin() || is_preview()
            || (function_exists('is_feed') && is_feed())
            || (function_exists('is_paged') && is_paged())
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return false;
        $id = (int)get_queried_object_id();
        if ($id <= 0 || get_permalink($id) !== self::URL) return false;
        $p = get_post($id);
        return is_object($p) && (int)($p->ID ?? 0) === $id
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && is_string($p->post_date_gmt ?? null)
            && substr($p->post_date_gmt, 0, 10) === '2017-07-04'
            && is_string($p->post_content ?? null)
            && strlen($p->post_content) >= 80
            && self::title_allowed($p->post_title ?? null);
    }

    public static function block() {
        return '<section '.self::MARKER
            .' aria-label="Vybrat ubytování u vody na jižní Moravě"'
            .' style="padding:20px;margin:18px 0 24px;border:2px solid #a9cdb7;'
            .'border-left:6px solid #246a49;border-radius:14px;background:#f2f8f3;'
            .'color:#1b3430;box-shadow:0 4px 14px rgba(0,0,0,.06)">'
            .'<p style="font-size:13px;font-weight:700;letter-spacing:.04em;margin:0 0 8px;'
            .'text-transform:uppercase;color:#205d43">Jižní Morava · rybářská dovolená</p>'
            .'<h2 style="margin:0 0 10px;color:#164532">Dvě konkrétní možnosti u Vranovské přehrady</h2>'
            .'<p style="margin:0 0 15px;line-height:1.55">Původní seznam níže obsahuje starší články. '
            .'Pokud chcete opravdu řešit pobyt, začněte dvěma samostatnými objekty, '
            .'u nichž již máme ověřené odkazy na konkrétní nabídky. '
            .'Nejde o balíčky s povolenkou ani o záruku dostupného rybaření.</p>'
            .'<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px">'
            .'<article style="border:1px solid #cee2d5;border-radius:11px;padding:15px;'
            .'background:white"><p style="margin:0 0 5px;color:#4b6557;font-size:13px">'
            .'Štítary · Vranovsko</p><h3 style="margin:0 0 8px;color:#163d30">Cyklochata Vranov</h3>'
            .'<p style="margin:0 0 12px">Zázemí pro skupinový pobyt. '
            .'Termín a cenu ověřte u konkrétní nabídky.</p>'
            .'<a href="'.self::CYCLO.'" style="display:inline-block;background:#246a49;'
            .'color:#fff;padding:11px 14px;border-radius:8px;font-weight:700;'
            .'text-decoration:none">Prohlédnout Cyklochatu</a></article>'
            .'<article style="border:1px solid #cee2d5;border-radius:11px;padding:15px;'
            .'background:white"><p style="margin:0 0 5px;color:#4b6557;font-size:13px">'
            .'Lančov · Vranovsko</p><h3 style="margin:0 0 8px;color:#163d30">Rybářský wellness srub</h3>'
            .'<p style="margin:0 0 12px">Wellness zázemí; nižší hladina přehrady může '
            .'výrazně omezit možnosti lovu.</p>'
            .'<a href="'.self::SRUB.'" style="display:inline-block;background:#246a49;'
            .'color:#fff;padding:11px 14px;border-radius:8px;font-weight:700;'
            .'text-decoration:none">Prohlédnout wellness srub</a></article>'
            .'</div><p style="margin:15px 0 8px">Nehodí se vám Vranov? '
            .'<a href="'.self::HUB.'"><strong>Porovnejte další rybářské pobyty v ČR</strong></a>.</p>'
            .'<p style="margin:0;font-size:13px;color:#425b50">Všechny tři odkazy vedou '
            .'nejprve na Na-Ryby.eu. Ubytování, povolenku, přístup k vodě, '
            .'aktuální ceny i podmínky rezervace kontrolujte samostatně. '
            .'Starší informace níže nemusí platit.</p></section>';
    }

    private static function has_old_list($html) {
        // Observed anonymous live 27 Nov 2026: 2017 south-Moravian list,
        // Zukalův and Popůvky entries in a Revíry / Ubytování archive.
        return is_string($html) && strlen($html) > 4000
            && strpos($html, 'Zukal') !== false
            && strpos($html, 'Popůvky') !== false
            && strpos($html, 'Ubytování') !== false
            && strpos($html, self::MARKER) === false
            && strpos($html, 'data-nry-jihocesky-stays') === false;
    }

    public static function transform($html) {
        if (!self::has_old_list($html)) return $html;
        $panel = self::block();
        if (substr_count($panel, self::MARKER) !== 1
            || substr_count($panel, 'href="'.self::CYCLO.'"') !== 1
            || substr_count($panel, 'href="'.self::SRUB.'"') !== 1
            || substr_count($panel, 'href="'.self::HUB.'"') !== 1) return $html;
        $result = $panel.$html;
        return substr_count($result, self::MARKER) === 1
            && substr($result, strlen($panel)) === $html ? $result : $html;
    }

    public static function render($html) {
        if (!self::eligible() || !function_exists('is_main_query')
            || !function_exists('in_the_loop') || !is_main_query() || !in_the_loop()) return $html;
        return self::transform($html);
    }

    // Only fallback to a bounded complete HTML buffer where the old theme
    // renders outside normal the_content / main loop. Insert after one exact H1.
    public static function transform_document($html) {
        if (!self::has_old_list($html)) return $html;
        $m = array();
        if (preg_match_all('~<h1\b[^>]{0,400}>([\s\S]{1,700}?)</h1>~iu', $html,
            $m, PREG_OFFSET_CAPTURE) !== 1) return $html;
        $raw = $m[1][0][0];
        if (preg_match('~\A<span>([^<>]+)</span>\z~u', $raw, $inside)) $raw = $inside[1];
        if (strpos($raw, '<') !== false || strpos($raw, '>') !== false) return $html;
        $title = trim(preg_replace('~[\s\x{00A0}]+~u', ' ',
            html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($title !== self::TITLE) return $html;
        $at = $m[0][0][1] + strlen($m[0][0][0]);
        $old = strpos($html, 'Zukal', $at);
        if ($old === false || $old - $at > 18000) return $html;
        $panel = self::block();
        $result = substr($html, 0, $at).$panel.substr($html, $at);
        return substr_count($result, self::MARKER) === 1
            && str_replace($panel, '', $result) === $html ? $result : $html;
    }

    public static function buffer() {
        if (self::eligible() && function_exists('ob_start') && !headers_sent())
            ob_start(array(__CLASS__, 'transform_document'));
    }

    /** Safe diagnostic: booleans only, only on owner-requested GET. No raw WP content. */
    public static function probe_footer() {
        if (($_GET['nry_jm_archive_probe'] ?? '') !== 'source_v1'
            || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || !function_exists('get_queried_object_id') || !function_exists('get_post')
            || !function_exists('get_permalink') || !function_exists('is_single')
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !is_single(self::SLUG)) return;
        $id = (int)get_queried_object_id();
        $p = $id > 0 ? get_post($id) : null;
        $status = array(
            'status' => 'NRY_JM_ARCHIVE_ELIGIBILITY_V1',
            'singleSlug' => is_single(self::SLUG),
            'permalink' => $id > 0 && get_permalink($id) === self::URL,
            'post' => is_object($p),
            'published' => is_object($p) && ($p->post_status ?? '') === 'publish',
            'typePost' => is_object($p) && ($p->post_type ?? '') === 'post',
            'slugMatches' => is_object($p) && ($p->post_name ?? '') === self::SLUG,
            'date20170704' => is_object($p) && substr((string)($p->post_date_gmt ?? ''),0,10) === '2017-07-04',
            'titleAllowed' => is_object($p) && self::title_allowed($p->post_title ?? null),
            'rawSourceNonempty' => is_object($p) && is_string($p->post_content ?? null)
                && strlen($p->post_content) >= 80,
            'eligible' => self::eligible(),
            'wordpressWrites' => 0,
            'syntheticClicks' => 0,
        );
        echo "\n<!-- NRY_JM_ARCHIVE_PROBE_V1 "
            .json_encode($status, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            ." -->\n";
    }

    public static function click_js() {
        if (self::eligible() && function_exists('wp_enqueue_script')
            && function_exists('plugins_url')) {
            wp_enqueue_script('nry-archive-stay-click-v1',
                plugins_url('assets/nry-archive-stay-click-v1.js', __FILE__),
                array(), '1.0.0', true);
        }
    }

    public static function boot() {
        // Do not load site hooks in partial WordPress offline test harnesses.
        if (function_exists('is_single') && function_exists('get_post')
            && function_exists('add_filter') && function_exists('add_action')) {
            add_filter('the_content', array(__CLASS__, 'render'), 98);
            add_action('template_redirect', array(__CLASS__, 'buffer'), 20);
            add_action('wp_footer', array(__CLASS__, 'probe_footer'), 99);
            add_action('wp_enqueue_scripts', array(__CLASS__, 'click_js'), 29);
        }
    }
}
NRY_South_Moravia_Stays_Archive_V1::boot();
