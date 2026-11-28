<?php
/** Six pinned root-guide choices; no WordPress writes. */
define('ABSPATH',__DIR__);
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['root']=['slug'=>'vybaveni-pro-rybare','parent'=>0,
 'url'=>'https://na-ryby.eu/category/vybaveni-pro-rybare/',
 'host'=>'https://na-ryby.eu','admin'=>false];
$GLOBALS['hooks']=[];$GLOBALS['assets']=[];
function add_action($a,$b,$c=10,$d=1){$GLOBALS['hooks'][$a]=$b;}
function is_category($s){return $s===$GLOBALS['root']['slug'];}
function is_admin(){return $GLOBALS['root']['admin'];}
function is_feed(){return false;}
function is_paged(){return false;}
function get_queried_object(){return (object)['taxonomy'=>'category',
 'slug'=>$GLOBALS['root']['slug'],'parent'=>$GLOBALS['root']['parent'],'term_id'=>42];}
function get_term_link($t){return $GLOBALS['root']['url'];}
function home_url($p='/'){return $GLOBALS['root']['host'].'/';}
function plugins_url($p,$f){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$p;}
function wp_enqueue_style($a,$b,$c,$d){$GLOBALS['assets'][]=$b;}
function wp_enqueue_script($a,$b,$c,$d,$e){$GLOBALS['assets'][]=$b;}
function check_nry($v,$msg){if(!$v)throw new RuntimeException($msg);}
require dirname(__DIR__).'/equipment-hub-decision-entry-v1.php';
$cl='NRY_Equipment_Hub_Decision_V1';$block=$cl::block();
check_nry(count($cl::choices())===6,'six cards');
check_nry(substr_count($block,'data-nry-equipment-choice=')===6,'six CTA');
check_nry(substr_count($block,$cl::MARKER)===1,'unique block');
foreach($cl::choices() as $c){
 check_nry(count($c)===4 && str_starts_with($c[3],'https://na-ryby.eu/'),'same site');
 check_nry(substr_count($block,'href="'.$c[3].'"')===1,'unique exact destination');
}
$h='<h1 class="td-page-title">Vybavení pro rybáře</h1>';
$source='<html><body>'.$h.str_repeat('<article>Unchanged category result</article>',180).$block.'</body></html>';
$m=$cl::move_after_h1($source);
check_nry($m!==$source&&str_contains($m,$h.$block),'first-screen place');
check_nry(strlen($m)===strlen($source),'historical content preserved');
check_nry($cl::move_after_h1($m)===$m,'idempotent');
check_nry($cl::move_after_h1($source.$block)===$source.$block,'duplicate fails closed');
$cl::assets();check_nry(count($GLOBALS['assets'])===2,'bounded assets');
$GLOBALS['root']['parent']=99;
check_nry($cl::move_after_h1($source)===$source,'only parent');
$GLOBALS['root']['parent']=0;$GLOBALS['root']['admin']=true;
check_nry($cl::move_after_h1($source)===$source,'no admin');
echo "EQUIPMENT_HUB_SIX_REAL_GUIDES_OFFLINE_VERIFIED\n";
