<?php
/** Offline contracts for source-bound, render-only chair buyer paths. */
define('ABSPATH', __DIR__);
require dirname(__DIR__) . '/chair-hub-decision-paths-60341-v1.php';

function nry_path_assert($condition, $message) {
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
}
$class = 'NRY_Chair_Hub_Decision_Paths_60341_V1';
$existingCJ = '<a href="http://www.dpbolvw.net/click-7777777-12345678-1234567890123">Původní CJ</a>';
$existingShop = '<a href="https://na-ryby.eu/kresla">Aktuální nabídky</a>';
$fixture = $class::CLEANUP_MARKER
    . '<section ' . $class::SOURCE_MARKER . '><h2>Jak vybírat křeslo</h2></section>'
    . '<p>Nejprve se zamyslete nad způsobem lovu.</p>'
    . $existingCJ . $existingShop
    . $class::INSERT_BEFORE . ' starší modely.</p>'
    . str_repeat('<p>Text o pohodlí a stabilitě u vody.</p>', 45);
$result = $class::transform($fixture);
nry_path_assert($result !== $fixture, 'exact reviewed source receives nav');
nry_path_assert(substr_count($result, $class::MARKER) === 1, 'one nav marker');
nry_path_assert(substr_count($result, '<nav ') === 1, 'one navigation');
nry_path_assert(strpos($result, $existingCJ) !== false
    && strpos($result, $existingShop) !== false, 'legacy and current affiliate anchors preserved');
foreach (array($class::EVERGREEN, $class::CHAIR_120, $class::CHAIR_200) as $url) {
    nry_path_assert(substr_count($result, 'href="' . $url . '"') === 1,
        'exact internal decision target present once');
}
nry_path_assert($class::transform($result) === $result, 'idempotent');
$noOwner = str_replace($class::SOURCE_MARKER, '', $fixture);
nry_path_assert($class::transform($noOwner) === $noOwner, 'missing source marker fails closed');
$duplicate = $fixture . $class::INSERT_BEFORE;
nry_path_assert($class::transform($duplicate) === $duplicate, 'duplicate anchor fails closed');
$noCleanup = str_replace($class::CLEANUP_MARKER, '', $fixture);
nry_path_assert($class::transform($noCleanup) === $noCleanup, 'missing reviewed cleanup fails closed');
$noAnchor = str_replace($class::INSERT_BEFORE, '', $fixture);
nry_path_assert($class::transform($noAnchor) === $noAnchor, 'changed heading fails closed');
nry_path_assert($class::transform('short source') === 'short source', 'small source ignored');

function is_single($id) { return $id === 60341; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return 60341; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return NRY_Chair_Hub_Decision_Paths_60341_V1::URL; }
$GLOBALS['nry_chair_post'] = (object)array(
    'ID' => 60341, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => $class::SLUG, 'post_modified_gmt' => $class::MODIFIED_GMT,
    'post_content' => str_repeat('Z', $class::SOURCE_BYTES));
function get_post($id) { return $GLOBALS['nry_chair_post']; }
nry_path_assert($class::render($fixture) === $fixture,
    'wrong WordPress source SHA prevents render change');
$GLOBALS['nry_chair_post']->post_modified_gmt = '2026-11-15 08:00:00';
nry_path_assert($class::render($fixture) === $fixture, 'post revision fails closed');
echo "PASS: three source-pinned chair paths, untouched affiliate anchors and safe rollback\n";
