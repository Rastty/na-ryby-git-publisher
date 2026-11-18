<?php
/**
 * Na-Ryby Buyer Offer Click Measurement V1 (consent-aware).
 * Exact buyer guides including one source-pinned baitboat entry handoff.
 * Bivak guides are read-only observations: older CJ IDs and OKfish Dognet
 * links are not treated as evidence of commission ownership.
 * Advisor is separately
 * WordPress page with its own funnel events; do not instrument it again here.
 * No new tracking cookies, visitor IDs,
 * redirects, affiliate click probes, private content or WordPress mutations.
 */
if (!defined('ABSPATH')) { exit; }

function nry_buyer_click_approved_scope($post_id, $public_path) {
    $allowed = array(
        42401 => '/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/',
        118218 => '/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/',
        35755 => '/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/',
        36702 => '/pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu/',
        46573 => '/pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare/',
        118455 => '/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/',
        118553 => '/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/',
        60637 => '/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/',
        36518 => '/porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023/',
        49275 => '/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/',
        61257 => '/zavazeci-lodky-pro-rybare/',
        118541 => '/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/',
        60341 => '/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/',
        11901 => '/jak-vybrat-rybarske-kreslo/',
        118388 => '/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/',
        119575 => '/nejlepsi-rybarske-bivaky-do-10-000-kc/',
        // Explicit source-pinned fishing-stay cohorts; not a sitewide allowlist.
        59891 => '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/',
        14517 => '/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/',
        12166 => '/rybarska-dovolena-jizni-morava/',
        59271 => '/rodinna-dovolena-u-vody-kam-na-ryby-s-detmi/'
    );
    return is_int($post_id) && isset($allowed[$post_id])
        && is_string($public_path) && $public_path === $allowed[$post_id];
}

function nry_buyer_click_enqueue() {
    if (!function_exists('is_singular') || !is_singular('post')
        || !function_exists('get_queried_object_id')
        || !function_exists('get_permalink')
        || !function_exists('wp_enqueue_script')
        || !function_exists('plugins_url')) return;
    $post_id = (int) get_queried_object_id();
    $permalink = get_permalink($post_id);
    if (!is_string($permalink)) return;
    $url = parse_url($permalink);
    if (!is_array($url) || ($url['scheme'] ?? '') !== 'https'
        || ($url['host'] ?? '') !== 'na-ryby.eu'
        || !empty($url['query']) || !empty($url['fragment'])
        || !nry_buyer_click_approved_scope($post_id, $url['path'] ?? '')) return;
    wp_enqueue_script(
        'nry-buyer-offer-click-v1',
        plugins_url('assets/nry-buyer-clicks-v1.js', __FILE__),
        array(),
        '1.11.3',
        true
    );
}
/**
 * Source-reviewed UX: make the five already-published fishing-stay links
 * obvious without rewriting article content, hrefs, tracking or inventory.
 * The extra hook is independent of the existing buyer click observer.
 */
final class NRY_Fishing_Stays_CTA_59891_V1 {
    const ID = 59891;
    const SLUG = 'rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare';
    const URL = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const MARKER = 'data-nry-editorial-edit="fishing-stays-cz-59891-20261118-v1"';
    const OFFER_IDS = array(
        '2462030', 'hportal-pension-u-vlcku', 'hportal-penzion-janata',
        'hportal-penzion-u-kohoutu', 'hportal-pension-rybarska-basta'
    );
    // The raw WordPress block can change harmlessly after KSES/paragraph
    // formatting. Only five exact, reviewed destinations may be emphasized.
    const APPROVED_HREFS = array(
        'https://www.slevomat.cz/akce/2462030-statek-na-jihu-cech-snidane-i-privatni-sauna-ci-rybareni?utm_source=affiliate&utm_medium=cpc&utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_narybyCZ&utm_content=stay_59891_jedraz&utm_term=j3d7tsgc&a_box=j3d7tsgc',
        'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fulice%2Fpension-u-vlcku%2F',
        'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Frabyne%2Fpenzion-janata%2F',
        'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F',
        'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Frozmberk-nad-vltavou%2Fpension-rybarska-basta%2F'
    );

    public static function eligible() {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int) get_queried_object_id() !== self::ID
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;

        $post = get_post(self::ID);
        if (!is_object($post) || (int) ($post->ID ?? 0) !== self::ID
            || ($post->post_type ?? '') !== 'post'
            || ($post->post_status ?? '') !== 'publish'
            || ($post->post_name ?? '') !== self::SLUG
            || !is_string($post->post_content ?? null)) return false;
        $raw = $post->post_content;
        if (substr_count($raw, self::MARKER) !== 1) return false;
        $start = strpos($raw, self::MARKER);
        if ($start === false) return false;
        $closing = strpos($raw, '</section>', $start);
        if ($closing === false) return false;
        $block = html_entity_decode(substr($raw, $start,
            $closing + strlen('</section>') - $start), ENT_QUOTES, 'UTF-8');
        foreach (self::OFFER_IDS as $id) {
            if (substr_count($block, 'data-nry-offer-id="' . $id . '"') !== 1) return false;
        }
        foreach (self::APPROVED_HREFS as $href) {
            // A different publisher ID, offer destination, or extra placement
            // prevents the enhancement; CSS never modifies those links.
            if (substr_count($block, $href) !== 1) return false;
        }
        return true;
    }

    public static function enqueue() {
        if (!self::eligible() || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-fishing-stays-cta-59891-v1',
            plugins_url('assets/nry-fishing-stays-cta-59891-v1.css', __FILE__),
            array(), '1.0.0');
    }
}
// Keep the existing event script callback registered last for older PHP
// contract tests; WordPress execution order follows the explicit priorities.
add_action('wp_enqueue_scripts', 'NRY_Fishing_Stays_CTA_59891_V1::enqueue', 30);
add_action('wp_enqueue_scripts', 'nry_buyer_click_enqueue', 24);
