<?php
/**
 * One-page first-party advisor buyer-guide handoff telemetry.
 * Existing advisor V4 engine shows source-bound internal buying guides when
 * verified offers are expired. This JS measures real guide-link clicks ONLY.
 * No new affiliate href, WP write, content rewrite, tracking cookie or endpoint.
 */
if (!defined('ABSPATH')) { exit; }

function nry_advisor_guide_click_enqueue() {
    if (!function_exists('is_page') || !is_page(119865)
        || !function_exists('is_singular') || !is_singular('page')
        || !function_exists('get_queried_object_id')
        || !function_exists('get_permalink')
        || !function_exists('home_url')
        || !function_exists('plugins_url')
        || !function_exists('wp_enqueue_script')) return;
    $post_id=(int)get_queried_object_id();
    if ($post_id!==119865
        || rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
        || get_permalink($post_id)!=='https://na-ryby.eu/poradce-vybavy/')
        return;
    wp_enqueue_script(
        'nry-advisor-guide-click-v1',
        plugins_url('assets/nry-advisor-guide-click-v1.js', __FILE__),
        array(),
        '1.0.0',
        true
    );
}
if (function_exists('add_action')) {
    add_action('wp_enqueue_scripts','nry_advisor_guide_click_enqueue',25);
}
