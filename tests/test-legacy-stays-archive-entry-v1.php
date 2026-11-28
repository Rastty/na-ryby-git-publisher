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
$GLOBALS['scripts']=array();
function wp_enqueue_script($handle,$url,$deps,$version,$footer){$GLOBALS['scripts'][]=compact('handle','url','deps','version','footer');}
function ok_stays($condition,$why){if(!$condition){fwrite(STDERR,'FAIL '.$why."\n");exit(1);}}
require dirname(__DIR__).'/legacy-stays-archive-entry-v1.php';
$c='NRY_Legacy_Stays_Archive_Entry_V1';
ok_stays(isset($GLOBALS['actions']['template_redirect']) && isset($GLOBALS['actions']['wp_enqueue_scripts']),'only precise hooks');
$block=$c::block();
ok_stays(substr_count($block,'data-nry-stay-planner="v1"')===1 && substr_count($block,'data-nry-plan=')===6,'six local planner fields');
ok_stays(substr_count($block,$c::MARKER)===1,'unique entry marker');
foreach(array($c::GUIDE,$c::SLAPY,$c::LIPNO,$c::VRANOV,$c::WELLNESS,$c::PRIVATE_WATER) as $url) {
    ok_stays(substr_count($block,'href="'.$url.'"')===1,'six first-party choices');
}
ok_stays(substr_count($block,'<a href=')===6,'no affiliate or external links');
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
// Public 21 Nov source: visible H1 stays exactly the same even if raw
// entities or nonbreaking-space encoding differ from the source string.
$encoded = str_replace('Ubytování v ČR</h1>', 'Ubytov&aacute;n&iacute; v &#268;R</h1>', $html);
$encoded = str_replace('Ubytov&amp;aacute;n&amp;iacute; v &amp;#268;R', 'Ubytov&aacute;n&iacute; v &#268;R', $encoded);
$encodedAfter = $c::place_after_title($encoded);
ok_stays($encodedAfter !== $encoded && strpos($encodedAfter, '</h1>'.$block) !== false, 'entity-encoded H1 exact insertion');
ok_stays(str_replace($block, '', $encodedAfter) === $encoded, 'entity-encoded original HTML byte preserving');
$nbsp = str_replace('Ubytování v ČR</h1>', 'Ubytování&nbsp;v&nbsp;ČR</h1>', $html);
ok_stays($c::place_after_title($nbsp) !== $nbsp, 'nonbreaking-space H1 insertion');
$nested = str_replace('Ubytování v ČR</h1>', "<span>Ubytování v ČR</span>\n          </h1>", $html);
$nestedChanged = $c::place_after_title($nested);
ok_stays($nestedChanged !== $nested && strpos($nestedChanged, '</h1>'.$block) !== false, 'exact observed bare span wrapped H1 insertion');
ok_stays(str_replace($block, '', $nestedChanged) === $nested, 'wrapped H1 original HTML byte preserved');
ok_stays($c::place_after_title($nestedChanged) === $nestedChanged, 'wrapped H1 idempotent');
$unknownAttributes = str_replace('Ubytování v ČR</h1>', '<span class="unverified">Ubytování v ČR</span></h1>', $html);
ok_stays($c::place_after_title($unknownAttributes) === $unknownAttributes, 'unapproved span attributes no-op');
$complexNested = str_replace('Ubytování v ČR</h1>', '<span><strong>Ubytování v ČR</strong></span></h1>', $html);
ok_stays($c::place_after_title($complexNested) === $complexNested, 'unapproved deeper nesting no-op');
$duplicateSpan = str_replace('Ubytování v ČR</h1>', '<span>Ubytování v ČR</span><span>jiný</span></h1>', $html);
ok_stays($c::place_after_title($duplicateSpan) === $duplicateSpan, 'multiple span source no-op');
$script = str_replace('Ubytování v ČR</h1>', '<script>Ubytování v ČR</script></h1>', $html);
ok_stays($c::place_after_title($script) === $script, 'script in H1 rejected');
$other = str_replace('Ubytování v ČR</h1>', 'Ubytování v Čechách</h1>', $html);
ok_stays($c::place_after_title($other) === $other, 'different title no-op');
$twice = str_replace('</body>', '<h1>Ubytování v ČR</h1></body>', $html);
ok_stays($c::place_after_title($twice) === $twice, 'two H1 still no-op');
$c::css();
ok_stays(count($GLOBALS['styles'])===1 && strpos($GLOBALS['styles'][0][1],'nry-legacy-stays-entry-v1.css')!==false,'CSS for only target page');
ok_stays(count($GLOBALS['scripts'])===1
    && $GLOBALS['scripts'][0]['handle']==='nry-archive-stay-click-v1'
    && str_ends_with($GLOBALS['scripts'][0]['url'],'/assets/nry-archive-stay-click-v1.js')
    && $GLOBALS['scripts'][0]['version']==='1.2.0'
    && $GLOBALS['scripts'][0]['footer']===true,
    'only exact public archive enqueues existing-consent local click observer');
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
