<?php
/* Offline exact identity, source, merchant-route and idempotence contract. */
define('ABSPATH','/');
$GLOBALS['ctx']=['id'=>118455,'main'=>true,'loop'=>true,'single'=>true,
 'admin'=>false,'preview'=>false,'type'=>'post','status'=>'publish',
 'slug'=>'nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz',
 'gmt'=>'2026-11-08 10:21:01','raw'=>str_repeat('x',39987)];
$GLOBALS['hooks']=[];
function add_filter($name,$fn,$p=10){$GLOBALS['hooks'][]=[$name,$fn,$p];}
function is_single($id){return $GLOBALS['ctx']['single']&&$id===$GLOBALS['ctx']['id'];}
function is_singular($type){return $type==='post'&&$GLOBALS['ctx']['type']==='post';}
function get_queried_object_id(){return $GLOBALS['ctx']['id'];}
function is_main_query(){return $GLOBALS['ctx']['main'];}
function in_the_loop(){return $GLOBALS['ctx']['loop'];}
function is_admin(){return $GLOBALS['ctx']['admin'];}
function is_preview(){return $GLOBALS['ctx']['preview'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return NRY_Budget_Owner_First_118455_V1::URL;}
function get_post($id){$x=$GLOBALS['ctx'];return (object)['ID'=>$id,
 'post_type'=>$x['type'],'post_status'=>$x['status'],'post_name'=>$x['slug'],
 'post_modified_gmt'=>$x['gmt'],'post_content'=>$x['raw']];}
function check($ok,$name){if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}}
require dirname(__DIR__).'/sonar-budget-owner-first-118455-v1.php';
$c='NRY_Budget_Owner_First_118455_V1';
check(count($GLOBALS['hooks'])===1&&$GLOBALS['hooks'][0][2]===60,'scoped hook');
$merchant='<a href="https://go.dognet.com/exact?a=1&amp;variant=2">Zjistit cenu</a>';
$buyer='<a href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/">Původní odkaz</a>';
$source=$c::TRUST.'<h2>'.$c::INTRO.'</h2><p>'.$merchant.$buyer.'</p>'
 .str_repeat('<p>Rybářský průvodce a užitečná doporučení.</p>',110);
check(strlen($source)>3000,'fixture');
$fixed=$c::transform($source);
check($fixed!==$source,'owner-first tip inserted');
check(substr_count($fixed,$c::MARKER)===1,'one marker');
check(substr_count($fixed,$c::PRACTICAL)===1,'one new route');
check(strpos($fixed,$merchant)!==false&&strpos($fixed,$buyer)!==false,'all original URLs immutable');
check(strpos($fixed,$c::MARKER)<strpos($fixed,$c::INTRO),'before buyer guide');
check($c::transform($fixed)===$fixed,'idempotent');
check($c::transform(str_replace($c::TRUST,'NO_TRUST',$source))===
 str_replace($c::TRUST,'NO_TRUST',$source),'missing trust fails closed');
$edited=str_replace($c::INTRO,'Redaktor upravil rozcestník',$source);
check($c::transform($edited)===$edited,'editor precedence');
$dup=$source.'<h2>'.$c::INTRO.'</h2>';
check($c::transform($dup)===$dup,'duplicated first decision fails closed');
$prelinked=$source.'<a href="'.$c::PRACTICAL.'">Existing owner link</a>';
check($c::transform($prelinked)===$prelinked,'existing link protected');
check($c::render($source)===$source,'unknown raw WordPress SHA prevents runtime change');
foreach(['id'=>118553,'single'=>false,'type'=>'page','main'=>false,
 'loop'=>false,'admin'=>true,'preview'=>true,'status'=>'draft',
 'slug'=>'different','gmt'=>'2026-11-10 00:00:00'] as $key=>$value) {
 $old=$GLOBALS['ctx'][$key];$GLOBALS['ctx'][$key]=$value;
 check($c::render($source)===$source,'scope guard '.$key);
 $GLOBALS['ctx'][$key]=$old;
}
echo "PASS exact buyer source guard, original merchant routes, one-owner handoff\n";
