<?php
/**
 * Keep the legacy 2017 stays archive accessible while giving the main
 * Ubytovani navigation a current, verified fishing-stay entry page.
 * Frontend menu rendering only: no redirects, DB writes or affiliate events.
 * WP menu editor wins on any title or URL drift. Remove one require to revert.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Stays_Main_Navigation_Bridge_V1 {
    const OLD_URL = 'https://na-ryby.eu/ubytovani-2/';
    const NEW_URL = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const EXPECTED_TITLE = 'Ubytování';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('home_url')
            && function_exists('is_admin') && function_exists('is_preview')) {
            add_filter('wp_nav_menu_objects', array(__CLASS__, 'filter_menu'), 50, 2);
        }
    }

    public static function filter_menu($items, $args = null) {
        if (!is_array($items) || !function_exists('home_url')
            || !function_exists('is_admin') || !function_exists('is_preview')
            || is_admin() || is_preview() || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') {
            return $items;
        }
        if (function_exists('is_customize_preview') && is_customize_preview()) return $items;

        // One exact top-level entry per menu rendering. Never guess when the
        // editor or another plugin has changed a menu or duplicated the link.
        $matches = array();
        foreach ($items as $index => $item) {
            if (!is_object($item)
                || ($item->title ?? null) !== self::EXPECTED_TITLE
                || ($item->url ?? null) !== self::OLD_URL
                || !isset($item->menu_item_parent)
                || (string)$item->menu_item_parent !== '0') continue;
            $matches[] = $index;
        }
        if (count($matches) !== 1) return $items;

        $index = $matches[0];
        $items[$index] = clone $items[$index];
        $items[$index]->url = self::NEW_URL;
        return $items;
    }
}
NRY_Stays_Main_Navigation_Bridge_V1::boot();
