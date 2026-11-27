<?php
// Self-contained fail-closed tests. No network, WP database or production clicks.
define('ABSPATH', '/');
$GLOBALS['filters'] = array();
$GLOBALS['site'] = 'https://na-ryby.eu/';
$GLOBALS['id'] = 9999;
$GLOBALS['post'] = null;
$GLOBALS['preview'] = false;
function add_filter($hook, $callback, $priority) {
    $GLOBALS['filters'][] = array($hook, $callback, $priority);
}
function is_single($id = null) { return $id === $GLOBALS['id']; }
function is_singular($type = '') { return $type === 'post'; }
function is_admin() { return false; }
function is_preview() { return $GLOBALS['preview']; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function get_queried_object_id() { return $GLOBALS['id']; }
function get_post($id) { return $GLOBALS['post']; }
function get_permalink($id) { return $GLOBALS['site'].$GLOBALS['post']->post_name.'/'; }
function home_url($path) { return $GLOBALS['site']; }
function check($ok, $name) {
    if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
require_once __DIR__.'/../legacy-sonar-buyer-bridge-v1.php';

$hdsSlug='recenze-a-zkusenosti-lowrance-echolot-hds-live-7-se-sondou-active-imaging-3v1-baterie-nabijecka-zdarma';
$hookSlug='lowrance-echolot-hook-4-chirp-gps';
$guide='https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';
$hds='<p>Lowrance Echolot HDS LIVE 7 s baterií a nabíječkou ZDARMA</p>'
    .str_repeat('<p>starší text <a href="https://www.tkqlhce.com/old">originální CJ odkaz</a></p>', 42);
$hook='<h2>Lowrance Echolot Hook-4 CHIRP GPS</h2>'
    .str_repeat('<p>historická recenze <a href="https://www.anrdoezrs.net/old">původní reklama</a></p>', 42);
check(count($GLOBALS['filters'])===1 && $GLOBALS['filters'][0][0]==='the_content'
    && $GLOBALS['filters'][0][2]===94, 'one specific render filter');

foreach (array('hds_live_7'=>$hds,'hook_4'=>$hook) as $kind=>$source) {
    $result=NRY_Legacy_Sonar_Buyer_Bridge_V1::transform($source,$kind);
    check($result!==$source && substr_count($result,'data-nry-legacy-sonar-bridge="v1"')===1,
        $kind.' one visible bridge');
    check(substr($result,-strlen($source))===$source, $kind.' original HTML and every affiliate href preserved');
    check(substr_count($result,'href="'.$guide.'"')===1, $kind.' one internal buyer handoff');
    check(NRY_Legacy_Sonar_Buyer_Bridge_V1::transform($result,$kind)===$result, $kind.' idempotent');
    check(NRY_Legacy_Sonar_Buyer_Bridge_V1::transform($source,'invalid')===$source, $kind.' unknown variant fail closed');
}
check(NRY_Legacy_Sonar_Buyer_Bridge_V1::transform($hds,'hook_4')===$hds,
    'HDS cannot receive Hook bridge');
check(NRY_Legacy_Sonar_Buyer_Bridge_V1::transform($hook,'hds_live_7')===$hook,
    'Hook cannot receive HDS bridge');
check(NRY_Legacy_Sonar_Buyer_Bridge_V1::transform('<p>HDS LIVE 7 ZDARMA</p>','hds_live_7')
    ==='<p>HDS LIVE 7 ZDARMA</p>', 'short unrelated excerpt skipped');
check(NRY_Legacy_Sonar_Buyer_Bridge_V1::transform(str_replace('ZDARMA','bonus',$hds),'hds_live_7')
    ===str_replace('ZDARMA','bonus',$hds), 'source drift fails closed');

$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['post']=(object)array(
    'ID'=>9999,'post_type'=>'post','post_status'=>'publish',
    'post_name'=>$hdsSlug,'post_title'=>'Recenze a zkušenosti – Lowrance Echolot HDS LIVE 7 se sondou',
    'post_content'=>$hds);
check(NRY_Legacy_Sonar_Buyer_Bridge_V1::render($hds)!==$hds, 'exact live-like HDS post eligible');
$GLOBALS['post']->post_status='draft';
check(NRY_Legacy_Sonar_Buyer_Bridge_V1::render($hds)===$hds, 'draft blocked');
$GLOBALS['post']->post_status='publish';
$GLOBALS['post']->post_name='unrelated';
check(NRY_Legacy_Sonar_Buyer_Bridge_V1::render($hds)===$hds, 'unrelated URL blocked');
$GLOBALS['post']->post_name=$hdsSlug;
$GLOBALS['preview']=true;
check(NRY_Legacy_Sonar_Buyer_Bridge_V1::render($hds)===$hds, 'preview blocked');
$GLOBALS['preview']=false;
$_SERVER['REQUEST_METHOD']='POST';
check(NRY_Legacy_Sonar_Buyer_Bridge_V1::render($hds)===$hds, 'non-GET blocked');
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['post']->post_name=$hookSlug;
$GLOBALS['post']->post_title='Sonar Lowrance Echolot Hook-4 CHIRP GPS – recenze';
$GLOBALS['post']->post_content=$hook;
check(NRY_Legacy_Sonar_Buyer_Bridge_V1::render($hook)!==$hook, 'exact live-like Hook post eligible');
echo "LEGACY_SONAR_TWO_OFFLINE_VERIFIED; writes=0; externalRequests=0\n";
