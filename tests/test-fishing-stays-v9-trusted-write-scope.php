<?php
/**
 * Trusted native writer is reused only after v9 source/HTML validation.
 * Prove temporary KSES detachment is scoped to one write and restored even
 * on an exception. No WordPress site, post or public affiliate link is used.
 */
define('ABSPATH', __DIR__);
class WP_Error { public function __construct($code, $message = '') {} }
function is_wp_error($v) { return $v instanceof WP_Error; }
function add_action($hook,$callback,$priority=10) { return true; }
function wp_kses($html,$allowed,$protocols) { return $html; }
$filters=array(
    'content_save_pre|wp_filter_post_kses'=>10,
    'content_save_pre|wp_filter_global_styles_post'=>20,
    'content_filtered_save_pre|wp_filter_post_kses'=>11,
    'content_filtered_save_pre|wp_filter_global_styles_post'=>21,
    'content_save_pre|custom_safety_filter'=>15,
);
$observed=array(); $throwWrite=false;
function has_filter($hook,$callback) {
    global $filters;
    return $filters[$hook.'|'.$callback]??false;
}
function remove_filter($hook,$callback,$priority=10) {
    global $filters;
    unset($filters[$hook.'|'.$callback]);
    return true;
}
function add_filter($hook,$callback,$priority=10) {
    global $filters;
    $filters[$hook.'|'.$callback]=$priority;
    return true;
}
function wp_slash($raw) { return 'WP_SLASHED:'.$raw; }
function wp_update_post($data,$returnError=false) {
    global $filters,$observed,$throwWrite;
    $observed[]=array('data'=>$data,'filters'=>$filters);
    if ($throwWrite) throw new RuntimeException('synthetic_failure');
    return $data['ID'];
}
function must($ok,$message) { if (!$ok) throw new RuntimeException($message); }
require dirname(__DIR__) . '/na-ryby-git-publisher.php';

$before=$filters;
$writer=new ReflectionMethod('NRY_Git_Publisher','verified_local_write');
$writer->setAccessible(true);
$html='<p>synthetic legacy markup, not a real page</p>';
$id=$writer->invoke(null,59891,$html,true);
must($id===59891,'trusted writer did not return exact ID');
must(count($observed)===1,'expected only one mock update');
must($observed[0]['data']['post_content']==='WP_SLASHED:'.$html,
    'trusted writer did not slash expected post bytes');
must(count($observed[0]['filters'])===1
    && ($observed[0]['filters']['content_save_pre|custom_safety_filter']??null)===15,
    'trusted writer detached unrelated filters or left KSES on during exact write');
must($filters==$before,'trusted writer did not restore original priorities');

$writer->invoke(null,59891,$html,false);
must($observed[1]['data']['post_content']===$html,
    'untrusted path unexpectedly gained trusted write semantics');
must($observed[1]['filters']==$before && $filters==$before,
    'untrusted path mutated filters');

$throwWrite=true;
try {
    $writer->invoke(null,59891,$html,true);
    throw new RuntimeException('mock write did not throw');
} catch (RuntimeException $error) {
    must($error->getMessage()==='synthetic_failure','wrong failure propagated');
}
must($filters==$before,'KSES filters not restored on write exception');
echo "ok: exact trusted writer detaches only named KSES filters, slashes, restores in finally\n";
