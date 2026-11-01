<?php
/**
 * Offline contract for new approved generic schema v8 batches.
 * Fake WP post store, no network, no real WordPress writes.
 */
define('ABSPATH', __DIR__);
class WP_Error { public function __construct($code, $message = '') {} }
function is_wp_error($v) { return $v instanceof WP_Error; }
function add_action($name, $fn) { return true; }
function wp_kses($html, $allowed, $protocols) { return $html; }
$options = array();
$posts = array();
$writes = 0;
function get_post($id) { global $posts; return $posts[$id] ?? null; }
function add_option($key, $value, $deprecated = '', $autoload = 'yes') {
    global $options;
    if (array_key_exists($key, $options)) { return false; }
    $options[$key] = $value;
    return true;
}
function get_option($key, $default = false) {
    global $options;
    return $options[$key] ?? $default;
}
function wp_update_post($data, $returnError = false) {
    global $posts, $writes;
    $id = (int) $data['ID'];
    if (!isset($posts[$id])) { return new WP_Error('bad_post'); }
    $writes++;
    $posts[$id]->post_content = $data['post_content'];
    $posts[$id]->post_modified_gmt = '2026-11-01 08:00:00';
    return $id;
}
function must($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
require dirname(__DIR__) . '/na-ryby-git-publisher.php';

$base = json_decode(file_get_contents(dirname(__DIR__) . '/manifest.json'), true);
must($base['schema_version'] === 7, 'Fixture must be existing approved schema 7');
$manifest = array('schema_version' => 8, 'site' => 'https://na-ryby.eu',
    'batch_id' => 'new-editorial-canary-20261101-01', 'edits' => array());
$before = '<h2>' . str_repeat('x', 7500) . '</h2>';
foreach (array(61001, 61002) as $index => $id) {
    $edit = $base['edits'][$index];
    $old = $edit['edit_id'];
    $new = 'generic-reviewed-' . $id . '-safe-v1';
    $edit['post_id'] = $id;
    $edit['slug'] = 'rybarsky-testovaci-pruvodce-bezpecny-' . $id;
    $edit['edit_id'] = $new;
    $edit['insert_html'] = str_replace(
        'data-nry-editorial-edit="' . $old . '"',
        'data-nry-editorial-edit="' . $new . '"', $edit['insert_html']);
    $edit['source_sha256'] = hash('sha256', $before);
    $manifest['edits'][] = $edit;
    $posts[$id] = (object) array('ID' => $id, 'post_type' => 'post',
        'post_status' => 'publish', 'post_name' => $edit['slug'],
        'post_modified_gmt' => $edit['source_modified_gmt'], 'post_content' => $before);
}
$ok = NRY_Git_Publisher::validate_manifest($manifest);
must(!is_wp_error($ok), 'New generic schema v8 manifest rejected');
$plan = NRY_Git_Publisher::plan($ok);
must(count($plan) === 2 && $plan[0]['status'] === 'ready'
    && $plan[1]['status'] === 'ready', 'Fresh generic posts not ready');

$bad = $manifest;
$bad['edits'][0]['source_sha256'] = str_repeat('f', 64);
must(NRY_Git_Publisher::plan($bad)[0]['status'] === 'blocked',
    'Mismatched raw content source SHA not rejected');
must($writes === 0, 'Unexpected write during rejected SHA preflight');

$bad = $manifest;
$bad['edits'][0]['post_id'] = 38064;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),
    'Protected measurement ID accepted');
$bad = $manifest;
$bad['edits'][0]['post_id'] = 44102;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),
    'Protected historic KEEP post accepted');
$bad = $manifest;
$bad['edits'][1]['edit_id'] = $bad['edits'][0]['edit_id'];
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),
    'Duplicate batch marker accepted');
$bad = $manifest;
$bad['edits'][0]['insert_html'] .= '<script>alert(1)</script>';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),
    'Unsafe raw HTML accepted');
$bad = $manifest;
$bad['edits'][0]['source_sha256'] = 'invalid';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),
    'Invalid SHA format accepted');
$bad = $manifest;
$bad['edits'] = array_fill(0, 5, $manifest['edits'][0]);
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),
    'Oversized batch accepted');
$bad = $manifest;
$posts[61002]->post_modified_gmt = '2026-11-01 07:00:00';
$blocked = NRY_Git_Publisher::execute($bad);
must($blocked['status'] === 'blocked_no_writes' && $writes === 0,
    'Concurrent modification did not fail entire batch');
$posts[61002]->post_modified_gmt = $manifest['edits'][1]['source_modified_gmt'];

$receipt = NRY_Git_Publisher::execute($manifest);
must($receipt['status'] === 'applied_and_verified' && $receipt['writes'] === 2,
    'Generic source-bound batch did not write and verify 2 posts');
must($writes === 2, 'Unexpected number of writes');
must(count($options) === 1 && count(reset($options)) === 2,
    'Independent pre-write backups missing');
$replay = NRY_Git_Publisher::execute($manifest);
must($replay['status'] === 'applied_and_verified' && $replay['writes'] === 0
    && $writes === 2, 'Idempotent replay performed a second write');
echo "ok: source-hash-bound v8 generic 2-post batch; safe HTML; bounded scope; backups; replays; fail closed\n";
