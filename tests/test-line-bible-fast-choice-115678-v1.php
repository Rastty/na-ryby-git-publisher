<?php
// WordPress-free, source-bound regression tests for the published line Bible page.
define('ABSPATH', __DIR__);
$GLOBALS['mode']=array('id'=>115678,'page'=>true,'singular'=>true,'main'=>true,
    'loop'=>true,'admin'=>false,'preview'=>false,
    'slug'=>'vlascova-bible-kompletni-pruvodce-vyberem-vlasce-a-snury',
    'modified'=>'2026-03-05 17:50:34','status'=>'publish','type'=>'page',
    'permalink'=>'https://na-ryby.eu/vlascova-bible-kompletni-pruvodce-vyberem-vlasce-a-snury/');
function add_filter($name,$cb,$priority=10) { $GLOBALS['filter']=compact('name','cb','priority'); }
function add_action($name,$cb,$priority=10) { $GLOBALS['action']=compact('name','cb','priority'); }
function get_queried_object_id() { return $GLOBALS['mode']['id']; }
function is_page($id) { return $GLOBALS['mode']['page'] && $GLOBALS['mode']['id']===$id; }
function is_singular($type) { return $GLOBALS['mode']['singular'] && $type==='page'; }
function is_main_query() { return $GLOBALS['mode']['main']; }
function in_the_loop() { return $GLOBALS['mode']['loop']; }
function is_admin() { return $GLOBALS['mode']['admin']; }
function is_preview() { return $GLOBALS['mode']['preview']; }
function home_url($path='/') { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return $GLOBALS['mode']['permalink']; }
function get_post($id) { return (object)array('ID'=>$id,'post_type'=>$GLOBALS['mode']['type'],
    'post_status'=>$GLOBALS['mode']['status'],'post_name'=>$GLOBALS['mode']['slug'],
    'post_modified_gmt'=>$GLOBALS['mode']['modified']); }
function wp_enqueue_style($name,$url,$deps,$version) { $GLOBALS['style']=compact('name','url','deps','version'); }
function plugins_url($path,$file) { return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path; }
function check($ok,$label) { if (!$ok) throw new RuntimeException($label); echo "PASS $label\n"; }
require __DIR__.'/../line-bible-fast-choice-115678-v1.php';
$class='NRY_Line_Bible_Fast_Choice_115678_V1';
check(($GLOBALS['filter']['priority']??null)===76,'late content filter');
check(($GLOBALS['action']['name']??null)==='wp_enqueue_scripts','stylesheet event registered');

$src='<div class="ryby-bible-container"><p>Original public content fixture</p>';
foreach ($class::replacements() as $original=>$changed) $src.="\n".$original;
$affiliate=$class::PARTNER;
$hrefs=$class::original_affiliates();
foreach (array_slice($hrefs,0,3) as $href) {
    $src.='<p><a href="'.$href.'">KOUPIT NA CHYTAPUST.CZ</a></p>';
}
$src.='<p><a href="'.$hrefs[3].'">PROHLÉDNOUT E-SHOP</a></p>';
$src.='<p><a href="https://na-ryby.eu/another-internal-guide/">Původní proklik</a></p>';
$src.=str_repeat('<p>Původní rozsáhlý poradenský text o návazcích a uzlech.</p>',250).'</div>';
$fixed=$class::render($src);
check($fixed!==$src,'selected page transformed');
check(substr_count($fixed,$class::MARKER)===1,'one quick chooser');
check(substr_count($fixed,'OVĚŘIT VARIANTU A DOSTUPNOST')===3,'three cautious product CTAs');
check(substr_count($fixed,'Cenu ověřte v obchodě')===3,'three unsupported static prices removed');
check(substr_count($fixed,$affiliate)===4,'all four affiliate targets preserved');
check(substr_count($fixed,'href="'.$class::WINDING.'"')===1,'one relevant internal practical guide');
check(substr_count($fixed,'href="#nry-line-materials"')===1
    && substr_count($fixed,'href="#nry-line-products"')===1,'two working on-page anchor links');
check(strpos($fixed,'class="nry-line-fast-choice"')<strpos($fixed,'id="nry-line-materials"'),
    'chooser placed before technical material comparison');
foreach ($class::replacements() as $original=>$changed) {
    check(strpos($fixed,$original)===false && substr_count($fixed,$changed)===1,
        'exact source correction '.substr(md5($original),0,8));
}
check($class::render($fixed)===$fixed,'idempotent render');
check(strpos($fixed,'href="https://na-ryby.eu/another-internal-guide/"')!==false,
    'unrelated editorial link preserved');
$GLOBALS['mode']['main']=false;
$GLOBALS['mode']['loop']=false;
$class::enqueue();
check(($GLOBALS['style']['version']??null)==='1.0.0','enqueue before WordPress main loop');
$GLOBALS['mode']['main']=true;
$GLOBALS['mode']['loop']=true;
foreach (array('id'=>999,'page'=>false,'singular'=>false,'main'=>false,'loop'=>false,
    'admin'=>true,'preview'=>true,'modified'=>'2026-11-17 00:00:00',
    'status'=>'draft','type'=>'post','slug'=>'edited-by-owner',
    'permalink'=>'https://na-ryby.eu/something-else/') as $key=>$bad) {
    $old=$GLOBALS['mode'][$key];$GLOBALS['mode'][$key]=$bad;
    check($class::render($src)===$src,'fail-closed page gate '.$key);
    $GLOBALS['mode'][$key]=$old;
}
$price_changed=str_replace('899 Kč','Nová cena', $src);
check($class::render($price_changed)===$price_changed,'editor price drift fails closed');
$affiliate_changed=str_replace($hrefs[1],$affiliate.'editor-changed-target', $src);
check($class::render($affiliate_changed)===$affiliate_changed,
    'exact affiliate destination drift fails closed');
$GLOBALS['mode']['id']=999;
unset($GLOBALS['style']);
$class::enqueue();
check(!isset($GLOBALS['style']),'no styles for unrelated page');
echo "PASS no stored post writes, no SEO changes, no affiliate URL edits\n";
