<?php
/** Offline source-contract tests. No WordPress, network or merchant requests. */
define('ABSPATH', __DIR__.'/');
$GLOBALS['nry_private_id'] = 59113;
$GLOBALS['nry_private_slug'] = 'nezapomenutelna-rybarska-dovolena-na-privatnim-reviru-s-komfortnim-ubytovanim';
$GLOBALS['nry_private_modified'] = '2023-08-18 07:08:57';
$GLOBALS['nry_private_raw'] = file_get_contents(__DIR__.'/fixtures/post-59113-20261119-rendered.html');
$GLOBALS['nry_private_admin'] = false;
$GLOBALS['nry_private_preview'] = false;
$GLOBALS['nry_private_inloop'] = true;
$GLOBALS['nry_private_styles'] = array();
$GLOBALS['nry_private_hooks'] = array();
function add_filter($tag, $callback, $priority) { $GLOBALS['nry_private_hooks'][] = array($tag, $callback, $priority); }
function add_action($tag, $callback, $priority) { $GLOBALS['nry_private_hooks'][] = array($tag, $callback, $priority); }
function is_single($id = 0) { return (int)$id === $GLOBALS['nry_private_id']; }
function is_singular($type = '') { return $type === 'post'; }
function get_queried_object_id() { return $GLOBALS['nry_private_id']; }
function is_main_query() { return true; }
function in_the_loop() { return $GLOBALS['nry_private_inloop']; }
function is_admin() { return $GLOBALS['nry_private_admin']; }
function is_preview() { return $GLOBALS['nry_private_preview']; }
function home_url($path = '') { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return NRY_Private_Fishing_Stay_Trust_59113_V1::URL; }
function get_post($id) {
    return (object)array(
        'ID'=>$GLOBALS['nry_private_id'], 'post_type'=>'post', 'post_status'=>'publish',
        'post_name'=>$GLOBALS['nry_private_slug'],
        'post_modified_gmt'=>$GLOBALS['nry_private_modified'],
        'post_content'=>$GLOBALS['nry_private_raw'],
    );
}
function plugins_url($path, $file) { return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path; }
function wp_enqueue_style($handle, $url, $deps, $version) { $GLOBALS['nry_private_styles'][] = array($handle, $url, $version); }
require __DIR__.'/../private-fishing-stay-trust-59113-v1.php';
function check_private($condition, $message) { if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
$class = 'NRY_Private_Fishing_Stay_Trust_59113_V1';
$original = $GLOBALS['nry_private_raw'];
check_private(strlen($original) > 8000, 'full archived public source fixture');
check_private(hash('sha256', substr($original, 0, 512)) === $class::RENDERED_PREFIX_SHA256, 'exact source prefix');
check_private(count($GLOBALS['nry_private_hooks']) === 2, 'frontend hooks only');
$changed = $class::render($original);
check_private($changed !== $original, 'source-matched article is repaired');
check_private(substr_count($changed, $class::MARKER) === 1, 'one reviewed marker');
check_private(substr_count($changed, 'data-nry-private-example="jedraz"') === 1, 'one source-checked example');
check_private(substr_count($changed, 'href="'.$class::STAYS.'"') === 1, 'one exact stay handoff');
check_private(substr_count($changed, 'href="'.$class::GEAR.'"') === 1, 'one gear guide link');
check_private(substr_count($changed, 'href=') === 2, 'no unreviewed external or affiliate redirects');
check_private(strpos($changed, 'Naše privátní revíry') === false, 'false owner claim removed');
check_private(strpos($changed, 'Rezervace je velmi jednoduchá') === false, 'false booking claim removed');
check_private(strpos($changed, 'www.nezapomenutelnyrevir.cz') === false, 'unverified offsite domain removed');
check_private(strpos($changed, '<!DOCTYPE html>') === false, 'nested documents removed');
check_private(strpos($changed, 'Tento článek pojednává') === false, 'internal drafting note removed');
check_private(strpos($changed, 'na dva pruty') !== false && strpos($changed, 'v sobotu') !== false, 'reviewed offer conditions');
check_private(strpos($changed, 'aktuální dostupnost') !== false, 'no inventory guarantee');
check_private($class::transform($changed) === $changed, 'no repeated transform');
check_private($class::transform('X'.substr($original, 1)) === 'X'.substr($original, 1), 'prefix drift stops');
check_private($class::transform(str_replace('Naše privátní revíry', 'Privátní revíry', $original))
    === str_replace('Naše privátní revíry', 'Privátní revíry', $original), 'editorial drift stops');
check_private($class::transform($original.$original) === $original.$original, 'duplicated article stops');
$class::enqueue();
check_private(count($GLOBALS['nry_private_styles']) === 1
    && $GLOBALS['nry_private_styles'][0][2] === '1.0.0', 'style enqueued only for eligible source');
$GLOBALS['nry_private_modified'] = '2026-11-19 00:00:00';
check_private($class::render($original) === $original, 'modified date drift stops');
$GLOBALS['nry_private_modified'] = $class::MODIFIED_GMT;
$GLOBALS['nry_private_slug'] = 'wrong';
check_private($class::render($original) === $original, 'slug drift stops');
$GLOBALS['nry_private_slug'] = $class::SLUG;
$GLOBALS['nry_private_id'] = 59114;
check_private($class::render($original) === $original, 'other post stops');
$GLOBALS['nry_private_id'] = 59113;
$GLOBALS['nry_private_admin'] = true;
check_private($class::render($original) === $original, 'admin stops');
$GLOBALS['nry_private_admin'] = false;
$GLOBALS['nry_private_preview'] = true;
check_private($class::render($original) === $original, 'preview stops');
$GLOBALS['nry_private_preview'] = false;
$GLOBALS['nry_private_inloop'] = false;
check_private($class::render($original) === $original, 'secondary loop stops');
echo "PASS: private fishing stay 59113 exact-source trust repair, internal booking handoff, no writes\n";
