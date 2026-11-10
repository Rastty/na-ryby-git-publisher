<?php
/* Offline, no-network contract for source-bound carp buying advice. */
define('ABSPATH', '/');
$GLOBALS['ctx'] = array('id'=>118541, 'single'=>true, 'main'=>true,
    'loop'=>true, 'admin'=>false, 'preview'=>false, 'post_type'=>'post',
    'status'=>'publish',
    'slug'=>'jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika');
$GLOBALS['filters'] = array();
function add_filter($name, $cb, $priority=10) {$GLOBALS['filters'][]=array($name,$cb,$priority);}
function is_single($id){return $GLOBALS['ctx']['single'] && $id===$GLOBALS['ctx']['id'];}
function is_singular($type){return $type==='post' && $GLOBALS['ctx']['post_type']==='post';}
function get_queried_object_id(){return $GLOBALS['ctx']['id'];}
function is_main_query(){return $GLOBALS['ctx']['main'];}
function in_the_loop(){return $GLOBALS['ctx']['loop'];}
function is_admin(){return $GLOBALS['ctx']['admin'];}
function is_preview(){return $GLOBALS['ctx']['preview'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return $id===118541 ? NRY_Carp_Pillar_Choice_118541_V1::URL : '';}
function get_post($id){return (object)array('ID'=>$id,
 'post_type'=>$GLOBALS['ctx']['post_type'],
 'post_status'=>$GLOBALS['ctx']['status'],
 'post_name'=>$GLOBALS['ctx']['slug']);}
function check($ok,$name){if(!$ok){fwrite(STDERR,"FAILED: $name\n");exit(1);}}
require dirname(__DIR__).'/carp-pillar-choice-before-shop-118541-v1.php';
$c='NRY_Carp_Pillar_Choice_118541_V1';
check(count($GLOBALS['filters'])===1,'one filter');
check($GLOBALS['filters'][0][2]===52,'late scoped filter');
$merchant='<a href="https://www.chytapust.cz/kaprove-pruty/?campaign=KEEP&amp;sku=KEEP">Prověřit nabídku</a>';
$anchor='<h2>Výběr</h2><span '. $c::SOURCE_MARKER .'></span>';
$before='<p class="buyer-intro">'.$c::OLD.'</p>';
$source=$anchor.$before.$merchant.str_repeat('<p>Informace pro výběr prutu.</p>',36);
check(strlen($source)>1000,'realistic source size');
$after=$c::render($source);
check($after!==$source,'exact buyer trust correction applied');
check(strpos($after,$c::NEW_MARKER)!==false,'single proof marker');
check(substr_count($after,$c::NEW_MARKER)===1,'proof marker unique');
check(strpos($after,$c::OLD)===false,'unsupported shop comparison removed');
check(strpos($after,$merchant)!==false,'merchant href byte preserved');
check(strpos($after,'<p class="buyer-intro">')!==false,'original paragraph wrapper kept');
check($c::render($after)===$after,'idempotence');
check($c::render(str_replace($c::SOURCE_MARKER,'source-changed',$source))===
    str_replace($c::SOURCE_MARKER,'source-changed',$source),'source marker guard');
check($c::render(str_replace($c::OLD,'Editor updated this paragraph.',$source))===
    str_replace($c::OLD,'Editor updated this paragraph.',$source),'editor precedence');
$withLink=str_replace($c::OLD,$c::OLD.' <a href="https://example.org/">editor link</a>',$source);
check($c::render($withLink)===$withLink,'inline editor link blocks change');
$withStrong=str_replace($c::OLD,str_replace('aktuálně','<strong>aktuálně</strong>',$c::OLD),$source);
check($c::render($withStrong)===$withStrong,'inline markup fails closed');
$dupe=$source.$before;
check($c::render($dupe)===$dupe,'two candidate paragraphs fail closed');
foreach (array('id'=>118542,'admin'=>true,'preview'=>true,'loop'=>false,
    'main'=>false,'status'=>'draft','slug'=>'another-slug') as $key=>$value) {
    $old=$GLOBALS['ctx'][$key];$GLOBALS['ctx'][$key]=$value;
    check($c::render($source)===$source,'wrong context protected: '.$key);
    $GLOBALS['ctx'][$key]=$old;
}
echo "PASS carp-pillar-choice-before-shop: exact public context, immutable merchant link, fail closed\n";
