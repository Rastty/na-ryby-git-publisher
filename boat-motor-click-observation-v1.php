<?php
/**
 * Two source-pinned purchase-guide click cohorts. GA4 consent is owned by
 * the existing site gtag setup. This file never modifies stored post content,
 * creates redirects, rewrites affiliate URLs or initiates telemetry itself.
 */
if (!defined('ABSPATH')) { exit; }

function nry_boat_motor_click_enqueue() {
    if (!function_exists('is_singular') || !is_singular('post')
        || !function_exists('get_queried_object_id') || !function_exists('get_post')
        || !function_exists('get_permalink') || !function_exists('home_url')
        || !function_exists('wp_enqueue_script') || !function_exists('plugins_url')
        || !function_exists('is_admin') || is_admin()
        || !function_exists('is_preview') || is_preview()
        || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return;

    $allowed = array(
        119594 => array('nejlepsi-lodni-elektromotory-pro-rybare', '2026-05-15 10:37:45', 'Baterie k elektromotoru: olovo, AGM nebo LiFePO4?'),
        119598 => array('nejlepsi-nafukovaci-cluny-pro-rybare', '2026-05-15 12:17:30', 'Nosnost: proč nestačí počítat jen lidi'),
    );
    $id = (int)get_queried_object_id();
    if (!isset($allowed[$id])) return;
    list($slug, $modified, $anchor) = $allowed[$id];
    $post = get_post($id);
    if (!is_object($post) || (int)($post->ID ?? 0) !== $id
        || ($post->post_type ?? '') !== 'post' || ($post->post_status ?? '') !== 'publish'
        || ($post->post_name ?? '') !== $slug
        || ($post->post_modified_gmt ?? '') !== $modified
        || !is_string($post->post_content ?? null)
        || strlen($post->post_content) < 18000
        || substr_count($post->post_content, 'click-100927011-12347575') !== 13
        || strpos($post->post_content, $anchor) === false
        || get_permalink($id) !== 'https://na-ryby.eu/'.$slug.'/') return;

    wp_enqueue_script('nry-boat-motor-click-v1',
        plugins_url('assets/nry-boat-motor-click-v1.js', __FILE__),
        array(), '1.0.0', true);
}

if (function_exists('add_action') && function_exists('is_singular')) {
    add_action('wp_enqueue_scripts', 'nry_boat_motor_click_enqueue', 26);
}
