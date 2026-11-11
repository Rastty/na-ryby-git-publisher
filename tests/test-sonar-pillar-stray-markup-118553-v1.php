<?php
define('ABSPATH','/');
$GLOBALS['ctx']=['id'=>118553,'main'=>true,'loop'=>true,'admin'=>false,
 'preview'=>false,'type'=>'post','status'=>'publish',
 'slug'=>'jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce','gmt'=>'2026-11-04 11:27:17'];
$GLOBALS['hooks']=[];
function add_filter($n,$f,$p=10){$GLOBALS['hooks'][]=[$n,$f,$p];}
function is_single($id){return $GLOBALS['ctx']['id']===$id;}
function is_singular($t){return $GLOBALS['ctx']['type']===$t;}
function get_queried_object_id(){return $GLOBALS['ctx']['id'];}
function is_main_query(){return $GLOBALS['ctx']['main'];}
function in_the_loop(){return $GLOBALS['ctx']['loop'];}
function is_admin(){return $GLOBALS['ctx']['admin'];}
function is_preview(){return $GLOBALS['ctx']['preview'];}
function home_url($p='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return 'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';}
function get_post($id){return (object)['ID'=>$id,'post_type'=>$GLOBALS['ctx']['type'],
 'post_status'=>$GLOBALS['ctx']['status'],'post_name'=>$GLOBALS['ctx']['slug'],
 'post_modified_gmt'=>$GLOBALS['ctx']['gmt']];}
function ok($b,$why){if(!$b){fwrite(STDERR,"FAIL $why\n");exit(1);}}
require dirname(__DIR__).'/sonar-pillar-stray-markup-118553-v1.php';
$c='NRY_Sonar_Pillar_Stray_Markup_118553_V1';$bad='<p>„'.chr(96).'</p>';
ok(count($GLOBALS['hooks'])===1&&$GLOBALS['hooks'][0][2]===57,'correct priority');
$link='<a href="https://www.kqzyfj.com/click-sample?keep=1&amp;id=2">Mrknout na dostupnost</a>';
$source='<section '.$c::SOURCE.'>Ověřený průvodce</section>'
 .'<p '.$c::PRACTICAL.'>Praktický návod</p>'
 .'<p '.$c::NEUTRAL.'>Bez nepodložené obchodní výhodnosti</p>'
 .str_repeat('<p>Věcné rozhodnutí podle stylu lovu.</p>',40)
 .'<h3>Lowrance Eagle Eye 9 Live</h3><p>'.$link.'</p>'
 .$bad.'<h2>'.$c::HEADING.'</h2><p>Navazující články</p>';
ok(strlen($source)>1000,'realistic size');
$fixed=$c::render($source);
ok($fixed!==$source&&substr_count($fixed,$c::MARKER)===1,'removed once');
ok(strpos($fixed,$bad)===false&&strpos($fixed,$link)!==false,'href preserved');
ok($c::transform($fixed)===$fixed,'idempotent');
$entity=str_replace($bad,'<p>&bdquo;&#96;</p>',$source);
ok($c::transform($entity)!==$entity,'HTML entity variant');
ok($c::transform($source.$bad)===$source.$bad,'duplicates fail closed');
$wrapped=str_replace($bad,'<p><strong>„'.chr(96).'</strong></p>',$source);
ok($c::transform($wrapped)===$wrapped,'editor markup fail closed');
$drift=str_replace($c::NEUTRAL,'removed',$source);
ok($c::transform($drift)===$drift,'previous release required');
$moved=str_replace($bad,'',$source).$bad;
ok($c::transform($moved)===$moved,'out of scope paragraph');
foreach(['id'=>99,'main'=>false,'loop'=>false,'admin'=>true,'preview'=>true,
 'type'=>'page','status'=>'draft','slug'=>'edited','gmt'=>'2026-11-10 12:00:00'] as $k=>$v){
 $was=$GLOBALS['ctx'][$k];$GLOBALS['ctx'][$k]=$v;
 ok($c::render($source)===$source,'scope '.$k);$GLOBALS['ctx'][$k]=$was;
}
echo "PASS one source-bound orphan cleanup; original merchant href untouched\n";
