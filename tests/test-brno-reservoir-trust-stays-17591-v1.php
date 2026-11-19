<?php
/** Offline guard and HTML contract for source-locked Brno chalet repair. */
define('ABSPATH', __DIR__);
function add_filter($name, $callback, $priority) { $GLOBALS['filter'] = array($name, $callback, $priority); }
function add_action($name, $callback, $priority) { $GLOBALS['action'] = array($name, $callback, $priority); }
function is_single($id = null) { return $id === null || $id === 17591; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return 17591; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return 'https://na-ryby.eu/chata-brnenska-prehrada/'; }
function get_post($id) {
    return (object)array('ID' => $id, 'post_type' => 'post', 'post_status' => 'publish',
        'post_name' => 'chata-brnenska-prehrada',
        'post_modified_gmt' => '2018-01-22 19:53:18',
        'post_content' => str_repeat('Synthetic raw WordPress article that must fail SHA. ', 160));
}
function check($condition, $why) { if (!$condition) throw new RuntimeException($why); }
require dirname(__DIR__).'/brno-reservoir-trust-stays-17591-v1.php';
$cls = 'NRY_Brno_Reservoir_Stays_17591_V1';
check($GLOBALS['filter'] === array('the_content', $cls.'::render', PHP_INT_MAX), 'main content filter only');
$prefix = base64_decode(trim(file_get_contents(__DIR__.'/fixtures/brno-reservoir-17591-wp-rendered-prefix-20261119.b64')), true);
check(is_string($prefix) && strlen($prefix) === 512 && hash('sha256', $prefix) === $cls::PREFIX_SHA256, 'exact REST prefix');
$html = $prefix.str_repeat('<p>Unmodified historical chalet description.</p>', 230)
    .str_repeat('<h2>'.$cls::BAD_TITLE.'</h2>', 3)
    .$cls::OLD_PERMIT.$cls::OLD_PRICE
    .'<a '.$cls::ORIGINAL_CTA_URL.'>'.$cls::OLD_CTA.'</a>';
check(strlen($html) > 8500, 'historical body length');
$new = $cls::transform($html);
check($new !== $html && substr_count($new, $cls::MARKER) === 1, 'front section visible once');
check(substr_count($new, 'data-nry-offer-id=') === 2, 'two distinct nearby alternatives');
check(substr_count($new, $cls::HAUZI) === 1 && substr_count($new, $cls::HPORTAL) === 1, 'approved URLs exactly once');
check(substr_count($new, 'rel="sponsored nofollow noopener"') === 2, 'partner disclosure');
check(strpos($new, $cls::BAD_TITLE) === false && strpos($new, $cls::OLD_CTA) === false, 'bad headings and cheapest claim gone');
check(strpos($new, $cls::ORIGINAL_CTA_URL) === false && strpos($new, 'href="#'.$cls::ANCHOR.'"') !== false, 'old CTA replaced by internal jump');
check(strpos($new, 'aktuální cena neověřena') !== false && strpos($new, 'není ověřen') !== false, 'historic price and permit caveat');
check(strpos($new, 'nejsou původní chata') !== false, 'alternatives are not mistaken for original');
check($cls::transform($new) === $new, 'idempotent');
check($cls::render($html) === $html, 'raw SHA drift fails closed');
$bad = 'x'.substr($html, 1);
check($cls::transform($bad) === $bad, 'REST prefix drift fails closed');
$bad = str_replace($cls::OLD_PERMIT, 'changed permit price', $html);
check($cls::transform($bad) === $bad, 'old price identity drift fails closed');
$bad = str_replace($cls::BAD_TITLE, 'Corrected title', $html);
check($cls::transform($bad) === $bad, 'title count drift fails closed');
echo "PASS: Brno reservoir chalet 17591 trust, offer and fail-closed HTML contract\n";
