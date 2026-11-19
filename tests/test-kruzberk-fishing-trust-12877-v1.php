<?php
/** Offline tests: source drift and public render guard, with no WordPress writes. */
define('ABSPATH', __DIR__);
function add_filter($name, $callback, $priority) { $GLOBALS['filter'] = array($name, $callback, $priority); }
function add_action($name, $callback, $priority) { $GLOBALS['action'] = array($name, $callback, $priority); }
function is_single($id = null) { return $id === null || $id === 12877; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return 12877; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return 'https://na-ryby.eu/rybarska-chata-kruzberk-rybarina-moravskoslezsky-kraj/'; }
function get_post($id) {
    return (object)array('ID' => $id, 'post_type' => 'post', 'post_status' => 'publish',
        'post_name' => 'rybarska-chata-kruzberk-rybarina-moravskoslezsky-kraj',
        'post_modified_gmt' => '2026-04-14 23:33:21',
        'post_content' => str_repeat('Sample that must fail the raw SHA guard. ', 400));
}
function check($pass, $reason) { if (!$pass) throw new RuntimeException($reason); }
require dirname(__DIR__).'/kruzberk-fishing-trust-12877-v1.php';
$klass = 'NRY_Kruzberk_Fishing_Trust_12877_V1';
check($GLOBALS['filter'] === array('the_content', $klass.'::render', PHP_INT_MAX), 'main-loop filter');
$prefix = base64_decode(trim(file_get_contents(__DIR__.'/fixtures/kruzberk-12877-wp-rendered-prefix-20261119.b64')), true);
check(is_string($prefix) && strlen($prefix) === 512, 'exact public prefix fixture');
check(hash('sha256', $prefix) === $klass::PREFIX_SHA256, 'reviewed prefix');
$html = $prefix.str_repeat('<p>Additional original article content.</p>', 400);
check(strlen($html) > 10000 && substr_count($html, $klass::ANCHOR) === 1, 'valid fixture');
$updated = $klass::transform($html);
check($updated !== $html && substr_count($updated, $klass::MARKER) === 1, 'visible notice inserted');
check(substr_count($updated, $klass::OBEC) === 1 && substr_count($updated, $klass::CRS) === 1, 'only official source links');
check(strpos($updated, 'V samotné přehradě Kružberk se rybařit nesmí') !== false, 'important warning visible');
check(strpos($updated, '300&nbsp;m pod hrází') !== false, 'Moravice segment distinguished');
check($klass::transform($updated) === $updated, 'idempotent');
check($klass::render($html) === $html, 'unverified raw source fails closed');
check($klass::transform(str_replace('Rybářská chata', 'Jiná chata', $html)) === str_replace('Rybářská chata', 'Jiná chata', $html), 'prefix drift');
check($klass::transform($html.$klass::OBEC) === $html.$klass::OBEC, 'preexisting source link drift');
check($klass::transform(str_replace($klass::ANCHOR, '<h1>Jiný titulek</h1>', $html)) === str_replace($klass::ANCHOR, '<h1>Jiný titulek</h1>', $html), 'anchor drift');
echo "PASS: Kruzberk notice source-locked, visible, official-only and no WP writes\n";
