<?php
// Strict no-network PHP fixture; no WordPress database, affiliate or merchant actions.
define('ABSPATH',__DIR__);
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['pwc'] = array('single'=>true,'admin'=>false,'preview'=>false,
    'loop'=>true,'query'=>true,'feed'=>false,'custom'=>false,
    'host'=>'https://na-ryby.eu',
    'permalink'=>'https://na-ryby.eu/nejlepsi-soukrome-reviry-pro-rybolov-v-cesku/',
    'post'=>(object)array('ID'=>73923,'post_type'=>'post','post_status'=>'publish',
        'post_name'=>'nejlepsi-soukrome-reviry-pro-rybolov-v-cesku'));
$GLOBALS['filters']=array();$GLOBALS['actions']=array();$GLOBALS['styles']=array();
function add_filter($n,$cb,$priority=10){$GLOBALS['filters'][$n]=$cb;}
function add_action($n,$cb,$priority=10){$GLOBALS['actions'][$n]=$cb;}
function is_single($slug){return $GLOBALS['pwc']['single'] && $slug==='nejlepsi-soukrome-reviry-pro-rybolov-v-cesku';}
function is_admin(){return $GLOBALS['pwc']['admin'];}
function is_preview(){return $GLOBALS['pwc']['preview'];}
function is_feed(){return $GLOBALS['pwc']['feed'];}
function is_customize_preview(){return $GLOBALS['pwc']['custom'];}
function is_main_query(){return $GLOBALS['pwc']['query'];}
function in_the_loop(){return $GLOBALS['pwc']['loop'];}
function home_url($p='/'){return $GLOBALS['pwc']['host'];}
function get_queried_object_id(){return 73923;}
function get_post($id){return $GLOBALS['pwc']['post'];}
function get_permalink($id){return $GLOBALS['pwc']['permalink'];}
function wp_enqueue_style($name,$url,$deps,$version){$GLOBALS['styles'][]=$url;}
function plugins_url($name,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$name;}
function pwcok($v,$message){if(!$v){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require dirname(__DIR__).'/private-waters-concrete-choice-v1.php';
$cls='NRY_Private_Waters_Concrete_Choice_V1';
pwcok(isset($GLOBALS['filters']['the_content']) && isset($GLOBALS['actions']['wp_enqueue_scripts']),'hooks');
$block=$cls::block();
pwcok(substr_count($block,$cls::MARKER)===1 && substr_count($block,'<article>')===4,'four-card block exactly once');
foreach(array($cls::STERKAC,$cls::RPETY,$cls::MRHAL,$cls::SPINDLER,$cls::ARCHIVE) as $url) {
    pwcok(substr_count($block,'href="'.$url.'"')===1,'five exact first party links');
}
pwcok(substr_count($block,'<a href=')===5,'no external or affiliate links');
$html='<p>Soukromé revíry umí nabídnout klid a zázemí.</p>'
    .'<h2>Podle čeho poznat dobrý soukromý revír</h2>'
    .str_repeat('<p>Praktické poradenství pro rybáře.</p>',100)
    .'<h2>Pro koho dávají soukromé revíry největší smysl</h2>';
$changed=$cls::render($html);
pwcok($changed!==$html,'rendered in main loop');
pwcok(strpos($changed,$block)<strpos($changed,'Podle čeho poznat'),'content before original first h2');
pwcok(str_replace($block,'',$changed)===$html,'original body byte-identical');
pwcok($cls::render($changed)===$changed,'idempotence');
foreach(array('single','admin','preview','loop','query','feed','custom','host','permalink','post') as $k) {
    $prior=$GLOBALS['pwc'][$k];
    if(in_array($k,array('single','loop','query'),true))$GLOBALS['pwc'][$k]=false;
    elseif(in_array($k,array('admin','preview','feed','custom'),true))$GLOBALS['pwc'][$k]=true;
    elseif($k==='host')$GLOBALS['pwc'][$k]='https://other.example';
    elseif($k==='permalink')$GLOBALS['pwc'][$k]='https://na-ryby.eu/other/';
    else $GLOBALS['pwc'][$k]=(object)array('ID'=>73923,'post_type'=>'post','post_status'=>'publish','post_name'=>'other');
    pwcok($cls::render($html)===$html,'guard '.$k);
    $GLOBALS['pwc'][$k]=$prior;
}
$old=$_SERVER['REQUEST_METHOD'];$_SERVER['REQUEST_METHOD']='POST';
pwcok($cls::render($html)===$html,'POST forbidden');
$_SERVER['REQUEST_METHOD']=$old;
foreach(array(
    str_replace($cls::ANCHOR,'Jiný nadpis',$html),
    str_replace($cls::OTHER_HEADING,'Jiný obsah',$html),
    $html.$html
) as $drift)pwcok($cls::render($drift)===$drift,'drift no-op');
$cls::enqueue();
pwcok(count($GLOBALS['styles'])===1 && strpos($GLOBALS['styles'][0],'nry-private-waters-examples-v1.css')!==false,'style scoped');
echo "NRY_PRIVATE_WATERS_CONCRETE_CHOICE_OFFLINE_VERIFIED\n";
