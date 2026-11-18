<?php
/** Offline, fail-closed tests. Never contacts merchants or WordPress. */
define('ABSPATH', __DIR__);
function add_filter($name, $cb, $priority) { return true; }
function add_action($name, $cb, $priority) { return true; }
function is_single($id) { return $id === 17606; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return 17606; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return 'https://na-ryby.eu/bungalov-u-prehrady-hracholusky/'; }
$GLOBALS['post_fixture'] = (object)array(
    'ID'=>17606, 'post_type'=>'post', 'post_status'=>'publish',
    'post_name'=>'bungalov-u-prehrady-hracholusky',
    'post_modified_gmt'=>'2018-02-01 19:51:47',
    'post_content'=>'<div class="agoda-booking-box">'
        .'https://www.anrdoezrs.net/click-8287231-15735403?url=https%3A%2F%2Fwww.agoda.com%2Fsearch%3Fcity%3D1420%26textToSearch%3DHracholusky%26ds%3DNVXXV6AsTxxuHYjr'
        .'* Garantovaná nejnižší cena</div>',
);
function get_post($id) { return $GLOBALS['post_fixture']; }
require dirname(__DIR__) . '/hracholusky-stay-trust-17606-v1.php';

function check($ok, $label) { if (!$ok) throw new RuntimeException($label); }
$class = 'NRY_Hracholusky_Stay_Trust_17606_V1';
$old = $class::LEGACY_URL;
$good = '<p>Starý popis bungalovu</p>'
    .'<a href="https://na-ryby.eu/jiny-pruvodce/">Původní interní odkaz</a>'
    .'<div class="agoda-booking-box" style="border:2px solid #0056b3">'
    .'<h4>🏡 Ubytování u revíru Hracholusky</h4>'
    .'<p>Našli jsme pro vás nejlepší penziony v okolí.</p>'
    .'<a href="'.$old.'">Zobrazit nabídky na nejlepší ubytování</a>'
    .'<div style="color:#999">* Garantovaná nejnižší cena a možnost zrušení zdarma u většiny objektů.</div>'
    .'</div><p>Původní obsah pokračuje</p>';
$fixed = $class::transform($good);
check($fixed !== $good, 'approved candidate must be changed');
check(substr_count($fixed, $class::MARKER) === 1, 'one marker');
check(strpos($fixed, $old) === false, 'unapproved Agoda path removed');
check(strpos($fixed, 'Garantovaná nejnižší cena') === false, 'price guarantee removed');
check(substr_count($fixed, $class::DESTINATION) === 1, 'one real source-reviewed internal route');
check(strpos($fixed, 'Starý popis bungalovu') !== false, 'old accommodation kept');
check(strpos($fixed, 'Původní obsah pokračuje') !== false, 'content after box kept');
check(strpos($fixed, 'https://na-ryby.eu/jiny-pruvodce/') !== false, 'old other links kept');
check($class::transform($fixed) === $fixed, 'idempotence');
check($class::render($good) === $fixed, 'source-eligible article changes');

foreach (array(
    str_replace($old, 'https://malicious.example/offer', $good),
    str_replace('Garantovaná nejnižší cena', 'nejlepší cena', $good),
    str_replace('Našli jsme pro vás nejlepší penziony', 'Náhodný katalog', $good),
    str_replace('agoda-booking-box', 'booking-box', $good),
    $good.$good,
    $good.'<a href="'.$class::DESTINATION.'">duplikát</a>',
) as $bad) {
    check($class::transform($bad) === $bad, 'unreviewed or duplicate HTML must fail closed');
}
$GLOBALS['post_fixture']->post_modified_gmt = '2026-11-18 12:00:00';
check($class::render($good) === $good, 'source drift blocks live render');
$GLOBALS['post_fixture']->post_modified_gmt = '2018-02-01 19:51:47';
$GLOBALS['post_fixture']->post_content = 'Changed source';
check($class::render($good) === $good, 'raw source changes block live render');
echo "PASS: post 17606 trust-only render, no new merchant clicks/writes, drift fail-closed\n";
