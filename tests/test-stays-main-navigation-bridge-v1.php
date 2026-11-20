<?php
/** Offline WP nav menu contract, no real WordPress, merchant or network. */
define('ABSPATH', __DIR__);
$GLOBALS['menu_hooks'] = array();
$GLOBALS['menu_admin'] = false;
$GLOBALS['menu_preview'] = false;
$GLOBALS['menu_customize'] = false;
$GLOBALS['menu_home'] = 'https://na-ryby.eu/';
function add_filter($name, $callback, $priority, $accepted_args = 1) {
    $GLOBALS['menu_hooks'][$name] = array($priority, $accepted_args);
}
function home_url($path = '/') { return $GLOBALS['menu_home']; }
function is_admin() { return $GLOBALS['menu_admin']; }
function is_preview() { return $GLOBALS['menu_preview']; }
function is_customize_preview() { return $GLOBALS['menu_customize']; }
function check_menu($ok, $message) { if (!$ok) throw new RuntimeException($message); }
require dirname(__DIR__).'/stays-main-navigation-bridge-v1.php';
$C = 'NRY_Stays_Main_Navigation_Bridge_V1';
check_menu($GLOBALS['menu_hooks']['wp_nav_menu_objects'] === array(50, 2), 'WP menu hook contract');
check_menu($C::OLD_URL !== $C::NEW_URL && strpos($C::NEW_URL, 'https://na-ryby.eu/') === 0, 'same-site destination');
$old = (object)array('title'=>'Ubytování','url'=>$C::OLD_URL,'menu_item_parent'=>'0','ID'=>123,'classes'=>array('menu-item'));
$other = (object)array('title'=>'Revíry','url'=>'https://na-ryby.eu/category/soukrome-rybarske-reviry/','menu_item_parent'=>'0','ID'=>124);
$initial = array($old, $other);
$result = $C::filter_menu($initial);
check_menu($result[0]->url === $C::NEW_URL, 'stays menu uses current stay offers page');
check_menu($result[1] === $other && $result[0]->title === 'Ubytování', 'other items and menu title preserved');
check_menu($initial[0] === $old && $old->url === $C::OLD_URL, 'original WP menu object remains immutable');
check_menu($result[0] !== $old && $result[0]->ID === $old->ID && $result[0]->classes === $old->classes, 'clone preserves menu metadata');
check_menu($C::filter_menu($result) === $result, 'already changed link is idempotent');
$duplicate = array($old, clone $old);
check_menu($C::filter_menu($duplicate) === $duplicate, 'duplicate candidates fail closed');
$renamed = clone $old; $renamed->title = 'Pobyty';
check_menu($C::filter_menu(array($renamed))[0] === $renamed, 'editor title update wins');
$relinked = clone $old; $relinked->url = 'https://na-ryby.eu/other/';
check_menu($C::filter_menu(array($relinked))[0] === $relinked, 'editor URL update wins');
$child = clone $old; $child->menu_item_parent = '19';
check_menu($C::filter_menu(array($child))[0] === $child, 'submenu is not changed');
$GLOBALS['menu_admin'] = true;
check_menu($C::filter_menu($initial)[0] === $old, 'admin remains untouched');
$GLOBALS['menu_admin'] = false;
$GLOBALS['menu_preview'] = true;
check_menu($C::filter_menu($initial)[0] === $old, 'post preview remains untouched');
$GLOBALS['menu_preview'] = false;
$GLOBALS['menu_customize'] = true;
check_menu($C::filter_menu($initial)[0] === $old, 'menu customizer remains untouched');
$GLOBALS['menu_customize'] = false;
$GLOBALS['menu_home'] = 'https://example.org/';
check_menu($C::filter_menu($initial)[0] === $old, 'cross-site scope is blocked');
echo "PASS: stay navigation bridge preserves editor/menu ownership and legacy archive\n";
