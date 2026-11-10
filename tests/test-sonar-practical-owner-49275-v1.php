<?php
/* No-network exact-source/identity and merchant-preservation contract. */
define('ABSPATH','/');
$GLOBALS['ctx']=['id'=>49275,'single'=>true,'main'=>true,'loop'=>true,'admin'=>false,
 'preview'=>false,'type'=>'post','status'=>'publish','slug'=>'jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun',
 'gmt'=>'2026-10-16 11:38:12','source'=>str_repeat('x',12027)];
$GLOBALS['filters']=[];
function add_filter($name,$cb,$priority=10){$GLOBALS['filters'][]=[$name,$cb,$priority];}
function is_single($id){return $GLOBALS['ctx']['single']&&$GLOBALS['ctx']['id']===$id;}
function is_singular($type){return $type==='post'&&$GLOBALS['ctx']['type']==='post';}
function get_queried_object_id(){return $GLOBALS['ctx']['id'];}
function is_main_query(){return $GLOBALS['ctx']['main'];}
function in_the_loop(){return $GLOBALS['ctx']['loop'];}
function is_admin(){return $GLOBALS['ctx']['admin'];}
function is_preview(){return $GLOBALS['ctx']['preview'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return NRY_Sonar_Practical_Owner_49275_V1::URL;}
function get_post($id){$c=$GLOBALS['ctx'];return (object)['ID'=>$id,'post_type'=>$c['type'],
 'post_status'=>$c['status'],'post_name'=>$c['slug'],
 'post_modified_gmt'=>$c['gmt'],'post_content'=>$c['source']];}
function chk($ok,$note){if(!$ok){fwrite(STDERR,"FAIL $note\n");exit(1);}}
require dirname(__DIR__).'/sonar-practical-owner-guide-49275-v1.php';
$c='NRY_Sonar_Practical_Owner_49275_V1';
chk(count($GLOBALS['filters'])===1&&$GLOBALS['filters'][0][2]===53,'single hook');
$original='<a href="https://www.chytapust.cz/e/?a=KEEP&amp;b=2">Původní obchod</a>';
$boat='<a href="https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/">Původní lodní návod</a>';
$heading='<h2>Jak efektivně používat echolot a sonar na loď nebo člun</h2>';
$rules=(new ReflectionMethod($c,'corrections'))->invoke(null);
$source='<div>'.$heading.'<p>Praktický průvodce</p>';
foreach($rules as $r){$source.='<p>'.$r[0].'</p>';}
$source.=$original.$boat.str_repeat('<p>Bezpečné informace o použití sonaru na vodě.</p>',130).'</div>';
chk(strlen($source)>6000,'full-sized fixture');
$after=$c::transform($source);
chk($after!==$source,'replaces five unsafe public claims and inserts choice guidance');
foreach($rules as $r){chk(strpos($after,$r[0])===false&&strpos($after,$r[1])!==false,
 'exactly one old claim replaced');}
chk(substr_count($after,$c::MARKER)===1,'unique marker');
chk(strpos($after,$original)!==false&&strpos($after,$boat)!==false,
 'original buyer/merchant links unchanged');
chk(substr_count($after,$c::PILLAR)===1&&substr_count($after,$c::BUDGET)===1,
 'two new exact internal routes');
chk($c::transform($after)===$after,'idempotence');
$dup=str_replace('</div>','<p>'.$rules[0][0].'</p></div>',$source);
chk($c::transform($dup)===$dup,'duplicate source fails closed');
$drift=str_replace($rules[1][0],'editor revised this claim',$source);
chk($c::transform($drift)===$drift,'editor change fails closed');
$noHeading=str_replace($heading,'<h2>Different heading</h2>',$source);
chk($c::transform($noHeading)===$noHeading,'heading drift fails closed');
$linked=str_replace($rules[0][0],'<a href="https://merchant.example/new">'.$rules[0][0].'</a>',$source);
chk($c::transform($linked)===$linked,'unauthorized new link blocks change');
$noPin=$GLOBALS['ctx']['source'];
chk($c::render($source)===$source,'unknown WP exact sha blocks render');
$GLOBALS['ctx']['source'].='X';
chk($c::render($source)===$source,'modified WP source blocks render');
$GLOBALS['ctx']['source']=$noPin;
foreach(['id'=>49276,'single'=>false,'main'=>false,'loop'=>false,
 'admin'=>true,'preview'=>true,'type'=>'page','status'=>'draft','slug'=>'other',
 'gmt'=>'2026-11-10 12:00:00'] as $key=>$value){
 $old=$GLOBALS['ctx'][$key];$GLOBALS['ctx'][$key]=$value;
 chk($c::render($source)===$source,'context guard '.$key);
 $GLOBALS['ctx'][$key]=$old;
}
echo "PASS sonar practical owner-only exact source + five trust fixes + 2 internal links; no WP writes\n";
