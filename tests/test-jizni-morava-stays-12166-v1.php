<?php
/** Offline source-lock and article-preservation test. No WordPress writes. */
define('ABSPATH', __DIR__);
$GLOBALS['jm_id'] = 12166;
$GLOBALS['jm_url'] = 'https://na-ryby.eu/rybarska-dovolena-jizni-morava/';
$GLOBALS['jm_post'] = (object)array(
    'ID'=>12166, 'post_type'=>'post', 'post_status'=>'publish',
    'post_name'=>'rybarska-dovolena-jizni-morava',
    'post_modified_gmt'=>'2000-01-01 00:00:00', 'post_content'=>'drift');
$GLOBALS['jm_styles'] = array();
function add_filter($name,$callback,$priority) { $GLOBALS['jm_priority']=$priority; return true; }
function add_action($name,$callback,$priority) { return true; }
function is_single($id) { return $GLOBALS['jm_id']===$id; }
function is_singular($name) { return $name==='post'; }
function get_queried_object_id() { return $GLOBALS['jm_id']; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($url) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return $GLOBALS['jm_url']; }
function get_post($id) { return $GLOBALS['jm_post']; }
function plugins_url($path,$file) { return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path; }
function wp_enqueue_style($handle,$src,$deps,$version) {
    $GLOBALS['jm_styles'][] = compact('handle','src','deps','version');
}
function check($ok,$msg) { if (!$ok) throw new RuntimeException($msg); }
require dirname(__DIR__).'/jizni-morava-stays-12166-v1.php';
$class = 'NRY_Jizni_Morava_Stays_12166_V1';
check(($GLOBALS['jm_priority'] ?? null) === PHP_INT_MAX, 'after WP content formatting');
check((bool)preg_match('/^[0-9a-f]{64}$/', $class::SOURCE_SHA256), 'source SHA must be real, never pending');
check((bool)preg_match('/^20\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $class::MODIFIED_GMT), 'source GMT must be actual');
$old = base64_decode(file_get_contents(__DIR__.'/fixtures/jizni-morava-12166-wp-rendered-20261118.b64'),true);
check(is_string($old) && strlen($old)>15000, 'complete authentic WP content.rendered fixture');
check(substr_count($old,$class::INTRO)===1, 'one reviewed formatted introduction');
check(substr_count($old,$class::LEGACY_AGODA)===1, 'one wrong-region Agoda redirect in source');
$new = $class::transform($old);
check($new!==$old, 'actual WordPress rendered article receives source-locked replacement');
check(substr_count($new,$class::MARKER)===1, 'one regional offer block');
check(substr_count($new,$class::ARCHIVE_MARKER)===1, 'one historical inventory warning');
check(substr_count($new,'data-nry-offer-id=')===3, 'three vetted partner offers');
check(substr_count($new,'<div id="su-post-')===substr_count($old,'<div id="su-post-'),
    'all historical catalog entries preserved');
check(strpos($old,'<strong>Olomouc</strong>')!==false && strpos($new,'<strong>Olomouc</strong>')===false,
    'incorrect Moravia to Olomouc hotel box removed');
check(strpos($new,$class::LEGACY_AGODA)===false && strpos($new,'Garantovaná nejnižší cena')===false,
    'unapproved legacy redirect and unsupported price guarantee removed');
check(strpos($new,'href="#'.$class::ARCHIVE_ID.'"')!==false,
    'legacy footer links to the reviewed regional choice');
check($class::transform($new)===$new, 'idempotent');
check($class::transform($old.$old)===$old.$old, 'duplicate source phrase blocks insert');
$drift = str_replace('Chata Brněnská přehrada','jiné', $old);
check($class::transform($drift)===$drift, 'lost archival landmark fails closed');
$drift = str_replace('Olomouc</strong>','Brno</strong>', $old);
check($class::transform($drift)===$drift, 'legacy target drift cannot remove an unreviewed box');
foreach (array($class::SLEVOMAT,$class::HPORTAL,$class::HAUZI) as $href) {
    check(substr_count($new,htmlspecialchars($href,ENT_QUOTES,'UTF-8'))===1,
        'exact owner-derived affiliate URL once');
}
check(strpos($class::SLEVOMAT,'j3d7tsgc_na-ryby.eu')!==false, 'canonical CZ owner campaign');
check(strpos($class::HAUZI,'o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz')!==false,
    'Hauzi encoded approved property destination');
check($class::render($old)===$old, 'unmatched WordPress source blocks modification');
// Preserve exact authenticated post_content including its native CRLFs.
// The Base64 fixture avoids normalizing raw WordPress source newlines.
$source = base64_decode(file_get_contents(__DIR__.'/fixtures/jizni-morava-12166-source-20261118.b64'),true);
check(is_string($source) && strlen($source)===1749, 'authenticated raw source bytes complete');
check(hash('sha256',$source)===$class::SOURCE_SHA256, 'WordPress raw source SHA pinned');
$GLOBALS['jm_post']->post_content = $source;
$GLOBALS['jm_post']->post_modified_gmt = $class::MODIFIED_GMT;
check($class::render($old)===$new, 'exact authenticated post allows safe frontend preview');
$class::enqueue();
check(count($GLOBALS['jm_styles'])===1, 'one scoped stylesheet on source-matched article');
$GLOBALS['jm_post']->post_content .= 'drift';
check($class::render($old)===$old, 'source drift prevents insertion');
$GLOBALS['jm_styles']=array();$class::enqueue();
check(count($GLOBALS['jm_styles'])===0, 'source drift prevents asset enqueue');
echo "PASS: South Moravia 12166 source-bound frontend offers, source preserved, no WP write\n";
