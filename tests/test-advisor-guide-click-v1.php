<?php
define('ABSPATH','/test/');
$GLOBALS['calls']=array();
$GLOBALS['hook']=array();
$GLOBALS['site']='https://na-ryby.eu';
$GLOBALS['post_id']=119865;
$GLOBALS['is_page']=true;
$GLOBALS['singular']=true;
function add_action($name,$func,$priority=10){$GLOBALS['hook'][]=array($name,$func,$priority);}
function is_page($id){return $id===119865&&$GLOBALS['is_page'];}
function is_singular($type){return $type==='page'&&$GLOBALS['singular'];}
function get_queried_object_id(){return $GLOBALS['post_id'];}
function get_permalink($id){return $id===119865?
  'https://na-ryby.eu/poradce-vybavy/':'https://na-ryby.eu/other/';}
function home_url($s){return $GLOBALS['site'].$s;}
function plugins_url($p,$base){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$p;}
function wp_enqueue_script($handle,$url,$deps,$version,$footer){
  $GLOBALS['calls'][]=array($handle,$url,$deps,$version,$footer);
}
function ok($v,$desc){if(!$v){fwrite(STDERR,"FAIL $desc\n");exit(1);}}
require dirname(__DIR__).'/advisor-guide-click-telemetry-v1.php';
ok(count($GLOBALS['hook'])===1,'exactly one listener');
ok($GLOBALS['hook'][0]===array('wp_enqueue_scripts','nry_advisor_guide_click_enqueue',25),
  'one WordPress enqueue hook');
nry_advisor_guide_click_enqueue();
ok(count($GLOBALS['calls'])===1,'only exact advisor page receives script');
ok($GLOBALS['calls'][0][0]==='nry-advisor-guide-click-v1','handle');
ok(strpos($GLOBALS['calls'][0][1],'assets/nry-advisor-guide-click-v1.js')!==false,'local asset');
ok($GLOBALS['calls'][0][2]===array()&&$GLOBALS['calls'][0][3]==='1.0.0'&&$GLOBALS['calls'][0][4]===true,'local footer script');
$GLOBALS['calls']=array();
$GLOBALS['post_id']=123;
nry_advisor_guide_click_enqueue();
ok(count($GLOBALS['calls'])===0,'another page blocked');
$GLOBALS['post_id']=119865;
$GLOBALS['is_page']=false;
nry_advisor_guide_click_enqueue();
ok(count($GLOBALS['calls'])===0,'wrong WP page blocked');
$GLOBALS['is_page']=true;$GLOBALS['singular']=false;
nry_advisor_guide_click_enqueue();
ok(count($GLOBALS['calls'])===0,'posts and search blocked');
$GLOBALS['singular']=true;$GLOBALS['site']='https://other.example';
nry_advisor_guide_click_enqueue();
ok(count($GLOBALS['calls'])===0,'wrong site blocked');
$code=file_get_contents(dirname(__DIR__).'/advisor-guide-click-telemetry-v1.php');
ok(!preg_match('/\b(?:wp_update_post|wp_insert_post|update_option|update_post_meta|delete_option)\s*\(/',$code),
  'no WP content/meta writes');
echo "PASS: exact advisor 119865 script only, no WordPress writes or new third-party destinations\n";
