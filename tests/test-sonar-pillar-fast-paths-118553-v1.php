<?php
/** Exact render-only quick paths: no WordPress writes or changed merchant URLs. */
define('ABSPATH', __DIR__);
$GLOBALS['ctx'] = array('id'=>118553,'main'=>true,'loop'=>true,'admin'=>false,
    'preview'=>false,'status'=>'publish','slug'=>'jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce',
    'modified'=>'2026-11-04 11:27:17');
$GLOBALS['hooks']=array();
function add_filter($name,$callback,$priority=10){$GLOBALS['hooks'][]=array($name,$callback,$priority);}
function is_single($id){return $id===$GLOBALS['ctx']['id'];}
function is_singular($type){return $type==='post';}
function get_queried_object_id(){return $GLOBALS['ctx']['id'];}
function is_main_query(){return $GLOBALS['ctx']['main'];}
function in_the_loop(){return $GLOBALS['ctx']['loop'];}
function is_admin(){return $GLOBALS['ctx']['admin'];}
function is_preview(){return $GLOBALS['ctx']['preview'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return 'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';}
function get_post($id){return (object)array('ID'=>$GLOBALS['ctx']['id'],
    'post_type'=>'post','post_status'=>$GLOBALS['ctx']['status'],
    'post_name'=>$GLOBALS['ctx']['slug'],'post_modified_gmt'=>$GLOBALS['ctx']['modified']);}
function check($ok,$msg){if(!$ok)throw new RuntimeException($msg);}
require dirname(__DIR__).'/sonar-pillar-fast-paths-118553-v1.php';
$cls='NRY_Sonar_Pillar_Fast_Paths_118553_V1';
check(count($GLOBALS['hooks'])===1&&$GLOBALS['hooks'][0][2]===43,'source-bound content hook');
$original='<section '.$cls::SOURCE.'><h2>Jak vybrat vhodný typ echolotu</h2>'
    .'<p>Echolot nezaručuje úlovek a pokud stávající vybavení dostačuje, další nákup není nutný.</p>'
    .'<ul><li>Ze břehu</li><li>Z člunu</li><li>GPS</li><li>SideScan</li></ul>'
    .'<p>Prověřte aktuální konečnou cenu kompletu '
    .'<a href="'.$cls::BUDGET.'">rozpočet</a> '
    .'<a href="'.$cls::BOAT.'">loď</a> '
    .'<a href="'.$cls::ADVISOR.'">poradce</a>.</p></section>'
    .'<h2>Jak vybrat echolot pro rybaření: Kompletní průvodce funkcemi</h2>'
    .str_repeat('<p>Aktuální odborné informace pro rybáře.</p>',20)
    .'<a href="https://www.chytapust.cz/category?opaque_id=secret">merchant</a>';
$result=$cls::render($original);
check($result!==$original,'fast paths should render');
check(substr_count($result,$cls::MARKER)===1,'single identifiable source component');
check(strpos($result,$cls::MARKER)<strpos($result,'<ul>'),'above the four existing scenarios');
check(substr_count($result,'<li>')===4,'original bullets preserved');
check(substr_count($result,$cls::BANK)===1,'new shore route');
foreach(array($cls::BUDGET,$cls::BOAT,$cls::ADVISOR) as $route){
    check(substr_count($result,$route)===2,'retains original target and adds one quick card');
}
check(strpos($result,'https://www.chytapust.cz/category?opaque_id=secret')!==false,
    'merchant link is unchanged');
check($cls::render($result)===$result,'idempotent after publication');
check($cls::transform($result)===$result,'pure transform idempotent');
check($cls::transform(array())===array(),'wrong input type safe');
$linebreakSource = str_replace('</p><ul>','</p>' . "\n" . '<ul>', $original);
$linebreakResult = $cls::render($linebreakSource);
check($linebreakResult !== $linebreakSource, 'wordpress newline between answer and list supported');
check(substr_count($linebreakResult, $cls::MARKER) === 1, 'whitespace path emits exactly one panel');
check($cls::render($linebreakResult) === $linebreakResult, 'whitespace path remains idempotent');
foreach(array('id','main','loop','admin','preview','status','slug','modified') as $key){
    $old=$GLOBALS['ctx'][$key];
    if($key==='id')$GLOBALS['ctx'][$key]=118455;
    elseif($key==='main'||$key==='loop')$GLOBALS['ctx'][$key]=false;
    elseif($key==='admin'||$key==='preview')$GLOBALS['ctx'][$key]=true;
    elseif($key==='status')$GLOBALS['ctx'][$key]='draft';
    elseif($key==='slug')$GLOBALS['ctx'][$key]='unrelated-article';
    else $GLOBALS['ctx'][$key]='2026-11-15 00:00:00';
    check($cls::render($original)===$original,'wrong context blocked: '.$key);
    $GLOBALS['ctx'][$key]=$old;
}
foreach(array(
    str_replace('další nákup není nutný','buy now',$original),
    str_replace('<li>GPS</li>','',$original),
    str_replace($cls::BOAT,'https://other.example/boat/',$original),
    str_replace('aktuální konečnou cenu kompletu','unknown',$original),
    str_replace('</p><ul>','</p><div><ul>',$original),
    str_replace('Jak vybrat vhodný typ echolotu','Unrelated topic',$original)
) as $drift) check($cls::render($drift)===$drift,'unreviewed source change blocked');
echo "PASS: exact sonar four-path quick selector, one safe render, no merchant mutation or writes\n";
