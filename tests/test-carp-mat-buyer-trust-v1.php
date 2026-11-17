<?php
// Isolated regression: source binding, evidence corrections, link preservation.
define('ABSPATH',__DIR__);
$GLOBALS['mode']=array('id'=>43541,'single'=>true,'singular'=>true,'main'=>true,
    'loop'=>true,'admin'=>false,'preview'=>false,'slug'=>'','modified'=>'',
    'status'=>'publish','type'=>'post','permalink'=>'');
function add_filter($n,$cb,$priority=10) { $GLOBALS['filter']=compact('n','cb','priority'); }
function add_action($n,$cb,$priority=10) { $GLOBALS['action']=compact('n','cb','priority'); }
function is_single($id) { return $GLOBALS['mode']['single'] && $id===$GLOBALS['mode']['id']; }
function is_singular($type) { return $GLOBALS['mode']['singular'] && $type==='post'; }
function get_queried_object_id() { return $GLOBALS['mode']['id']; }
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
function check($ok,$msg) { if (!$ok) throw new RuntimeException($msg); echo "PASS $msg\n"; }
require __DIR__.'/../carp-mat-buyer-trust-v1.php';
$class='NRY_Carp_Mat_Buyer_Trust_V1';
$cohorts=$class::cohorts();
check(count($cohorts)===3,'three precisely scoped 2023 mat posts');
foreach ($cohorts as $id=>$set) {
    $GLOBALS['mode']['id']=$id;
    $GLOBALS['mode']['slug']=$set['slug'];
    $GLOBALS['mode']['modified']=$set['modified'];
    $GLOBALS['mode']['permalink']='https://na-ryby.eu/'.$set['slug'].'/';
    $set['is_main']=$id===43541;
    $src='<p><a href="https://na-ryby.eu/podlozky">Affiliate 1</a></p>'
        .$set['intro'];
    foreach ($set['replacements'] as $old=>$new) $src.='<p>'.$old.'</p>';
    if (!empty($set['replace_faq'])) {
        $src.="<div>\n<h2>FAQs &#8211; Historical fake bait rig FAQ</h2>"
            .'<ul><li>Potápivé podložky</li></ul></div>';
    }
    if (!empty($set['replace_retailer_faq'])) {
        $src.="<ul>\n<li>Rybářské potřeby Max, s.r.o.</li><li>Rybářské potřeby Foo</li></ul>";
        $src.='<p>V těchto obchodech najdete širokou škálu rybářských podložek, včetně těch určených pro lov kaprů.</p>';
    }
    $src.='<p><a href="https://na-ryby.eu/podlozky">Affiliate 2</a></p>'
        .'<p><a href="https://na-ryby.eu/podlozky">Affiliate 3</a></p>'
        .'<p><a href="https://na-ryby.eu/podlozky"><img src="/historic-banner.jpg" alt="" /></a></p>'
        .'<p><a href="https://www.usgs.gov/source">Historic source</a></p>'
        .str_repeat('<p>Starší obsah k vybavení a manipulaci s rybou.</p>',150);
    if ($id===43541 || $id===42643) $src.=$set['intro']; // Historical repeated H2.
    $fixed=$class::render($src);
    check($fixed!==$src,'source-pinned correction active '.$id);
    check(substr_count($fixed,$class::MARKER)===1,'one useful buyer comparison '.$id);
    check(substr_count($fixed,'href="'.$class::FISH_CARE.'"')===1,'primary fish care source '.$id);
    check(substr_count($fixed,'href="'.$class::MAIN.'"')===($id===43541?0:1),
        'central buyer guide paths '.$id);
    check(strpos($fixed,'Lehká skládací')!==false && strpos($fixed,'Kolébka s bočnicemi')!==false,
        'three concrete selection scenarios '.$id);
    if ($id===43541 || $id===42643) {
        check(substr_count($fixed,$set['intro'])===2,
            'repeated original headings remain readable '.$id);
    }
    foreach ($set['replacements'] as $old=>$new) {
        check(strpos($fixed,$old)===false && substr_count($fixed,$new)===1,
            'correction '.substr(md5($old),0,7).' '.$id);
    }
    check(substr_count($fixed,'href="https://na-ryby.eu/podlozky"')===4,
        'all four affiliate hrefs preserved '.$id);
    check(substr_count($fixed,'href="https://www.usgs.gov/source"')===1,
        'unrelated source link preserved '.$id);
    if (!empty($set['replace_faq'])) {
        check(strpos($fixed,'Potápivé podložky')===false
            && strpos($fixed,'Je podložka součást montáže?')!==false,
            'fake rig FAQ removed '.$id);
    }
    if (!empty($set['replace_retailer_faq'])) {
        check(strpos($fixed,'<li>Rybářské potřeby Max')===false
            && strpos($fixed,'Ověřené podmínky vrácení')!==false,
            'unsupported seller list replaced '.$id);
    }
    check($class::render($fixed)===$fixed,'idempotent '.$id);
    $GLOBALS['mode']['main']=false; $GLOBALS['mode']['loop']=false;
    $class::enqueue();
    check(($GLOBALS['style']['version']??null)==='1.0.0','CSS loaded before loop '.$id);
    $GLOBALS['mode']['main']=true; $GLOBALS['mode']['loop']=true;
    foreach (array('single'=>false,'singular'=>false,'main'=>false,'loop'=>false,
        'admin'=>true,'preview'=>true,'modified'=>'2026-11-17 00:00:00',
        'status'=>'draft','type'=>'page','slug'=>'post-edited',
        'permalink'=>'https://na-ryby.eu/other/') as $key=>$bad) {
        $prev=$GLOBALS['mode'][$key]; $GLOBALS['mode'][$key]=$bad;
        check($class::render($src)===$src,'rejects '.$key.' '.$id);
        $GLOBALS['mode'][$key]=$prev;
    }
    $drift=str_replace(array_keys($set['replacements'])[0],'editor changed this',$src);
    check($class::render($drift)===$drift,'new editor content blocked '.$id);
    $drift=str_replace('href="https://na-ryby.eu/podlozky"',
        'href="https://na-ryby.eu/new-merchant"',$src);
    check($class::render($drift)===$drift,'affiliate change blocked '.$id);
}
$GLOBALS['mode']['id']=100;
unset($GLOBALS['style']); $class::enqueue();
check(!isset($GLOBALS['style']),'unrelated post does not enqueue CSS');
echo "PASS render-only, no stored WordPress changes or affiliate URL rewrites\n";
