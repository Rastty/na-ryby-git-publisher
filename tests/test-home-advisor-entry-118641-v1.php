<?php
/* Standalone no-network test for a guarded homepage guidance entry. */
define('ABSPATH','/');
$GLOBALS['ctx']=array('id'=>118641,'front'=>true,'page'=>true,'main'=>true,
 'loop'=>true,'admin'=>false,'preview'=>false,'type'=>'page','status'=>'publish',
 'slug'=>'na-ryby-eu-vse-pro-rybare');
$GLOBALS['filters']=array();
function add_filter($hook,$fn,$priority=10){$GLOBALS['filters'][]=array($hook,$fn,$priority);}
function is_front_page(){return $GLOBALS['ctx']['front'];}
function is_page($id){return $GLOBALS['ctx']['page'] && $GLOBALS['ctx']['id']===$id;}
function get_queried_object_id(){return $GLOBALS['ctx']['id'];}
function is_main_query(){return $GLOBALS['ctx']['main'];}
function in_the_loop(){return $GLOBALS['ctx']['loop'];}
function is_admin(){return $GLOBALS['ctx']['admin'];}
function is_preview(){return $GLOBALS['ctx']['preview'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return 'https://na-ryby.eu/';}
function get_post($id){return (object)array('ID'=>$id,
 'post_type'=>$GLOBALS['ctx']['type'],'post_status'=>$GLOBALS['ctx']['status'],
 'post_name'=>$GLOBALS['ctx']['slug']);}
function chk($cond,$note){if(!$cond){fwrite(STDERR,"FAIL $note\n");exit(1);}}
require dirname(__DIR__).'/home-advisor-entry-118641-v1.php';
$c='NRY_Home_Advisor_Entry_118641_V1';
chk(count($GLOBALS['filters'])===1 && $GLOBALS['filters'][0][2]===54,'scoped hook');
$merchant='<a href="https://www.chytapust.cz/old-product/?keep=1&amp;src=2">Existing merchant</a>';
$oldLink='<a href="https://na-ryby.eu/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/">Existing guide</a>';
$source='<section><span hidden '.$c::SOURCE_MARKER.'></span>'
 .'<h2>Na-Ryby.eu – vše pro rybáře na jednom místě</h2>'
 .'<p>'.$c::HERO.'</p><p>'.$merchant.$oldLink.'</p>'
 .'<h2>Vybavení podle hlavních kategorií</h2>'
 .str_repeat('<p>Další původní průvodce zůstává bez změny.</p>',75).'</section>';
chk(strlen($source)>2500,'realistic home fixture');
$fixed=$c::render($source);
chk($fixed!==$source,'added buyer guidance');
chk(substr_count($fixed,$c::MARKER)===1,'one unique marker');
chk(substr_count($fixed,$c::ADVISOR)===1,'one internal advisor route');
chk(substr_count($fixed,$c::PROBLEMS_MARKER)===1,'one problem navigation');
foreach(array($c::CARP_GUIDE,$c::SONAR_BUDGET,$c::SONAR_OWNER) as $link){
 chk(substr_count($fixed,'href="'.$link.'"')===1,'one exact problem route: '.$link);
}
$navStart=strpos($fixed,'<nav '.$c::PROBLEMS_MARKER);
$navEnd=strpos($fixed,'</nav>',$navStart);
chk($navStart!==false&&$navEnd!==false&&$navEnd>$navStart,
 'bounded navigation');
$nav=substr($fixed,$navStart,$navEnd-$navStart);
preg_match_all('~href="([^"]+)"~',$nav,$h);
chk($h[1]===array($c::CARP_GUIDE,$c::SONAR_BUDGET,$c::SONAR_OWNER),
 'exactly three first-party known safe guides, no merchant links');
chk(strpos($fixed,$c::MARKER)<$navStart
 && $navEnd<strpos($fixed,'Vybavení podle hlavních kategorií'),
 'problem routes between original advisor and category cards');
chk(substr_count($fixed,$merchant)===1,'merchant destination preserved');
chk(substr_count($fixed,$oldLink)===1,'existing guide destination preserved');
chk($c::render($fixed)===$fixed,'idempotent multiple content filters');
$oldSourceWithProblems=str_replace('</section>',
  '<nav '.$c::PROBLEMS_MARKER.'>Editor guidance</nav></section>',$source);
chk($c::render($oldSourceWithProblems)===$oldSourceWithProblems,
 'never duplicate editor-inserted routes');
chk(strpos($fixed,$c::MARKER)>strpos($fixed,$c::HERO)
 && strpos($fixed,$c::MARKER)<strpos($fixed,'Vybavení podle hlavních kategorií'),
 'decision entry after hero introduction and before categories');
$changed=str_replace($c::HERO,'Copy changed by editor.',$source);
chk($c::render($changed)===$changed,'editor drift blocks');
$missing=str_replace($c::SOURCE_MARKER,'old-trust-marker',$source);
chk($c::render($missing)===$missing,'source marker drift blocks');
$duplicate=str_replace('</section>','<p>'.$c::HERO.'</p></section>',$source);
chk($c::render($duplicate)===$duplicate,'duplicate content blocks');
$markup=str_replace($c::HERO,'Průvodci <strong>výběrem</strong> vybavení.',$source);
chk($c::render($markup)===$markup,'changed hero markup blocks');
foreach(array('id'=>118642,'front'=>false,'page'=>false,'main'=>false,
 'loop'=>false,'admin'=>true,'preview'=>true,'type'=>'post',
 'status'=>'draft','slug'=>'other') as $key=>$value){
 $prev=$GLOBALS['ctx'][$key];$GLOBALS['ctx'][$key]=$value;
 chk($c::render($source)===$source,'other context: '.$key);
 $GLOBALS['ctx'][$key]=$prev;
}
echo "PASS homepage advisor plus three problem-first routes; unchanged prior merchant links; no writes\n";
