<?php
/** Exact homepage enqueue test; no WP or network calls. */
define('ABSPATH', '/');
$GLOBALS['home_ctx'] = array(
    'front'=>true, 'page'=>true, 'admin'=>false, 'preview'=>false,
    'id'=>118641, 'url'=>'https://na-ryby.eu/', 'site'=>'https://na-ryby.eu/',
    'type'=>'page', 'status'=>'publish', 'slug'=>'na-ryby-eu-vse-pro-rybare'
);
$GLOBALS['home_hooks'] = array();
$GLOBALS['home_scripts'] = array();
$GLOBALS['home_styles'] = array();
function add_action($hook,$fn,$priority=10){$GLOBALS['home_hooks'][]=array($hook,$fn,$priority);}
function is_front_page(){return $GLOBALS['home_ctx']['front'];}
function is_page($id){return $GLOBALS['home_ctx']['page'] && $id===$GLOBALS['home_ctx']['id'];}
function is_admin(){return $GLOBALS['home_ctx']['admin'];}
function is_preview(){return $GLOBALS['home_ctx']['preview'];}
function get_queried_object_id(){return $GLOBALS['home_ctx']['id'];}
function get_permalink($id){return $GLOBALS['home_ctx']['url'];}
function home_url($path='/'){return $GLOBALS['home_ctx']['site'];}
function get_post($id){return (object) array(
    'ID'=>$GLOBALS['home_ctx']['id'], 'post_type'=>$GLOBALS['home_ctx']['type'],
    'post_status'=>$GLOBALS['home_ctx']['status'], 'post_name'=>$GLOBALS['home_ctx']['slug']
);}
function plugins_url($file,$base){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$file;}
function wp_enqueue_script(...$args){$GLOBALS['home_scripts'][]=$args;}
function wp_enqueue_style(...$args){$GLOBALS['home_styles'][]=$args;}
function check_home($pass,$why){if(!$pass){fwrite(STDERR,"FAIL $why\n");exit(1);}}
require dirname(__DIR__).'/home-buyer-entry-measurement-v1.php';
check_home($GLOBALS['home_hooks']===array(array('wp_enqueue_scripts','nry_home_buyer_entry_enqueue_v1',25)),'exact hook');
nry_home_buyer_entry_enqueue_v1();
check_home(count($GLOBALS['home_scripts'])===1,'exact one script');
check_home($GLOBALS['home_scripts'][0][0]==='nry-home-buyer-entry-v1','script handle');
check_home(strpos($GLOBALS['home_scripts'][0][1],'/assets/nry-home-buyer-entry-v1.js')!==false,'asset path');
check_home($GLOBALS['home_scripts'][0][3]==='1.2.0' && $GLOBALS['home_scripts'][0][4]===true,'version and footer');
check_home(count($GLOBALS['home_styles'])===1,'exact one style');
check_home($GLOBALS['home_styles'][0][0]==='nry-home-buyer-entry-style-v1','style handle');
check_home(strpos($GLOBALS['home_styles'][0][1],'/assets/nry-home-buyer-entry-v1.css')!==false,'local CSS path');
check_home($GLOBALS['home_styles'][0][3]==='1.1.0' && $GLOBALS['home_styles'][0][4]==='all','CSS version and media');
foreach (array('front'=>false,'page'=>false,'admin'=>true,'preview'=>true,'id'=>118642,
    'url'=>'https://na-ryby.eu/other/','site'=>'https://other.example/',
    'type'=>'post','status'=>'draft','slug'=>'different') as $key=>$value) {
    $old=$GLOBALS['home_ctx'][$key]; $GLOBALS['home_ctx'][$key]=$value;
    $GLOBALS['home_scripts']=array();
    $GLOBALS['home_styles']=array();
    nry_home_buyer_entry_enqueue_v1();
    check_home($GLOBALS['home_scripts']===array(),'wrong context must not enqueue: '.$key);
    check_home($GLOBALS['home_styles']===array(),'wrong context must not style: '.$key);
    $GLOBALS['home_ctx'][$key]=$old;
}
echo "PASS homepage-only script and scoped style enqueue; no WordPress writes\n";
