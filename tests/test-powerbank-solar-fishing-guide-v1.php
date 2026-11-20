<?php
/** Offline PHP contract, no requests or WordPress writes. */
define('ABSPATH', __DIR__);
define('OBJECT', 'OBJECT');
$GLOBALS['hooked'] = array();
$GLOBALS['ctx'] = array('origin'=>'https://na-ryby.eu/', 'collision'=>false,
    'single'=>true, 'singular'=>true, 'admin'=>false, 'preview'=>false,
    'main'=>true, 'loop'=>true, 'id'=>61257, 'permalink'=>'https://na-ryby.eu/zavazeci-lodky-pro-rybare/',
    'slug'=>'zavazeci-lodky-pro-rybare', 'status'=>'publish', 'type'=>'post',
    'modified'=>'2026-05-13 10:22:17');
function add_action($hook,$fn,$priority=10){$GLOBALS['hooked'][]=array($hook,$fn,$priority);}
function add_filter($hook,$fn,$priority=10){$GLOBALS['hooked'][]=array($hook,$fn,$priority);}
function get_page_by_path($slug,$obj,$type){return false;}
function get_post_types($a,$type){return array('post','page','attachment');}
function get_post_stati($a,$type){return array('publish','draft','private','future','trash');}
function get_posts($args){return $GLOBALS['ctx']['collision']?array(99):array();}
function home_url($path='/'){return $GLOBALS['ctx']['origin'];}
function is_ssl(){return true;}
function is_single($id){return $GLOBALS['ctx']['single'] && $id===$GLOBALS['ctx']['id'];}
function is_singular($type){return $GLOBALS['ctx']['singular'] && $type==='post';}
function is_admin(){return $GLOBALS['ctx']['admin'];}
function is_preview(){return $GLOBALS['ctx']['preview'];}
function is_main_query(){return $GLOBALS['ctx']['main'];}
function in_the_loop(){return $GLOBALS['ctx']['loop'];}
function get_queried_object_id(){return $GLOBALS['ctx']['id'];}
function get_permalink($id){return $GLOBALS['ctx']['permalink'];}
function get_post($id){return (object)array('ID'=>$GLOBALS['ctx']['id'],
    'post_name'=>$GLOBALS['ctx']['slug'], 'post_status'=>$GLOBALS['ctx']['status'],
    'post_type'=>$GLOBALS['ctx']['type'], 'post_modified_gmt'=>$GLOBALS['ctx']['modified']);}
function check($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL: {$msg}\n");exit(1);}}

require dirname(__DIR__).'/powerbank-solar-fishing-guide-v1.php';
$c='NRY_Powerbank_Solar_Fishing_Guide_V1';
$asset=$c::approved_bytes();
check(is_string($asset) && strlen($asset)>9000,'reviewed, SHA-pinned static asset');
check($c::ready(),'origin and collision-safe allowlist ready');
check(substr_count($asset, $c::URL) >= 2,'canonical and self-link refer to exact route');
check(substr_count($asset, 'https://www.foxint.com/home/product/')===2,
    'only two primary-source manufacturer product references');
check(strpos($asset,'data-nry-whcalc="v1"')!==false,'self-contained Wh calculator');
check(strpos($asset,'Externí regulátor')===false,'no unqualified universal regulator claim');
check(in_array(array('template_redirect',array($c,'serve'),-4),$GLOBALS['hooked'],true),
    'narrow pre-canonical static GET route registered');
check(in_array(array('init',array($c,'register_entry'),10),$GLOBALS['hooked'],true),
    'discoverability filter initialized on WP init');
$c::register_entry();
check(in_array(array('the_content',array($c,'entry'),60),$GLOBALS['hooked'],true),
    'after existing exact-post baitboat-choice content filter');

$base='<h1>Zavážecí loďky</h1><aside data-nry-baitboat-choice-link="v1">Návštěvnický výběr</aside>'
    .'<p><a href="http://www.dpbolvw.net/click-100927011-12347575?url=SOURCE">'
    .'Původní affiliate odkaz</a></p>'.str_repeat('<p>Historický text článku.</p>',190);
check(strlen($base)>5000,'representative source fixture length');
$out=$c::entry($base);
check($out!==$base && substr_count($out,$c::ENTRY_MARKER)===1,'one contextual internal CTA');
check(substr_count($out,$c::URL)===1,'one exact new guide link');
check(strpos($out,$base)===0,'original editorial and affiliate bytes unchanged');
check($c::entry($out)===$out,'idempotent');
$marker_drift=str_replace('data-nry-baitboat-choice-link="v1"','changed',$base);
check($c::entry($marker_drift)===$marker_drift,
    'drifted existing marker prevents insertion');

foreach(array('single'=>false,'singular'=>false,'admin'=>true,'preview'=>true,
    'main'=>false,'loop'=>false,'id'=>123,'slug'=>'other','status'=>'draft',
    'type'=>'page','modified'=>'2026-11-20 10:00:00',
    'origin'=>'https://other.example/','permalink'=>'https://na-ryby.eu/other/') as $key=>$value){
    $old=$GLOBALS['ctx'][$key];$GLOBALS['ctx'][$key]=$value;
    check($c::entry($base)===$base,'unexpected WP context rejected: '.$key);
    $GLOBALS['ctx'][$key]=$old;
}
$GLOBALS['ctx']['collision']=true;
check(!$c::ready(),'existing WP slug collision blocks serving');
$GLOBALS['ctx']['collision']=false;
$GLOBALS['ctx']['origin']='https://other.example/';
check(!$c::ready(),'origin drift blocks serving');
$GLOBALS['ctx']['origin']='https://na-ryby.eu/';
$_SERVER['REQUEST_METHOD']='POST';$_SERVER['HTTP_HOST']='na-ryby.eu';
$_SERVER['REQUEST_URI']='/powerbanka-nebo-solarni-panel-na-ryby/';
ob_start();$c::serve();$output=ob_get_clean();
check($output==='','non-GET/HEAD never serves a page');
$_SERVER['REQUEST_METHOD']='GET';$_SERVER['REQUEST_URI']='/other/';
ob_start();$c::serve();$output=ob_get_clean();
check($output==='','unlisted URL never serves the page');
echo "PASS: SHA-bound Wh guide, one source-pinned internal link, no WP writes or external requests\n";
