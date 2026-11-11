<?php
declare(strict_types=1);
define('ABSPATH','/');
$GLOBALS['ctx']=[
    'id'=>115409,'main'=>true,'loop'=>true,'admin'=>false,'preview'=>false,
    'type'=>'page','status'=>'publish','slug'=>'ochrana-osobnich-udaju-gdpr',
    'gmt'=>'2026-02-16 07:29:16'
];
$GLOBALS['hooks']=[];
function add_filter($name,$callback,$priority=10){$GLOBALS['hooks'][]=[$name,$callback,$priority];}
function is_page($id){return $GLOBALS['ctx']['id']===$id;}
function get_queried_object_id(){return $GLOBALS['ctx']['id'];}
function is_main_query(){return $GLOBALS['ctx']['main'];}
function in_the_loop(){return $GLOBALS['ctx']['loop'];}
function is_admin(){return $GLOBALS['ctx']['admin'];}
function is_preview(){return $GLOBALS['ctx']['preview'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return 'https://na-ryby.eu/ochrana-osobnich-udaju-gdpr/';}
function get_post($id){return (object)[
    'ID'=>$id,'post_type'=>$GLOBALS['ctx']['type'],
    'post_status'=>$GLOBALS['ctx']['status'],
    'post_name'=>$GLOBALS['ctx']['slug'],
    'post_modified_gmt'=>$GLOBALS['ctx']['gmt']
];}
function ok($pass,$why){if(!$pass){fwrite(STDERR,"FAIL: $why\n");exit(1);}}
require dirname(__DIR__).'/nry-privacy-contact-cleanup-v1.php';
$c='NRY_Privacy_Contact_Copy_115409_V1';
ok(count($GLOBALS['hooks'])===1&&$GLOBALS['hooks'][0][2]===56,'scoped priority');
$old=$c::OLD_CONTACT;
$draft=$c::DRAFT;
$original='<h2>1) '.$c::HEAD.'</h2>'
 .'<p>Správcem osobních údajů je provozovatel webu na-ryby.eu.</p>'
 .'<p>Kontakt: současný správce.</p>'
 .'<p>E-mail: <a href="mailto:'.$old.'">'.$old.'</a></p>'
 .'<p>'.$draft.'</p>'
 .str_repeat('<p>Vysvětlení zpracování a zachování práv návštěvníka.</p>',80)
 .'<h2>9) '.$c::RIGHTS.'</h2>'
 .'<p>Napište nám: <a href="mailto:'.$old.'">'.$old.'</a></p>'
 .'<a href="https://www.tkqlhce.com/click-8287231-12347575?url=https%3A%2F%2Fwww.chytapust.cz%2F">Odkaz bez změny</a>'
 .'<a href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/">Interní průvodce</a>';
ok(strlen($original)>3200&&substr_count($original,$old)===4,'realistic source');
$fixed=$c::render($original);
ok($fixed!==$original,'replace one scoped public page');
ok(substr_count($fixed,$c::MARKER)===1&&substr_count($fixed,$old)===0,'one marker and no wrong contact');
ok(substr_count($fixed,$c::NEW_CONTACT)===4,'new contact exactly four times incl mailto');
ok(strpos($fixed,$draft)===false&&strpos($fixed,'<h2>9) '.$c::RIGHTS.'</h2>')!==false,'remove draft without legal rewrite');
ok(strpos($fixed,'https://www.tkqlhce.com/click-8287231-12347575?url=https%3A%2F%2Fwww.chytapust.cz%2F')!==false,'commercial href unchanged');
ok(strpos($fixed,'href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/"')!==false,'internal href unchanged');
ok($c::transform($fixed)===$fixed,'idempotent');
ok($c::transform($original.$draft)===$original.$draft,'duplicate draft blocked');
ok($c::transform(str_replace($c::HEAD,'edited heading',$original))===str_replace($c::HEAD,'edited heading',$original),'source heading change blocked');
ok($c::transform(str_replace($old,'something.else@example.com',$original))===str_replace($old,'something.else@example.com',$original),'source contact drift blocked');
foreach(['id'=>99,'main'=>false,'loop'=>false,'admin'=>true,'preview'=>true,
    'type'=>'post','status'=>'draft','slug'=>'other','gmt'=>'2026-11-11 00:00:00'] as $key=>$wrong){
    $save=$GLOBALS['ctx'][$key];
    $GLOBALS['ctx'][$key]=$wrong;
    ok($c::render($original)===$original,'scope guard '.$key);
    $GLOBALS['ctx'][$key]=$save;
}
echo "PASS source-pinned public policy copy correction; external links and all other legal clauses preserved\n";
