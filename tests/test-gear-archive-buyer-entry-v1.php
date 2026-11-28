<?php
/** Ten existing WP archives, fail-closed scoped frontend contracts. No network. */
define('ABSPATH', __DIR__);
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['gear'] = array('slug'=>'navijaky','parent'=>22,'url'=>
  'https://na-ryby.eu/category/vybaveni-pro-rybare/navijaky/',
  'host'=>'https://na-ryby.eu','admin'=>false,'feed'=>false,'paged'=>false,
  'preview'=>false,'customize'=>false,'ajax'=>false);
$GLOBALS['gear_actions']=array();$GLOBALS['gear_assets']=array();
function add_action($name,$callback,$priority=10,$args=1){
 $GLOBALS['gear_actions'][$name]=array($callback,$priority,$args);
}
function is_category($slug){return $GLOBALS['gear']['slug']===$slug;}
function is_admin(){return $GLOBALS['gear']['admin'];}
function is_feed(){return $GLOBALS['gear']['feed'];}
function is_paged(){return $GLOBALS['gear']['paged'];}
function is_preview(){return $GLOBALS['gear']['preview'];}
function is_customize_preview(){return $GLOBALS['gear']['customize'];}
function wp_doing_ajax(){return $GLOBALS['gear']['ajax'];}
function home_url($path='/'){return $GLOBALS['gear']['host'].'/';}
function get_queried_object(){
 return (object)array('taxonomy'=>'category','slug'=>$GLOBALS['gear']['slug'],
  'term_id'=>207,'parent'=>$GLOBALS['gear']['parent']);
}
function get_term_link($term){return $GLOBALS['gear']['url'];}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_style($handle,$src,$deps,$ver){$GLOBALS['gear_assets'][]=array('style',$handle,$src,$ver);}
function wp_enqueue_script($handle,$src,$deps,$ver,$footer){$GLOBALS['gear_assets'][]=array('script',$handle,$src,$ver,$footer);}
function check_gear($ok,$label){if(!$ok)throw new RuntimeException($label);}
require dirname(__DIR__).'/gear-archive-buyer-entry-v1.php';
$C='NRY_Gear_Archive_Buyer_Entry_V1';
$routes=$C::routes();
check_gear(count($routes)===10,'exact ten eligible categories');
check_gear(isset($GLOBALS['gear_actions']['template_redirect'],$GLOBALS['gear_actions']['loop_start'],$GLOBALS['gear_actions']['wp_enqueue_scripts']),'three guarded WP hooks');
$total=0;
foreach($routes as $slug=>$cfg){
 $GLOBALS['gear']['slug']=$slug;$GLOBALS['gear']['url']=$cfg['url'];
 check_gear(strpos($cfg['url'],'https://na-ryby.eu/category/vybaveni-pro-rybare/')===0,'same-site archive origin');
 $block=$C::block(array('slug'=>$slug)+$cfg);
 $links=count($cfg['links']);$total+=$links;
 check_gear($links>=2 && $links<=3,'bounded buyer paths');
 check_gear(substr_count($block,$C::MARKER)===1,'one block marker');
 check_gear(substr_count($block,'data-nry-gear-category="'.$slug.'"')===1,'exact slug');
 check_gear(substr_count($block,'<a class="nry-gear-archive-entry__choice"')===$links,'exact CTA count');
 check_gear(strpos($block,'https://na-ryby.eu/')!==false,'first-party-only block');
 foreach($cfg['links'] as $link){
  check_gear(count($link)===4,'static internal route schema');
  check_gear(substr_count($block,'data-nry-gear-guide="'.$link[0].'"')===1,'unique role');
  check_gear(substr_count($block,'href="'.$link[3].'"')===1,'exact destination');
  check_gear(strpos($link[3],'https://na-ryby.eu/')===0,'never merchant affiliate href');
 }
 $h1='<h1 class="td-page-title">'.$cfg['title'].'</h1>';
 $original='<html><head><title>Another '.$cfg['title'].'</title></head><body>'
    .$h1.'<p>Historical category description must remain unchanged</p>'
    .str_repeat('<article><h2>Historical WordPress result</h2></article>',100)
    .$block.'</body></html>';
 if($slug==='navijaky')$GLOBALS['navijaky_original']=$original;
 $changed=$C::place_after_archive_h1($original);
 check_gear($changed!==$original,'selected canonical archive transformed');
 check_gear(strpos($changed,$h1.$block)!==false,'block immediately after exact H1');
 check_gear(strlen($changed)===strlen($original),'no bytes added or removed');
 check_gear(str_replace($block,'',$changed)===str_replace($block,'',$original),
    'original WP archive and category content byte preserved');
 check_gear($C::place_after_archive_h1($changed)===$changed,'idempotent');
 $noBlock=str_replace($block,'',$original);
 if(!empty($cfg['fallback'])){
  check_gear($C::place_after_archive_h1($noBlock)===$C::place_after_archive_h1($original),'exact single-result fallback');
  $wrong=str_replace($h1,'<h1>Wrong</h1>',$noBlock);
  check_gear($C::place_after_archive_h1($wrong)===$wrong,'fallback rejects heading drift');
 } else check_gear($C::place_after_archive_h1($noBlock)===$noBlock,'legacy archives never use fallback');
 check_gear($C::place_after_archive_h1(str_replace($cfg['title'],'Wrong H1',$original))===str_replace($cfg['title'],'Wrong H1',$original),'H1 source drift fails closed');
 check_gear($C::place_after_archive_h1($original.$block)===$original.$block,'duplicate fails closed');
 $span=str_replace($h1,'<h1 class="td-page-title"><span>'.$cfg['title'].'</span></h1>',$original);
 check_gear($C::place_after_archive_h1($span)!==$span,'reviewed simple span supported');
 $bad=str_replace($h1,'<h1><a href="https://merchant.example/">'.$cfg['title'].'</a></h1>',$original);
 check_gear($C::place_after_archive_h1($bad)===$bad,'nested unreviewed title fails closed');
}
check_gear($total===29,'twenty-nine relevant first-party paths');
$GLOBALS['gear']['slug']='navijaky';$GLOBALS['gear']['url']=$routes['navijaky']['url'];
$C::enqueue();
check_gear(count($GLOBALS['gear_assets'])===2
 && $GLOBALS['gear_assets'][0][0]==='style'
 && strpos($GLOBALS['gear_assets'][0][2],'nry-gear-archive-entry-v1.css')!==false
 && $GLOBALS['gear_assets'][1][0]==='script'
 && strpos($GLOBALS['gear_assets'][1][2],'nry-gear-archive-click-v1.js')!==false
 && $GLOBALS['gear_assets'][1][4]===true,'bounded assets CSS and JS');
class Gear_Query {
 private $main;public function __construct($x){$this->main=$x;}
 public function is_main_query(){return $this->main;}
}
ob_start();$C::render_on_main_loop(new Gear_Query(false));$secondary=ob_get_clean();
check_gear($secondary==='','secondary loop never outputs');
ob_start();$C::render_on_main_loop(new Gear_Query(true));$main=ob_get_clean();
check_gear($main===$C::block(array('slug'=>'navijaky')+$routes['navijaky']),'main loop once');
ob_start();$C::render_on_main_loop(new Gear_Query(true));$second=ob_get_clean();
check_gear($second==='','no duplicate output');
$flags=array('admin'=>true,'feed'=>true,'paged'=>true,'preview'=>true,
 'customize'=>true,'ajax'=>true,'host'=>'https://other.example','parent'=>0,
 'slug'=>'unknown','url'=>'https://na-ryby.eu/category/other/');
foreach($flags as $key=>$value){
 $was=$GLOBALS['gear'][$key];$GLOBALS['gear'][$key]=$value;
 $GLOBALS['gear_assets']=array();$C::enqueue();
 check_gear(count($GLOBALS['gear_assets'])===0,'assets blocked on '.$key);
 check_gear($C::place_after_archive_h1($GLOBALS['navijaky_original'])===$GLOBALS['navijaky_original'],'no transform under '.$key);
 $GLOBALS['gear'][$key]=$was;
}
echo "GEAR_ARCHIVE_TEN_BUYER_PATHS_OFFLINE_VERIFIED\n";
