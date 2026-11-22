<?php
define('ABSPATH', true);
define('OBJECT', 'OBJECT');
$slugs=array(
    'wellness-a-rybolov-v-jednom-luxusni-ubytovani-pro-narocne-rybare',
    'wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice',
);
$GLOBALS['entries']=array();
foreach ($slugs as $i=>$slug) {
    $GLOBALS['entries'][$slug]=(object)array(
        'ID'=>120000+$i, 'post_name'=>$slug,'post_type'=>'post',
        'post_status'=>'publish','post_content'=>str_repeat('source text ', 150),
        'post_modified_gmt'=>'2026-11-20 12:34:56');
}
function add_action($name,$callback,$priority=10) {}
function get_page_by_path($slug,$object,$type) { return $GLOBALS['entries'][$slug] ?? null; }
function get_permalink($id) {
    foreach($GLOBALS['entries'] as $slug=>$p) if ((int)$p->ID===$id) return 'https://na-ryby.eu/'.$slug.'/';
    return '';
}
require __DIR__.'/../wellness-evergreen-source-proof-v1.php';
function nry_assert($ok,$msg) { if (!$ok) {fwrite(STDERR,"FAIL ".$msg."\n"); exit(1);} }
$r=NRY_Wellness_Evergreen_Source_V1::report();
nry_assert($r['publicationAuthorized']===false && count($r['targets'])===2,'scope');
foreach ($r['targets'] as $t) {
    nry_assert($t['state']==='SOURCE_SHA_AVAILABLE_NO_PUBLICATION','source ready');
    nry_assert($t['sourceSha256']===hash('sha256',str_repeat('source text ',150)),'sha');
    nry_assert($t['wordpressWrites']===0 && $t['merchantRequests']===0,'no external writes');
}
$slug=$slugs[0];
$GLOBALS['entries'][$slug]->post_status='draft';
nry_assert(NRY_Wellness_Evergreen_Source_V1::snapshot($slug)['state']==='BLOCKED_SOURCE_IDENTITY','draft fail closed');
$GLOBALS['entries'][$slug]->post_status='publish';
$GLOBALS['entries'][$slug]->post_name='editor-changed';
nry_assert(NRY_Wellness_Evergreen_Source_V1::snapshot($slug)['state']==='BLOCKED_SOURCE_IDENTITY','slug drift');
nry_assert(NRY_Wellness_Evergreen_Source_V1::snapshot('unexpected')['state']==='BLOCKED_SOURCE_IDENTITY','unknown target');
echo "WELLNESS_EVERGREEN_SOURCE_CONTRACT_PASS\n";
