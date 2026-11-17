<?php
/**
 * Three optional one-click entry scenarios for the existing equipment advisor.
 * Page-only UI enhancement; the existing source-pinned engine still decides.
 * No product, affiliate, stock, price, post or WordPress option changes.
 */
if (!defined('ABSPATH')) { exit; }

function nry_advisor_scenario_shortcuts_enqueue() {
    if (!function_exists('is_page') || !is_page(119865)
        || !function_exists('is_singular') || !is_singular('page')
        || !function_exists('get_queried_object_id')
        || !function_exists('get_permalink')
        || !function_exists('home_url')
        || !function_exists('plugins_url')
        || !function_exists('wp_enqueue_script')
        || !function_exists('wp_enqueue_style')) return;
    if ((int) get_queried_object_id() !== 119865
        || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
        || get_permalink(119865) !== 'https://na-ryby.eu/poradce-vybavy/') return;
    wp_enqueue_style('nry-advisor-scenarios-v1',
        plugins_url('assets/nry-advisor-scenarios-v1.css', __FILE__),
        array(), '1.0.0');
    wp_enqueue_script('nry-advisor-scenarios-v1',
        plugins_url('assets/nry-advisor-scenarios-v1.js', __FILE__),
        array(), '1.0.0', true);
}
if (function_exists('add_action')) {
    add_action('wp_enqueue_scripts', 'nry_advisor_scenario_shortcuts_enqueue', 26);
}
