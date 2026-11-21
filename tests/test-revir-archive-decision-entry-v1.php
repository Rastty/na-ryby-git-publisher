<?php
// Offline only: no network, WordPress database or merchant requests.
define('ABSPATH', __DIR__);
$GLOBALS['mode'] = array('category'=>true,'admin'=>false,'feed'=>false,'paged'=>false,
    'preview'=>false,'ajax'=>false,'host'=>'https://na-ryby.eu',
    'url'=>'https://na-ryby.eu/category/soukrome-rybarske-reviry/',
    'term'=>(object)array('taxonomy'=>'category','slug'=>'soukrome-rybarske-reviry','term_id'=>62,'parent'=>0));
$GLOBALS['actions'] = array();
$GLOBALS['styles'] = array();
function add_action($tag,$fn,$priority=10,$count=1){$GLOBALS['actions'][$tag]=$fn;}
function is_category($slug){return $slug==='soukrome-rybarske-reviry' && $GLOBALS['mode']['category'];}
function is_admin(){return $GLOBALS['mode']['admin'];}
function is_feed(){return $GLOBALS['mode']['feed'];}
function is_paged(){return $GLOBALS['mode']['paged'];}
function is_preview(){return $GLOBALS['mode']['preview'];}
function wp_doing_ajax(){return $GLOBALS['mode']['ajax'];}
function home_url($unused='/'){return $GLOBALS['mode']['host'];}
function get_queried_object(){return $GLOBALS['mode']['term'];}
function get_term_link($term){return $GLOBALS['mode']['url'];}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_style($handle,$url,$deps,$version){$GLOBALS['styles'][]=array($handle,$url,$version);}
final class Fake_Query {private $main;function __construct($m){$this->main=$m;}function is_main_query(){return $this->main;}}
function check_revir($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL ".$msg."\n");exit(1);}}
require dirname(__DIR__).'/revir-archive-decision-entry-v1.php';
$class='NRY_Revir_Archive_Decision_Entry_V1';
check_revir(isset($GLOBALS['actions']['loop_start']) && isset($GLOBALS['actions']['wp_enqueue_scripts']),'two precise hooks');
$markup=$class::block();
check_revir(substr_count($markup,$class::MARKER)===1,'one archive marker');
foreach (array($class::REGION_URL,$class::WATER_URL,$class::STAY_URL,$class::RULES_URL) as $url) {
    check_revir(substr_count($markup,'href="'.$url.'"')===1,'exact useful URL');
}
check_revir(substr_count($markup,'<a href=')===4,'four same-site links only');
$wrong=new Fake_Query(false);$main=new Fake_Query(true);
ob_start();$class::render_on_main_loop($wrong);
check_revir(ob_get_clean()==='','secondary loop ignored');
foreach(array('category','admin','feed','paged','preview','ajax','host','url','term') as $k) {
    $original=$GLOBALS['mode'][$k];
    if($k==='category')$GLOBALS['mode'][$k]=false;
    elseif(in_array($k,array('admin','feed','paged','preview','ajax'),true))$GLOBALS['mode'][$k]=true;
    elseif($k==='host')$GLOBALS['mode'][$k]='https://other.example';
    elseif($k==='url')$GLOBALS['mode'][$k]='https://na-ryby.eu/other/';
    else $GLOBALS['mode'][$k]=(object)array('taxonomy'=>'category','slug'=>'other','term_id'=>1,'parent'=>0);
    ob_start();$class::render_on_main_loop($main);$printed=ob_get_clean();
    check_revir($printed==='','fail closed on '.$k);
    $GLOBALS['mode'][$k]=$original;
}
$class::enqueue();
check_revir(count($GLOBALS['styles'])===1 && strpos($GLOBALS['styles'][0][1],'nry-revir-archive-entry-v1.css')!==false,'targeted CSS');
ob_start();$class::render_on_main_loop($main);$first=ob_get_clean();
ob_start();$class::render_on_main_loop($main);$second=ob_get_clean();
check_revir($first===$markup && $second==='','only once in the main loop');
echo "REVIEW_ARCHIVE_OFFLINE_VERIFIED\n";
