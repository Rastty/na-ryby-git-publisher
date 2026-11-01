<?php
// Standalone deterministic tests for guarded auto publisher, with no WordPress or network.
define('ABSPATH', __DIR__);
define('MINUTE_IN_SECONDS', 60);
$GLOBALS['options'] = array();
$GLOBALS['transients'] = array();
$GLOBALS['events'] = array();
$GLOBALS['actions'] = array();
$GLOBALS['exec_calls'] = 0;
function add_action($name, $cb) { $GLOBALS['actions'][$name] = $cb; }
function get_option($k, $default = false) { return array_key_exists($k, $GLOBALS['options']) ? $GLOBALS['options'][$k] : $default; }
function update_option($k, $v, $autoload = null) { $GLOBALS['options'][$k] = $v; return true; }
function get_transient($k) { return $GLOBALS['transients'][$k] ?? false; }
function set_transient($k, $v, $ttl = 0) { $GLOBALS['transients'][$k] = $v; return true; }
function delete_transient($k) { unset($GLOBALS['transients'][$k]); }
function wp_next_scheduled($k) { return $GLOBALS['events'][$k] ?? false; }
function wp_schedule_event($ts, $recurrence, $k) { $GLOBALS['events'][$k] = $ts; return true; }
function wp_clear_scheduled_hook($k) { unset($GLOBALS['events'][$k]); }
function is_wp_error($v) { return $v instanceof WP_Error; }
class WP_Error {}
final class NRY_Git_Publisher {
    public static $manifest = array();
    public static $rows = array();
    public static $result = array('status' => 'applied_and_verified', 'writes' => 1);
    public static function manifest() { return self::$manifest; }
    public static function plan($m) { return self::$rows; }
    public static function execute($m) { $GLOBALS['exec_calls']++; return self::$result; }
}
require __DIR__ . '/../guarded-auto-publisher.php';
function ok($test, $description) {
    if (!$test) { fwrite(STDERR, "FAIL: $description\n"); exit(1); }
    echo "PASS: $description\n";
}
function reset_case($schema = 7, $count = 1) {
    $GLOBALS['options'] = array(NRY_Guarded_Auto_Publisher::ENABLED => 'yes');
    $GLOBALS['transients'] = array();
    $GLOBALS['events'] = array();
    $GLOBALS['exec_calls'] = 0;
    NRY_Git_Publisher::$manifest = array('schema_version' => $schema,
        'batch_id' => 'auto-canary-batch-0001',
        'edits' => array_fill(0, $count, array('post_id' => 123)));
    NRY_Git_Publisher::$rows = array_fill(0, $count, array('status' => 'ready'));
    NRY_Git_Publisher::$result = array('status' => 'applied_and_verified', 'writes' => $count);
}
// Auto disabled by default, including when plugin is deployed.
$GLOBALS['options'] = array();
NRY_Guarded_Auto_Publisher::schedule();
ok(!wp_next_scheduled(NRY_Guarded_Auto_Publisher::EVENT), 'disabled by default: no scheduled event');
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 0, 'disabled by default: no content changes');
reset_case();
NRY_Guarded_Auto_Publisher::schedule();
ok((bool) wp_next_scheduled(NRY_Guarded_Auto_Publisher::EVENT), 'enabled schedules hourly tick');
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 1, 'eligible bounded approved manifest executes once');
ok(get_option(NRY_Guarded_Auto_Publisher::LAST_BATCH) === 'auto-canary-batch-0001', 'receipt saves last processed batch');
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 1, 'duplicate batch is idempotently skipped');
reset_case(4);
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 0 && get_option(NRY_Guarded_Auto_Publisher::ENABLED) === 'no', 'unsupported schema stops without write');
reset_case(7, 5);
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 0 && get_option(NRY_Guarded_Auto_Publisher::ENABLED) === 'no', 'oversized batch stops without write');
reset_case();
NRY_Git_Publisher::$rows = array(array('status' => 'blocked'));
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 0 && get_option(NRY_Guarded_Auto_Publisher::ENABLED) === 'no', 'preflight block disables automation');
reset_case();
NRY_Git_Publisher::$result = array('status' => 'uncertain');
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 1 && get_option(NRY_Guarded_Auto_Publisher::ENABLED) === 'no', 'uncertain write disables automation');
reset_case();
$GLOBALS['transients'][NRY_Guarded_Auto_Publisher::LOCK] = '1';
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 0, 'active lock prevents overlapping execution');
reset_case();
NRY_Git_Publisher::$manifest = new WP_Error();
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 0 && get_option(NRY_Guarded_Auto_Publisher::ENABLED) === 'no', 'invalid manifest stops before writing');
echo "Guarded auto publisher tests successful.\n";
