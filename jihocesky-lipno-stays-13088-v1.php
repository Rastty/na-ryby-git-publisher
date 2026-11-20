<?php
/**
 * Two independently verified accommodation choices on the old South Bohemia
 * fishing/stays index. Only adds a visible section before its historical table.
 * Does not modify stored posts, cookies, redirects, prices or click tracking.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Jihocesky_Lipno_Stays_13088_V1 {
    const ID = 13088;
    const SLUG = 'rybareni-s-ubytovanim-jihocesky-kraj';
    const URL = 'https://na-ryby.eu/rybareni-s-ubytovanim-jihocesky-kraj/';
    const MODIFIED_GMT = '2017-07-04 10:16:21';
    // First 512 bytes of anonymous REST content.rendered verified 19 Nov 2026.
    // The old index expands dynamic [su_posts] lists. Drift fails closed.
    const PREFIX_BYTES = 512;
    const PREFIX_SHA256 = '3778effd5b7c012dfeedbc3cc2f1e13f07769efd805fabaf6996535c292bb801';
    // Anonymous public td-post-content bytes captured 20 Nov 2026 via PR #321,
    // distinct from REST because [su_posts] rendering depends on context.
    // Both pins remain exact digests; all original anchors and identity still required.
    const FRONTEND_PREFIX_SHA256 = 'b09da61b0ae9b652455f18e563414fc2573f473ab9c088f55097d927999f4385';
    // Exact input to the_content observed in live bounded render guard on
    // protected FTPS release #44798893816: 12916 bytes, 7/7 original anchors,
    // eligible=true, mainQuery=true, inLoop=true, zero WP writes.
    const LOOP_RENDER_PREFIX_SHA256 = '269e3a69fea8eda490c4e779c89b90c9ffff95a1921036dcd614a08a24e1488c';
    const MARKER = 'data-nry-jihocesky-stays="v1"';
    const HPORTAL = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F';
    const HAUZI = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fkomfortny-penzion-so-saunou-na-lipne';
    const STAYS = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), PHP_INT_MAX);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 28);
            // Diagnostic comment is emitted only on explicit exact-post GET.
            add_action('wp_footer', array(__CLASS__, 'probe_footer'), PHP_INT_MAX);
        }
    }

    private static function eligible($loop = true) {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id() !== self::ID
            || ($loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post' && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null)
            && strlen($p->post_content) > 100;
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-jihocesky-lipno-stays-13088-v1',
            plugins_url('assets/nry-jihocesky-lipno-stays-13088-v1.css', __FILE__),
            array(), '1.0.0');
    }

    public static function block() {
        return '<section class="nry-jc-stays" '.self::MARKER.' aria-label="Dvě možnosti ubytování u Lipna">'
            .'<p class="nry-jc-stays__eyebrow">Jižní Čechy · konkrétní ubytování</p>'
            .'<h2>Na ryby k Lipnu a kde přespat?</h2>'
            .'<p class="nry-jc-stays__lead">Při plánování rybářského víkendu řešte dvě věci zvlášť: '
            .'ubytování a oprávnění lovit na konkrétním revíru. Níže jsou dvě konkrétní možnosti '
            .'v okolí Lipna, nikoli pobytové balíčky s povolenkou.</p>'
            .'<div class="nry-jc-stays__grid">'
            .'<article class="nry-jc-stays__offer" data-nry-jc-offer="kohoutu">'
            .'<p class="nry-jc-stays__place">Horní Planá · Lipno</p>'
            .'<h3>Pension U Kohoutů</h3>'
            .'<p>Hportal uvádí penzion v Horní Plané a rybaření mezi aktivitami v okolí. '
            .'Neuvádí tím automaticky místo lovu, povolenku ani rybaření v ceně ubytování.</p>'
            .'<a class="nry-jc-stays__cta" href="'.htmlspecialchars(self::HPORTAL, ENT_QUOTES, 'UTF-8').'"'
            .' target="_blank" rel="sponsored nofollow noopener">Prověřit Pension U Kohoutů</a>'
            .'</article>'
            .'<article class="nry-jc-stays__offer" data-nry-jc-offer="vyhlidka">'
            .'<p class="nry-jc-stays__place">Přední Výtoň · Lipno</p>'
            .'<h3>Pension Vyhlídka</h3>'
            .'<p>Hauzi jej řadí také do výběru ubytování na Lipně pro rybáře; '
            .'nabízí pokoje i chaty. Přístup k vybrané vodě a podmínky lovu si ověřte samostatně.</p>'
            .'<a class="nry-jc-stays__cta" href="'.htmlspecialchars(self::HAUZI, ENT_QUOTES, 'UTF-8').'"'
            .' target="_blank" rel="sponsored nofollow noopener">Prověřit Pension Vyhlídka</a>'
            .'</article></div>'
            .'<p class="nry-jc-stays__more">Hledáte jiné místo? '
            .'<a href="'.self::STAYS.'">Projděte další rybářské pobyty</a>.</p>'
            .'<p class="nry-jc-stays__notice"><small>Partnerské odkazy · nabídky prověřeny 19. 11. 2026. '
            .'Cena, volné termíny a vybavení se mění. Na svazových vodách ověřte rybářský lístek '
            .'a příslušnou povolenku; na soukromých vodách pravidla provozovatele. '
            .'Tabulka níže je starší rozcestník na články, nikoli ověřený katalog aktuálních rezervací.</small></p>'
            .'</section>';
    }

    public static function source_prefix_allowed($digest) {
        return is_string($digest)
            && (hash_equals(self::PREFIX_SHA256, $digest)
                || hash_equals(self::FRONTEND_PREFIX_SHA256, $digest)
                || hash_equals(self::LOOP_RENDER_PREFIX_SHA256, $digest));
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 7000
            || !self::source_prefix_allowed(hash('sha256', substr($html, 0, self::PREFIX_BYTES)))
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::HPORTAL) !== false
            || strpos($html, self::HAUZI) !== false
            || strpos($html, self::STAYS) !== false) return $html;
        foreach (array(
            '<div class="su-table su-table-alternate">',
            '<td>Revíry</td>',
            '<td>Ubytování</td>',
            'id="su-post-17530"',
            'id="su-post-17753"',
            'https://na-ryby.eu/sportovni-rybolov-raselina/',
            'https://na-ryby.eu/apartmany-nadeje/',
        ) as $anchor) {
            if (substr_count($html, $anchor) < 1) return $html;
        }
        $block = self::block();
        if (substr_count($block, self::MARKER) !== 1
            || substr_count($block, htmlspecialchars(self::HPORTAL, ENT_QUOTES, 'UTF-8')) !== 1
            || substr_count($block, htmlspecialchars(self::HAUZI, ENT_QUOTES, 'UTF-8')) !== 1
            || substr_count($block, self::STAYS) !== 1) return $html;
        // Preserve every old entry, anchor, and shortcode expansion byte-for-byte.
        return $block."\n".$html;
    }

    private static $probe_seen = 0;
    private static $probe_first = null;
    private static $probe_best = null;
    private static $probe_best_score = -1;

    private static function probe_requested() {
        return isset($_GET['nry_jc_guard_probe'])
            && $_GET['nry_jc_guard_probe'] === 'source_v1';
    }

    private static function probe_snapshot($html) {
        if (!self::probe_requested()) return;
        self::$probe_seen++;
        if (!is_string($html)) return;
        $anchors = array(
            '<div class="su-table su-table-alternate">',
            '<td>Revíry</td>',
            '<td>Ubytování</td>',
            'id="su-post-17530"',
            'id="su-post-17753"',
            'https://na-ryby.eu/sportovni-rybolov-raselina/',
            'https://na-ryby.eu/apartmany-nadeje/',
        );
        $mask = 0;
        $score = 0;
        foreach ($anchors as $index => $anchor) {
            if (strpos($html, $anchor) !== false) {
                $mask |= (1 << $index);
                $score++;
            }
        }
        $digest = hash('sha256', substr($html, 0, self::PREFIX_BYTES));
        $row = array(
            'bytes' => strlen($html),
            'prefixSha256' => $digest,
            'prefixAllowed' => self::source_prefix_allowed($digest),
            'mainQuery' => function_exists('is_main_query') && is_main_query(),
            'inLoop' => function_exists('in_the_loop') && in_the_loop(),
            'eligible' => self::eligible(),
            'anchorMask' => $mask,
            'alreadyContainsBlock' => strpos($html, self::MARKER) !== false,
            'alreadyContainsStaysLink' => strpos($html, self::STAYS) !== false,
        );
        if (self::$probe_first === null) self::$probe_first = $row;
        if ($score > self::$probe_best_score) {
            self::$probe_best_score = $score;
            self::$probe_best = $row;
        }
    }

    public static function probe_footer() {
        if (!self::probe_requested() || !self::eligible(false)) return;
        // All values are bounded predicates, lengths or digests. Never page
        // content, cookies, visitor identifiers, credentials or click events.
        $proof = array(
            'status' => 'JIHOCESKY_13088_GUARD_PROBE_V1',
            'postId' => self::ID,
            'eligibleWithoutLoop' => true,
            'renderCalls' => self::$probe_seen,
            'first' => self::$probe_first,
            'best' => self::$probe_best,
            'wordpressWrites' => 0,
            'merchantRequests' => 0,
            'syntheticClicks' => 0,
        );
        echo "\n<!-- NRY_JC13088_PROBE_V1 "
            .json_encode($proof, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            ." -->\n";
    }

    public static function render($html) {
        self::probe_snapshot($html);
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Jihocesky_Lipno_Stays_13088_V1::boot();
