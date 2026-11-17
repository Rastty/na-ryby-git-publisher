<?php
// Pure-PHP contract tests: old affiliate URLs retained, content unchanged in DB.
define('ABSPATH',__DIR__);
$GLOBALS['nry_mode']=array('id'=>119575,'type'=>'post','status'=>'publish',
    'slug'=>'nejlepsi-rybarske-bivaky-do-10-000-kc',
    'modified'=>'2026-05-13 14:09:39','singular'=>true,
    'admin'=>false,'preview'=>false,'site'=>'https://na-ryby.eu/',
    'permalink'=>'https://na-ryby.eu/nejlepsi-rybarske-bivaky-do-10-000-kc/');
function add_filter($name,$callback,$priority=10){$GLOBALS['nry_filter']=compact('name','callback','priority');}
function get_queried_object_id(){return $GLOBALS['nry_mode']['id'];}
function is_singular($type){return $type==='post' && $GLOBALS['nry_mode']['singular'];}
function is_admin(){return $GLOBALS['nry_mode']['admin'];}
function is_preview(){return $GLOBALS['nry_mode']['preview'];}
function home_url($path='/'){return $GLOBALS['nry_mode']['site'];}
function get_post($id){$v=$GLOBALS['nry_mode'];return (object)array(
    'ID'=>$id,'post_type'=>$v['type'],'post_status'=>$v['status'],
    'post_name'=>$v['slug'],'post_modified_gmt'=>$v['modified']);}
function get_permalink($id){return $GLOBALS['nry_mode']['permalink'];}
function verify($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS $message\n";}
require __DIR__.'/../bivak-budget-buyer-trust-119575-v1.php';
$c='NRY_Bivak_Budget_Buyer_Trust_119575_V1';
verify($GLOBALS['nry_filter']['name']==='the_content','read-only render filter registered');
$source='<div><h1>Nejlepší rybářské bivaky do 10 000 Kč</h1><p>Úvod průvodce</p>';
$source.=$c::ANCHOR.' padding:24px">';
foreach($c::changes() as $old=>$replacement){$source.=str_repeat('<div>'.$old.'</div>',$replacement[1]);}
$source.='<p>Prologic C-Series 2 Man; Mivardi Entrix XL; Delphin Panorama Eazy</p>';
for($i=0;$i<22;$i++){
    $source.='<a href="https://www.tkqlhce.com/click-100927011-12347575?url=https%3A%2F%2Fwww.chytapust.cz%2FBivaky%2FModel-'
        .$i.'">Ověřit aktuální detail</a>';
}
$source.=str_repeat('<p>Podle délky výpravy a skutečných rozměrů vybíráme konstrukci.</p>',430).'</div>';
$updated=$c::render($source);
verify($updated!==$source && substr_count($updated,$c::MARKER)===1,'one new buyer verification note');
verify(strpos($updated,$c::MARKER)<strpos($updated,$c::HISTORICAL_CJ),
    'buyer checks are shown before first merchant link');
verify(substr_count($updated,$c::LINK)===1,'one exact internal product-choice guide');
verify(substr_count($updated,$c::HISTORICAL_CJ)===22,'all 22 legacy affiliate links left intact');
foreach($c::changes() as $old=>$replacement){
    verify(strpos($updated,$old)===false && substr_count($updated,$replacement[0])===$replacement[1],
        'unverified commercial superlative corrected: '.substr(md5($old),0,7));
}
verify($c::render($updated)===$updated,'render is idempotent');
foreach(array('id'=>42,'type'=>'page','status'=>'draft','slug'=>'different',
    'modified'=>'2026-11-17 00:00:00','singular'=>false,'admin'=>true,
    'preview'=>true,'site'=>'https://another.example/','permalink'=>'https://na-ryby.eu/different/') as $field=>$value){
    $original=$GLOBALS['nry_mode'][$field];$GLOBALS['nry_mode'][$field]=$value;
    verify($c::render($source)===$source,'identity guard '.$field);
    $GLOBALS['nry_mode'][$field]=$original;
}
foreach(array($c::ANCHOR,'Nejlevnější 2man volba','Delphin Panorama Eazy') as $key){
    verify($c::render(str_replace($key,'changed by editor',$source))===str_replace($key,'changed by editor',$source),
        'source drift refuses partial change '.substr(md5($key),0,7));
}
verify($c::render(str_replace('click-100927011-12347575','unverified-other-identity',$source))===
    str_replace('click-100927011-12347575','unverified-other-identity',$source),
    'historical affiliate context change blocks stale transform');
verify($c::render(str_replace($c::ANCHOR,$c::ANCHOR.$c::ANCHOR,$source))===
    str_replace($c::ANCHOR,$c::ANCHOR.$c::ANCHOR,$source),'duplicate source anchor blocked');
echo "PASS no WordPress stored mutation, tracking rewrite or invented current price\n";
