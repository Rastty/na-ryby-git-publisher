<?php
// Offline release gate regression. Does not connect to or mutate WordPress.
define('ABSPATH', __DIR__);
define('OBJECT', 'OBJECT');
$GLOBALS['nry_test_options'] = array();
$GLOBALS['nry_test_origin'] = 'https://na-ryby.eu/';
$GLOBALS['nry_test_collision'] = false;
function add_action() {}
function home_url($path = '/') { return $GLOBALS['nry_test_origin']; }
function get_page_by_path() { return $GLOBALS['nry_test_collision'] ? (object) array('ID' => 123) : null; }
function get_posts() { return $GLOBALS['nry_test_collision'] ? array((object) array('ID' => 123)) : array(); }
function get_option($key, $default = false) { return $GLOBALS['nry_test_options'][$key] ?? $default; }
function check($condition, $label) {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
}
require dirname(__DIR__) . '/checklist-release-v1.php';

$sha = NRY_Checklist_Release_V1::asset_sha();
check(is_string($sha) && preg_match('/^[a-f0-9]{64}$/D', $sha), 'validated standalone asset SHA');
check(NRY_Checklist_Release_V1::inspection()['ready'], 'ready without slug collision');
check(!NRY_Checklist_Release_V1::inspection()['approved'], 'git checkout is NOT publication');
check(!NRY_Checklist_Release_V1::inspection()['approval_conflict'], 'no initial approval conflict');

if (isset($argv[1]) && $argv[1] === 'serve') {
    $GLOBALS['nry_test_options'][NRY_Checklist_Release_V1::APPROVAL] =
        array('sha256' => $sha, 'approved_at_utc' => '2026-10-30T13:00:00Z');
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
$GLOBALS['nry_test_origin'] = 'https://wrong.example/';
check(!NRY_Checklist_Release_V1::inspection()['ready'], 'wrong WordPress origin blocks');
$GLOBALS['nry_test_origin'] = 'https://na-ryby.eu/';
$_SERVER['REQUEST_URI'] = '/not-our-path/';
check(NRY_Checklist_Release_V1::serve() === null, 'unrelated routes remain untouched');
echo "Checklist release contract PASS: SHA approval, drift, origin, collision, route\n";
