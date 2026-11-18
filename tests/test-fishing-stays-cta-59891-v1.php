<?php
/** Offline style-only contract for an already published fixed stay cohort. */
define('ABSPATH', __DIR__);
$GLOBALS['stay_id'] = 59891;
$GLOBALS['stay_url'] = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
$GLOBALS['stay_styles'] = array();
$source = file_get_contents(__DIR__.'/../editorial/fishing-stays-cz-20261118-recommendation-blocks.html');
preg_match('~<section data-nry-editorial-edit="fishing-stays-cz-59891-20261118-v1"[\s\S]*?</section>~', $source, $m);
if (!isset($m[0])) throw new RuntimeException('frozen reviewed v9 offer block missing');
$GLOBALS['stay_post'] = (object) array(
    'ID'=>59891, 'post_type'=>'post', 'post_status'=>'publish',
    'post_name'=>'rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare',
    'post_content'=>$m[0].'\nOriginal article remains unchanged.'
);
function add_action($name, $callback, $priority) { $GLOBALS['stay_hooks'][] = compact('name','callback','priority'); }
function is_single($id) { return $id === $GLOBALS['stay_id']; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return $GLOBALS['stay_id']; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return $GLOBALS['stay_url']; }
function get_post($id) { return $GLOBALS['stay_post']; }
function plugins_url($path, $file) { return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path; }
function wp_enqueue_style($handle, $src, $deps, $version) {
    $GLOBALS['stay_styles'][] = compact('handle', 'src', 'deps', 'version');
}
function check($ok, $msg) { if (!$ok) throw new RuntimeException($msg); }
require __DIR__.'/../buyer-click-telemetry-v1.php';
check($GLOBALS['stay_hooks'][0]['name']==='wp_enqueue_scripts' && $GLOBALS['stay_hooks'][0]['priority']===30,
    'one late scoped enqueue');
$class = 'NRY_Fishing_Stays_CTA_59891_V1';
check($class::eligible(), 'approved existing v9 block qualifies');
$class::enqueue();
check(count($GLOBALS['stay_styles'])===1, 'exactly one style');
check($GLOBALS['stay_styles'][0]['handle']==='nry-fishing-stays-cta-59891-v1', 'scoped handle');
check($GLOBALS['stay_styles'][0]['version']==='1.0.0', 'versioned browser cache');
check(strpos($GLOBALS['stay_styles'][0]['src'], '/assets/nry-fishing-stays-cta-59891-v1.css')!==false,
    'site-local stylesheet only');

foreach (array('draft_status','wrong_page','different_slug','removed_offer','duplicate_marker',
    'altered_affiliate','changed_destination') as $scenario) {
    $GLOBALS['stay_id']=59891;
    $GLOBALS['stay_url']=$class::URL;
    $GLOBALS['stay_post']->post_status='publish';
    $GLOBALS['stay_post']->post_name=$class::SLUG;
    $GLOBALS['stay_post']->post_content=$m[0];
    if ($scenario==='draft_status') $GLOBALS['stay_post']->post_status='draft';
    if ($scenario==='wrong_page') $GLOBALS['stay_id']=123;
    if ($scenario==='different_slug') $GLOBALS['stay_post']->post_name='unreviewed';
    if ($scenario==='removed_offer') $GLOBALS['stay_post']->post_content=str_replace(
        'data-nry-offer-id="2462030"', 'data-nry-offer-id="unapproved"', $m[0]);
    if ($scenario==='duplicate_marker') $GLOBALS['stay_post']->post_content=$m[0].$m[0];
    if ($scenario==='altered_affiliate') $GLOBALS['stay_post']->post_content=str_replace(
        'chid=5QoXiSqx', 'chid=unreviewed', $m[0]);
    if ($scenario==='changed_destination') $GLOBALS['stay_post']->post_content=str_replace(
        'pension-u-vlcku%2F', 'pension-unreviewed%2F', $m[0]);
    $GLOBALS['stay_styles']=array();
    check(!$class::eligible(), 'blocked scenario: '.$scenario);
    $class::enqueue();
    check(count($GLOBALS['stay_styles'])===0, 'no CSS when source guard fails: '.$scenario);
}
$GLOBALS['stay_id']=59891;
$GLOBALS['stay_url']=$class::URL;
$GLOBALS['stay_post']->post_status='publish';
$GLOBALS['stay_post']->post_name=$class::SLUG;
$GLOBALS['stay_post']->post_content=str_replace('Vybrali jsme pobyt',
    'Právě doporučujeme pobyt', $m[0]);
check($class::eligible(), 'unrelated editorial wording changes do not break exact affiliate cards');
echo "PASS: published five-offer block gets scoped visible CTAs; drift fails closed\n";
