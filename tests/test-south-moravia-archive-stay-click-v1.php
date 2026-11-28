<?php
/** Isolated exact post hook for one 2017 South Moravia editorial landing. */
define('ABSPATH', '/');
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['scripts']=array();
$GLOBALS['post']=(object)array(
 'ID'=>2651, 'post_name'=>'rybareni-s-ubytovanim-jihomoravsky-kraj',
 'post_status'=>'publish','post_type'=>'post',
 'post_date_gmt'=>'2017-07-04 10:16:21',
 'post_title'=>'Rybaření s ubytováním - Jihomoravský kraj',
 'post_content'=>str_repeat('2017 archiv. ',30));
$GLOBALS['url']='https://na-ryby.eu/rybareni-s-ubytovanim-jihomoravsky-kraj/';
$GLOBALS['host']='https://na-ryby.eu';
$GLOBALS['enabled']=true;
$GLOBALS['admin']=false;
function add_filter($a,$c,$p=10){}
function add_action($a,$c,$p=10){}
function is_single($id){return $GLOBALS['enabled'] && $id==='rybareni-s-ubytovanim-jihomoravsky-kraj';}
function is_singular($type){return $type==='post';}
function get_queried_object_id(){return 2651;}
function is_admin(){return $GLOBALS['admin'];}
function is_preview(){return false;}
function is_feed(){return false;}
function is_paged(){return false;}
function home_url($path='/'){return $GLOBALS['host'];}
function get_permalink($id){return $GLOBALS['url'];}
function get_post($id){return $GLOBALS['post'];}
function plugins_url($p,$f){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$p;}
function wp_enqueue_script($h,$u,$d,$v,$f){$GLOBALS['scripts'][]=compact('h','u','d','v','f');}
require dirname(__DIR__).'/south-moravia-stays-archive-v1.php';
$c='NRY_South_Moravia_Stays_Archive_V1';
function check($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL ".$msg."\n");exit(1);}}
$c::click_js();
check(count($GLOBALS['scripts'])===1 && $GLOBALS['scripts'][0]['h']==='nry-archive-stay-click-v1'
 && str_ends_with($GLOBALS['scripts'][0]['u'],'/assets/nry-archive-stay-click-v1.js')
 && $GLOBALS['scripts'][0]['v']==='1.1.0' && $GLOBALS['scripts'][0]['f']===true,
 'only own canonical published post queues local script');
foreach(array('enabled','admin','host','url','post') as $k){
 $orig=$GLOBALS[$k];
 if($k==='enabled')$GLOBALS[$k]=false;
 if($k==='admin')$GLOBALS[$k]=true;
 if($k==='host')$GLOBALS[$k]='https://other.example';
 if($k==='url')$GLOBALS[$k]='https://na-ryby.eu/other/';
 if($k==='post')$GLOBALS[$k]=(object)array_merge((array)$orig,array('post_status'=>'draft'));
 $GLOBALS['scripts']=array();$c::click_js();
 check(count($GLOBALS['scripts'])===0,'unsafe page disabled: '.$k);
 $GLOBALS[$k]=$orig;
}
echo "SOUTH_MORAVIA_EXACT_TELEMETRY_PHP_PASS\n";
