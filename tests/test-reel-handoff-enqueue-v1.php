<?php
// Isolated WordPress hook contract; no real network, WordPress, or user events.
define('ABSPATH', __DIR__);
$GLOBALS['page']=true;$GLOBALS['singular']=true;$GLOBALS['id']=118015;
$GLOBALS['canonical']='https://na-ryby.eu/navijaky-dashboard/';
$GLOBALS['home']='https://na-ryby.eu/';
$GLOBALS['hooks']=array();$GLOBALS['scripts']=array();
function add_action($name,$fn,$priority=10){$GLOBALS['hooks'][$name][]=$fn;}
function add_filter($name,$fn,$priority=10){$GLOBALS['hooks'][$name][]=$fn;}
function is_page($id){return $id===118015 && $GLOBALS['page'];}
function is_singular($type){return $type==='page' && $GLOBALS['singular'];}
function get_queried_object_id(){return $GLOBALS['id'];}
function get_permalink($id){return $GLOBALS['canonical'];}
function home_url($path='/'){return $GLOBALS['home'];}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_script($handle,$src,$deps,$ver,$footer){$GLOBALS['scripts'][]=compact('handle','src','deps','ver','footer');}
function must($cond,$msg){if(!$cond)throw new RuntimeException($msg);echo "PASS: $msg\n";}
require __DIR__.'/../reel-dashboard-nextsteps-v1.php';
must(isset($GLOBALS['hooks']['wp_enqueue_scripts']),'safe WordPress enqueue hook registered');
NRY_Reel_Dashboard_Nextsteps_V1::enqueue();
must(count($GLOBALS['scripts'])===1,'exact page enqueues one local asset');
must($GLOBALS['scripts'][0]['ver']==='1.0.0','version matches GET-only asset probe');
must(str_ends_with($GLOBALS['scripts'][0]['src'],'/assets/nry-reel-handoff-v1.js'),'exact local asset');
must($GLOBALS['scripts'][0]['footer']===true && $GLOBALS['scripts'][0]['deps']===array(),'no external dependency');
foreach(array('page','singular','id','canonical','home') as $name){
  $original=$GLOBALS[$name];
  $GLOBALS[$name]=($name==='id')?119865:(($name==='canonical')?'https://na-ryby.eu/poradce-vybavy/':(($name==='home')?'https://other.example/':false));
  $GLOBALS['scripts']=array();
  NRY_Reel_Dashboard_Nextsteps_V1::enqueue();
  must(count($GLOBALS['scripts'])===0,'unapproved context '.$name.' excluded');
  $GLOBALS[$name]=$original;
}
echo "PASS: exact reel dashboard only; no WP writes, merchant or click traffic\n";
