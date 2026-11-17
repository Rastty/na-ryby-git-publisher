<?php
// WordPress-free contract for the published signal dashboard. No network or writes.
define('ABSPATH', __DIR__);
$GLOBALS['mode'] = array('page'=>true, 'singular'=>true, 'admin'=>false,
    'main'=>true, 'loop'=>true, 'slug'=>'signalizatory-dashboard',
    'status'=>'publish', 'id'=>118209, 'url'=>'https://na-ryby.eu/signalizatory-dashboard/');
function add_filter($name,$cb,$priority=10) { $GLOBALS['hooks'][$name] = $cb; }
function add_action($name,$cb,$priority=10) { $GLOBALS['actions'][$name] = $cb; }
function is_page($id) { return $GLOBALS['mode']['page'] && $id === 118209; }
function is_singular($type) { return $GLOBALS['mode']['singular'] && $type === 'page'; }
function get_queried_object_id() { return $GLOBALS['mode']['id']; }
function is_admin() { return $GLOBALS['mode']['admin']; }
function is_main_query() { return $GLOBALS['mode']['main']; }
function in_the_loop() { return $GLOBALS['mode']['loop']; }
function home_url($x='/') { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return $GLOBALS['mode']['url']; }
function get_post($id) { return (object)array('ID'=>$id, 'post_type'=>'page',
    'post_status'=>$GLOBALS['mode']['status'], 'post_name'=>$GLOBALS['mode']['slug']); }
function esc_url($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function esc_html($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function wp_enqueue_style($handle,$url,$deps,$version) { $GLOBALS['style']=compact('handle','url','deps','version'); }
function plugins_url($path,$file) { return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path; }
function check($bool,$what) { if (!$bool) throw new RuntimeException($what); echo "PASS $what\n"; }
require __DIR__.'/../signal-dashboard-paths-118209-v1.php';

$deadA='https://na-ryby.eu/signalizator-zaberov-do-100-eur-najlepsie-modely/';
$deadB='https://na-ryby.eu/sada-signalizatorov-do-150-eur-co-kupit-a-comu-sa-vyhnut/';
$deadC='https://na-ryby.eu/signalizatory-zaberov-kompletny-sprievodca-vyberom/';
$original='<section id="kam-dal"><div><h2>Rychlý rozcestník</h2></div>'
    .'<h3>Jsem začátečník</h3><p>První pípák.</p>'
    .'<h3>Řeším nižší rozpočet</h3><p>Levnější sada.</p>'
    .'<h3>Chci nejlepší poměr cena / výkon</h3><p>Rozumný kompromis.</p>'
    .'<h3>Nevím, jestli 2+1 nebo 3+1</h3><p>Více prutů.</p></section>'
    .'<h2 id="srovnani">Který směr dává smysl</h2>'
    .'<h2 id="top-modely">Konkrétní doporučení</h2>'
    .'<p><a href="'.$deadA.'">Projít levnější modely</a></p>'
    .'<p><a href="'.$deadB.'">Projít doporučené sety</a></p>'
    .'<p><a href="'.$deadA.'">Levnější modely</a></p>'
    .'<p><a href="'.$deadC.'">Kompletní průvodce</a></p>'
    .str_repeat('<p>Bezpečný užitečný obsah.</p>',30);
$fixed=NRY_Signal_Dashboard_Paths_118209_V1::render($original);
check($fixed!==$original, 'repairs four dead anchors');
check(strpos($fixed, $deadA)===false && strpos($fixed, $deadB)===false
    && strpos($fixed, $deadC)===false, 'all original 404 links gone');
check(substr_count($fixed, 'href="#top-modely"')===3,
    'both budget CTAs and new budget decision point to existing models');
check(substr_count($fixed, 'href="#srovnani"')===2,
    'comparison CTA and new decision point to existing table');
check(substr_count($fixed, 'href="'.NRY_Signal_Dashboard_Paths_118209_V1::PILLAR.'"')===1,
    'complete guide points to verified Czech pillar');
check(substr_count($fixed, NRY_Signal_Dashboard_Paths_118209_V1::MARKER)===1,
    'exactly one four-intent decision section');
check(strpos($fixed, '<h3>Jsem začátečník</h3>')!==false,
    'editorial content retained');
check(NRY_Signal_Dashboard_Paths_118209_V1::render($fixed)===$fixed,
    'read-only render is idempotent');
foreach (array('page'=>false, 'singular'=>false, 'admin'=>true, 'main'=>false,
               'loop'=>false, 'slug'=>'other', 'status'=>'draft', 'id'=>42,
               'url'=>'https://na-ryby.eu/elsewhere/') as $k=>$wrong) {
    $before=$GLOBALS['mode'][$k]; $GLOBALS['mode'][$k]=$wrong;
    check(NRY_Signal_Dashboard_Paths_118209_V1::render($original)===$original,
        'rejects mismatched '.$k);
    $GLOBALS['mode'][$k]=$before;
}
$editorLinked=str_replace('Jsem začátečník</h3>',
    '<a href="https://na-ryby.eu/editor/">Jsem začátečník</a></h3>', $original);
check(NRY_Signal_Dashboard_Paths_118209_V1::render($editorLinked)===$editorLinked,
    'existing situation link prevents overlay');
$drift=str_replace('href="'.$deadB.'"', 'href="https://na-ryby.eu/editor/"', $original);
check(NRY_Signal_Dashboard_Paths_118209_V1::render($drift)===$drift,
    'one changed old URL prevents partial repair');
$drift=str_replace('id="srovnani"', 'id="editor-table"', $original);
check(NRY_Signal_Dashboard_Paths_118209_V1::render($drift)===$drift,
    'missing anchor target prevents broken in-page links');
NRY_Signal_Dashboard_Paths_118209_V1::enqueue();
check(($GLOBALS['style']['version']??'')==='1.0.0', 'scoped stylesheet enqueued');
$GLOBALS['mode']['id']=999;
unset($GLOBALS['style']);
NRY_Signal_Dashboard_Paths_118209_V1::enqueue();
check(!isset($GLOBALS['style']), 'no stylesheet on other page');
echo "PASS no database, affiliate or external network writes\n";
