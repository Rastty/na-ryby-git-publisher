<?php
/**
 * Na-Ryby Buyer Offer Click Measurement V1 (consent-aware).
 * Nine buyer guides and one exact practical sonar post. Advisor is separately
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
        49275 => '/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/'
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
        '1.3.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'nry_buyer_click_enqueue', 24);
