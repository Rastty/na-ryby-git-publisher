<?php
/**
 * Give readers three verified, useful routes from the old private-waters
 * archive menu. Preserve the parent's URL and all saved WordPress menu data.
 * Pure frontend rendering; revert by removing one require_once.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Revir_Main_Menu_Discovery_V1 {
    const OLD_URL = 'https://na-ryby.eu/category/soukrome-rybarske-reviry/';
    const TITLE = 'Revíry';

    private static function routes() {
        return array(
            array('Jižní Morava', 'https://na-ryby.eu/category/soukrome-rybarske-reviry/soukrome-rybarske-reviry-jihomoravsky-kraj/'),
            array('Dyje 6 – Nové Mlýny', 'https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/'),
            array('Jak číst pravidla revírů', 'https://na-ryby.eu/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/'),
        );
    }

    public static function boot() {
        if (function_exists('add_filter') && function_exists('home_url')
            && function_exists('is_admin') && function_exists('is_preview')) {
            add_filter('wp_nav_menu_objects', array(__CLASS__, 'filter_menu'), 55, 2);
        }
    }

    public static function filter_menu($items, $args = null) {
        if (!is_array($items) || !function_exists('home_url') || !function_exists('is_admin')
            || !function_exists('is_preview') || is_admin() || is_preview()
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || (function_exists('is_customize_preview') && is_customize_preview())) return $items;

        $matches = array();
        foreach ($items as $index => $item) {
            if (!is_object($item) || ($item->title ?? '') !== self::TITLE
                || ($item->url ?? '') !== self::OLD_URL
                || (string)($item->menu_item_parent ?? '') !== '0'
                || !isset($item->ID) || (int)$item->ID <= 0) continue;
            $matches[] = $index;
        }
        if (count($matches) !== 1) return $items;
        $index = $matches[0];
        $parent = $items[$index];
        $parent_id = (string)$parent->ID;
        $routes = self::routes();

        // A human editor's submenu or a concurrent menu bridge wins. Fail
        // closed rather than creating duplicate links or reordered children.
        foreach ($items as $item) {
            if (!is_object($item)) return $items;
            if ((string)($item->menu_item_parent ?? '') === $parent_id) return $items;
            foreach ($routes as $route) {
                if (($item->url ?? '') === $route[1]) return $items;
            }
        }

        $result = $items;
        $result[$index] = clone $parent;
        $classes = is_array($parent->classes ?? null) ? $parent->classes : array();
        if (!in_array('menu-item-has-children', $classes, true)) {
            $classes[] = 'menu-item-has-children';
        }
        $result[$index]->classes = $classes;
        foreach ($routes as $offset => $route) {
            $child = new stdClass();
            $child->ID = -(1000000 + (int)$parent->ID * 10 + $offset);
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
            $child->classes = array('menu-item', 'menu-item-type-custom', 'nry-revir-discovery-v1');
            $child->current = false;
            $child->current_item_ancestor = false;
            $child->current_item_parent = false;
            $child->menu_order = count($result) + 1;
            $result[] = $child;
        }
        return $result;
    }
}
NRY_Revir_Main_Menu_Discovery_V1::boot();
