<?php
/** Offline source-locked render, attribution and non-target safety contract. */
define('ABSPATH', __DIR__);
$GLOBALS['nry_id'] = 13088;
$GLOBALS['nry_admin'] = false;
$GLOBALS['nry_loop'] = true;
$GLOBALS['nry_preview'] = false;
$GLOBALS['nry_modified'] = '2017-07-04 10:16:21';
$GLOBALS['nry_permalink'] = 'https://na-ryby.eu/rybareni-s-ubytovanim-jihocesky-kraj/';
$GLOBALS['nry_styles'] = array();
function is_single($id) { return $GLOBALS['nry_id'] === $id; }
function is_singular($kind) { return $kind === 'post'; }
function get_queried_object_id() { return $GLOBALS['nry_id']; }
function is_main_query() { return true; }
function in_the_loop() { return $GLOBALS['nry_loop']; }
function is_admin() { return $GLOBALS['nry_admin']; }
function is_preview() { return $GLOBALS['nry_preview']; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return $GLOBALS['nry_permalink']; }
function get_post($id) {
    return (object)array('ID' => $id, 'post_type' => 'post', 'post_status' => 'publish',
        'post_name' => 'rybareni-s-ubytovanim-jihocesky-kraj',
        'post_modified_gmt' => $GLOBALS['nry_modified'],
        'post_content' => str_repeat('[su_posts posts_per_page="5"]', 5));
}
function add_filter($hook, $callback, $priority) {}
function add_action($hook, $callback, $priority) {}
function plugins_url($path, $file) { return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path; }
function wp_enqueue_style($handle, $url, $deps, $version) {
    $GLOBALS['nry_styles'][] = array($handle, $url, $version);
}
function check($value, $message) { if (!$value) { throw new RuntimeException($message); } }
require dirname(__DIR__).'/jihocesky-lipno-stays-13088-v1.php';
$cl = 'NRY_Jihocesky_Lipno_Stays_13088_V1';
// Pinned, anonymous 512-byte REST source prefix; the appended content is a
// synthetic fixture and is NOT presented as the full historical WP source.
$prefix = base64_decode(
    'PHA+Jm5ic3A7PC9wPgo8ZGl2IGNsYXNzPSJzdS10YWJsZSBzdS10YWJsZS1hbHRlcm5hdGUiPgo8dGFibGU+Cjx0Ym9keT4KPHRyPgo8dGQ+UmV2w61yeTwvdGQ+Cjx0ZD5VYnl0b3bDoW7DrTwvdGQ+CjwvdHI+Cjx0cj4KPHRkPgoKPGRpdiBjbGFzcz0ic3UtcG9zdHMgc3UtcG9zdHMtZGVmYXVsdC1sb29wICI+CgoJCgkJCQkJCgkJCQoJCQk8ZGl2IGlkPSJzdS1wb3N0LTE3NTMwIiBjbGFzcz0ic3UtcG9zdCAiPgoKCQkJCQkJCQkJPGEgY2xhc3M9InN1LXBvc3QtdGh1bWJuYWlsIiBocmVmPSJodHRwczovL25hLXJ5YnkuZXUvc3BvcnRvdm5pLXJ5Ym9sb3YtcmFzZWxpbmEvIj48aW1nIGxvYWRpbmc9ImxhenkiIGRlY29kaW5nPSJhc3luYyIgd2lkdGg9IjU2MCIgaGVpZ2h0PSIzMTUiIHNyYz0iaHR0cHM6Ly9uYS1yeWJ5LmV1L3dwLWNvbnRlbnQvdXBsb2Fkcy8yMDE3LzEyL0xvd3JhbmNlLUVsaXRlLTctSERJLTIxLnBuZyIgY2xhc3M9ImF0dGFjaG1lbnQtcG9zdC10aHVtYm5haWwgc2l6ZS1wb3M=',
    true);
check(is_string($prefix) && strlen($prefix) === 512, 'PINNED_PREFIX_BAD_BYTES');
check(hash('sha256', $prefix) === $cl::PREFIX_SHA256, 'PINNED_PREFIX_BAD_SHA');
check($cl::source_prefix_allowed($cl::PREFIX_SHA256), 'REST_PREFIX_REJECTED');
check($cl::source_prefix_allowed($cl::FRONTEND_PREFIX_SHA256), 'OBSERVED_FRONTEND_PREFIX_REJECTED');
check(!$cl::source_prefix_allowed(str_repeat('0', 64)), 'UNREVIEWED_PREFIX_ACCEPTED');
check(!$cl::source_prefix_allowed(null), 'NULL_PREFIX_ACCEPTED');
check($cl::FRONTEND_PREFIX_SHA256 !== $cl::PREFIX_SHA256, 'DIAGNOSTIC_NOOP');
$old = $prefix.str_repeat(' ', 7100)
    .'<div id="su-post-17753" class="su-post ">'
    .'<a href="https://na-ryby.eu/apartmany-nadeje/">Apartmány Naděje</a>';
$new = $cl::render($old);
check($new !== $old && substr($new, -strlen($old)) === $old, 'ORIGINAL_HTML_NOT_PRESERVED');
check(substr_count($new, $cl::MARKER) === 1, 'BLOCK_NOT_EXACT_ONCE');
check(substr_count($new, 'data-nry-jc-offer=') === 2, 'OFFER_COUNT');
check(substr_count($new, 'rel="sponsored nofollow noopener"') === 2, 'AFFILIATE_DISCLOSURE');
check(substr_count($new, htmlspecialchars($cl::HPORTAL, ENT_QUOTES, 'UTF-8')) === 1, 'HPORTAL_LINK');
check(substr_count($new, htmlspecialchars($cl::HAUZI, ENT_QUOTES, 'UTF-8')) === 1, 'HAUZI_LINK');
check(substr_count($new, $cl::STAYS) === 1, 'INTERNAL_HANDOFF');
check($cl::render($new) === $new, 'DUPLICATE_INSERTION');
check($cl::render('short source') === 'short source', 'SHORT_SOURCE_MUTATED');
check($cl::render('x'.$old) === 'x'.$old, 'PREFIX_DRIFT_NOT_BLOCKED');
check($cl::render(str_replace('su-post-17753', 'su-post-99999', $old)) === str_replace('su-post-17753', 'su-post-99999', $old), 'ANCHOR_DRIFT_NOT_BLOCKED');
foreach (array(array('nry_id', 22), array('nry_admin', true), array('nry_loop', false),
               array('nry_preview', true), array('nry_modified', '2026-11-19 00:00:00'),
               array('nry_permalink', 'https://na-ryby.eu/other/')) as $case) {
    list($variable, $changed) = $case;
    $original = $GLOBALS[$variable];
    $GLOBALS[$variable] = $changed;
    check($cl::render($old) === $old, 'WRONG_CONTEXT_MUTATED_'.$variable);
    $GLOBALS[$variable] = $original;
}
$cl::enqueue();
check(count($GLOBALS['nry_styles']) === 1
    && $GLOBALS['nry_styles'][0][2] === '1.0.0', 'STYLESHEET_CONTRACT');
$parts = parse_url($cl::HPORTAL);
parse_str($parts['query'] ?? '', $q);
check(($parts['host'] ?? '') === 'go.dognet.com'
    && ($q['chid'] ?? '') === '5QoXiSqx'
    && ($q['url'] ?? '') === 'https://hportal.cz/cs/horni-plana/penzion-u-kohoutu/'
    && count($q) === 2, 'HPORTAL_DEEP_LINK_DRIFT');
$parts = parse_url($cl::HAUZI);
parse_str($parts['query'] ?? '', $q);
check(($parts['host'] ?? '') === 'tracking.affiliateport.eu'
    && ($q['o'] ?? '') === '1990' && ($q['a'] ?? '') === '1736'
    && ($q['sub_id5'] ?? '') === 'https://www.hauzi.cz/komfortny-penzion-so-saunou-na-lipne'
    && count($q) === 3, 'HAUZI_DEEP_LINK_DRIFT');
// The diagnostic never changes a response without the exact opt-in query.
ob_start();
$cl::probe_footer();
check(ob_get_clean() === '', 'UNREQUESTED_PROBE_OUTPUT');
$_GET['nry_jc_guard_probe'] = 'source_v1';
check($cl::render($old) === $new, 'PROBE_CHANGED_RENDER_OUTPUT');
ob_start();
$cl::probe_footer();
$probeHtml = ob_get_clean();
check(preg_match('/<!-- NRY_JC13088_PROBE_V1 (\\{.*\\}) -->/', $probeHtml, $probeMatches) === 1,
    'PROBE_COMMENT_MISSING');
$probe = json_decode($probeMatches[1], true);
check(is_array($probe) && $probe['status'] === 'JIHOCESKY_13088_GUARD_PROBE_V1'
    && $probe['postId'] === 13088 && $probe['renderCalls'] === 1
    && $probe['best']['anchorMask'] === 127
    && $probe['best']['prefixAllowed'] === true
    && $probe['best']['eligible'] === true, 'PROBE_BOUNDS_OR_FLAGS_DRIFT');
unset($_GET['nry_jc_guard_probe']);
ob_start();
$cl::probe_footer();
check(ob_get_clean() === '', 'PROBE_LEAK_WITHOUT_QUERY');
echo "JIHOCESKY_LIPNO_13088_CONTRACT_OK\n";
