<?php
/**
 * Sitewide, source-bound fishing-stay discovery through one existing menu.
 * Keep the parent menu click to the live stays hub; add two already-published
 * first-party offer pages as submenu choices. Output-only, no stored menu,
 * WordPress content, affiliate URL or consent changes. Editor menu wins.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Stays_Main_Navigation_Bridge_V1 {
    const OLD_URL = 'https://na-ryby.eu/ubytovani-2/';
    const NEW_URL = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const EXPECTED_TITLE = 'Ubytování';
    const CYCLO = 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/';
    const WELLNESS = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/';
    const CLASS_NAME = 'nry-stays-menu-choice-v1';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('home_url')
            && function_exists('is_admin') && function_exists('is_preview')) {
            add_filter('wp_nav_menu_objects', array(__CLASS__, 'filter_menu'), 50, 2);
        }
        if (function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'click_js'), 26);
        }
    }

    private static function site_allowed() {
        return function_exists('home_url') && function_exists('is_admin')
            && function_exists('is_preview')
            && !is_admin() && !is_preview()
            && rtrim(home_url('/'), '/') === 'https://na-ryby.eu'
            && !(function_exists('is_customize_preview') && is_customize_preview());
    }

    public static function click_js() {
        if (!self::site_allowed() || !function_exists('wp_enqueue_script')
            || !function_exists('plugins_url')) return;
        wp_enqueue_script('nry-stays-menu-click-v1',
            plugins_url('assets/nry-stays-menu-click-v1.js', __FILE__),
            array(), '1.0.0', true);
    }

    public static function filter_menu($items, $args = null) {
        if (!is_array($items) || !self::site_allowed()) return $items;
        $matches = array();
        foreach ($items as $index => $item) {
            if (!is_object($item)) return $items;
            if (($item->title ?? null) !== self::EXPECTED_TITLE
                || ($item->url ?? null) !== self::OLD_URL
                || (string)($item->menu_item_parent ?? '') !== '0'
                || !isset($item->ID) || (int)$item->ID <= 0) continue;
            $matches[] = $index;
        }
        if (count($matches) !== 1) return $items;
        $index = $matches[0];
        $parent = $items[$index];
        $parent_id = (string)$parent->ID;
        $routes = array(
            array('Cyklochata Vranov', self::CYCLO),
            array('Wellness srub Vranov', self::WELLNESS),
        );
        // Existing children, a second menu bridge, editor links, or a duplicate
        // destination always win. Do not silently duplicate the site's nav.
        foreach ($items as $item) {
            if ((string)($item->menu_item_parent ?? '') === $parent_id) return $items;
            foreach ($routes as $route) {
                if (($item->url ?? '') === $route[1]) return $items;
            }
        }

        $result = $items;
        $result[$index] = clone $parent;
        $result[$index]->url = self::NEW_URL;
        $classes = is_array($parent->classes ?? null) ? $parent->classes : array();
        if (!in_array('menu-item-has-children', $classes, true)) {
            $classes[] = 'menu-item-has-children';
        }
        $result[$index]->classes = $classes;

        foreach ($routes as $offset => $route) {
            $child = new stdClass();
            $child->ID = -(2000000 + (int)$parent->ID * 10 + $offset);
            $child->db_id = 0;
            $child->menu_item_parent = $parent_id;
            $child->object_id = 0;
            $child->object = 'custom';
            $child->type = 'custom';
            $child->type_label = 'Custom Link';
            $child->title = $route[0];
            $child->url = $route[1];
            $child->target = '';
            $child->attr_title = '';
            $child->description = '';
            $child->xfn = '';
            $child->classes = array('menu-item', 'menu-item-type-custom', self::CLASS_NAME);
            $child->current = false;
            $child->current_item_ancestor = false;
            $child->current_item_parent = false;
            $child->menu_order = count($result) + 1;
            $result[] = $child;
        }
        return $result;
    }
}
NRY_Stays_Main_Navigation_Bridge_V1::boot();
