<?php
// Local-only WordPress fixture; no HTTP, database or affiliate tracking.
define('ABSPATH', __DIR__);
$_SERVER['REQUEST_METHOD'] = 'GET';
$GLOBALS['mode'] = array('page'=>true, 'admin'=>false, 'preview'=>false,
    'feed'=>false, 'paged'=>false, 'ajax'=>false, 'custom'=>false,
    'host'=>'https://na-ryby.eu','permalink'=>'https://na-ryby.eu/ubytovani-2/',
    'post'=>(object)array('ID'=>814,'post_type'=>'page','post_status'=>'publish','post_name'=>'ubytovani-2'));
$GLOBALS['actions'] = array();
$GLOBALS['styles'] = array();
function add_action($name,$action,$priority=10){$GLOBALS['actions'][$name]=$action;}
function is_page($slug){return $GLOBALS['mode']['page'] && $slug==='ubytovani-2';}
function is_admin(){return $GLOBALS['mode']['admin'];}
function is_preview(){return $GLOBALS['mode']['preview'];}
function is_feed(){return $GLOBALS['mode']['feed'];}
function is_paged(){return $GLOBALS['mode']['paged'];}
function wp_doing_ajax(){return $GLOBALS['mode']['ajax'];}
function is_customize_preview(){return $GLOBALS['mode']['custom'];}
function home_url($path='/'){return $GLOBALS['mode']['host'];}
function get_queried_object_id(){return 814;}
function get_post($id){return $GLOBALS['mode']['post'];}
function get_permalink($id){return $GLOBALS['mode']['permalink'];}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_style($handle,$url,$deps,$version){$GLOBALS['styles'][]=array($handle,$url);}
function ok_stays($condition,$why){if(!$condition){fwrite(STDERR,'FAIL '.$why."\n");exit(1);}}
require dirname(__DIR__).'/legacy-stays-archive-entry-v1.php';
$c='NRY_Legacy_Stays_Archive_Entry_V1';
ok_stays(isset($GLOBALS['actions']['template_redirect']) && isset($GLOBALS['actions']['wp_enqueue_scripts']),'only precise hooks');
$block=$c::block();
ok_stays(substr_count($block,$c::MARKER)===1,'unique entry marker');
foreach(array($c::GUIDE,$c::SLAPY,$c::LIPNO) as $url) {
    ok_stays(substr_count($block,'href="'.$url.'"')===1,'three first-party choices');
}
ok_stays(substr_count($block,'<a href=')===3,'no affiliate or external links');
$html='<html><head><title>Ubytování v ČR – historický přehled</title></head>'
    .'<body><h1 class="td-page-title">Ubytování v ČR</h1>'
    .'<div class="old-list"><h2><a>Rekreační dům Harvánek – Rybařina rybník Sykovec</a></h2>'
    .str_repeat('<p>Historický text zůstává beze změny.</p>',180)
    .'</div></body></html>';
$after=$c::place_after_title($html);
ok_stays($after!==$html,'new hub inserted');
ok_stays(strpos($after,$block)<strpos($after,'Rekreační dům Harvánek'),'before first archived offer');
ok_stays(strpos($after,'</h1>'.$block)!==false,'immediately after H1');
ok_stays(str_replace($block,'',$after)===$html,'original HTML byte preserving');
ok_stays($c::place_after_title($after)===$after,'no duplicate second insertion');
ok_stays($c::place_after_title(str_replace('Ubytování v ČR</h1>','Jiný archiv</h1>',$html))===str_replace('Ubytování v ČR</h1>','Jiný archiv</h1>',$html),'H1 drift no-op');
ok_stays($c::place_after_title(str_replace('Rekreační dům Harvánek','Jiná nabídka',$html))===str_replace('Rekreační dům Harvánek','Jiná nabídka',$html),'listing drift no-op');
ok_stays($c::place_after_title($html.$html)===$html.$html,'two H1 no-op');
$c::css();
ok_stays(count($GLOBALS['styles'])===1 && strpos($GLOBALS['styles'][0][1],'nry-legacy-stays-entry-v1.css')!==false,'CSS for only target page');
foreach(array('page','admin','preview','feed','paged','ajax','custom','host','permalink','post') as $key) {
    $saved=$GLOBALS['mode'][$key];
    if($key==='page')$GLOBALS['mode'][$key]=false;
    elseif(in_array($key,array('admin','preview','feed','paged','ajax','custom'),true))$GLOBALS['mode'][$key]=true;
    elseif($key==='host')$GLOBALS['mode'][$key]='https://other.example';
    elseif($key==='permalink')$GLOBALS['mode'][$key]='https://na-ryby.eu/jina/';
    else $GLOBALS['mode'][$key]=(object)array('ID'=>814,'post_type'=>'page','post_status'=>'publish','post_name'=>'jina');
    ob_start();$c::buffer();echo 'unchanged';$output=ob_get_clean();
    ok_stays($output==='unchanged','scope must fail-closed: '.$key);
    $GLOBALS['mode'][$key]=$saved;
}
echo "NRY_LEGACY_STAYS_ENTRY_OFFLINE_VERIFIED\n";
