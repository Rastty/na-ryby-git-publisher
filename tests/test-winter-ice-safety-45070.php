<?php
define('ABSPATH','/test/');
$GLOBALS['filters']=array();
function add_filter($hook,$cb,$prio){$GLOBALS['filters'][]=array($hook,$cb,$prio);}
function did_action($name){return 1;}
function is_singular($type){return $type==='post';}
function in_the_loop(){return true;}
function get_the_ID(){return 45070;}
function home_url($path){return 'https://na-ryby.eu'.$path;}
function get_permalink($id){return NRY_Winter_Ice_Safety_45070::URL;}
function get_post($id){
 return (object)array('ID'=>$id,'post_type'=>'post','post_status'=>'publish',
   'post_name'=>NRY_Winter_Ice_Safety_45070::SLUG,
   'post_modified_gmt'=>NRY_Winter_Ice_Safety_45070::SOURCE_GMT,
   'post_content'=>'UNAUTHENTICATED_EXACT_SOURCE_NOT_AVAILABLE_IN_TEST');
}
require __DIR__.'/../winter-ice-safety-45070-v1.php';
function must($ok,$label){if(!$ok){fwrite(STDERR,'FAIL '.$label."\n");exit(1);}}
must(count($GLOBALS['filters'])===1,'only one content presentation filter');
must($GLOBALS['filters'][0][0]==='the_content'&&$GLOBALS['filters'][0][2]===8,
  'one exact filter, not a writer hook');
$pair=array(
  NRY_Winter_Ice_Safety_45070::OLD_ICE,
  NRY_Winter_Ice_Safety_45070::OLD_WARNING,
  NRY_Winter_Ice_Safety_45070::OLD_DEFINITION
);
$html='<h2>Zimní přívlač</h2><p>'.str_repeat('Test bezpečnosti u vody. ',96).'</p>'.
 '<p>'.$pair[0].' '.$pair[1].' a dodržovat zásady. </p>'.
 '<p>'.$pair[2].'</p>'.
 '<p><a href="https://na-ryby.eu/privlac/">Rybářské rady</a>'.
 '<img src="https://na-ryby.eu/wp-content/test.jpg">[shortcode x="y"]</p>';
must(strlen($html)>1500,'synthetic full source length');
$revised=NRY_Winter_Ice_Safety_45070::transform($html);
must($revised!==$html,'safety correction active for exact anchors');
must(substr_count($revised,NRY_Winter_Ice_Safety_45070::MARKER)===1,
  'exact prominent safety banner');
must(strpos($revised,NRY_Winter_Ice_Safety_45070::OLD_WARNING)===false,
  'no dangerous ignore-the-warning instruction');
must(strpos($revised,NRY_Winter_Ice_Safety_45070::OLD_ICE)===false,'no instruction to fish from ice');
must(strpos($revised,NRY_Winter_Ice_Safety_45070::OLD_DEFINITION)===false,
  'no conflated ice definition');
must(strpos($revised,'respektovat všechna varování')!==false,'safe direction');
must(strpos($revised,'Nevstupujte kvůli rybolovu')!==false,'safe summary');
must(strpos($revised,'href="https://na-ryby.eu/privlac/"')!==false,'internal link preserved');
must(strpos($revised,'src="https://na-ryby.eu/wp-content/test.jpg"')!==false,'media preserved');
must(strpos($revised,'[shortcode x="y"]')!==false,'shortcodes preserved');
must(NRY_Winter_Ice_Safety_45070::transform($revised)===$revised,
  'idempotent output');
must(NRY_Winter_Ice_Safety_45070::transform(str_replace($pair[1],'respektovat varování',$html))
 ===str_replace($pair[1],'respektovat varování',$html),'missing exact original fails closed');
must(NRY_Winter_Ice_Safety_45070::transform($html.$pair[1])===$html.$pair[1],
  'duplicate dangerous anchor fails closed');
must(NRY_Winter_Ice_Safety_45070::render($html)===$html,
  'unverified WordPress source SHA blocks display alteration');
$source=file_get_contents(__DIR__.'/../winter-ice-safety-45070-v1.php');
must(!preg_match('/\\b(wp_update_post|update_post_meta|update_option|wp_insert_post)\\s*\\(/',$source),
  'no post, option or meta writes');
echo "PASS: exact 45070 source-only visitor safety rendering, immutable links and no WP writes\n";
