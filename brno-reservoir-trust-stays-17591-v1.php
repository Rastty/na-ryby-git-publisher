<?php
/**
 * Source-locked public repair for the historical 2018 Brno reservoir chalet.
 * Separates the original, unverified property from two genuine nearby
 * accommodation alternatives on already approved partner networks.
 * Frontend only: no WordPress writes, merchant GETs or fabricated clicks.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Brno_Reservoir_Stays_17591_V1 {
    const ID = 17591;
    const SLUG = 'chata-brnenska-prehrada';
    const URL = 'https://na-ryby.eu/chata-brnenska-prehrada/';
    const MODIFIED_GMT = '2018-01-22 19:53:18';
    // Publicly verified, raw WordPress SHA from GET-only proof in PR #278.
    const RAW_SHA256 = '28dcd7c75cd173508727082c1b7f69a322211cde3acf0ef55fed7136b215a261';
    // First 512 BYTES of anonymous WordPress REST content.rendered, 19 Nov 2026.
    const PREFIX_SHA256 = 'dd1eba5e105271ee254a0f3cecc9553172ad51f7070c4715acc75a882a6d6b33';
    const MARKER = 'data-nry-brno-stays="v1"';
    const ANCHOR = 'nry-brno-stays-17591';
    const HAUZI = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fchata-u-brnenske-prehrady';
    const HPORTAL = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fbrno%2Fhotel-kozi-horka%2F';
    const ORIGINAL_CTA_URL = 'href="https://na-ryby.eu/chata-brnenska"';
    const OLD_CTA = 'NEJLEVNĚJŠÍ NABÍDKA';
    // The unchanged original article has TWO obsolete self-linked buttons:
    // one ordinary date CTA and one unsupported cheapest-offer superlative.
    const NORMALIZED_CTA = 'Volné termíny a další informace';
    const BAD_TITLE = 'Chata Brněnská přehrada &#8211; Vranovská přehrada';
    const OLD_PERMIT = '<div>možnost zakoupení povolenky 300 Kč / týden</div>';
    const OLD_PRICE = '<span class="complete-price">2 420 Kč</span>';

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
            && ($post->post_type ?? '') === 'post' && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($post->post_content ?? null)
            && hash_equals(self::RAW_SHA256, hash('sha256', $post->post_content));
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-brno-reservoir-stays-17591-v1',
            plugins_url('assets/nry-brno-reservoir-stays-17591-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function offer($id, $name, $description, $url, $cta) {
        return '<article data-nry-offer-id="'.$id.'"><h3>'.$name.'</h3>'
            .'<p>'.$description.'</p><p><a class="nry-brno-stays__cta" href="'
            .htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            .'" target="_blank" rel="sponsored nofollow noopener">'.$cta.'</a></p></article>';
    }

    public static function block() {
        return '<section id="'.self::ANCHOR.'" class="nry-brno-stays" '.self::MARKER.' aria-label="Aktuální orientace v ubytování u Brněnské přehrady">'
            .'<p class="nry-brno-stays__eyebrow">Brněnská přehrada · ubytování a rybaření</p>'
            .'<h2>Hledáte chatu u Brněnské přehrady? Nejdříve si ověřte termín a revír</h2>'
            .'<p><strong>Důležité:</strong> Původní chata popsaná níže je historický záznam z ledna 2018. '
            .'Její aktuální dostupnost, cena ani možnost získat rybářskou povolenku nejsou ověřené. '
            .'Brněnská a Vranovská přehrada jsou dvě odlišná místa; starší nadpisy je chybně směšovaly.</p>'
            .'<p>Chcete porovnat <strong>jiné ubytování v okolí</strong>? Následující dvě nabídky nejsou původní chata '
            .'a ani jedna sama o sobě nezaručuje místo pro lov, platnou povolenku nebo volné datum.</p>'
            .'<div class="nry-brno-stays__grid">'
            .self::offer('hauzi-chata-u-brnenske-prehrady', 'Chata u Brněnské přehrady – Hauzi',
                'Jiný objekt v Brně-Bystrci, uváděný až pro 6 hostů. Podle nabídky přibližně 2 km od přehrady; konkrétní polohu a volný termín ověřte na stránce ubytování.',
                self::HAUZI, 'Ověřit chatu na Hauzi')
            .self::offer('hportal-hotel-kozi-horka', 'Hotel Kozí Horka – Hportal',
                'Další možnost pobytu u Brněnské přehrady; poskytovatel uvádí rybaření mezi okolními aktivitami. Podmínky lovu i varianty pokojů ověřte samostatně.',
                self::HPORTAL, 'Ověřit hotel na Hportalu')
            .'</div><p class="nry-brno-stays__disclaimer"><small>Partnerské odkazy; nabídky zkontrolovány 19. 11. 2026. '
            .'Ceny, dostupnost a pravidla rybolovu se mohou změnit. Před cestou ověřte správce revíru a potřebnou povolenku.</small></p>'
            .'</section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 8500
            || hash('sha256', substr($html, 0, 512)) !== self::PREFIX_SHA256
            || strpos($html, self::MARKER) !== false
            || substr_count($html, self::BAD_TITLE) !== 3
            || substr_count($html, self::OLD_PERMIT) !== 1
            || substr_count($html, self::OLD_PRICE) !== 1
            || substr_count($html, self::ORIGINAL_CTA_URL) !== 2
            || substr_count($html, self::OLD_CTA) !== 1
            || substr_count($html, self::NORMALIZED_CTA) !== 1
            || strpos($html, self::HAUZI) !== false
            || strpos($html, self::HPORTAL) !== false) return $html;
        $changed = str_replace(self::BAD_TITLE, 'Chata Brněnská přehrada', $html, $count_title);
        $changed = str_replace(self::OLD_PERMIT,
            '<div>Starší údaj o povolence 300 Kč/týden pochází z roku 2018 a není ověřen. Aktuální pravidla a cenu zjistěte u správce revíru.</div>',
            $changed, $count_permit);
        $changed = str_replace(self::OLD_PRICE,
            '<span class="complete-price">2 420 Kč (archiv 2018; aktuální cena neověřena)</span>',
            $changed, $count_price);
        $changed = str_replace(self::ORIGINAL_CTA_URL, 'href="#'.self::ANCHOR.'"', $changed, $count_href);
        $changed = str_replace(self::OLD_CTA, 'OVĚŘIT UBYTOVÁNÍ V OKOLÍ', $changed, $count_old_cta);
        $changed = str_replace(self::NORMALIZED_CTA, 'POROVNAT DALŠÍ CHATU A HOTEL', $changed, $count_normalized_cta);
        $changed = self::block()."\n".$changed;
        return ($count_title === 3 && $count_permit === 1 && $count_price === 1
            && $count_href === 2 && $count_old_cta === 1 && $count_normalized_cta === 1
            && substr_count($changed, self::MARKER) === 1
            && substr_count($changed, 'data-nry-offer-id=') === 2
            && substr_count($changed, htmlspecialchars(self::HAUZI, ENT_QUOTES, 'UTF-8')) === 1
            && substr_count($changed, htmlspecialchars(self::HPORTAL, ENT_QUOTES, 'UTF-8')) === 1
            && strpos($changed, self::BAD_TITLE) === false
            && strpos($changed, self::OLD_CTA) === false
            && strpos($changed, self::NORMALIZED_CTA) === false)
            ? $changed : $html;
    }

    public static function render($html) {
        // tagDiv single-content blocks can render the queried post outside
        // the global main Loop. The exact queried post ID + raw WP source SHA
        // and original rendered-prefix/literal guards still bind this to the
        // one reviewed article, even when is_main_query() is false here.
        return self::eligible(false) ? self::transform($html) : $html;
    }
}
NRY_Brno_Reservoir_Stays_17591_V1::boot();
