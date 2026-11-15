<?php
/* Offline source guard, idempotence and strictly preserved merchant hrefs. */
define('ABSPATH','/');
$GLOBALS['context']=['id'=>118455,'single'=>true,'main'=>true,'loop'=>true,
    'admin'=>false,'preview'=>false,'type'=>'post','status'=>'publish',
    'slug'=>'nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz',
    'gmt'=>'2026-11-08 10:21:01','raw'=>str_repeat('x',39987)];
$GLOBALS['hooks']=[];
function add_filter($name,$callback,$priority=10){$GLOBALS['hooks'][]=[$name,$callback,$priority];}
function is_single($id){return $GLOBALS['context']['single'] && $id===$GLOBALS['context']['id'];}
function is_singular($type){return $type==='post' && $GLOBALS['context']['type']==='post';}
function get_queried_object_id(){return $GLOBALS['context']['id'];}
function is_main_query(){return $GLOBALS['context']['main'];}
function in_the_loop(){return $GLOBALS['context']['loop'];}
function is_admin(){return $GLOBALS['context']['admin'];}
function is_preview(){return $GLOBALS['context']['preview'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return NRY_Sonar_Budget_Scenario_Jumps_118455_V1::URL;}
function get_post($id){$x=$GLOBALS['context'];return (object)[
    'ID'=>$id,'post_type'=>$x['type'],'post_status'=>$x['status'],
    'post_name'=>$x['slug'],'post_modified_gmt'=>$x['gmt'],
    'post_content'=>$x['raw']];}
function ensure($ok,$name){if(!$ok){fwrite(STDERR,"FAIL $name\n");exit(1);}}
require dirname(__DIR__).'/sonar-budget-scenario-jumps-118455-v1.php';
$class='NRY_Sonar_Budget_Scenario_Jumps_118455_V1';
ensure(count($GLOBALS['hooks'])===1 && $GLOBALS['hooks'][0][2]===65,'scoped after owner');
$owner='<aside '.$class::OWNER_MARKER.' aria-label="Než koupíte">Pozor na sondu</aside>';
$merchant='<a href="https://go.dognet.com/exact?a=1&amp;campaign=2">Zjistit cenu</a>';
$other='<a href="https://www.chytapust.cz/some-product/">Historická nabídka</a>';
$headings='<h3>Lowrance Eagle 4X se sondou Bullet (bonusy ověřte)</h3>'
    .'<p>'.$other.'</p>'
    .'<h3>Lowrance Hook Reveal 5, 50/200 HDI ROW</h3>'
    .'<p>'.$merchant.'</p>'
    .'<h3>Lowrance Eagle 7 se sondou SplitShot HD (balení ověřte)</h3>';
$html=$class::TRUST_MARKER.$owner.'<h2>Úvod</h2>'
    .str_repeat('<p>Praktické porovnání sonarů.</p>',110).$headings;
ensure(strlen($html)>3000,'fixture sufficient');
$out=$class::transform($html);
ensure($out!==$html,'3 shortcuts inserted');
ensure(substr_count($out,$class::MARKER)===1,'one nav');
foreach (['nry-budget-eagle4x','nry-budget-hook5','nry-budget-eagle7'] as $id) {
    ensure(substr_count($out,'href="#'.$id.'"')===1,'unique local link '.$id);
    ensure(substr_count($out,'id="'.$id.'"')===1,'unique target '.$id);
}
ensure(strpos($out,$class::MARKER)<strpos($out,$merchant),'navigation before merchant');
ensure(strpos($out,$other)!==false && strpos($out,$merchant)!==false,'original merchant intact');
ensure($class::transform($out)===$out,'idempotence');
$withoutHeading=str_replace('<h3>Lowrance Hook Reveal 5, 50/200 HDI ROW</h3>',
    '<h3>Editor změnil nabídku Hook Reveal</h3>',$html);
ensure($class::transform($withoutHeading)===$withoutHeading,'editor-change protected');
$duplicated=$html.'<h3>Lowrance Hook Reveal 5, 50/200 HDI ROW</h3>';
ensure($class::transform($duplicated)===$duplicated,'duplicate heading guarded');
$invalidExistingId=str_replace('<h3>Lowrance Eagle 4X',
    '<h3 id="editor-anchor">Lowrance Eagle 4X',$html);
ensure($class::transform($invalidExistingId)===$invalidExistingId,'editor anchor guarded');
$withoutOwner=str_replace($owner,'',$html);
ensure($class::transform($withoutOwner)===$withoutOwner,'owner filter must precede');
$withoutTrust=str_replace($class::TRUST_MARKER,'',$html);
ensure($class::transform($withoutTrust)===$withoutTrust,'trust filter required');
ensure($class::render($html)===$html,'raw WordPress SHA mismatch blocks render');
foreach (['id'=>123,'single'=>false,'main'=>false,'loop'=>false,'admin'=>true,
    'preview'=>true,'type'=>'page','status'=>'draft','slug'=>'changed',
    'gmt'=>'2026-11-11 00:00:00'] as $field=>$value) {
    $old=$GLOBALS['context'][$field];$GLOBALS['context'][$field]=$value;
    ensure($class::render($html)===$html,'scope '.$field);
    $GLOBALS['context'][$field]=$old;
}
echo "PASS three shortcuts, original links, idempotence and exact source guards\n";
