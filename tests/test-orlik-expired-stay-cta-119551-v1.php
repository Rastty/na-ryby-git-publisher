<?php
/** Offline exact old/new CTA, source pin, eligibility and attribution proof. */
define('ABSPATH', __DIR__);
$GLOBALS['nry_id'] = 119551;
$GLOBALS['nry_modified'] = '2026-05-10 09:23:09';
$GLOBALS['nry_admin'] = false;
$GLOBALS['nry_preview'] = false;
$GLOBALS['nry_loop'] = true;
$GLOBALS['nry_permalink'] = 'https://na-ryby.eu/kde-prespat-u-orliku-na-rybarsky-vikend/';
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
    return (object)array('ID'=>$id,'post_type'=>'post','post_status'=>'publish',
        'post_name'=>'kde-prespat-u-orliku-na-rybarsky-vikend',
        'post_modified_gmt'=>$GLOBALS['nry_modified'],
        'post_content'=>str_repeat('Nocleh a rybářský víkend u Orlíku. ', 700));
}
function add_filter($hook, $callback, $priority) {}
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
require dirname(__DIR__).'/orlik-expired-stay-cta-119551-v1.php';
$cl = 'NRY_Orlik_Expired_Stay_CTA_119551_V1';
$prefix = base64_decode(
    'PGgxIHN0eWxlPSJtYXJnaW46MCAwIDE4cHggMDtmb250LXNpemU6MzRweDtsaW5lLWhlaWdodDoxLjE1O2NvbG9yOiMxZjI5Mzc7Ij5LZGUgcMWZZXNwYXQgdSBPcmzDrWt1IG5hIHJ5YsOhxZlza8O9IHbDrWtlbmQ8L2gxPgo8c2VjdGlvbiBzdHlsZT0iYmFja2dyb3VuZDojZjdmYWZjO2JvcmRlcjoxcHggc29saWQgI2RiZTRlZTtib3JkZXItcmFkaXVzOjE2cHg7cGFkZGluZzoyMnB4IDIwcHg7bWFyZ2luOjAgMCAxOHB4IDA7Ij4KPGRpdiBzdHlsZT0iZGlzcGxheTpmbGV4O2ZsZXgtZGlyZWN0aW9uOmNvbHVtbjtnYXA6MTBweDsiPgo8cCBzdHlsZT0ibWFyZ2luOjA7Zm9udC1zaXplOjE4cHg7bGluZS1oZWlnaHQ6MS43O2NvbG9yOiMyNDMyNDQ7Ij48c3Ryb25nPk9ybMOtayBuZW7DrSB2b2RhIG5hIOKAnm7Em2pha8Op4oCcIHDFmWVzcMOhbsOtLjwvc3Ryb25nPiBLZHnFviBzZW0gamVkZcWhIG5hIHbDrWtlbmQsIHJvemhvZHVqZSBobGF2bsSbIHRvLCBqYWsgYnVkZcWhIG1lemkgdWJ5dG92w6E=', true);
check(strlen($prefix) === 512 && hash('sha256', $prefix) === $cl::PREFIX_SHA256, 'PINNED_SOURCE_PREFIX');
$original = $prefix.str_repeat(' ', 22000).$cl::original_first().'<br />'.$cl::original_second().'<div>Other article content</div>';
$out = $cl::render($original);
check($out !== $original, 'RENDER_STILL_ORIGINAL');
check(substr_count($out, $cl::MARKER) === 1, 'MARKER_NOT_EXACT_ONCE');
check(strpos($out, $cl::EXPIRED_OFFER) === false, 'EXPIRED_LINK_STILL_PRESENT');
check(substr_count($out, $cl::SLEVOMAT_CATEGORY) === 1, 'ORIGINAL_SLEVOMAT_LINK_CHANGED');
check(substr_count($out, htmlspecialchars($cl::HPORTAL, ENT_QUOTES, 'UTF-8')) === 1, 'NEW_DEEP_LINK_MISSING');
check(strpos($out, 'Zjistit cenu a termín') === false, 'MISLEADING_OLD_LABEL');
check(strpos($out, 'Prohlédnout pobyty ve středních Čechách') !== false, 'GENERIC_LABEL_MISSING');
check(strpos($out, 'automaticky nezahrnuje rybářský lístek') !== false, 'PERMIT_DISCLAIMER');
check(strpos($out, 'Other article content') !== false, 'UNRELATED_ARTICLE_LOST');
check($cl::render($out) === $out, 'DOUBLE_MUTATION');
check($cl::render('x'.$original) === 'x'.$original, 'PREFIX_DRIFT_NOT_BLOCKED');
$changed = str_replace('Projít další možnost','Jiná možnost',$original);
check($cl::render($changed) === $changed, 'LEGACY_ANCHOR_DRIFT_NOT_BLOCKED');
foreach (array(array('nry_id',22),array('nry_modified','2026-11-19 00:00:00'),
    array('nry_admin',true),array('nry_loop',false),array('nry_preview',true),
    array('nry_permalink','https://na-ryby.eu/other/')) as $case) {
    list($variable,$other) = $case;
    $prior = $GLOBALS[$variable]; $GLOBALS[$variable] = $other;
    check($cl::render($original) === $original, 'NON_TARGET_MUTATED_'.$variable);
    $GLOBALS[$variable] = $prior;
}
$url = parse_url($cl::HPORTAL);
parse_str($url['query'] ?? '',$q);
check(($url['host']??'') === 'go.dognet.com' && ($q['chid']??'') === '5QoXiSqx'
    && ($q['url']??'') === 'https://hportal.cz/krasna-hora-nad-vltavou/liskuv-mlyn-orlicka-prehrada/'
    && count($q) === 2, 'HPORTAL_ATTRIBUTION_DRIFT');
echo "ORLIK_EXPIRED_STAY_119551_CONTRACT_OK\n";
