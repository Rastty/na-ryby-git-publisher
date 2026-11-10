<?php
/** No network, no WP state mutation. PHP 7.4 exact post/section regression. */
define('ABSPATH', __DIR__);
$GLOBALS['ctx'] = array('id'=>118553,'main'=>true,'loop'=>true,
    'admin'=>false,'preview'=>false,'slug'=>
    'jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce',
    'status'=>'publish','modified'=>'2026-11-04 11:27:17');
$GLOBALS['hooks'] = array();
function add_filter($type,$cb,$priority=10){$GLOBALS['hooks'][]=array($type,$cb,$priority);}
function is_single($id){return $id===$GLOBALS['ctx']['id'];}
function is_singular($type){return $type==='post';}
function get_queried_object_id(){return $GLOBALS['ctx']['id'];}
function is_main_query(){return $GLOBALS['ctx']['main'];}
function in_the_loop(){return $GLOBALS['ctx']['loop'];}
function is_admin(){return $GLOBALS['ctx']['admin'];}
function is_preview(){return $GLOBALS['ctx']['preview'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return 'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';}
function get_post($id){return (object)array(
    'ID'=>118553,'post_type'=>'post','post_status'=>$GLOBALS['ctx']['status'],
    'post_name'=>$GLOBALS['ctx']['slug'],
    'post_modified_gmt'=>$GLOBALS['ctx']['modified']);}
function check($condition,$message){
    if(!$condition)throw new RuntimeException($message);
}
require __DIR__.'/../sonar-pillar-practical-handoff-118553-v1.php';
$cls='NRY_Sonar_Pillar_Practical_Handoff_118553_V1';
check(count($GLOBALS['hooks'])===1,'one hook only');
check($GLOBALS['hooks'][0][0]==='the_content'&&$GLOBALS['hooks'][0][2]===44,
    'registered after original source section');
$original = '<section '.$cls::SOURCE_MARKER.'>'
    .'<h2>Jak vybrat vhodný typ echolotu</h2>'
    .'<p>Echolot nezaručuje úlovek. Pokud stávající vybavení dostačuje, další nákup není nutný.</p>'
    .'<ul><li>Ze břehu</li><li>Z člunu</li><li>Opakované návraty</li>'
    .'<li>Boční sken</li></ul>'
    .'<p>Aktuální konečnou cenu kompletu si prověřte; '
    .'aktuální konečnou cenu kompletu '
    .'<a href="https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/">rozpočet</a> '
    .'<a href="https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/">loď</a> '
    .'<a href="https://na-ryby.eu/poradce-vybavy/">poradce</a>.</p>'
    .'</section><h2>Jak vybrat echolot pro rybaření: Kompletní průvodce funkcemi</h2>'
    .str_repeat('<p>Rybářské informace, které se nemění.</p>',20)
    .'<a href="https://www.chytapust.cz/test?k=private">Prověřit nabídku</a>';
$good=$cls::render($original);
check($good!==$original,'one practical handoff inserted');
check(substr_count($good,$cls::MARKER)===1,'single scoped marker');
check(substr_count($good,$cls::PRACTICAL)===1,'exact one intended internal target');
check(strpos($good,'Echolot už máte?')!==false,'reader first intent');
check(strpos($good,'<strong>Echolot už máte?</strong>')>
    strpos($good,'</section>'),'no mutation of existing editor section');
check(strpos($good,'Echolot už máte?')<
    strpos($good,'<h2>Jak vybrat echolot pro rybaření:'),
    'guidance before original body and affiliate merchants');
check(strpos($good,'https://www.chytapust.cz/test?k=private')!==false,
    'all original affiliate links untouched');
check($cls::render($good)===$good,'idempotent');
check($cls::transform($good)===$good,'pure transform idempotent');
check(substr_count($good,'<li>')===4,'four original cases preserved');
foreach(array('id','main','loop','admin','preview','slug','status','modified') as $key){
    $before=$GLOBALS['ctx'][$key];
    if($key==='id')$GLOBALS['ctx'][$key]=118455;
    elseif(in_array($key,array('main','loop'))) $GLOBALS['ctx'][$key]=false;
    elseif(in_array($key,array('admin','preview'))) $GLOBALS['ctx'][$key]=true;
    elseif($key==='slug')$GLOBALS['ctx'][$key]='wrong-article';
    elseif($key==='status')$GLOBALS['ctx'][$key]='draft';
    else $GLOBALS['ctx'][$key]='2026-11-10 12:00:00';
    check($cls::render($original)===$original,'wrong context '.$key.' blocked');
    $GLOBALS['ctx'][$key]=$before;
}
$already=$original.'<p><a href="'.$cls::PRACTICAL.'">editor link</a></p>';
check($cls::render($already)===$already,'existing editor link suppresses duplication');
$changed=str_replace('další nákup není nutný','kupte nový hned',$original);
check($cls::render($changed)===$changed,'changed core reader advice blocks');
$changed=str_replace('aktuální konečnou cenu kompletu','starší cenu',$original);
check($cls::render($changed)===$changed,'changed price guard blocks');
$changed=str_replace('<li>Z člunu</li>','',$original);
check($cls::render($changed)===$changed,'four-case assumption drift blocks');
$changed=str_replace('https://na-ryby.eu/poradce-vybavy/','https://malicious.example/',$original);
check($cls::render($changed)===$changed,'source links drift blocks');
$changed=str_replace('<h2>Jak vybrat echolot pro rybaření:',
   '<h2>Vlastní editorská sekce:', $original);
check($cls::render($changed)===$changed,'next section drift blocks');
check($cls::transform(array())===array(),'wrong type protected');
echo "PASS: exact pillar practical ownership handoff, independent context/DOM guards, zero external links changed\n";
