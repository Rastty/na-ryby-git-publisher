<?php
// WordPress-free contract: only one dated public post, zero stored writes.
define('ABSPATH', __DIR__);
$GLOBALS['mode'] = array('single'=>true, 'singular'=>true, 'main'=>true, 'loop'=>true,
    'admin'=>false, 'preview'=>false, 'id'=>61251, 'slug'=>'jak-vybrat-zavazeci-lodku-pro-rybare',
    'type'=>'post', 'status'=>'publish', 'modified'=>'2023-09-23 08:44:03',
    'permalink'=>'https://na-ryby.eu/jak-vybrat-zavazeci-lodku-pro-rybare/');
function add_filter($n,$cb,$p=10) { $GLOBALS['filter']=compact('n','cb','p'); }
function add_action($n,$cb,$p=10) { $GLOBALS['action']=compact('n','cb','p'); }
function is_single($id) { return $GLOBALS['mode']['single'] && $id === 61251; }
function is_singular($type) { return $GLOBALS['mode']['singular'] && $type === 'post'; }
function get_queried_object_id() { return $GLOBALS['mode']['id']; }
function is_main_query() { return $GLOBALS['mode']['main']; }
function in_the_loop() { return $GLOBALS['mode']['loop']; }
function is_admin() { return $GLOBALS['mode']['admin']; }
function is_preview() { return $GLOBALS['mode']['preview']; }
function home_url($path='/') { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return $GLOBALS['mode']['permalink']; }
function get_post($id) { return (object)array('ID'=>$id, 'post_type'=>$GLOBALS['mode']['type'],
    'post_status'=>$GLOBALS['mode']['status'], 'post_name'=>$GLOBALS['mode']['slug'],
    'post_modified_gmt'=>$GLOBALS['mode']['modified']); }
function wp_enqueue_style($handle,$url,$deps,$version) {
    $GLOBALS['style']=compact('handle','url','deps','version');
}
function plugins_url($path,$file) { return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path; }
function check($ok,$desc) { if (!$ok) throw new RuntimeException($desc); echo "PASS $desc\n"; }
require __DIR__.'/../legacy-baitboat-trust-61251-v1.php';

$class='NRY_Legacy_Baitboat_Trust_61251_V1';
$reflect=new ReflectionMethod($class,'replacements');
$reflect->setAccessible(true);
$replacements=$reflect->invoke(null);
check(count($replacements) >= 23, 'many distinct source-anchored editorial repairs');
$src='<p><a href="https://na-ryby.eu/zavazeci-lodky-parys">Affiliate A</a></p>'
    .'<h2>Jak vybrat zavážecí loďku pro rybáře</h2>';
foreach ($replacements as $original=>$replacement) { $src.='<p>'.$original.'</p>'; }
$src.='<p><strong>HODNOCENÍ: ⭐⭐⭐⭐⭐</strong></p>'
    .'<p><strong>HODNOCENÍ: ⭐⭐⭐⭐</strong></p>'
    .'<p><strong>HODNOCENÍ: ⭐⭐⭐⭐⭐</strong></p>'
    .'<h2>Jak vybrat zavážecí loďku pro rybáře</h2>'
    .'<h2>Jak vybrat zavážecí loďku pro rybáře</h2>';
for ($i=0;$i<3;$i++) {
    $src.='<p><a href="https://na-ryby.eu/zavazeci-lodky-parys">Affiliate '.($i+2).'</a></p>';
}
$src.='<p><a href="https://na-ryby.eu/ponechat-puvodni/">Editorial</a></p>';
$src.=str_repeat('<p>Historický popis modelu s přiměřenou délkou textu.</p>',500);

$fixed=$class::render($src);
check($fixed!==$src,'valid archived post receives factual correction');
check(substr_count($fixed,$class::MARKER)===1,'one prominent buyer notice');
check(strpos($fixed,$class::GUIDE)!==false && strpos($fixed,$class::PICKER)!==false,
    'two working internal buyer routes');
check(strpos($fixed,$class::GUIDE)<strpos($fixed,'Historický popis modelu'),
    'guide appears above historical examples');
foreach ($replacements as $old=>$new) {
    check(substr_count($fixed,$old)===0 && substr_count($fixed,$new)===1,
        'source correction '.substr(md5($old),0,8));
}
check(strpos($fixed,'HODNOCENÍ:')===false && substr_count($fixed,'Historické hodnocení')===3,
    'three unverified ratings replaced without new scores');
check(substr_count($fixed,'href="https://na-ryby.eu/zavazeci-lodky-parys"')===4,
    'four untouched historical affiliate CTA targets');
check(strpos($fixed,'href="https://na-ryby.eu/ponechat-puvodni/"')!==false,
    'unrelated editorial target preserved');
check($class::render($fixed)===$fixed,'idempotent render');
foreach (array('single'=>false,'singular'=>false,'main'=>false,'loop'=>false,
               'admin'=>true,'preview'=>true,'id'=>1,'slug'=>'different',
               'type'=>'page','status'=>'draft','modified'=>'2026-11-17 12:00:00',
               'permalink'=>'https://na-ryby.eu/other/') as $key=>$wrong) {
    $old=$GLOBALS['mode'][$key]; $GLOBALS['mode'][$key]=$wrong;
    check($class::render($src)===$src, 'guard rejects '.$key);
    $GLOBALS['mode'][$key]=$old;
}
$editor=str_replace('Ocelový trup pro maximální pevnost','Editor změnil konstrukci',$src);
check($class::render($editor)===$editor,'editor change fails closed as one unit');
$editor=str_replace('href="https://na-ryby.eu/zavazeci-lodky-parys"',
    'href="https://na-ryby.eu/editor/"',$src);
check($class::render($editor)===$editor,'affiliate source drift fails closed');
$GLOBALS['mode']['loop']=false;
$GLOBALS['mode']['main']=false;
$class::enqueue();
check(($GLOBALS['style']['version']??null)==='1.0.0',
    'scoped styling enqueued before the content loop');
$GLOBALS['mode']['loop']=true;
$GLOBALS['mode']['main']=true;
$GLOBALS['mode']['id']=0;
unset($GLOBALS['style']);
$class::enqueue();
check(!isset($GLOBALS['style']),'no styling on unrelated post');
check($GLOBALS['filter']['p']===75 && $GLOBALS['action']['p']===28,
    'registered late content filter and scoped style action');
echo "PASS no post writes, affiliate rewrites, price updates or redirects\n";
