<?php
// Offline security and behavior contract. No live WordPress calls.
define('ABSPATH', __DIR__);
$GLOBALS['mode'] = array('page'=>true,'admin'=>false,'loop'=>true,'main'=>true,'slug'=>'navijaky-dashboard','status'=>'publish');
$GLOBALS['hook_name'] = null;
function add_filter($name,$cb,$priority=10){$GLOBALS['hook_name']=$name;}
function is_page($id){return $GLOBALS['mode']['page'] && $id===118015;}
function is_admin(){return $GLOBALS['mode']['admin'];}
function is_main_query(){return $GLOBALS['mode']['main'];}
function in_the_loop(){return $GLOBALS['mode']['loop'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return 'https://na-ryby.eu/navijaky-dashboard/';}
function get_post($id){return (object)array('ID'=>118015,'post_type'=>'page','post_status'=>$GLOBALS['mode']['status'],'post_name'=>$GLOBALS['mode']['slug']);}
function esc_url($value){return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
function esc_html($value){return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
function ok($truth,$message){if(!$truth)throw new RuntimeException($message); echo "PASS: $message\n";}
require __DIR__.'/../reel-dashboard-nextsteps-v1.php';
ok($GLOBALS['hook_name']==='the_content','safe render hook registered');
$original='<h2>Pokračuj dál</h2><h3>Jak vybrat naviják</h3><p>Kompletní průvodce.</p>'
    .'<h3>Nejlepší přívlačové navijáky</h3><p>Modely pro vláčení.</p>'
    .'<h3>Nejlepší feederové navijáky</h3><p>Feeder a proč.</p>'
    .'<h3>Údržba a servis navijáku</h3><p>Údržba.</p>'
    .'<h2>Často kladené otázky</h2>'.str_repeat('<p>Rady pro rybáře.</p>',20);
$updated=NRY_Reel_Dashboard_Nextsteps_V1::render($original);
ok($updated!==$original,'missing links get real navigation');
ok(substr_count($updated,NRY_Reel_Dashboard_Nextsteps_V1::MARKER)===1,'exactly one navigation marker');
ok(substr_count($updated,'<li><a href="https://na-ryby.eu/')===4,'exactly four first-party anchors');
ok(strpos($updated,'<h2>Často kladené otázky</h2>')!==false,'FAQ retained');
ok(strpos($updated,'<h3>Nejlepší feederové navijáky</h3>')!==false,'original buyer content retained');
ok(NRY_Reel_Dashboard_Nextsteps_V1::render($updated)===$updated,'idempotent repeat rendering');
foreach (array('page','admin','loop','main','slug','status') as $key) {
    $old=$GLOBALS['mode'][$key];
    $GLOBALS['mode'][$key] = $key==='page'||$key==='loop'||$key==='main'?false:($key==='admin'?true:($key==='slug'?'different-page':'draft'));
    ok(NRY_Reel_Dashboard_Nextsteps_V1::render($original)===$original,'wrong context '.$key.' stays unchanged');
    $GLOBALS['mode'][$key]=$old;
}
$with_link=str_replace('<h3>Jak vybrat naviják</h3>',
    '<h3><a href="https://na-ryby.eu/jak-vybrat-navijak/">Jak vybrat naviják</a></h3>',$original);
ok(NRY_Reel_Dashboard_Nextsteps_V1::render($with_link)===$with_link,'editor links suppress overlay');
$drift=str_replace('Údržba a servis navijáku','Opravujeme navijáky',$original);
ok(NRY_Reel_Dashboard_Nextsteps_V1::render($drift)===$drift,'source drift suppresses overlay');
echo "PASS: no database, click or network writer exists in the render-only module\n";


// The real dashboard has a second, previously linkless "choose by situation"
// section before the original "Pokračuj dál" section. This is render-only.
$situations='<h2>Vyber podle situace</h2>'
    .'<h3>Jsem začátečník</h3><p>První naviják.</p>'
    .'<h3>Chytám přívlačí</h3><p>Aktivní lov.</p>'
    .'<h3>Chci naviják na feeder</h3><p>Feeder.</p>'
    .'<h3>Řeším kaprařinu</h3><p>Long cast.</p>'
    .'<h2>Rychlé srovnání</h2>';
$both=NRY_Reel_Dashboard_Nextsteps_V1::render($situations.$original);
ok(substr_count($both,NRY_Reel_Dashboard_Nextsteps_V1::SITUATIONS_MARKER)===1,
 'single four-intent situation nav');
ok(substr_count($both,'<li><a href="https://na-ryby.eu/')===8,
 'original four nav links plus four decision links');
ok(strpos($both,'nejlepsi-privlacove-navijaky-jak-vybrat/')!==false,
 'spinning reel decision path matches live canonical');
ok(strpos($both,'nejlepsi-kaprove-navijaky/')!==false,
 'carp reel decision path matches live canonical');
ok(NRY_Reel_Dashboard_Nextsteps_V1::render($both)===$both,
 'both navs are idempotent');
$linkedSituation=str_replace('<h3>Chytám přívlačí</h3>',
 '<h3><a href="https://na-ryby.eu/privlac/">Chytám přívlačí</a></h3>',
 $situations);
$editorWins=NRY_Reel_Dashboard_Nextsteps_V1::render($linkedSituation.$original);
ok(strpos($editorWins,NRY_Reel_Dashboard_Nextsteps_V1::SITUATIONS_MARKER)===false,
 'existing editor link suppresses situation nav');
ok(strpos($editorWins,NRY_Reel_Dashboard_Nextsteps_V1::MARKER)!==false,
 'editorial situation link does not break original bottom nav');
$editedSituation=str_replace('Řeším kaprařinu','Jdu na sumce',$situations);
$drifted=NRY_Reel_Dashboard_Nextsteps_V1::render($editedSituation.$original);
ok(strpos($drifted,NRY_Reel_Dashboard_Nextsteps_V1::SITUATIONS_MARKER)===false,
 'changed situation intent suppresses new nav');
ok(strpos($drifted,NRY_Reel_Dashboard_Nextsteps_V1::MARKER)!==false,
 'source drift leaves old nav intact');
$notThisPage=NRY_Reel_Dashboard_Nextsteps_V1::render($original);
ok(strpos($notThisPage,NRY_Reel_Dashboard_Nextsteps_V1::SITUATIONS_MARKER)===false,
 'unrelated page source never receives situation links');
