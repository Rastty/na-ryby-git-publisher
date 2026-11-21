<?php
/** GET-only source SHA guard. No stored WordPress edits. */
define('ABSPATH', __DIR__);
$GLOBALS['sourcePost'] = (object)array(
    'ID'=>56493, 'post_type'=>'post', 'post_status'=>'publish',
    'post_name'=>'1-nejlepsi-rybarske-reviry-v-cesku-kam-se-dostanete-autem',
    'post_modified_gmt'=>'2023-06-28 11:29:01',
    'post_content'=>str_repeat('Lorem ipsum rybářské místo. ', 100)
        .'Rybářský revír Podyjí Rybářský revír Šumava '
        .'Rybářský revír Lipno Orlická přehrada se nachází východně od Hradce Králové',
);
function add_action($a,$b,$c) {$GLOBALS['sourceHook']=array($a,$b,$c);}
function get_post($i) {return $i===56493 ? $GLOBALS['sourcePost'] : null;}
function get_permalink($i) {return $i===56493 ? NRY_Reviry_Autem_Source_56493_V1::URL : '';}
function check_source($ok,$reason) {if (!$ok) throw new RuntimeException($reason);}
require dirname(__DIR__).'/reviry-autem-source-proof-56493-v1.php';
$c='NRY_Reviry_Autem_Source_56493_V1';
check_source($GLOBALS['sourceHook']===array('template_redirect',array($c,'handle'),23),'handler missing');
$r=$c::snapshot();
check_source($r['state']==='SOURCE_SHA_AVAILABLE_NO_PUBLICATION','source absent');
check_source($r['sourceSha256']===hash('sha256',$GLOBALS['sourcePost']->post_content),'raw digest incorrect');
check_source($r['sourceBytes']===strlen($GLOBALS['sourcePost']->post_content),'raw byte count incorrect');
check_source($r['literalCounts']['wrongGeo']===1 && $r['literalCounts']['podyji']===1,'wrong literal counts');
check_source(!isset($r['post_content']) && !isset($r['content']),'raw content exposed');
check_source($r['wordpressWrites']===0 && $r['merchantRequests']===0 && $r['syntheticClicks']===0,'side effects');
foreach (array('post_status'=>'draft','post_name'=>'another-article','post_modified_gmt'=>'2023-06-28 11:29:02') as $key=>$bad) {
    $old=$GLOBALS['sourcePost']->$key;
    $GLOBALS['sourcePost']->$key=$bad;
    check_source($c::snapshot()['state']==='BLOCKED_SOURCE_DRIFT','source drift not blocked');
    $GLOBALS['sourcePost']->$key=$old;
}
echo "REVIRY_AUTEM_RAW_SOURCE_SNAPSHOT_OK\n";
