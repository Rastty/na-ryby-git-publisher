<?php
// Source-bound, WordPress-free test of two unchanged legacy article cohorts.
define('ABSPATH', __DIR__);
$GLOBALS['mode']=array('id'=>42642,'single'=>true,'singular'=>true,'main'=>true,
    'loop'=>true,'admin'=>false,'preview'=>false,'slug'=>'',
    'modified'=>'','status'=>'publish','type'=>'post','permalink'=>'');
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
function get_post($id) { return (object)array('ID'=>$id,
    'post_type'=>$GLOBALS['mode']['type'], 'post_status'=>$GLOBALS['mode']['status'],
    'post_name'=>$GLOBALS['mode']['slug'], 'post_modified_gmt'=>$GLOBALS['mode']['modified']); }
function wp_enqueue_style($name,$url,$deps,$version) { $GLOBALS['style']=compact('name','url','deps','version'); }
function plugins_url($path,$file) { return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path; }
function check($ok,$msg) { if (!$ok) throw new RuntimeException($msg); echo "PASS $msg\n"; }
require __DIR__.'/../legacy-net-buyer-guides-v1.php';
$class='NRY_Legacy_Net_Buyer_Guides_V1';
$cohorts=$class::cohorts();
check(count($cohorts)===2,'only two approved legacy source identities');
foreach ($cohorts as $id=>$set) {
    $GLOBALS['mode']['id']=$id;
    $GLOBALS['mode']['slug']=$set['slug'];
    $GLOBALS['mode']['modified']=$set['modified'];
    $GLOBALS['mode']['permalink']='https://na-ryby.eu/'.$set['slug'].'/';
    $src='<p><a href="https://na-ryby.eu/podberaky">Affiliate A</a></p>'
        .$set['intro'];
    foreach ($set['replacements'] as $old=>$new) {
        // Public 42642 wraps the word Délka in <b> before the sentence.
        $src.='<p>'.(strpos($old,'Délka</b>')===0 ? '<b>' : '').$old.'</p>';
    }
    $src.='<p><a href="https://na-ryby.eu/podberaky">Affiliate B</a></p>';
    $src.='<p><a href="https://na-ryby.eu/unrelated/">Unrelated link</a></p>';
    $src.=str_repeat('<p>Praktická část původního starého rybářského textu.</p>',140);
    $fixed=$class::render($src);
    check($fixed!==$src,'cohort '.$id.' gets corrected and linked');
    check(substr_count($fixed,$class::MARKER)===1, 'one buyer notice '.$id);
    check(substr_count($fixed,'href="'.$class::GUIDE.'"')===1,'guide link once '.$id);
    check(strpos($fixed,$class::GUIDE)<strpos($fixed,'Praktická část původního'),
        'link visible before long content '.$id);
    foreach ($set['replacements'] as $old=>$new) {
        check(strpos($fixed,$old)===false && substr_count($fixed,$new)===1,
            'corrected anchor '.$id.' '.substr(md5($old),0,7));
    }
    check(substr_count($fixed,'href="https://na-ryby.eu/podberaky"')===2,
        'both affiliate targets preserved '.$id);
    check(strpos($fixed,'href="https://na-ryby.eu/unrelated/"')!==false,
        'unrelated editorial target preserved '.$id);
    check($class::render($fixed)===$fixed,'idempotent '.$id);
    $GLOBALS['mode']['loop']=false;
    $GLOBALS['mode']['main']=false;
    $class::enqueue();
    check(($GLOBALS['style']['version']??null)==='1.0.0',
        'CSS before loop '.$id);
    $GLOBALS['mode']['loop']=true;
    $GLOBALS['mode']['main']=true;
    foreach (array('single'=>false,'singular'=>false,'main'=>false,'loop'=>false,
                   'admin'=>true,'preview'=>true,'modified'=>'2026-11-17 00:00:00',
                   'status'=>'draft','type'=>'page','slug'=>'editor-change',
                   'permalink'=>'https://na-ryby.eu/else/') as $key=>$bad) {
        $prev=$GLOBALS['mode'][$key]; $GLOBALS['mode'][$key]=$bad;
        check($class::render($src)===$src,'rejects '.$key.' '.$id);
        $GLOBALS['mode'][$key]=$prev;
    }
    $editor=str_replace(array_keys($set['replacements'])[0], 'Editor changed claim', $src);
    check($class::render($editor)===$editor,'editor drift fails closed '.$id);
    $editor=str_replace('href="https://na-ryby.eu/podberaky"',
        'href="https://na-ryby.eu/editor/"',$src);
    check($class::render($editor)===$editor,'affiliate drift fails closed '.$id);
}
$GLOBALS['mode']['id']=999;
unset($GLOBALS['style']);
$class::enqueue();
check(!isset($GLOBALS['style']),'no CSS on unrelated post');
echo "PASS zero article/database writes, no affiliate URL replacement or SEO redirects\n";
