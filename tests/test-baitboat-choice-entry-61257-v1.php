<?php
/* No-network source-pinned render-only entry tests. */
define('ABSPATH','/');
$GLOBALS['context']=array(
 'id'=>61257,'single'=>true,'singular'=>true,'main'=>true,'loop'=>true,
 'admin'=>false,'preview'=>false,'post_type'=>'post','status'=>'publish',
 'slug'=>'zavazeci-lodky-pro-rybare','modified'=>'2026-05-13 10:22:17',
 'origin'=>'https://na-ryby.eu/','permalink'=>'https://na-ryby.eu/zavazeci-lodky-pro-rybare/'
);
$GLOBALS['filters']=array();
function add_filter($hook,$fn,$priority=10){$GLOBALS['filters'][]=array($hook,$fn,$priority);}
function is_single($id){return $GLOBALS['context']['single'] && $GLOBALS['context']['id']===$id;}
function is_singular($type){return $type==='post' && $GLOBALS['context']['singular'];}
function get_queried_object_id(){return $GLOBALS['context']['id'];}
function is_main_query(){return $GLOBALS['context']['main'];}
function in_the_loop(){return $GLOBALS['context']['loop'];}
function is_admin(){return $GLOBALS['context']['admin'];}
function is_preview(){return $GLOBALS['context']['preview'];}
function home_url($path='/'){return $GLOBALS['context']['origin'];}
function get_permalink($id){return $GLOBALS['context']['permalink'];}
function get_post($id){return (object)array('ID'=>$GLOBALS['context']['id'],
 'post_type'=>$GLOBALS['context']['post_type'],
 'post_status'=>$GLOBALS['context']['status'],
 'post_name'=>$GLOBALS['context']['slug'],
 'post_modified_gmt'=>$GLOBALS['context']['modified']);}
function check($ok,$message){if(!$ok){fwrite(STDERR,"FAIL: ".$message."\n");exit(1);}}
require dirname(__DIR__).'/baitboat-choice-entry-61257-v1.php';
$c='NRY_Baitboat_Choice_Entry_61257_V1';
check(count($GLOBALS['filters'])===1 && $GLOBALS['filters'][0][2]===47,'one scoped render-only filter');
$origMerchant='<a href="https://www.chytapust.cz/Some-Existing-Item?keep=1&amp;ref=2">Ověřit cenu</a>';
$oldInternal='<a href="https://na-ryby.eu/poradce-vybavy/">Existující poradce</a>';
$intro='Zavážecí loďka pomůže při přesném doručení montáže na místo. Nejdříve je ale třeba zvážit cenu, místní pravidla a to, zda je potřeba sonar nebo GPS. Tento článek porovnává jednotlivé možnosti.';
$source='<h1>Nejlepší zavážecí loďky pro rybáře: srovnání</h1>'
 .'<p>'.$intro.'</p><p>'.$origMerchant.$oldInternal.'</p>'
 .'<h2>Kdy zavážecí loďka dává opravdu smysl</h2>'
 .'<p>Boatman Actor Plus Basic pro jednoduché zavážení. '
 .'Deeper QUEST pro bohatší elektroniku.</p>'
 .str_repeat('<p>Podrobný obsah a nákupní kontext u vody.</p>',170);
check(strlen($source)>5500,'realistic fixture');
$fixed=$c::render($source);
check($fixed!==$source,'added relevant internal entry');
check(substr_count($fixed,$c::MARKER)===1,'unique internal choice marker');
check(substr_count($fixed,'href="'.$c::PICKER_URL.'"')===1,'exactly one picker href');
check(substr_count($fixed,$origMerchant)===1,'unaltered merchant URL');
check(substr_count($fixed,$oldInternal)===1,'unaltered original internal URL');
check(strpos($fixed,$c::MARKER)>strpos($fixed,$intro)
 && strpos($fixed,$c::MARKER)<strpos($fixed,'Kdy zavážecí loďka dává opravdu smysl'),
 'entry after intro and before first h2');
check($c::render($fixed)===$fixed,'idempotence');
check($c::transform($fixed)===$fixed,'idempotent direct transform');
$modelDrift=str_replace('Deeper QUEST','Deeper unknown',$source);\ncheck($c::transform($modelDrift)===$modelDrift,'product source drift fails closed');
$edited=str_replace('Kdy zavážecí loďka dává opravdu smysl','Editor changed heading',$source);
check($c::transform($edited)===$edited,'heading drift fails closed');
$already=str_replace($oldInternal,'<a href="'.$c::PICKER_URL.'">Already linked</a>',$source);
check($c::transform($already)===$already,'editor-existing link blocks duplicates');
$duplicate=str_replace('Kdy zavážecí loďka dává opravdu smysl',
 'Kdy zavážecí loďka dává opravdu smysl Kdy zavážecí loďka dává opravdu smysl',$source);
check($c::transform($duplicate)===$duplicate,'duplicate content blocks');
$nested=str_replace('<p>'.$intro.'</p>','<p><b>'.$intro.'</b></p>',$source);
check($c::transform($nested)===$nested,'unknown intro markup fails closed');
foreach(array(
 'id'=>123,'single'=>false,'singular'=>false,'main'=>false,'loop'=>false,
 'admin'=>true,'preview'=>true,'post_type'=>'page','status'=>'draft',
 'slug'=>'other-post','modified'=>'2026-05-14 10:22:17',
 'origin'=>'https://another-host.invalid/','permalink'=>'https://na-ryby.eu/elsewhere/'
) as $key=>$value){
 $old=$GLOBALS['context'][$key];$GLOBALS['context'][$key]=$value;
 check($c::render($source)===$source,'no changes on unexpected context '.$key);
 $GLOBALS['context'][$key]=$old;
}
echo "PASS: exact post 61257 source-gated visitor navigation, zero merchant mutations\n";
