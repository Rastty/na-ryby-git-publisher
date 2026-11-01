<?php
// Offline release gate regression. Does not connect to or mutate WordPress.
define('ABSPATH', __DIR__);
define('OBJECT', 'OBJECT');
$GLOBALS['nry_test_options'] = array();
$GLOBALS['nry_test_origin'] = 'https://na-ryby.eu/';
$GLOBALS['nry_test_collision'] = false;
$GLOBALS['nry_test_collision_kind'] = '';
$GLOBALS['nry_test_last_query'] = null;
function add_action() {}
function status_header($status) { if ($status !== 200) { throw new RuntimeException('Wrong HTTP status'); } }
function home_url($path = '/') { return $GLOBALS['nry_test_origin']; }
function get_page_by_path() { return $GLOBALS['nry_test_collision'] ? (object) array('ID' => 123) : null; }
function get_post_types() {
    return array('post' => 'post', 'page' => 'page', 'attachment' => 'attachment', 'private_doc' => 'private_doc');
}
function get_post_stati() {
    return array('publish' => 'publish', 'private' => 'private', 'inherit' => 'inherit', 'trash' => 'trash');
}
function get_posts($args = array()) {
    $GLOBALS['nry_test_last_query'] = $args;
    if ($GLOBALS['nry_test_collision']) { return array(123); }
    $kind = $GLOBALS['nry_test_collision_kind'];
    if ($kind === 'attachment' && in_array('attachment', $args['post_type'], true)
        && in_array('inherit', $args['post_status'], true)) { return array(456); }
    if ($kind === 'trashed_private' && in_array('private_doc', $args['post_type'], true)
        && in_array('trash', $args['post_status'], true)) { return array(789); }
    return array();
}
function get_option($key, $default = false) { return $GLOBALS['nry_test_options'][$key] ?? $default; }
function check($condition, $label) {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
}
require dirname(__DIR__) . '/checklist-release-v1.php';

$sha = NRY_Checklist_Release_V1::asset_sha();
check(is_string($sha) && preg_match('/^[a-f0-9]{64}$/D', $sha), 'validated standalone asset SHA');
check(NRY_Checklist_Release_V1::approved_asset_bytes($sha) ===
    file_get_contents(NRY_Checklist_Release_V1::asset_path()),
    'serve exactly the bytes verified against approved SHA');
check(NRY_Checklist_Release_V1::approved_asset_bytes(str_repeat('0', 64)) === false,
    'stale approval cannot serve asset bytes');
check(NRY_Checklist_Release_V1::approved_asset_bytes('not-a-sha') === false,
    'malformed approval cannot serve asset bytes');
check(NRY_Checklist_Release_V1::inspection()['ready'], 'ready without slug collision');
check(!NRY_Checklist_Release_V1::inspection()['approved'], 'git checkout is NOT publication');
check(!NRY_Checklist_Release_V1::inspection()['approval_conflict'], 'no initial approval conflict');

// Simulate the canonical incoming request authority, not only WP home_url.
$_SERVER['HTTP_HOST'] = 'na-ryby.eu';
if (isset($argv[1]) && in_array($argv[1], array('serve', 'head'), true)) {
    $GLOBALS['nry_test_options'][NRY_Checklist_Release_V1::APPROVAL] =
        array('sha256' => $sha, 'approved_at_utc' => '2026-10-30T13:00:00Z');
    $_SERVER['REQUEST_METHOD'] = $argv[1] === 'head' ? 'HEAD' : 'GET';
    $_SERVER['REQUEST_URI'] = '/co-sbalit-na-ryby/?method=feeder';
    NRY_Checklist_Release_V1::serve();
    throw new RuntimeException('approved exact route must exit after output');
}

$GLOBALS['nry_test_options'][NRY_Checklist_Release_V1::APPROVAL] =
    array('sha256' => str_repeat('0', 64));
check(!NRY_Checklist_Release_V1::inspection()['approved'], 'wrong hash cannot publish');
check(NRY_Checklist_Release_V1::inspection()['approval_conflict'], 'hash drift requires rollback and new Apply');
$GLOBALS['nry_test_options'][NRY_Checklist_Release_V1::APPROVAL] = array('sha256' => $sha);
check(NRY_Checklist_Release_V1::inspection()['approved'], 'exact hash plus no collision approves');
$GLOBALS['nry_test_collision'] = true;
check(!NRY_Checklist_Release_V1::inspection()['ready'], 'existing WP slug collision blocks');
check(!NRY_Checklist_Release_V1::inspection()['approved'], 'collision overrides approval');
$GLOBALS['nry_test_collision'] = false;
$GLOBALS['nry_test_collision_kind'] = 'attachment';
check(!NRY_Checklist_Release_V1::inspection()['ready'], 'attachment with inherited status blocks route');
check(!NRY_Checklist_Release_V1::inspection()['approved'], 'attachment collision overrides SHA approval');
$GLOBALS['nry_test_collision_kind'] = 'trashed_private';
check(!NRY_Checklist_Release_V1::inspection()['ready'], 'trashed private custom post blocks route');
check($GLOBALS['nry_test_last_query']['name'] === 'co-sbalit-na-ryby', 'exact slug scoped query');
check($GLOBALS['nry_test_last_query']['fields'] === 'ids', 'bounded collision query');
check($GLOBALS['nry_test_last_query']['suppress_filters'] === true, 'collision query resists content filters');
$GLOBALS['nry_test_collision_kind'] = '';
$GLOBALS['nry_test_origin'] = 'https://wrong.example/';
check(!NRY_Checklist_Release_V1::inspection()['ready'], 'wrong WordPress origin blocks');
$GLOBALS['nry_test_origin'] = 'https://na-ryby.eu/';
$_SERVER['REQUEST_URI'] = '/co-sbalit-na-ryby/';
foreach (array('POST', 'PUT', 'PATCH', 'DELETE', '') as $method) {
    $_SERVER['REQUEST_METHOD'] = $method;
    check(NRY_Checklist_Release_V1::serve() === null,
        'non-GET/HEAD request must not intercept approved route: ' . $method);
}
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/co-sbalit-na-ryby/';
foreach (array('', 'alias.example', 'na-ryby.eu.attacker.invalid',
               'na-ryby.eu:8443') as $host) {
    $_SERVER['HTTP_HOST'] = $host;
    check(NRY_Checklist_Release_V1::serve() === null,
        'noncanonical request host must not serve approved asset: ' . $host);
}
$_SERVER['HTTP_HOST'] = 'na-ryby.eu';
$_SERVER['REQUEST_URI'] = '/not-our-path/';
check(NRY_Checklist_Release_V1::serve() === null, 'unrelated routes remain untouched');
echo "Checklist release contract PASS: SHA approval, drift, origin, collision, route\n";
