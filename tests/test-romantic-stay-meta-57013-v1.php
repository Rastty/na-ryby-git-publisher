<?php
/* Offline exact-page regression; all hooks are read-only render filters. */
define('ABSPATH', '/');
$GLOBALS['ctx'] = array(
    'id'=>57013, 'slug'=>'romanticky-pobyt-pro-dva-s-rybarenim-nezapomenutelne-chvile-plne-lasky-a-rybolovu',
    'url'=>'https://na-ryby.eu/romanticky-pobyt-pro-dva-s-rybarenim-nezapomenutelne-chvile-plne-lasky-a-rybolovu/',
    'site'=>'https://na-ryby.eu/', 'type'=>'post', 'status'=>'publish',
    'modified'=>'2026-04-19 12:01:55', 'singular'=>true, 'admin'=>false, 'preview'=>false,
);
$GLOBALS['filters'] = array();
function add_filter($name,$callback,$priority=10) {$GLOBALS['filters'][$name]=array($callback,$priority);}
function is_singular($type) {return $GLOBALS['ctx']['singular'] && $type==='post';}
function is_admin() {return $GLOBALS['ctx']['admin'];}
function is_preview() {return $GLOBALS['ctx']['preview'];}
function get_queried_object_id() {return $GLOBALS['ctx']['id'];}
function home_url($path='/') {return $GLOBALS['ctx']['site'];}
function get_permalink($id) {return $GLOBALS['ctx']['url'];}
function get_post($id) {return (object)array(
    'ID'=>$GLOBALS['ctx']['id'], 'post_type'=>$GLOBALS['ctx']['type'],
    'post_status'=>$GLOBALS['ctx']['status'], 'post_name'=>$GLOBALS['ctx']['slug'],
    'post_modified_gmt'=>$GLOBALS['ctx']['modified'],
);}
function check_romantic($condition,$message) {if (!$condition) {fwrite(STDERR,"FAIL: $message\n");exit(1);}}

require dirname(__DIR__).'/romantic-stay-meta-57013-v1.php';
$c='NRY_Romantic_Stay_Meta_57013_V1';
check_romantic(array_keys($GLOBALS['filters'])===array('wpseo_metadesc','wpseo_opengraph_desc'),
    'exactly two Yoast description hooks');
check_romantic(strlen($c::NEW_DESCRIPTION)>110 && strlen($c::NEW_DESCRIPTION)<175,
    'bounded meta description');
check_romantic($c::description($c::ORIGINAL_DESCRIPTION)===$c::NEW_DESCRIPTION,
    'only precise published baseline is replaced');
foreach (array('', 'New editor-written description', 'Romantický pobyt - mírně pozměněný text') as $other) {
    check_romantic($c::description($other)===$other, 'current editor description preserved');
}
check_romantic($c::description(null)===null, 'non-string metadata preserved');
foreach (array(
    'id'=>57014, 'slug'=>'other-slug', 'url'=>'https://na-ryby.eu/other/',
    'site'=>'https://another-domain.test/', 'type'=>'page', 'status'=>'draft',
    'modified'=>'2026-04-19 12:01:56', 'singular'=>false,
    'admin'=>true, 'preview'=>true,
) as $field=>$changed) {
    $before=$GLOBALS['ctx'][$field];
    $GLOBALS['ctx'][$field]=$changed;
    check_romantic($c::description($c::ORIGINAL_DESCRIPTION)===$c::ORIGINAL_DESCRIPTION,
        'fail closed when '.$field.' differs');
    $GLOBALS['ctx'][$field]=$before;
}
echo "PASS: romantic meta only on exact post, same baseline, safe read-only hooks\n";
