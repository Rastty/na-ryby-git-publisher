<?php
/** Offline historical map bridge; no network, WP writes or map interaction. */
define('ABSPATH', __DIR__);
$GLOBALS['map_hooks'] = array();
$GLOBALS['map_admin'] = false;
$GLOBALS['map_preview'] = false;
$GLOBALS['map_home'] = 'https://na-ryby.eu/';
$GLOBALS['map_post'] = (object)array('ID'=>883, 'post_type'=>'page', 'post_status'=>'publish', 'post_name'=>'dovolena-u-vody');
function add_filter($hook,$cb,$priority) { $GLOBALS['map_hooks'][$hook] = $priority; }
function home_url($path = '/') { return $GLOBALS['map_home']; }
function is_admin() { return $GLOBALS['map_admin']; }
function is_preview() { return $GLOBALS['map_preview']; }
function is_front_page() { return false; }
function is_page($slug) { return $slug === 'dovolena-u-vody'; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function get_queried_object_id() { return 883; }
function get_post($id) { return $GLOBALS['map_post']; }
function get_permalink($id) { return 'https://na-ryby.eu/dovolena-u-vody/'; }
function check_map($ok,$why) { if (!$ok) throw new RuntimeException($why); }
require dirname(__DIR__).'/legacy-map-discovery-bridge-v1.php';
$C = 'NRY_Legacy_Map_Discovery_Bridge_V1';
check_map($GLOBALS['map_hooks']['the_content'] === 92, 'map bridge hook');
$embed = '<iframe width="800" src="https://'.$C::MAP_URL_TOKEN.'" height="600"></iframe>';
$html = '<p>Stará mapa revírů</p>'.$embed.'<p>Další historický text.</p>';
$new = $C::render($html);
check_map($new !== $html, 'map page has actionable editorial links');
check_map(substr_count($new, $embed) === 1, 'migrated map embed is preserved byte for byte');
check_map(substr_count($new, $C::MARKER) === 1, 'single visible section');
check_map(substr_count($new, $C::REVIR_GUIDE) === 1, 'verified root private-water archive route');
check_map(substr_count($new, $C::RULES_GUIDE) === 1, 'official rules orientation route');
check_map(substr_count($new, $C::STAYS_GUIDE) === 1, 'current stay choices route');
check_map(strpos($new, $C::MARKER) < strpos($new, $embed), 'first-party decision precedes large map');
check_map($C::render($new) === $new, 'idempotent');
$drift = str_replace($C::MAP_URL_TOKEN, 'maps.example.com/other', $html);
check_map($C::render($drift) === $drift, 'edited map URL wins');
check_map($C::render($html.$html) === $html.$html, 'duplicate embeds fail closed');
$GLOBALS['map_admin'] = true;
check_map($C::render($html) === $html, 'admin not changed');
$GLOBALS['map_admin'] = false;
$GLOBALS['map_preview'] = true;
check_map($C::render($html) === $html, 'preview not changed');
$GLOBALS['map_preview'] = false;
$GLOBALS['map_post']->post_name = 'different-page';
check_map($C::render($html) === $html, 'slug scope enforced');
$GLOBALS['map_post']->post_name = 'dovolena-u-vody';
$GLOBALS['map_home'] = 'https://other.example/';
check_map($C::render($html) === $html, 'same-site guard enforced');
echo "PASS: legacy map preserved with current revír and stay discovery on exact page\n";
