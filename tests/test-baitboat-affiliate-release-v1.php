<?php
/** Offline test. No merchant click, WordPress mutation, or network request. */
define('ABSPATH', __DIR__);
define('OBJECT', 'OBJECT');
$GLOBALS['nry_hooks'] = array();
$GLOBALS['nry_options'] = array();
function add_action($name, $handler, $priority=10) { $GLOBALS['nry_hooks'][]=array($name,$handler,$priority); }
function add_filter($name, $handler, $priority=10) { $GLOBALS['nry_hooks'][]=array($name,$handler,$priority); }
function get_option($name, $fallback=false) { return $GLOBALS['nry_options'][$name] ?? $fallback; }
function home_url($path='/') { return 'https://na-ryby.eu' . $path; }
function get_page_by_path($path, $object, $kind) { return false; }
function get_post_types($a,$b) { return array('post','page'); }
function get_post_stati($a,$b) { return array('publish'); }
function get_posts($args) { return array(); }
function is_singular() { return true; }
function check($yes, $reason) { if (!$yes) { fwrite(STDERR, "FAIL: $reason\n"); exit(1); } }

require dirname(__DIR__) . '/baitboat-release-v3.php';
require dirname(__DIR__) . '/baitboat-affiliate-release-v1.php';
$v3 = NRY_Baitboat_Release_V3::asset_sha();
check(is_string($v3) && strlen($v3)===64, 'intact production V3 asset');
$GLOBALS['nry_options'][NRY_Baitboat_Release_V3::APPROVAL] = array('image_rights_confirmed'=>true,'sha256'=>$v3);
$gate = NRY_Baitboat_Affiliate_Release_V1::inspection();
check($gate['ready'] && !$gate['approved'] && !$gate['conflict'], 'V3 unaffected before opt-in');
$source = file_get_contents(dirname(__DIR__).'/assets/baitboat-picker-v3.html');
$render = NRY_Baitboat_Affiliate_Release_V1::selector_html($source);
check(is_string($render) && hash('sha256',$render)===$gate['sha256'], 'pinned affiliate output');
check(substr_count($render,'data-nry-baitboat-rc="v4-cj"')===1, 'correct public version marker');
check(strpos($render,'Po zapojení schválených provizních odkazů')===false, 'no future affiliate language');
check(strpos($render,'Odkazy na nabídky Chyť a pusť jsou affiliate.')!==false, 'honest current disclosure');
check(strpos($source,'data-nry-baitboat-rc="v3"')!==false, 'V3 remains unmodified');
check(strlen($render)>strlen($source), 'all affiliate wrappers present');
foreach(NRY_Baitboat_Affiliate_Release_V1::selector_targets() as $merchant => $count){
    $tracked=NRY_Baitboat_Affiliate_Release_V1::cj_link($merchant,true);
    // The category tracking URL is a prefix of six product tracking URLs;
    // count only a complete URL terminated by the original HTML/JS quote.
    check($tracked!==false && substr_count($render,$tracked.'"')===$count, 'all links wrapped exactly: '.$merchant);
    check(substr_count($render,$merchant)===0, 'no naked merchant link: '.$merchant);
    $parsed=parse_url($tracked);
    check(($parsed['host']??'')==='www.anrdoezrs.net' && ($parsed['path']??'')==='/click-8287231-15734253', 'own CJ identity');
    parse_str($parsed['query'], $params);
    check(count($params)===1 && ($params['url']??null)===$merchant, 'exact destination survives one encode/decode');
}
check(NRY_Baitboat_Affiliate_Release_V1::selector_html(str_replace('data-nry-baitboat-rc="v3"','changed',$source))===false, 'version fail closed');
check(NRY_Baitboat_Affiliate_Release_V1::selector_html(str_replace('Deeper-Zavazeci-lod-SPARK','fake-product',$source))===false, 'unexpected product fails closed');
foreach(array(
    'http://www.chytapust.cz/a/b', 'https://www.chytapust.cz/a/b?utm_source=test',
    'https://www.chytapust.cz/a/b#review', 'https://chytapust.cz/a/b',
    'https://www.chytapust.cz.evil.test/a/b', 'https://www.parys.cz/a/b',
    'javascript:alert(1)', 'https://www.chytapust.cz/a/b/c',
    'https://www.chytapust.cz/Zavazeci-lodky-2165/'
) as $bad){ check(NRY_Baitboat_Affiliate_Release_V1::cj_link($bad)===false, 'refuse malformed/unapproved direct url: '.$bad); }
check(NRY_Baitboat_Affiliate_Release_V1::cj_link('https://www.chytapust.cz/test-kategorie/Model-Kod')!==false,'other verified-format Chyť a pusť product eligible');
$GLOBALS['nry_options'][NRY_Baitboat_Affiliate_Release_V1::APPROVAL] = array('sha256'=>$gate['sha256'],'source_sha'=>$v3);
check(NRY_Baitboat_Affiliate_Release_V1::inspection()['approved']===true, 'exact owner approval enables routing');
$GLOBALS['nry_options'][NRY_Baitboat_Affiliate_Release_V1::APPROVAL]['sha256'] = str_repeat('0',64);
check(NRY_Baitboat_Affiliate_Release_V1::inspection()['conflict']===true, 'changed fingerprint blocks routing');
unset($GLOBALS['nry_options'][NRY_Baitboat_Affiliate_Release_V1::APPROVAL]);
check(NRY_Baitboat_Affiliate_Release_V1::inspection()['approved']===false, 'reversible gate immediately disables');
check(in_array(array('template_redirect',array('NRY_Baitboat_Affiliate_Release_V1','serve'),-3),$GLOBALS['nry_hooks'],true), 'V4 takes priority before untouched V3');
check(in_array(array('init',array('NRY_Baitboat_Affiliate_Release_V1','register_content_filter'),10),$GLOBALS['nry_hooks'],true), 'defer article filter until WP init');
NRY_Baitboat_Affiliate_Release_V1::register_content_filter();
check(in_array(array('the_content',array('NRY_Baitboat_Affiliate_Release_V1','article_content'),99),$GLOBALS['nry_hooks'],true), 'sitewide article filter registered on init');
echo "PASS: 21 source occurrences -> 21 own-property CJ links, approval/rollback, no direct merchant leakage\n";
