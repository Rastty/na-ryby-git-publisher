<?php
define('ABSPATH',__DIR__);
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['post_id']=999;$GLOBALS['slug']='rybarska-chata-bitov';
$GLOBALS['title']='Rybářská chata Bítov – Vranovská přehrada';
$GLOBALS['post_content']=str_repeat('historická dvojchata, pobyt u vody. ',120);
$GLOBALS['admin']=false;$GLOBALS['preview']=false;$GLOBALS['loop']=true;
$GLOBALS['hooks']=0;
function add_filter($a,$b,$c=10){$GLOBALS['hooks']++;return true;}
function add_action($a,$b,$c=10){$GLOBALS['hooks']++;return true;}
function is_single($id){return $id===$GLOBALS['post_id'];}
function is_singular($a){return $a==='post';}
function is_admin(){return $GLOBALS['admin'];}
function is_preview(){return $GLOBALS['preview'];}
function is_feed(){return false;}
function is_customize_preview(){return false;}
function in_the_loop(){return $GLOBALS['loop'];}
function is_main_query(){return true;}
function home_url($p='/'){return 'https://na-ryby.eu/';}
function get_queried_object_id(){return $GLOBALS['post_id'];}
function get_permalink($id){return 'https://na-ryby.eu/'.$GLOBALS['slug'].'/';}
function get_post($id){return (object)array('ID'=>$id,'post_type'=>'post',
  'post_status'=>'publish','post_name'=>$GLOBALS['slug'],
  'post_title'=>$GLOBALS['title'],'post_date_gmt'=>'2017-06-28 08:00:00',
  'post_content'=>$GLOBALS['post_content']);}
function assert_stay($ok,$msg){if(!$ok)throw new RuntimeException($msg);}
require dirname(__DIR__).'/six-real-stay-offers-v1.php';
$C='NRY_Six_Real_Stay_Offers_V1';
$routes=$C::routes();$urls=$C::approvedLinks();
assert_stay(count($routes)===6&&count($urls)===4,'six pages four published affiliate URLs');
$placements=0;
foreach($routes as $slug=>$row){
  assert_stay(count($row['offers'])===2,'two exact offers');
  $block=$C::panel(array('slug'=>$slug)+$row);
  assert_stay(substr_count($block,$C::MARKER)===1,'one panel');
  assert_stay(substr_count($block,'data-nry-real-stay-offer=')===2,'two merchant choices');
  assert_stay(substr_count($block,'rel="sponsored nofollow noopener"')===2,'affiliate disclosure');
  foreach($row['offers'] as $offer){
    assert_stay(isset($urls[$offer[0]]),'approved merchant ID');
    assert_stay(substr_count($block,htmlspecialchars($urls[$offer[0]],ENT_QUOTES,'UTF-8'))===1,
      'exact approved affiliate URL');
    $placements++;
  }
}
assert_stay($placements===12,'twelve real merchant exits');
$orig=str_repeat('<p>historická dvojchata a rybaření</p>',75);
$GLOBALS['preview']=true;
assert_stay($C::render($orig)===$orig,'preview blocked');
$GLOBALS['preview']=false;$GLOBALS['admin']=true;
assert_stay($C::render($orig)===$orig,'admin blocked');
$GLOBALS['admin']=false;$GLOBALS['loop']=false;
assert_stay($C::render($orig)===$orig,'outside main loop blocked');
$GLOBALS['loop']=true;$GLOBALS['slug']='wrong-post';
assert_stay($C::render($orig)===$orig,'other post blocked');
$GLOBALS['slug']='rybarska-chata-bitov';$GLOBALS['title']='Rybářská chata Bítov – Vranovská přehrada';
$changed=$C::render($orig);
assert_stay($changed!==$orig && substr($changed,-strlen($orig))===$orig,
  'source body byte-preserved with visible hero');
assert_stay(substr_count($changed,$C::MARKER)===1,'exact one new real-offer hero');
assert_stay($C::render($orig)===$orig,'no duplicate injection');
echo "NRY_SIX_STAY_MERCHANT_CTA_TWELVE_OFFLINE_PASS\n";
