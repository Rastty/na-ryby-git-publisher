<?php
/**
 * Na-Ryby Buyer Offer Click Measurement V1 (consent-aware).
 * Only on two exact existing public posts. No new tracking cookies, visitor IDs,
 * redirects, affiliate click probes, private content or WordPress mutations.
 */
if (!defined('ABSPATH')) { exit; }

function nry_buyer_click_approved_scope($post_id, $public_path) {
    $allowed = array(
        42401 => '/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/',
        118218 => '/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'
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
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'nry_buyer_click_enqueue', 24);
