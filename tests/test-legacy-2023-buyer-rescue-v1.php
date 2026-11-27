<?php
/** Six explicit historical 2023 posts, never a sitewide text rewrite. */
define('ABSPATH',__DIR__);
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['post_id']=777;
$GLOBALS['slug']='nejlepsi-rybarske-lehatko-jak-vybrat-nejlepsi-lehatko-pro-rybare';
$GLOBALS['title']='Nejlepší rybářské lehátko - jak vybrat nejlepší lehátko pro rybáře';
$GLOBALS['date']='2023-09-22 07:00:00';
$GLOBALS['preview']=false;$GLOBALS['admin']=false;$GLOBALS['loop']=true;
$GLOBALS['enqueue']=array();
function add_filter($a,$b,$c=10){return true;}
function add_action($a,$b,$c=10){return true;}
function is_singular($a){return $a==='post';}
function is_single($id){return $id===$GLOBALS['post_id'];}
function is_admin(){return $GLOBALS['admin'];}
function is_preview(){return $GLOBALS['preview'];}
function is_feed(){return false;}
function is_customize_preview(){return false;}
function in_the_loop(){return $GLOBALS['loop'];}
function is_main_query(){return true;}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_queried_object_id(){return $GLOBALS['post_id'];}
function get_post($id){return (object)array('ID'=>$id,'post_type'=>'post',
  'post_status'=>'publish','post_name'=>$GLOBALS['slug'],
  'post_title'=>$GLOBALS['title'],'post_date_gmt'=>$GLOBALS['date'],
  'post_content'=>str_repeat('Historický obsah a recenze. ',130));}
function get_permalink($id){return 'https://na-ryby.eu/'.$GLOBALS['slug'].'/';}
function plugins_url($p,$f){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$p;}
function wp_enqueue_style($a,$b,$c,$d){$GLOBALS['enqueue'][]=array($a,$b);}
function wp_enqueue_script($a,$b,$c,$d,$e){$GLOBALS['enqueue'][]=array($a,$b);}
function verify_old($condition,$name){if(!$condition)throw new RuntimeException($name);}
require dirname(__DIR__).'/legacy-2023-buyer-rescue-v1.php';
$C='NRY_Legacy_2023_Buyer_Rescue_V1';
$cases=$C::routes();verify_old(count($cases)===6,'six exact sources');
$total=0;
foreach($cases as $slug=>$cfg){
  $total+=count($cfg['links']);
  verify_old(count($cfg['links'])===3,'three routes per old article');
  $block=$C::block(array('slug'=>$slug)+$cfg);
  verify_old(substr_count($block,$C::MARKER)===1,'unique panel');
  verify_old(substr_count($block,'data-nry-legacy-guide=')===3,'exact guides');
  foreach($cfg['links'] as $x){
    verify_old(strpos($x[3],'https://na-ryby.eu/')===0,'internal links only');
    verify_old(substr_count($block,'href="'.$x[3].'"')===1,'exact internal URL');
  }
}
verify_old($total===18,'eighteen guides');
$original=str_repeat('<p>Původní affiliate odkazy a recenze.</p>',70);
$GLOBALS['preview']=true;
verify_old($C::render($original)===$original,'preview must be unchanged');
$GLOBALS['preview']=false;
$GLOBALS['admin']=true;
verify_old($C::render($original)===$original,'admin must be unchanged');
$GLOBALS['admin']=false;
$GLOBALS['date']='2026-02-01 10:00:00';
verify_old($C::render($original)===$original,'new posts blocked');
$GLOBALS['date']='2023-09-22 07:00:00';
$GLOBALS['slug']='unknown-buyer-post';
verify_old($C::render($original)===$original,'unknown slug blocked');
$GLOBALS['slug']='nejlepsi-rybarske-lehatko-jak-vybrat-nejlepsi-lehatko-pro-rybare';
$GLOBALS['loop']=false;
verify_old($C::render($original)===$original,'noncontent render blocked');
$GLOBALS['loop']=true;
$C::enqueue();
verify_old(count($GLOBALS['enqueue'])===2,'bounded CSS/JS enqueues');
$changed=$C::render($original);
verify_old($changed!==$original,'eligible original transformed');
verify_old(str_ends_with($changed,$original),'all old HTML preserved byte-for-byte');
verify_old(substr_count($changed,$C::MARKER)===1,'single new panel');
verify_old(substr_count($changed,'data-nry-legacy-guide=')===3,'three guide buttons visible');
verify_old($C::render($original)===$original,'no duplicate render');
echo "LEGACY_2023_SIX_BUYER_RESCUES_EIGHTEEN_PATHS_OFFLINE_PASS\n";
