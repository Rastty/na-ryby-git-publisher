<?php
/** Offline reviewed-span contract: no links, tracking or WordPress writes. */
define('ABSPATH', __DIR__);
require dirname(__DIR__) . '/chair-hub-historical-ratings-60341-v1.php';

function check_hub($condition, $message) {
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
}

$class = 'NRY_Chair_Hub_Historical_Ratings_60341_V1';
$method = new ReflectionMethod($class, 'replacements');
$method->setAccessible(true);
$pairs = $method->invoke(null);
check_hub(count($pairs) === 10, 'ten exact reviewed claim replacements');

$store = '<a href="https://na-ryby.eu/kresla" class="su-button">Původní odkaz na nabídky</a>';
$cjs = '<a href="https://www.anrdoezrs.net/click-8287231-15734253?url=test&amp;subid=1">Původní affiliate</a>';
$fixture = '<section data-nry-editorial-edit="chair-hub-trust-60341-v1">'
    . '<p>Existující výběrová tabulka a odkazy zůstávají.</p></section>'
    . implode("\n", array_keys($pairs))
    . '<p><strong>klíčová slova:</strong> rybářské křeslo, židlička na ryby, pohodlí, ergonomický design</p>'
    . str_repeat('<h3>Hodnocení:</h3>', 3)
    . '<p><strong>Hodnocení: </strong>⭐⭐⭐⭐⭐</p>'
    . '<p><strong>Hodnocení: </strong>⭐⭐⭐⭐</p>'
    . '<p><strong>Hodnocení: </strong>⭐⭐⭐⭐⭐</p>'
    . $store . $cjs . str_repeat('<p>Stávající průvodce zůstává beze změny.</p>', 300);

$result = $class::transform($fixture);
check_hub($result !== $fixture, 'reviewed historical content changes');
check_hub(substr_count($result, $class::MARKER) === 1, 'one public marker');
check_hub(strpos($result, '⭐⭐') === false, 'all unsupported stars removed');
check_hub(strpos($result, 'klíčová slova:') === false, 'editorial keyword list removed');
check_hub(substr_count($result, 'Co ověřit před koupí') === 3, 'three review sections fixed');
check_hub(substr_count($result, 'Historická zmínka bez ověřeného') === 3,
    'three rating paragraphs neutralized');
foreach ($pairs as $old => $new) {
    check_hub(strpos($result, $old) === false, 'old claim no longer displayed');
    check_hub(strpos($result, $new) !== false, 'reviewed replacement displayed');
}
check_hub(substr_count($result, $store) === 1 && substr_count($result, $cjs) === 1,
    'all original merchant hrefs and attribution bytes preserved');
check_hub($class::transform($result) === $result, 'idempotent');
check_hub($class::transform(str_replace($class::SOURCE_MARKER, '', $fixture))
    === str_replace($class::SOURCE_MARKER, '', $fixture), 'no owner marker fail closed');
check_hub($class::transform(str_replace($class::SOURCE_MARKER,
    $class::SOURCE_MARKER . $class::SOURCE_MARKER, $fixture))
    === str_replace($class::SOURCE_MARKER,
        $class::SOURCE_MARKER . $class::SOURCE_MARKER, $fixture),
    'duplicate owner marker fail closed');
$drift = str_replace('Mivardi Křeslo Premium', 'Mivardi Changed Premium', $fixture);
check_hub($class::transform($drift) === $drift, 'changed product heading blocks whole transaction');
$unknown = $fixture . '<p>Další neověřené ⭐⭐⭐⭐⭐ hvězdy.</p>';
check_hub($class::transform($unknown) === $unknown, 'unreviewed star occurrence blocks edit');
check_hub($class::transform('short text') === 'short text', 'short content ignored');

// Render is not a write: without a pinned matching WordPress source it must
// return the original content even if the visible reviewed spans match.
$GLOBALS['hub_test_post'] = (object)array(
    'ID' => 60341, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => $class::SLUG, 'post_modified_gmt' => $class::MODIFIED_GMT,
    'post_content' => str_repeat('Z', $class::SOURCE_BYTES));
function is_single($id) { return $id === 60341; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return 60341; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) {
    return 'https://na-ryby.eu/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/';
}
function get_post($id) { return $GLOBALS['hub_test_post']; }
check_hub($class::render($fixture) === $fixture, 'wrong post-content SHA blocks frontend edit');

echo "PASS: chair hub reviewed historical ratings cleaned, original links preserved, source drift safe\n";
