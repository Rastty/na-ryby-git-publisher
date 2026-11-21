<?php
/** Offline exact-post render-only source and editorial trust regression. */
define('ABSPATH', __DIR__);
$GLOBALS['hooks'] = array();
$GLOBALS['ctx'] = array('id'=>56493, 'slug'=>'1-nejlepsi-rybarske-reviry-v-cesku-kam-se-dostanete-autem',
    'modified'=>'2023-06-28 11:29:01', 'status'=>'publish', 'type'=>'post',
    'url'=>'https://na-ryby.eu/1-nejlepsi-rybarske-reviry-v-cesku-kam-se-dostanete-autem/',
    'site'=>'https://na-ryby.eu/', 'single'=>true, 'singular'=>true,
    'main'=>true, 'loop'=>true, 'admin'=>false, 'preview'=>false, 'raw'=>'raw placeholder');
function add_filter($n,$cb,$priority) {$GLOBALS['hooks'][$n]=array($cb,$priority);}
function add_action($n,$cb,$priority) {$GLOBALS['hooks'][$n]=array($cb,$priority);}
function is_single($i) {return $GLOBALS['ctx']['single'] && $i===$GLOBALS['ctx']['id'];}
function is_singular($t) {return $GLOBALS['ctx']['singular'] && $t==='post';}
function get_queried_object_id() {return $GLOBALS['ctx']['id'];}
function is_main_query() {return $GLOBALS['ctx']['main'];}
function in_the_loop() {return $GLOBALS['ctx']['loop'];}
function is_admin() {return $GLOBALS['ctx']['admin'];}
function is_preview() {return $GLOBALS['ctx']['preview'];}
function home_url($u='/') {return $GLOBALS['ctx']['site'];}
function get_permalink($id) {return $GLOBALS['ctx']['url'];}
function get_post($id) {return (object)array(
    'ID'=>$GLOBALS['ctx']['id'], 'post_type'=>$GLOBALS['ctx']['type'],
    'post_status'=>$GLOBALS['ctx']['status'], 'post_name'=>$GLOBALS['ctx']['slug'],
    'post_modified_gmt'=>$GLOBALS['ctx']['modified'],
    'post_content'=>$GLOBALS['ctx']['raw']);}
function plugins_url($path,$base) {return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_style($handle,$url,$deps,$ver) {
    $GLOBALS['styles'][] = compact('handle','url','deps','ver');
}
function chk($x,$s) {if (!$x) throw new RuntimeException($s);}
require dirname(__DIR__).'/reviry-autem-trust-56493-v1.php';
$c='NRY_Reviry_Autem_Trust_56493_V1';
chk($GLOBALS['hooks']['the_content']===array($c.'::render',PHP_INT_MAX),'source render hook');
chk($GLOBALS['hooks']['wp_enqueue_scripts']===array(array($c,'enqueue'),28),'css source hook');
$before='<h1>Nejlepší rybářské revíry v Česku</h1>'
    .'<h2>Kam se dostanete autem</h2><p>'.str_repeat('Rybářské výlety. ', 140).'</p>'
    .'<h3>'.$c::OLD_PODYJI.'</h3><p>Historický průvodce.</p>'
    .'<h3>'.$c::OLD_SUMAVA.'</h3><p>Historický průvodce.</p>'
    .'<h3>Rybářský revír Orlická přehrada</h3><p>'.$c::BAD_ORLIK.'</p>'
    .'<a href="https://www.podyji.cz/">Původní oficiální odkaz</a>';
$after=$c::transform($before);
chk($after!==$before,'must produce scoped render change');
chk(substr_count($after,$c::MARKER)===1,'one inline panel');
chk(strpos($after,'<h2>Kam se dostanete autem</h2>') <
    strpos($after,$c::MARKER),'panel after existing heading');
chk(strpos($after,$c::MARKER)<strpos($after,'Historický průvodce.'),'panel early');
chk(strpos($after,$c::BAD_ORLIK)===false && substr_count($after,$c::GOOD_ORLIK)===1,'geography corrected');
chk(strpos($after,$c::OLD_PODYJI)===false && strpos($after,$c::OLD_SUMAVA)===false,'park headings corrected');
foreach (array($c::LIPNO,$c::ORLIK,$c::DYJE,$c::RULES) as $url) {
    chk(substr_count($after,$url)===1,'one genuine internal guide link');
}
chk(strpos($after,'https://www.podyji.cz/')!==false,'historical links preserved');
chk($c::transform($after)===$after,'idempotence');
foreach (array(
    str_replace($c::BAD_ORLIK,'old editor text',$before),
    str_replace($c::OLD_PODYJI,'other park',$before),
    str_replace('<h2>Kam se dostanete autem</h2>','<h2>Different heading</h2>',$before),
    $before.'<a href="'.$c::LIPNO.'">new editor link</a>',
) as $drift) chk($c::transform($drift)===$drift,'must fail closed');
chk($c::render($before)===$before,'no raw source SHA match, must not render');
$GLOBALS['styles']=array();
$c::enqueue();
chk($GLOBALS['styles']===array(),'no CSS if source identity not pinned');
foreach (array('id'=>56494,'slug'=>'other','status'=>'draft','modified'=>'2023-06-28 11:29:02',
    'site'=>'https://other.test/','admin'=>true,'preview'=>true,'single'=>false,'singular'=>false) as $key=>$bad) {
    $old=$GLOBALS['ctx'][$key];
    $GLOBALS['ctx'][$key]=$bad;
    chk($c::render($before)===$before,'other context must not be edited');
    $GLOBALS['ctx'][$key]=$old;
}
echo "REVIRY_AUTEM_56493_RENDER_ONLY_CONTRACT_OK\n";
