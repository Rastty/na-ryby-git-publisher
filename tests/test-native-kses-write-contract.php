<?php
// Simulate anonymous WordPress wp_update_post: slashed arrays, KSES on save.
// This is offline-only; no WordPress or production network access.
define('ABSPATH',__DIR__);
$GLOBALS['hooks']=array('content_save_pre'=>array(
    'wp_filter_post_kses'=>10,'wp_filter_global_styles_post'=>9),
    'content_filtered_save_pre'=>array('wp_filter_post_kses'=>10));
$GLOBALS['stored']='';
$GLOBALS['writes']=0;
$GLOBALS['throw_on_write']=false;
function add_action($name,$cb,$priority=10) { return true; }
function has_filter($name,$cb) { return $GLOBALS['hooks'][$name][$cb]??false; }
function remove_filter($name,$cb,$priority=10) {
    unset($GLOBALS['hooks'][$name][$cb]); return true;
}
function add_filter($name,$cb,$priority=10) {
    $GLOBALS['hooks'][$name][$cb]=$priority; return true;
}
function wp_slash($value) { return addslashes($value); }
function wp_update_post($data,$err=false) {
    if ($GLOBALS['throw_on_write']) throw new RuntimeException('test_exception');
    $GLOBALS['writes']++;
    $content=stripslashes($data['post_content']);
    if (has_filter('content_save_pre','wp_filter_post_kses')!==false) {
        $content=preg_replace('~<script\b[^>]*>[\s\S]*?</script>~i','',$content);
    }
    $GLOBALS['stored']=$content;
    return (int)$data['ID'];
}
function ok($cond,$msg) {
    if (!$cond) { fwrite(STDERR,"FAIL $msg\n"); exit(1); }
}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$method=new ReflectionMethod('NRY_Git_Publisher','verified_local_write');
$method->setAccessible(true);
$source='<section data-edit="safe"><script data-original="yes">const path="a\\b";</script><p title="x">Živé výstupy</p></section>';
$before=$GLOBALS['hooks'];
$r=$method->invoke(null,119865,$source,true);
ok($r===119865,'valid exact helper update');
ok($GLOBALS['stored']===$source,'source scripts and backslashes preserved exactly in anonymous context');
ok($GLOBALS['hooks']===$before,'WordPress content filters restored after write');
ok($GLOBALS['writes']===1,'exactly one write');
$method->invoke(null,119865,$source,false);
ok($GLOBALS['stored']!==$source,'untrusted legacy path cannot bypass WordPress KSES');
ok($GLOBALS['hooks']===$before,'legacy path does not touch hooks');
$GLOBALS['throw_on_write']=true;
try { $method->invoke(null,119865,$source,true); ok(false,'exception must propagate'); }
catch (RuntimeException $e) { ok($e->getMessage()==='test_exception','expected exception'); }
ok($GLOBALS['hooks']===$before,'KSES hooks restored after unexpected write exception');
echo "PASS: bounded source-safe writes survive anonymous WP KSES and slash normalization; hooks restored on all paths\n";
