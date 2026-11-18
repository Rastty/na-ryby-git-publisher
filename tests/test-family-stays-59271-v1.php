<?php
/** Offline-only tests. No WordPress writes, partner navigation or clicks. */
define('ABSPATH', __DIR__);
$GLOBALS['family_filter_calls'] = array();
$GLOBALS['family_styles'] = array();
$GLOBALS['family_wrong_query'] = false;
$GLOBALS['family_preview'] = false;
function add_filter($tag, $cb, $priority) { $GLOBALS['family_filter_calls'][] = array($tag, $cb, $priority); }
function add_action($tag, $cb, $priority) { return true; }
function is_single($id = null) { return !$GLOBALS['family_wrong_query'] && ($id === null || $id === 59271); }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return $GLOBALS['family_wrong_query'] ? 111 : 59271; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function is_admin() { return false; }
function is_preview() { return $GLOBALS['family_preview']; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return 'https://na-ryby.eu/rodinna-dovolena-u-vody-kam-na-ryby-s-detmi/'; }
function get_post($id) { return $GLOBALS['family_post']; }
function plugins_url($path, $file) { return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path; }
function wp_enqueue_style($name, $path, $deps, $ver) { $GLOBALS['family_styles'][] = array($name, $path, $ver); }
function check($assertion, $message) { if (!$assertion) throw new RuntimeException($message); }

require dirname(__DIR__) . '/family-stays-59271-v1.php';
$klass = 'NRY_Family_Stays_59271_V1';
$GLOBALS['family_post'] = (object)array(
    'ID'=>59271, 'post_type'=>'post', 'post_status'=>'publish',
    'post_name'=>'rodinna-dovolena-u-vody-kam-na-ryby-s-detmi',
    'post_modified_gmt'=>'2023-08-23 08:26:35',
    'post_content'=>'Kam na ryby s dětmi? Rybolov na řece Vltavě. Rybářská osada Dolní Vltavice.',
);
// Exact first 512 bytes of 18 Nov public REST rendered content: an immutable
// source fingerprint independent of the subsequent local test fixture body.
$prefix = '<p style="text-align: center;"><a href="https://na-ryby.eu/lov-kapra" class="su-button su-button-style-default" style="color:#ffffff;background-color:#fa0127;border-color:#c80120;border-radius:15px" target="_self"><span style="color:#ffffff;padding:11px 36px;font-size:26px;line-height:39px;border-color:#fc4e68;border-radius:15px;text-shadow:none"><i class="sui sui-arrow-right" style="font-size:26px;color:#FFFFFF"></i> Vše na KAPRY v AKCI</span></a>'
    ."\n".'<h2>Rodinná dovolená u vody: Kam na ryby s dětmi?</h2>'."\n";
check(strlen($prefix) === $klass::RENDERED_PREFIX_BYTES, 'reviewed source prefix length');
check(hash('sha256', $prefix) === $klass::RENDERED_PREFIX_SHA256, 'reviewed prefix hash');
$original = $prefix
    .'<p>Text pro rodiče bez obchodní nabídky.</p>'
    .$klass::ANCHOR
    .'<p>1. Rybníky ...</p><h3>Rybolov na řece Vltavě</h3>'
    .'<p>'.str_repeat('Stávající rodinný text. ', 80).'</p>'
    .'<h3>Rybářská osada Dolní Vltavice</h3>'
    .'<a href="https://na-ryby.eu/existing/">Starý interní odkaz</a>'
    .'<a href="https://www.lipno.info/">Starý externí odkaz</a>';
check(strlen($original) > 2000, 'sufficient source body');
check(count($GLOBALS['family_filter_calls']) === 1, 'one render filter registered');
$result = $klass::render($original);
$decoded_result = html_entity_decode($result, ENT_QUOTES, 'UTF-8');
check($result !== $original, 'matching source gets one family booking block');
check(substr_count($result, $klass::MARKER) === 1, 'exactly one section');
check(substr_count($result, 'data-nry-family-offer=') === 2, 'exactly two partner offers');
check(substr_count($decoded_result, $klass::HPORTAL_LIPNO) === 1, 'unchanged Lipno affiliate URL');
check(substr_count($decoded_result, $klass::HPORTAL_SLAPY) === 1, 'unchanged Slapy affiliate URL');
check(substr_count($result, $klass::STAYS) === 1, 'one internal comparison link');
check(substr_count($result, 'rel="sponsored nofollow noopener"') === 2, 'both affiliate disclosures');
check(strpos($result, $klass::MARKER) < strpos($result, $klass::ANCHOR), 'CTA near top');
check(strpos($result, 'https://na-ryby.eu/existing/') !== false, 'old internal link preserved');
check(strpos($result, 'https://www.lipno.info/') !== false, 'old external link preserved');
check($klass::transform($result) === $result, 'idempotent on subsequent filter pass');
$drift = 'x'.substr($original, 1);
check($klass::transform($drift) === $drift, 'source prefix drift blocks insertion');
$drift = str_replace($klass::ANCHOR, $klass::ANCHOR.$klass::ANCHOR, $original);
check($klass::transform($drift) === $drift, 'duplicate placement anchor blocks insertion');
$drift = str_replace('<h3>Rybářská osada Dolní Vltavice</h3>', '', $original);
check($klass::transform($drift) === $drift, 'missing original location blocks insertion');
$drift = $original.'<a href="'.$klass::HPORTAL_LIPNO.'">existing affiliate</a>';
check($klass::transform($drift) === $drift, 'existing partner link blocks a duplicate');
$GLOBALS['family_wrong_query'] = true;
check($klass::render($original) === $original, 'unrelated post remains untouched');
$GLOBALS['family_wrong_query'] = false;
$GLOBALS['family_preview'] = true;
check($klass::render($original) === $original, 'preview remains untouched');
$GLOBALS['family_preview'] = false;
$GLOBALS['family_post']->post_modified_gmt = '2026-11-19 00:00:00';
check($klass::render($original) === $original, 'metadata change fails closed');
$klass::enqueue();
check(count($GLOBALS['family_styles']) === 0, 'no style enqueue on drifted source');
$GLOBALS['family_post']->post_modified_gmt = '2023-08-23 08:26:35';
$klass::enqueue();
check(count($GLOBALS['family_styles']) === 1
    && strpos($GLOBALS['family_styles'][0][1], 'assets/nry-family-stays-59271-v1.css') !== false,
    'one reviewed stylesheet only for eligible published post');
echo "PASS: family stay offer source guard, unchanged affiliate IDs, links and no writes\n";
