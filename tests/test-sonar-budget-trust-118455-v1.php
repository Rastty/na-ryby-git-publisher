<?php
/* Standalone contracts for the exact render-only sonar editorial repair. */
define('ABSPATH', '/');
$GLOBALS['ctx'] = array('id'=>118455, 'admin'=>false, 'preview'=>false,
    'main'=>true, 'loop'=>true, 'single'=>true, 'post_type'=>'post',
    'status'=>'publish', 'slug'=>'nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz');
$GLOBALS['filters'] = array();
function add_filter($hook, $callback, $priority=10) { $GLOBALS['filters'][$hook][]=$callback; }
function is_single($id=null) { return $GLOBALS['ctx']['single'] && ($id===null || $id===$GLOBALS['ctx']['id']); }
function is_singular($type='') { return $type==='post' && $GLOBALS['ctx']['post_type']==='post'; }
function get_queried_object_id() { return $GLOBALS['ctx']['id']; }
function is_main_query() { return $GLOBALS['ctx']['main']; }
function in_the_loop() { return $GLOBALS['ctx']['loop']; }
function is_admin() { return $GLOBALS['ctx']['admin']; }
function is_preview() { return $GLOBALS['ctx']['preview']; }
function home_url($path='/') { return 'https://na-ryby.eu/'; }
function get_permalink($id) {
    return $id===118455 ? 'https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/' : '';
}
function get_post($id) {
    return (object)array('ID'=>$id,'post_type'=>$GLOBALS['ctx']['post_type'],
        'post_status'=>$GLOBALS['ctx']['status'],'post_name'=>$GLOBALS['ctx']['slug']);
}
require dirname(__DIR__).'/sonar-budget-trust-118455-v1.php';
function check($ok,$label) { if (!$ok) { fwrite(STDERR,"FAIL: $label\n"); exit(1); } }
check(count($GLOBALS['filters']['the_content']??array())===1,'hook registered once');
$reflection=new ReflectionMethod('NRY_Sonar_Budget_Trust_118455_V1','corrections');
$pairs=$reflection->invoke(null);
check(count($pairs)===6,'six bounded corrections');
$href='<a href="https://go.dognet.com/unchanged-example?a=1&amp;b=2">Zjistit cenu</a>';
$source='<h2>Echolot</h2><p>'.implode('</p><p>',array_keys($pairs)).'</p><p>'.$href.'</p>';
check(strlen($source)>300,'valid fixture');
$render='NRY_Sonar_Budget_Trust_118455_V1';
$result=$render::render($source);
check($result!==$source,'real buyer page repaired');
check(strpos($result,$render::MARKER)===0,'public readback marker');
foreach($pairs as $before=>$after) {
    check(strpos($result,$before)===false,'old claim removed');
    check(substr_count($result,$after)===1,'exact neutral correction inserted');
}
check(strpos($result,$href)!==false,'affiliate href byte-preserved');
check($render::render($result)===$result,'idempotent double filter');

$keys=array_keys($pairs);
$values=array_values($pairs);
// Both failing public variants: styling can split an exact old phrase into
// multiple DOM text nodes; HTML entities can break an exact UTF-8 replacement.
$formatted3=str_replace('lepší aktuální nabídku',
    '<strong>lepší aktuální nabídku</strong>', $keys[2]);
$formatted5=str_replace('Chyť a pusť tu má rozumnou aktuální nabídku',
    'Chyť a pusť tu má <em>rozumnou aktuální nabídku</em>', $keys[4]);
$fixture='<p class="intro">'.$formatted3.'</p>'
    .'<p>'.str_replace('za displej.', 'za&nbsp;displej.', $formatted5).'</p>'
    .'<p>'.$href.'</p><p>Other content held unchanged.</p>';
check(strlen($fixture)>300,'formatted fixture long enough');
$fixed=$render::render($fixture);
check(substr_count($fixed, $values[2])===1,'formatted claim 3 corrected');
check(substr_count($fixed, $values[4])===1,'formatted claim 5 corrected');
check(strpos($fixed,$href)!==false,'formatted fixture affiliate unchanged');
check(strpos($fixed,'Other content held unchanged.')!==false,'other paragraph untouched');
check($render::render($fixed)===$fixed,'formatted page idempotent');
$hostile='<p>'.$formatted3.' <a href="https://outside.example/">external</a></p>'
    .'<p>'.$formatted5.' <a href="https://outside.example/">external</a></p>';
check($render::render($hostile)===$hostile,
    'protected paragraphs with editor-supplied links fail closed');
$duplicate='<p>'.$formatted3.'</p><p>'.$formatted3.'</p>';
check($render::render($duplicate)===$duplicate,
    'two matching formatted paragraphs are ambiguous and untouched');

$other=$source;
$GLOBALS['ctx']['id']=118456;
check($render::render($other)===$other,'wrong ID untouched');
$GLOBALS['ctx']['id']=118455;
$GLOBALS['ctx']['status']='draft';
check($render::render($other)===$other,'draft untouched');
$GLOBALS['ctx']['status']='publish';
$GLOBALS['ctx']['slug']='unexpected-article';
check($render::render($other)===$other,'slug drift untouched');
$GLOBALS['ctx']['slug']='nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz';
foreach(array('admin','preview') as $flag) {
    $GLOBALS['ctx'][$flag]=true;
    check($render::render($other)===$other,$flag.' untouched');
    $GLOBALS['ctx'][$flag]=false;
}
$GLOBALS['ctx']['main']=false;
check($render::render($other)===$other,'secondary query untouched');
$GLOBALS['ctx']['main']=true;
$GLOBALS['ctx']['loop']=false;
check($render::render($other)===$other,'outside loop untouched');
$GLOBALS['ctx']['loop']=true;
// Editorial overwrite must win for that single span, without suppressing
// independent, evidence-grounded corrections.
$edited=str_replace(array_keys($pairs)[0],'Nové redakční znění.', $source);
$part=$render::render($edited);
check(strpos($part,'Nové redakční znění.')!==false,'editor precedence');
check(strpos($part,array_values($pairs)[0])===false,'no invented overwrite');
// Duplicate phrase must never be replaced ambiguously.
$duplicate=$source.'<p>'.array_keys($pairs)[1].'</p>';
$dup=$render::render($duplicate);
check(substr_count($dup,array_keys($pairs)[1])===2,'ambiguous span untouched');
check(strpos($dup,$href)!==false,'no affiliate rewrite on duplicate');
echo "OK sonar-budget-render-only-contract: six fixes, identities, editor precedence, idempotence, href unchanged\n";
