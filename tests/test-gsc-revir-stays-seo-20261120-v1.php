<?php
/** Fully offline three-URL GSC snippet contract; no WordPress writes. */
define('ABSPATH', __DIR__);
$GLOBALS['gsc_hooks'] = array();
$GLOBALS['gsc_actions'] = array();
$GLOBALS['gsc_id'] = 12166;
$GLOBALS['gsc_admin'] = false;
$GLOBALS['gsc_preview'] = false;
$GLOBALS['gsc_home'] = 'https://na-ryby.eu/';
$GLOBALS['gsc_permalink'] = 'https://na-ryby.eu/rybarska-dovolena-jizni-morava/';
$source = base64_decode(file_get_contents(__DIR__.'/fixtures/jizni-morava-12166-source-20261118.b64'), true);
if (!is_string($source) || hash('sha256', $source)
    !== '2bdb8599825facdb6bd90393136074ea5af40efe3b265e58ab75da99e0ff9587') {
    throw new RuntimeException('authenticated test fixture missing or modified');
}
$GLOBALS['gsc_post'] = (object)array(
    'ID' => 12166, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => 'rybarska-dovolena-jizni-morava',
    'post_modified_gmt' => '2017-06-28 21:26:22', 'post_content' => $source,
);
function add_filter($name, $handler, $priority) {
    $GLOBALS['gsc_hooks'][$name] = array($handler, $priority);
}
function add_action($name, $handler, $priority) {
    $GLOBALS['gsc_actions'][$name] = array($handler, $priority);
}
function is_single($id) { return $GLOBALS['gsc_id'] === $id; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return $GLOBALS['gsc_id']; }
function is_admin() { return $GLOBALS['gsc_admin']; }
function is_preview() { return $GLOBALS['gsc_preview']; }
function home_url($path) { return $GLOBALS['gsc_home']; }
function get_permalink($id) { return $GLOBALS['gsc_permalink']; }
function get_post($id) { return $id === $GLOBALS['gsc_id'] ? $GLOBALS['gsc_post'] : null; }
function gsc_assert($condition, $reason) { if (!$condition) throw new RuntimeException($reason); }

require dirname(__DIR__).'/gsc-revir-stays-seo-20261120-v1.php';
$class = 'NRY_GSC_Revir_Stays_SEO_20261120_V1';
gsc_assert(count($GLOBALS['gsc_hooks']) === 2, 'only two Yoast output filters');
gsc_assert(isset($GLOBALS['gsc_actions']['wp_footer']), 'only opt-in diagnostic footer hook');
gsc_assert(($GLOBALS['gsc_hooks']['wpseo_title'][1] ?? null) === 20, 'Yoast title priority');
gsc_assert(($GLOBALS['gsc_hooks']['wpseo_metadesc'][1] ?? null) === 20, 'Yoast meta priority');
gsc_assert($class::title('Rybářská dovolená jižní morava')
    === 'Rybářská dovolená jižní Morava: chaty a revíry', 'source-matched SEO title');
$meta = $class::description('');
gsc_assert(strpos($meta, 'Nové Mlýny') !== false && strpos($meta, 'pravidla lovu') !== false,
    'missing SEO description receives useful search-intent summary');
gsc_assert($class::title('Autorem změněný SEO titulek') === 'Autorem změněný SEO titulek',
    'editor title wins');
gsc_assert($class::description('Vlastní SEO popis') === 'Vlastní SEO popis',
    'editor description wins');
gsc_assert($class::title(array('other')) === array('other'), 'unexpected type fail closed');

$GLOBALS['gsc_post']->post_content .= 'drift';
gsc_assert($class::title('Rybářská dovolená jižní morava') === 'Rybářská dovolená jižní morava',
    'raw source drift blocks changes');
$GLOBALS['gsc_post']->post_content = $source;
$GLOBALS['gsc_post']->post_modified_gmt = '2026-11-20 00:00:00';
gsc_assert($class::description('') === '', 'editor date change blocks changes');
$GLOBALS['gsc_post']->post_modified_gmt = '2017-06-28 21:26:22';
$GLOBALS['gsc_post']->post_name = 'other-slug';
gsc_assert($class::description('') === '', 'slug change blocks changes');
$GLOBALS['gsc_post']->post_name = 'rybarska-dovolena-jizni-morava';
$GLOBALS['gsc_post']->post_status = 'draft';
gsc_assert($class::description('') === '', 'unpublished post blocks changes');
$GLOBALS['gsc_post']->post_status = 'publish';
$GLOBALS['gsc_permalink'] = 'https://na-ryby.eu/changed/';
gsc_assert($class::description('') === '', 'permalink drift blocks changes');
$GLOBALS['gsc_permalink'] = 'https://na-ryby.eu/rybarska-dovolena-jizni-morava/';
$GLOBALS['gsc_home'] = 'https://another-site.cz/';
gsc_assert($class::description('') === '', 'other site blocks changes');
$GLOBALS['gsc_home'] = 'https://na-ryby.eu/';
$GLOBALS['gsc_preview'] = true;
gsc_assert($class::description('') === '', 'preview blocks changes');
$GLOBALS['gsc_preview'] = false;
$GLOBALS['gsc_admin'] = true;
gsc_assert($class::description('') === '', 'admin blocks changes');
$GLOBALS['gsc_admin'] = false;
$GLOBALS['gsc_id'] = 9999;
gsc_assert($class::description('') === '', 'unrelated URL blocks changes');

$reflection = new ReflectionMethod($class, 'records');
$reflection->setAccessible(true);
$records = $reflection->invoke(null);
gsc_assert(count($records) === 3, 'exactly three reviewed URLs');
foreach (array(14517 => 'dyje6-stay-trust-14517-v1.php',
    17591 => 'brno-reservoir-trust-stays-17591-v1.php',
    12166 => 'jizni-morava-stays-12166-v1.php') as $id => $file) {
    $content = file_get_contents(dirname(__DIR__).'/'.$file);
    gsc_assert(strpos($content, $records[$id]['sha256']) !== false, 'source SHA shared with '.$id);
    gsc_assert(strpos($content, $records[$id]['modified']) !== false, 'GMT shared with '.$id);
    gsc_assert(strpos($content, $records[$id]['slug']) !== false, 'slug shared with '.$id);
    gsc_assert(strpos($records[$id]['title'], '2026') === false, 'avoid a dated evergreen title');
    gsc_assert(strlen($records[$id]['title']) < 100, 'bounded SEO title');
    gsc_assert(strlen($records[$id]['description']) < 250, 'bounded SEO meta');
}
// The bounded diagnostic never appears without explicit exact-post opt-in.
$GLOBALS['gsc_id'] = 12166;
ob_start();
$class::probe_footer();
gsc_assert(ob_get_clean() === '', 'probe leaked on normal visitor request');
$_GET['nry_gsc_snippet_probe'] = 'source_v1';
gsc_assert($class::title('Rybářská dovolená jižní morava')
    === 'Rybářská dovolená jižní Morava: chaty a revíry', 'probe altered SEO result');
$class::description('');
ob_start();
$class::probe_footer();
$probe_html = ob_get_clean();
gsc_assert(preg_match('/<!-- NRY_GSC_STAYS_SNIPPET_PROBE_V1 (\\{.*\\}) -->/', $probe_html, $match) === 1,
    'missing bounded diagnostic comment');
$probe = json_decode($match[1], true);
gsc_assert(is_array($probe) && $probe['postId'] === 12166
    && $probe['status'] === 'NRY_GSC_STAYS_SNIPPET_PROBE_V1'
    && $probe['eligible'] === true && $probe['sourceMatches'] === true
    && $probe['modifiedMatches'] === true
    && $probe['title']['calls'] === 1 && $probe['title']['applied'] === true
    && $probe['title']['first']['baselineMatched'] === true
    && $probe['description']['calls'] === 1 && $probe['description']['applied'] === true
    && $probe['wordpressWrites'] === 0 && $probe['merchantRequests'] === 0
    && $probe['syntheticClicks'] === 0, 'bounded probe proof drift');
$GLOBALS['gsc_post']->post_content .= 'source drift';
ob_start();
$class::probe_footer();
$probe_html = ob_get_clean();
gsc_assert(preg_match('/<!-- NRY_GSC_STAYS_SNIPPET_PROBE_V1 (\\{.*\\}) -->/', $probe_html, $match) === 1
    && json_decode($match[1], true)['eligible'] === false
    && json_decode($match[1], true)['sourceMatches'] === false,
    'source drift not visible in safe probe');
$GLOBALS['gsc_post']->post_content = $source;
$GLOBALS['gsc_id'] = 9999;
ob_start();
$class::probe_footer();
gsc_assert(ob_get_clean() === '', 'probe leaked to non-target');
unset($_GET['nry_gsc_snippet_probe']);

echo "PASS: GSC 3-page SEO snippets: exact source, current baseline, editor override, bounded opt-in diagnostic\n";
