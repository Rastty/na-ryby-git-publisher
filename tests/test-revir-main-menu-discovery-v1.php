<?php
/** Offline contract for one editor-owned WordPress navigation menu. */
define('ABSPATH', __DIR__);
$GLOBALS['revir_hooks'] = array();
$GLOBALS['revir_admin'] = false;
$GLOBALS['revir_preview'] = false;
$GLOBALS['revir_customize'] = false;
$GLOBALS['revir_home'] = 'https://na-ryby.eu/';
function add_filter($name, $callback, $priority, $accepted_args = 1) {
    $GLOBALS['revir_hooks'][$name] = array($callback, $priority, $accepted_args);
}
function home_url($path = '/') { return $GLOBALS['revir_home']; }
function is_admin() { return $GLOBALS['revir_admin']; }
function is_preview() { return $GLOBALS['revir_preview']; }
function is_customize_preview() { return $GLOBALS['revir_customize']; }
function check_revir($ok, $why) { if (!$ok) throw new RuntimeException($why); }
require dirname(__DIR__).'/revir-main-menu-discovery-v1.php';
$C = 'NRY_Revir_Main_Menu_Discovery_V1';
check_revir($GLOBALS['revir_hooks']['wp_nav_menu_objects'][1] === 55
    && $GLOBALS['revir_hooks']['wp_nav_menu_objects'][2] === 2, 'menu hook');
$parent = (object)array('ID'=>124, 'title'=>'Revíry', 'url'=>$C::OLD_URL,
    'menu_item_parent'=>'0', 'classes'=>array('menu-item'));
$other = (object)array('ID'=>125, 'title'=>'Ubytování', 'url'=>'https://na-ryby.eu/ubytovani-2/',
    'menu_item_parent'=>'0');
$original = array($parent, $other);
$result = $C::filter_menu($original);
check_revir(count($result) === 5, 'three submenu routes');
check_revir($result[0] !== $parent && $result[0]->url === $C::OLD_URL
    && in_array('menu-item-has-children', $result[0]->classes, true), 'parent preserved and marked');
check_revir($parent->classes === array('menu-item') && $result[1] === $other, 'input immutable');
$urls = array();
foreach (array_slice($result, 2) as $child) {
    check_revir($child->menu_item_parent === '124' && $child->ID < 0
        && $child->target === '' && $child->current === false, 'safe child object');
    $urls[] = $child->url;
}
check_revir(count(array_unique($urls)) === 3, 'unique routes');
check_revir(strpos($urls[0], 'soukrome-rybarske-reviry-jihomoravsky-kraj/') !== false
    && strpos($urls[1], 'vestonicka-nadrz-dyje-6-') !== false
    && strpos($urls[2], 'jak-cist-soupis-reviru-a-ris-') !== false, 'verified destinations');
check_revir($C::filter_menu($result) === $result, 'idempotent on inserted submenu');
$child = (object)array('ID'=>126, 'title'=>'Editor choice', 'url'=>'https://na-ryby.eu/other/', 'menu_item_parent'=>'124');
check_revir($C::filter_menu(array($parent, $child))[0] === $parent, 'editor submenu wins');
$duplicate = clone $parent;
check_revir($C::filter_menu(array($parent, $duplicate)) === array($parent, $duplicate), 'duplicates fail closed');
$renamed = clone $parent; $renamed->title = 'Kam na ryby';
check_revir($C::filter_menu(array($renamed))[0] === $renamed, 'editor rename wins');
$relinked = clone $parent; $relinked->url = 'https://na-ryby.eu/changed/';
check_revir($C::filter_menu(array($relinked))[0] === $relinked, 'editor URL wins');
$GLOBALS['revir_admin'] = true;
check_revir($C::filter_menu($original) === $original, 'admin unaffected');
$GLOBALS['revir_admin'] = false; $GLOBALS['revir_preview'] = true;
check_revir($C::filter_menu($original) === $original, 'preview unaffected');
$GLOBALS['revir_preview'] = false; $GLOBALS['revir_customize'] = true;
check_revir($C::filter_menu($original) === $original, 'customizer unaffected');
$GLOBALS['revir_customize'] = false; $GLOBALS['revir_home'] = 'https://example.org/';
check_revir($C::filter_menu($original) === $original, 'site binding');
echo "PASS: Revíry menu offers three distinct source-reviewed routes\n";
