<?php
/** Consent-aware measurement must not mutate content or track other articles. */
define('ABSPATH', __DIR__);
$GLOBALS['queue']=array();
$GLOBALS['id']=42401;
$GLOBALS['path']='https://na-ryby.eu/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/';
$GLOBALS['singular']=true;
function add_action($a,$callback,$priority=10) {
    $GLOBALS['action']=array($a,$callback,$priority);
}
function is_singular($type){return $type==='post'&&$GLOBALS['singular'];}
function get_queried_object_id(){return $GLOBALS['id'];}
function get_permalink($id){return $GLOBALS['path'];}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_script($handle,$src,$deps,$version,$footer){
    $GLOBALS['queue'][]=compact('handle','src','deps','version','footer');
}
function must($good,$m){if(!$good){fwrite(STDERR,'FAIL: '.$m."\n");exit(1);}}
require dirname(__DIR__).'/buyer-click-telemetry-v1.php';
must($GLOBALS['action']===array('wp_enqueue_scripts','nry_buyer_click_enqueue',24),'correct hook');
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===1,'real pípáky buyer instrumented');
must($GLOBALS['queue'][0]['footer']===true,'async footer');
must($GLOBALS['queue'][0]['deps']===array(),'no third-party tracker introduced');
must(str_ends_with($GLOBALS['queue'][0]['src'],'/assets/nry-buyer-clicks-v1.js'),'only bundled local asset');
$GLOBALS['queue']=array();
$GLOBALS['id']=118218;
$GLOBALS['path']='https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/';
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===1,'buyer guide is approved');
$GLOBALS['queue']=array();
$GLOBALS['id']=35755;
$GLOBALS['path']='https://na-ryby.eu/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/';
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===0,'unapproved sibling has no telemetry');
$GLOBALS['id']=42401;
$GLOBALS['path']='https://other.example/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/';
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===0,'outside host blocked');
$GLOBALS['path']='https://na-ryby.eu/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/';
$GLOBALS['singular']=false;
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===0,'nonpost context blocked');
must(nry_buyer_click_approved_scope(42401,'/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/'),'exact approved canonical');
must(!nry_buyer_click_approved_scope(42401,'/signalizatory/'),'no arbitrary target');
echo "PASS: only two exact published routes enqueue privacy-bounded first-party JS\n";
