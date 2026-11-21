<?php
/**
 * Homepage buyer-route presentation + passive click measurement.
 * Only enqueue on the exact published homepage; do not change editorial HTML,
 * merchant links, consent, affiliate routing or WordPress options.
 */
if (!defined('ABSPATH')) { exit; }

function nry_home_buyer_entry_enqueue_v1() {
    if (!function_exists('is_front_page') || !is_front_page()
        || !function_exists('is_admin') || is_admin()
        || !function_exists('is_preview') || is_preview()
        || !function_exists('is_page') || !is_page(118641)
        || !function_exists('get_queried_object_id') || (int) get_queried_object_id() !== 118641
        || !function_exists('get_post') || !function_exists('get_permalink')
        || get_permalink(118641) !== 'https://na-ryby.eu/'
        || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
        || !function_exists('wp_enqueue_script') || !function_exists('wp_enqueue_style')
        || !function_exists('plugins_url')) return;

    $post = get_post(118641);
    if (!is_object($post) || (int) ($post->ID ?? 0) !== 118641
        || ($post->post_type ?? '') !== 'page'
        || ($post->post_status ?? '') !== 'publish'
        || ($post->post_name ?? '') !== 'na-ryby-eu-vse-pro-rybare') return;

    wp_enqueue_script(
        'nry-home-buyer-entry-v1',
        plugins_url('assets/nry-home-buyer-entry-v1.js', __FILE__),
        array(),
        '1.2.0',
        true
    );
    wp_enqueue_style(
        'nry-home-buyer-entry-style-v1',
        plugins_url('assets/nry-home-buyer-entry-v1.css', __FILE__),
        array(),
        '1.1.0',
        'all'
    );
}
add_action('wp_enqueue_scripts', 'nry_home_buyer_entry_enqueue_v1', 25);
