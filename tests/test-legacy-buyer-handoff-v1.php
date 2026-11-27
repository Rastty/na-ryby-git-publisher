<?php
/** Four existing review handoffs: scoped PHP enqueue, offline only. */
define('ABSPATH', __DIR__);
$GLOBALS['id']=999;
$GLOBALS['queue']=array();
$GLOBALS['site']='https://na-ryby.eu';
$GLOBALS['admin']=false;
$GLOBALS['preview']=false;
$GLOBALS['post']=null;
function add_action($a,$c,$p=10){}
function is_singular($type){return $type==='post';}
function is_single($id){return $id===$GLOBALS['id'];}
function get_queried_object_id(){return $GLOBALS['id'];}
function get_permalink($id){return $GLOBALS['site'].'/'.$GLOBALS['post']->post_name.'/';}
function get_post($id){return $GLOBALS['post'];}
function home_url($path){return $GLOBALS['site'].'/';}
function is_admin(){return $GLOBALS['admin'];}
function is_preview(){return $GLOBALS['preview'];}
function plugins_url($path,$file){return $GLOBALS['site'].'/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_script($handle,$src,$deps,$version,$footer){$GLOBALS['queue'][]=compact('handle','src','deps','version','footer');}
function must($v,$msg){if(!$v){fwrite(STDERR,"FAIL: ".$msg."\n");exit(1);}}
require dirname(__DIR__).'/buyer-click-telemetry-v1.php';
$rows=array(
 'recenze-a-zkusenosti-lowrance-echolot-hds-live-7-se-sondou-active-imaging-3v1-baterie-nabijecka-zdarma'=>array('Lowrance Echolot HDS LIVE 7','HDS LIVE 7','ZDARMA'),
 'lowrance-echolot-hook-4-chirp-gps'=>array('Lowrance Echolot Hook-4','Hook-4','CHIRP'),
 'holdcarp-solarni-panel-foldable-solar-system-20w-recenze'=>array('Holdcarp Foldable Solar System 20W','Foldable Solar System 20W','HODNOCENÍ'),
 'holdcarp-solarni-panel-foldable-solar-system-40w-recenze'=>array('Holdcarp Foldable Solar System 40W','Foldable Solar System 40W','HODNOCENÍ')
);
foreach($rows as $slug=>$markers){
    $_SERVER['REQUEST_METHOD']='GET';
    $GLOBALS['post']=(object)array('ID'=>999,'post_name'=>$slug,'post_type'=>'post',
        'post_status'=>'publish','post_title'=>$markers[0],'post_content'=>$markers[1].' '.$markers[2]);
    $GLOBALS['queue']=array();
    nry_buyer_click_enqueue();
    must(count($GLOBALS['queue'])===1 && $GLOBALS['queue'][0]['handle']==='nry-legacy-buyer-handoff-v1'
        && $GLOBALS['queue'][0]['version']==='1.0.0' && $GLOBALS['queue'][0]['footer']===true
        && $GLOBALS['queue'][0]['deps']===array()
        && substr($GLOBALS['queue'][0]['src'],-strlen('/assets/nry-legacy-buyer-handoff-v1.js'))==='/assets/nry-legacy-buyer-handoff-v1.js','exact page queues one local script '.$slug);
    $GLOBALS['queue']=array();
    $GLOBALS['post']->post_content='unexpected changed content';
    nry_buyer_click_enqueue();must(count($GLOBALS['queue'])===0,'changed source rejected');
    $GLOBALS['post']->post_content=$markers[1].' '.$markers[2];
    $GLOBALS['preview']=true;
    nry_buyer_click_enqueue();must(count($GLOBALS['queue'])===0,'preview rejected');
    $GLOBALS['preview']=false;
    $GLOBALS['admin']=true;
    nry_buyer_click_enqueue();must(count($GLOBALS['queue'])===0,'admin rejected');
    $GLOBALS['admin']=false;
    $GLOBALS['post']->post_status='draft';
    nry_buyer_click_enqueue();must(count($GLOBALS['queue'])===0,'draft rejected');
    $GLOBALS['post']->post_status='publish';
    $_SERVER['REQUEST_METHOD']='POST';
    nry_buyer_click_enqueue();must(count($GLOBALS['queue'])===0,'non-GET rejected');
    $_SERVER['REQUEST_METHOD']='GET';
    must(!nry_legacy_buyer_handoff_scope(38064,'/'.$slug.'/'),'protected post rejected');
}
$GLOBALS['site']='https://other.example';
nry_buyer_click_enqueue();must(count($GLOBALS['queue'])===0,'foreign host rejected');
$GLOBALS['site']='https://na-ryby.eu';
$GLOBALS['post']->post_name='unrelated';
nry_buyer_click_enqueue();must(count($GLOBALS['queue'])===0,'unrelated path rejected');
echo "LEGACY_BUYER_HANDOFF_PHP_PASS four exact local enqueues; zero network or DB writes\n";
