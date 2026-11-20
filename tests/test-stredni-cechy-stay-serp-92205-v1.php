<?php
/** Standalone offline fail-closed contract; no WordPress or live HTTP needed. */
define('ABSPATH', '/test/');
$GLOBALS['nry_serp_hooks'] = array();
$GLOBALS['nry_serp_id'] = 92205;
$GLOBALS['nry_serp_slug'] = 'zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach';
$GLOBALS['nry_serp_status'] = 'publish';
$GLOBALS['nry_serp_modified'] = '2024-10-07 12:15:48';
$GLOBALS['nry_serp_admin'] = false;
$GLOBALS['nry_serp_preview'] = false;
$GLOBALS['nry_serp_host'] = 'https://na-ryby.eu/';
$GLOBALS['nry_serp_canonical'] = 'https://na-ryby.eu/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/';

function check_serp($truth, $message) {
    if (!$truth) { fwrite(STDERR, 'FAIL '.$message."\n"); exit(1); }
}
function add_filter($name, $callback, $priority) {
    $GLOBALS['nry_serp_hooks'][$name] = array($callback, $priority);
}
function is_single($id) { return $GLOBALS['nry_serp_id'] === $id; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return $GLOBALS['nry_serp_id']; }
function is_admin() { return $GLOBALS['nry_serp_admin']; }
function is_preview() { return $GLOBALS['nry_serp_preview']; }
function home_url($path) { return $GLOBALS['nry_serp_host']; }
function get_permalink($id) { return $GLOBALS['nry_serp_canonical']; }
function get_post($id) {
    return (object)array('ID' => $id, 'post_type' => 'post',
        'post_status' => $GLOBALS['nry_serp_status'],
        'post_name' => $GLOBALS['nry_serp_slug'],
        'post_modified_gmt' => $GLOBALS['nry_serp_modified'],
        // The private raw WordPress source cannot be reconstructed from a
        // public rendered REST response. Synthetic source MUST fail closed.
        'post_content' => str_repeat('not verified raw WordPress content', 200));
}

require dirname(__DIR__).'/seo-stredni-cechy-stay-92205-v1.php';
$klass = 'NRY_Stredni_Cechy_Stay_SERP_92205_V1';
$baseline = $klass::BASELINE;
$candidate = $klass::CANDIDATE;
check_serp(array_keys($GLOBALS['nry_serp_hooks']) === array('wpseo_metadesc', 'wpseo_opengraph_desc'),
    'only two render-only Yoast filters');
check_serp($klass::description($baseline) === $baseline, 'synthetic raw source fails closed');
check_serp($klass::description('Redakčně upravený popis') === 'Redakčně upravený popis',
    'manual Yoast change preserved');
check_serp($klass::description('') === '', 'missing description not invented');
check_serp($klass::description(null) === null, 'non-string input preserved');

$negative = array(
    array('nry_serp_id', 119865),
    array('nry_serp_slug', 'different-post'),
    array('nry_serp_status', 'draft'),
    array('nry_serp_modified', '2026-11-20 12:00:00'),
    array('nry_serp_admin', true),
    array('nry_serp_preview', true),
    array('nry_serp_host', 'https://other.example/'),
    array('nry_serp_canonical', 'https://na-ryby.eu/not-this-page/'),
);
foreach ($negative as $case) {
    $key = $case[0]; $prior = $GLOBALS[$key];
    $GLOBALS[$key] = $case[1];
    check_serp($klass::description($baseline) === $baseline, 'fail closed on '.$key);
    $GLOBALS[$key] = $prior;
}
check_serp($klass::description($baseline) === $baseline, 'synthetic source is never accepted');
check_serp(!function_exists('update_post_meta') && !function_exists('wp_update_post'),
    'test requires no WordPress writer capability');
check_serp(preg_match_all('/./u', $candidate) < 160, 'SERP description fits 160 chars');
check_serp($klass::SOURCE_BYTES === 5881, 'raw source byte ceiling pinned');
check_serp($klass::SOURCE_SHA256 === 'ca79666566a68867a45eb6cb5504014813419e7c3055dc4880b04510a8560a74',
    'read-only production proof shared SHA');
$trusted = file_get_contents(dirname(__DIR__).'/stredni-cechy-stay-offer-trust-92205-v1.php');
check_serp(strpos($trusted, $klass::SOURCE_SHA256) !== false,
    'new render-only scope reuses deployed source hash guard');
echo "PASS: Stredni Cechy 92205 exact render-only SERP candidate; no writes\n";
