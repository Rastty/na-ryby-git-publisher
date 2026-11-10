<?php
define('ABSPATH','/');
$GLOBALS['ctx']=['id'=>118553,'main'=>true,'loop'=>true,'admin'=>false,
 'preview'=>false,'type'=>'post','status'=>'publish',
 'slug'=>'jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce',
 'gmt'=>'2026-11-04 11:27:17'];
$GLOBALS['hooks']=[];
function add_filter($name,$fn,$priority=10){$GLOBALS['hooks'][]=[$name,$fn,$priority];}
function is_single($id){return $GLOBALS['ctx']['id']===$id;}
function is_singular($type){return $GLOBALS['ctx']['type']===$type;}
function get_queried_object_id(){return $GLOBALS['ctx']['id'];}
function is_main_query(){return $GLOBALS['ctx']['main'];}
function in_the_loop(){return $GLOBALS['ctx']['loop'];}
function is_admin(){return $GLOBALS['ctx']['admin'];}
function is_preview(){return $GLOBALS['ctx']['preview'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return 'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';}
function get_post($id){return (object)['ID'=>$id,'post_type'=>$GLOBALS['ctx']['type'],
 'post_status'=>$GLOBALS['ctx']['status'],'post_name'=>$GLOBALS['ctx']['slug'],
 'post_modified_gmt'=>$GLOBALS['ctx']['gmt']];}
function check($value,$name){if(!$value){fwrite(STDERR,"FAIL $name\n");exit(1);}}
require dirname(__DIR__).'/sonar-pillar-merchant-neutral-118553-v1.php';
$c='NRY_Sonar_Pillar_Merchant_Neutral_118553_V1';
check(count($GLOBALS['hooks'])===1&&$GLOBALS['hooks'][0][2]===56,'exact hook priority');
$merchant='<a href="https://www.chytapust.cz/Echoloty/?keep=1&amp;src=2">Prověřit nabídku</a>';
$oldLink='<a href="https://na-ryby.eu/poradce-vybavy/">Poradce</a>';
$source='<section '.$c::SOURCE_MARKER.'><h2>Jak vybrat vhodný typ echolotu</h2></section>'
 .'<h2>Jak vybrat echolot pro rybaření: Kompletní průvodce funkcemi, typy sonaru a použitím v praxi</h2>'
 .'<p>Chcete si rovnou otevřít přehled echolotů a sonarů?</p>'
 .'<p>Když už si chcete při čtení porovnávat reálné modely, dává smysl mít otevřenou i kategorii echolotů. '.$c::OLD.'</p>'
 .'<p>'.$merchant.' '.$oldLink.'</p>'
 .str_repeat('<p>Ověřené a věcné technické vysvětlení sonaru.</p>',45);
check(strlen($source)>600,'source');
$fixed=$c::render($source);
check($fixed!==$source,'merchant winner corrected');
check(substr_count($fixed,$c::MARKER)===1,'one exact marker');
check(strpos($fixed,$c::OLD)===false,'unverified claim removed');
check(strpos($fixed,'nikoli důkazem dnešní nejširší nabídky')!==false,'explicit uncertainty');
check(substr_count($fixed,$merchant)===1&&substr_count($fixed,$oldLink)===1,'original merchant and internal href byte preserving');
check($c::transform($fixed)===$fixed,'idempotence');
$dupe=$source.'<p>'.$c::OLD.'</p>';
check($c::transform($dupe)===$dupe,'duplicate source claim fail closed');
$changed=str_replace($c::SOURCE_MARKER,'editor-replaced-source',$source);
check($c::transform($changed)===$changed,'source drift fails closed');
$wrapped=str_replace($c::OLD,'<a href="https://merchant.example.invalid/">'
 .$c::OLD.'</a>',$source);
check($c::transform($wrapped)===$wrapped,'linked merchant phrase cannot be changed');
$inline=str_replace($c::OLD,'<strong>'.$c::OLD.'</strong>',$source);
check($c::transform($inline)===$source?false:$c::transform($inline)===$inline,
 'editor markup cannot silently change');
// Correct source scope and post metadata are required before modifying HTML.
check($c::render($source)===$fixed,'real post eligible');
foreach(['id'=>999,'main'=>false,'loop'=>false,'admin'=>true,
 'preview'=>true,'type'=>'page','status'=>'draft','slug'=>'changed',
 'gmt'=>'2026-11-10 12:00:00'] as $field=>$value){
 $old=$GLOBALS['ctx'][$field];$GLOBALS['ctx'][$field]=$value;
 check($c::render($source)===$source,'scope '.$field);
 $GLOBALS['ctx'][$field]=$old;
}
echo "PASS exact sonar merchant-neutral advice, original affiliate href immutable, source/editor guard\n";
