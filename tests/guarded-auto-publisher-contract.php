<?php
// Standalone deterministic tests for guarded auto publisher, with no WordPress or network.
define('ABSPATH', __DIR__);
define('MINUTE_IN_SECONDS', 60);
$GLOBALS['options'] = array();
$GLOBALS['transients'] = array();
$GLOBALS['uuid_counter'] = 0;
$GLOBALS['events'] = array();
$GLOBALS['actions'] = array();
$GLOBALS['exec_calls'] = 0;
function add_action($name, $cb) { $GLOBALS['actions'][$name] = $cb; }
function get_option($k, $default = false) { return array_key_exists($k, $GLOBALS['options']) ? $GLOBALS['options'][$k] : $default; }
function home_url($path='/') { return 'https://na-ryby.eu/'; }
function update_option($k, $v, $autoload = null) { $GLOBALS['options'][$k] = $v; return true; }
function add_option($k, $v, $deprecated = '', $autoload = 'yes') {
    if (array_key_exists($k, $GLOBALS['options'])) { return false; }
    $GLOBALS['options'][$k] = $v;
    return true;
}
function delete_option($k) { unset($GLOBALS['options'][$k]); return true; }
function wp_generate_uuid4() { return 'test-uuid-' . (++$GLOBALS['uuid_counter']); }
function wp_json_encode($data) { return json_encode($data); }
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
    public static $throw_execute = false;
    public static function manifest() { return self::$manifest; }
    public static function plan($m) { return self::$rows; }
    public static function execute($m) {
        $GLOBALS['exec_calls']++;
        if (self::$throw_execute) { throw new RuntimeException('simulated_failure'); }
        return self::$result;
    }
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
    NRY_Git_Publisher::$throw_execute = false;
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
reset_case(8);
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 1
    && get_option(NRY_Guarded_Auto_Publisher::LAST_BATCH) === 'auto-canary-batch-0001',
    'new approved v8 schema may auto-apply after source-bound preflight');
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
$GLOBALS['options'][NRY_Guarded_Auto_Publisher::LOCK] = array('token' => 'other-worker', 'expires' => time() + 300);
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 0 && get_option(NRY_Guarded_Auto_Publisher::ENABLED) === 'yes',
    'existing atomic lock prevents overlapping execution without disabling a healthy publisher');
reset_case();
$GLOBALS['options'][NRY_Guarded_Auto_Publisher::LOCK] = array('token' => 'abandoned-worker', 'expires' => time() - 1);
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 0 && get_option(NRY_Guarded_Auto_Publisher::ENABLED) === 'no',
    'expired lock fail-closes without stealing a potentially live lock');
ok(get_option(NRY_Guarded_Auto_Publisher::LOCK) !== false, 'stale lock preserved for manual investigation');
reset_case();
NRY_Guarded_Auto_Publisher::tick();
ok(get_option(NRY_Guarded_Auto_Publisher::LOCK) === false, 'successful run releases owned lock');
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 1, 'second tick does not write duplicate batch');
NRY_Git_Publisher::$manifest['edits'][0]['post_id'] = 555;
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 1 && get_option(NRY_Guarded_Auto_Publisher::ENABLED) === 'no',
    'reused batch ID with changed manifest fails closed');
reset_case();
NRY_Git_Publisher::$manifest = new WP_Error();
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 0 && get_option(NRY_Guarded_Auto_Publisher::ENABLED) === 'no', 'invalid manifest stops before writing');
reset_case();
NRY_Git_Publisher::$throw_execute = true;
NRY_Guarded_Auto_Publisher::tick();
ok($GLOBALS['exec_calls'] === 1 && get_option(NRY_Guarded_Auto_Publisher::ENABLED) === 'no',
    'exception fail closes instead of retrying an uncertain batch');
ok(get_option(NRY_Guarded_Auto_Publisher::LOCK) === false,
    'exception releases owned atomic lock');

// The old plugin parser once saw the new manifest during DFG atomic update
// and safely stopped. Owner recovery is allowed only for that exact already
// authorized one-post/same-SHA manifest, with no admin disable or stale lock.
function recovery_case() {
    reset_case(8);
    $GLOBALS['options']=array(
        NRY_Guarded_Auto_Publisher::ENABLED=>'no',
        'nry_gp_owner_bootstrap_20261102'=>'yes',
        NRY_Guarded_Auto_Publisher::LAST_BATCH=>
            'pipaky-pokrocile-rozhodnuti-36702-20261107-v1',
        NRY_Guarded_Auto_Publisher::LAST_MANIFEST_SHA=>str_repeat('a',64),
        NRY_Guarded_Auto_Publisher::RECEIPT=>array('status'=>'stopped',
            'reason'=>'invalid_manifest_on_deploy','at'=>'2026-11-07T13:09:00Z')
    );
    NRY_Git_Publisher::$manifest=array(
        'schema_version'=>8,
        'batch_id'=>'pipaky-pokrocile-five-price-fix-36702-20261107-v1',
        'edits'=>array(array('post_id'=>36702,
            'placement'=>'price_corrections_only',
            'source_sha256'=>'b862ecbd7abc3a87864df1eb6e2c6ff1c4a2621216d941acbfab646bff71d735',
            'source_modified_gmt'=>'2026-11-07 11:23:04',
            'source_min_bytes'=>12938,'source_max_bytes'=>12939))
    );
    NRY_Git_Publisher::$rows=array(array('id'=>36702,
        'status'=>'ready',
        'source_sha'=>'b862ecbd7abc3a87864df1eb6e2c6ff1c4a2621216d941acbfab646bff71d735',
        'candidate_sha'=>str_repeat('b',64)));
    NRY_Git_Publisher::$result=array('status'=>'applied_and_verified','writes'=>1);
}
recovery_case();
NRY_Guarded_Auto_Publisher::recover_price_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='yes'
 && get_option('nry_gp_owner_price_recovery_20261107',false)!==false
 && get_option(NRY_Guarded_Auto_Publisher::RECEIPT)['status']
     ==='owner_recovered_validated_manifest'
 && $GLOBALS['exec_calls']===0,
 'specific owner recovery only re-enables, never writes directly');
NRY_Guarded_Auto_Publisher::tick_on_new_manifest();
ok($GLOBALS['exec_calls']===1
 && get_option(NRY_Guarded_Auto_Publisher::LAST_BATCH)
    ==='pipaky-pokrocile-five-price-fix-36702-20261107-v1',
 'existing source-bound tick runs one verified five-price batch');
NRY_Guarded_Auto_Publisher::recover_price_manifest_after_update();
NRY_Guarded_Auto_Publisher::tick_on_new_manifest();
ok($GLOBALS['exec_calls']===1,'recovery cannot replay already applied batch');
recovery_case();
$GLOBALS['options'][NRY_Guarded_Auto_Publisher::RECEIPT]=array(
 'status'=>'disabled_by_admin');
NRY_Guarded_Auto_Publisher::recover_price_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no'
 &&$GLOBALS['exec_calls']===0,'administrator disable always authoritative');
recovery_case();
$GLOBALS['options'][NRY_Guarded_Auto_Publisher::RECEIPT]['reason']='preflight_blocked';
NRY_Guarded_Auto_Publisher::recover_price_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no',
 'other safety stops never auto-recover');
recovery_case();
$GLOBALS['options'][NRY_Guarded_Auto_Publisher::LOCK]=array(
 'token'=>'unknown','expires'=>time()-100);
NRY_Guarded_Auto_Publisher::recover_price_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no'
 &&get_option(NRY_Guarded_Auto_Publisher::LOCK)!==false,
 'stale lock never stolen for recovery');
recovery_case();
unset($GLOBALS['options']['nry_gp_owner_bootstrap_20261102']);
NRY_Guarded_Auto_Publisher::recover_price_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no',
 'no previous owner enable means no recovery');
recovery_case();
NRY_Git_Publisher::$manifest['edits'][0]['source_sha256']=str_repeat('0',64);
NRY_Guarded_Auto_Publisher::recover_price_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no',
 'unapproved source SHA cannot recover');
recovery_case();
NRY_Git_Publisher::$rows=array(array('id'=>36702,'status'=>'blocked'));
NRY_Guarded_Auto_Publisher::recover_price_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no',
 'current WordPress source preflight blocked means no recovery');
recovery_case();
$GLOBALS['options'][NRY_Guarded_Auto_Publisher::LAST_BATCH]='other-batch';
NRY_Guarded_Auto_Publisher::recover_price_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no',
 'unexpected last processed batch prohibits recovery');
recovery_case();
$GLOBALS['options']['nry_gp_owner_price_recovery_20261107']=array('already'=>true);
NRY_Guarded_Auto_Publisher::recover_price_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no',
 'single-use recovery guard is irreversible even after a failure');


function carp_recovery_case() {
    reset_case(8);
    $GLOBALS['options']=array(
        NRY_Guarded_Auto_Publisher::ENABLED=>'no',
        'nry_gp_owner_bootstrap_20261102'=>'yes',
        NRY_Guarded_Auto_Publisher::LAST_BATCH=>
            'echolot-budget-118455-spec-bonus-fix-20261108-v1',
        NRY_Guarded_Auto_Publisher::LAST_MANIFEST_SHA=>str_repeat('a',64),
        NRY_Guarded_Auto_Publisher::RECEIPT=>array('status'=>'stopped',
            'reason'=>'invalid_manifest_on_deploy','at'=>'2026-11-09T11:51:00Z')
    );
    NRY_Git_Publisher::$manifest=array(
        'schema_version'=>8,
        'batch_id'=>'carp-rods-3m-54272-source-trust-20261109-v1',
        'edits'=>array(array('post_id'=>54272,
            'slug'=>'kaprove-pruty-3m-recenze-nejlepsich-vyrobku-na-trhu',
            'edit_id'=>'kaprove-pruty-3m-54272-20261109-v1',
            'placement'=>'prepend',
            'source_sha256'=>'73cd04df7abb7199f68397323a9139ae791f3f0a20bb79333da9d7d65df60751',
            'source_modified_gmt'=>'2023-06-12 22:16:22',
            'source_min_bytes'=>10207,'source_max_bytes'=>10208,
            'text_corrections'=>array_fill(0,9,array('old_text'=>'a','new_text'=>'b'))))
    );
    NRY_Git_Publisher::$rows=array(array('id'=>54272,'status'=>'ready',
        'source_sha'=>'73cd04df7abb7199f68397323a9139ae791f3f0a20bb79333da9d7d65df60751',
        'candidate_sha'=>str_repeat('b',64)));
}
carp_recovery_case();
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='yes'
 &&get_option('nry_gp_owner_carp_3m_recovery_20261109',false)!==false
 &&get_option(NRY_Guarded_Auto_Publisher::RECEIPT)['status']==='owner_recovered_validated_manifest'
 &&$GLOBALS['exec_calls']===0,'54272 recovery only re-enables; no direct WordPress mutation');
NRY_Guarded_Auto_Publisher::tick_on_new_manifest();
ok($GLOBALS['exec_calls']===1
 &&get_option(NRY_Guarded_Auto_Publisher::LAST_BATCH)==='carp-rods-3m-54272-source-trust-20261109-v1',
 '54272 reviewed source goes through normal guarded single writer');
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
NRY_Guarded_Auto_Publisher::tick_on_new_manifest();
ok($GLOBALS['exec_calls']===1,'54272 cannot replay after local application');
carp_recovery_case();
$GLOBALS['options'][NRY_Guarded_Auto_Publisher::RECEIPT]=array('status'=>'disabled_by_admin');
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no','explicit admin disable never recovered');
carp_recovery_case();
$GLOBALS['options'][NRY_Guarded_Auto_Publisher::RECEIPT]['reason']='preflight_blocked';
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no','other stop reasons cannot recover');
carp_recovery_case();
$GLOBALS['options'][NRY_Guarded_Auto_Publisher::LOCK]=array('token'=>'unknown','expires'=>time()-1);
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no','stale lock never stolen');
carp_recovery_case();
NRY_Git_Publisher::$manifest['edits'][0]['source_sha256']=str_repeat('0',64);
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no','unexpected source SHA blocks recovery');
carp_recovery_case();
NRY_Git_Publisher::$manifest['edits'][0]['text_corrections']=array();
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no','edited correction count blocks recovery');
carp_recovery_case();
NRY_Git_Publisher::$rows[0]['status']='blocked';
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no','live source preflight block never recovered');
carp_recovery_case();
$GLOBALS['options'][NRY_Guarded_Auto_Publisher::LAST_BATCH]='unexpected-batch';
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no','unexpected prior processed batch never recovered');
carp_recovery_case();
$GLOBALS['options']['nry_gp_owner_carp_3m_recovery_20261109']='used';
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no','recovery one-shot option cannot be reused');
carp_recovery_case();
unset($GLOBALS['options']['nry_gp_owner_bootstrap_20261102']);
NRY_Guarded_Auto_Publisher::recover_carp_3m_manifest_after_update();
ok(get_option(NRY_Guarded_Auto_Publisher::ENABLED)==='no','owner bootstrap prerequisite remains required');

echo "Guarded auto publisher tests successful.\n";
