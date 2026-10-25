<?php
/**
 * Offline contract checks for the server-native v5 publisher. No WordPress
 * credentials, network calls or actual production writes are used.
 */
define('ABSPATH', __DIR__);
class WP_Error {
    private $code;
    public function __construct($code, $message) { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function add_action($name, $callback) { return true; }
$options = array();
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
function wp_kses($html, $allowed, $protocols) { return $html; } // WP handles KSES in production.
$posts = array();
function get_post($id) { global $posts; return $posts[$id] ?? null; }
function wp_update_post($change, $returnError = false) {
    global $posts;
    $id = (int) $change['ID'];
    if (!isset($posts[$id])) { return new WP_Error('not_found', 'missing'); }
    $posts[$id]->post_content = $change['post_content'];
    $posts[$id]->post_modified_gmt = '2026-10-25 13:00:00';
    return $id;
}
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }

require dirname(__DIR__) . '/na-ryby-git-publisher.php';
$manifest = NRY_Git_Publisher::validate_manifest(json_decode(file_get_contents(__DIR__ . '/v5-fixture-manifest.json'), true));
check(!is_wp_error($manifest), 'v5 public manifest rejected');
check($manifest['schema_version'] === 5, 'wrong manifest schema');
check(array_column($manifest['edits'], 'post_id') === array(50011, 53630), 'unexpected scope');
foreach ($manifest['edits'] as $edit) {
    $posts[$edit['post_id']] = (object) array(
        'ID' => $edit['post_id'], 'post_type' => 'post', 'post_status' => 'publish',
        'post_name' => $edit['slug'], 'post_modified_gmt' => $edit['source_modified_gmt'],
        'post_content' => '<p>' . str_repeat('A', $edit['source_bytes'] - 7) . '</p>'
    );
}
$rows = NRY_Git_Publisher::plan($manifest);
check(count($rows) === 2 && $rows[0]['status'] === 'ready' &&
    $rows[1]['status'] === 'ready', 'source matching baseline unexpectedly blocked');
foreach ($rows as $i => $row) {
    $insert = $manifest['edits'][$i]['insert_html'] . "\n";
    check(strpos($row['candidate'], $insert) === 0, 'not an exact prepend');
    check(substr($row['candidate'], strlen($insert)) === $row['source'], 'original source drift');
    check(hash('sha256', $row['source']) === $row['source_sha'], 'source snapshot SHA mismatch');
    check(hash('sha256', $row['candidate']) === $row['candidate_sha'], 'candidate SHA mismatch');
}
$receipt = NRY_Git_Publisher::execute($manifest);
check(count($options) === 1, 'originals not stored as one immutable private backup');
$backedUp = reset($options);
check(count($backedUp) === 2, 'backup not covering both articles');
check($backedUp[0]['source_sha'] === $rows[0]['source_sha'] &&
    $backedUp[1]['source_sha'] === $rows[1]['source_sha'], 'backup hashes mismatch');
check($receipt['status'] === 'applied_and_verified', 'verified server apply failed');
check($receipt['writes'] === 2 && $receipt['changed'] === array(50011, 53630),
    'wrong number or IDs of writes');
$again = NRY_Git_Publisher::execute($manifest);
check($again['status'] === 'applied_and_verified' && $again['writes'] === 0 &&
    $again['already_applied'] === 2, 'not idempotent');
foreach ($manifest['edits'] as $edit) {
    $id = $edit['post_id'];
    $posts[$id]->post_content = '<p>' . str_repeat('B', $edit['source_bytes'] - 7) . '</p>';
    $posts[$id]->post_modified_gmt = $edit['source_modified_gmt'];
}
$posts[50011]->post_content .= 'X';
$plan = NRY_Git_Publisher::plan($manifest);
check($plan[0]['status'] === 'blocked' &&
    $plan[0]['reason'] === 'source_modified_or_bytes_drift', 'byte drift allowed');
$noWrites = NRY_Git_Publisher::execute($manifest);
check($noWrites['status'] === 'blocked_no_writes' &&
    $noWrites['writes'] === 0, 'partial write allowed after drift');
$changed = $manifest;
$changed['edits'][0]['post_id'] = 99999;
check(is_wp_error(NRY_Git_Publisher::validate_manifest($changed)), 'unapproved ID allowed');
$changed = $manifest;
$changed['edits'][0]['insert_html'] .= '<script>alert(1)</script>';
check(is_wp_error(NRY_Git_Publisher::validate_manifest($changed)), 'script allowed');
$changed = $manifest;
$changed['edits'][1]['insert_html'] = str_replace(
    'https://na-ryby.eu/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/',
    'https://fake-affiliate.example/destination',
    $changed['edits'][1]['insert_html']
);
check(is_wp_error(NRY_Git_Publisher::validate_manifest($changed)), 'external link allowed');
$changed = $manifest;
$changed['edits'][0]['post_id'] = 38064;
check(is_wp_error(NRY_Git_Publisher::validate_manifest($changed)), 'protected ID allowed');
echo "ok: v5 validates, pins baseline, preserves source, rolls out exactly two, is idempotent and fails closed\n";
