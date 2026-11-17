<?php
define('ABSPATH', '/test/');
$GLOBALS['hooks'] = array();
$GLOBALS['enqueued'] = array();
$GLOBALS['page_id'] = 119865;
$GLOBALS['site'] = 'https://na-ryby.eu';
function add_action($hook, $callback, $priority = 10) {
    $GLOBALS['hooks'][] = array($hook, $callback, $priority);
}
function is_page($id) { return $id === $GLOBALS['page_id']; }
function is_singular($type) { return $type === 'page'; }
function get_queried_object_id() { return $GLOBALS['page_id']; }
function get_permalink($id) { return $id === 119865 ? $GLOBALS['site'].'/poradce-vybavy/' : ''; }
function home_url($path) { return $GLOBALS['site'].$path; }
function plugins_url($path, $file) { return 'https://na-ryby.eu/plugins/nry/'.$path; }
function wp_enqueue_script($handle, $url, $deps, $version, $footer) {
    $GLOBALS['enqueued'][] = array('script', $handle, $url, $deps, $version, $footer);
}
function wp_enqueue_style($handle, $url, $deps, $version) {
    $GLOBALS['enqueued'][] = array('style', $handle, $url, $deps, $version);
}
function ok($condition, $message) {
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
}
require dirname(__DIR__).'/advisor-scenario-shortcuts-v1.php';
ok($GLOBALS['hooks'] === array(array('wp_enqueue_scripts', 'nry_advisor_scenario_shortcuts_enqueue', 26)),
    'only one narrowly scoped WordPress enqueue hook');
nry_advisor_scenario_shortcuts_enqueue();
ok(count($GLOBALS['enqueued']) === 2, 'approved advisor receives one local CSS and JS');
ok($GLOBALS['enqueued'][0][0] === 'style'
    && strpos($GLOBALS['enqueued'][0][2], 'assets/nry-advisor-scenarios-v1.css') !== false,
    'local style only');
ok($GLOBALS['enqueued'][1][0] === 'script'
    && strpos($GLOBALS['enqueued'][1][2], 'assets/nry-advisor-scenarios-v1.js') !== false
    && $GLOBALS['enqueued'][1][5] === true, 'local footer script only');
$GLOBALS['enqueued'] = array();
$GLOBALS['page_id'] = 123;
nry_advisor_scenario_shortcuts_enqueue();
ok(!$GLOBALS['enqueued'], 'other page excluded');
$GLOBALS['page_id'] = 119865;
$GLOBALS['site'] = 'https://other.example';
nry_advisor_scenario_shortcuts_enqueue();
ok(!$GLOBALS['enqueued'], 'other origin excluded');
$php = file_get_contents(dirname(__DIR__).'/advisor-scenario-shortcuts-v1.php');
ok(!preg_match('/\b(?:wp_update_post|wp_insert_post|update_post_meta|update_option|delete_option)\s*\(/', $php),
    'no WordPress content or option writes');
echo "PASS: exact advisor-only scenarios with no WordPress writes\n";
