<?php
/* Exact render-only romantic-stay affiliate offer regression (no WP writes). */
define('ABSPATH', '/');
$GLOBALS['ctx'] = array('id'=>57013, 'slug'=>'romanticky-pobyt-pro-dva-s-rybarenim-nezapomenutelne-chvile-plne-lasky-a-rybolovu',
    'url'=>'https://na-ryby.eu/romanticky-pobyt-pro-dva-s-rybarenim-nezapomenutelne-chvile-plne-lasky-a-rybolovu/',
    'site'=>'https://na-ryby.eu/', 'modified'=>'2026-04-19 12:01:55',
    'type'=>'post', 'status'=>'publish', 'raw'=>'Romantický pobyt pro dva s rybařením',
    'single'=>true, 'singular'=>true, 'admin'=>false, 'preview'=>false);
$GLOBALS['hooks'] = array();
$GLOBALS['styles'] = array();
function add_filter($hook,$callback,$priority=10) {$GLOBALS['hooks'][$hook]=array($callback,$priority);}
function add_action($hook,$callback,$priority=10) {$GLOBALS['hooks'][$hook]=array($callback,$priority);}
function is_single($id) {return $GLOBALS['ctx']['single'] && $id===$GLOBALS['ctx']['id'];}
function is_singular($type) {return $GLOBALS['ctx']['singular'] && $type==='post';}
function get_queried_object_id() {return $GLOBALS['ctx']['id'];}
function is_admin() {return $GLOBALS['ctx']['admin'];}
function is_preview() {return $GLOBALS['ctx']['preview'];}
function home_url($path='/') {return $GLOBALS['ctx']['site'];}
function get_permalink($id) {return $GLOBALS['ctx']['url'];}
function get_post($id) {return (object)array('ID'=>$GLOBALS['ctx']['id'],
    'post_type'=>$GLOBALS['ctx']['type'], 'post_status'=>$GLOBALS['ctx']['status'],
    'post_name'=>$GLOBALS['ctx']['slug'], 'post_modified_gmt'=>$GLOBALS['ctx']['modified'],
    'post_content'=>$GLOBALS['ctx']['raw']);}
function plugins_url($path,$base) {return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_style($handle,$url,$deps,$ver) {$GLOBALS['styles'][]=compact('handle','url','deps','ver');}
function offer_check($yes,$why) {if (!$yes) {fwrite(STDERR, "FAIL $why\n");exit(1);}}
require dirname(__DIR__).'/romantic-stay-offers-57013-v1.php';
$class='NRY_Romantic_Stay_Offers_57013_V1';
offer_check($GLOBALS['hooks']['the_content']===array($class.'::render',PHP_INT_MAX), 'late render hook');
offer_check($GLOBALS['hooks']['wp_enqueue_scripts']===array($class.'::enqueue',28), 'css hook');
offer_check($GLOBALS['hooks']['wp_footer']===array($class.'::probe_footer',PHP_INT_MAX), 'GET-only source hook');
foreach (array('HAUZI_SRUB'=>'https://www.hauzi.cz/rybarsky-srub',
    'HAUZI_MORE'=>'https://www.hauzi.cz/cz/pro-dva/pro-rybare/wellness') as $constant=>$expected) {
    $url=constant($class.'::'.$constant);
    parse_str(parse_url($url,PHP_URL_QUERY),$params);
    offer_check(parse_url($url,PHP_URL_HOST)==='tracking.affiliateport.eu'
        && $params['o']==='1990' && $params['a']==='1736'
        && $params['sub_id5']===$expected, 'approved Hauzi direct target '.$constant);
}
$prefix="<h1>Romantický pobyt pro dva s rybařením</h1>\n"
    ."<p>Když někdo hledá romantický pobyt pro dva s rybařením, většinou nechce jen hezký pokoj a vodu někde v dohledu. Chce klid, soukromí, možnost chytat bez zbytečného stresu a zároveň pobyt, který nebude působit jako kompromis na sílu. Právě tohle bývá největší problém: najít místo, kde se dobře cítí rybář i partner, který třeba k vodě jezdí hlavně kvůli odpočinku.</p>\n"
    ."<p>V tomhle článku najdete praktický pohled na to, co od takového pobytu čekat, podle čeho vybírat a na co si dát pozor, aby z romantického víkendu nebyla jen drahá noc v chatě u vody. Neřešíme pohádku. Řešíme praxi.</p>\n";
offer_check(hash('sha256',substr($prefix,0,$class::RENDERED_PREFIX_BYTES))===$class::RENDERED_PREFIX_SHA256,
    'precise public REST rendered prefix SHA');
$source=$prefix.'<p><a href="https://na-ryby.eu/">původní odkaz</a></p>'
    .$class::OLD_BUTTON.'<p><a href="'.$class::CATEGORY.'">Další původní ubytování</a></p>'
    .'<h2>Co si pod tím lidé obvykle představují</h2>'
    .str_repeat('původní redakční text.',110);
// The published raw WordPress bytes are intentionally NOT fabricated into a
// fixture. Test the transform positively and the raw source lock negatively.
offer_check($class::RAW_SHA256 === '17e8cc812393057c20343c3812c059291e3102421a2fbad94190ad73abada185'
    && $class::RAW_BYTES === 14225, 'pin exact publicly proven raw WP source');
offer_check($class::render($source)===$source, 'unapproved mock raw source cannot publish');
$updated=$class::transform($source);
offer_check($updated!==$source && strpos($updated,$class::MARKER)!==false, 'one top-fold offer block');
offer_check(substr_count($updated,'href="'.$class::CATEGORY.'"')===2,
    'both historical editor category URLs are retained');
offer_check(substr_count($updated,'data-nry-romantic-offer=')===2, 'exactly two choices');
offer_check(strpos($updated,'jen v jarních měsících')!==false, 'Hauzi seasonal fishing caveat remains visible');
offer_check(strpos($updated,$class::OLD_BUTTON)===false, 'generic bright button replaced');
offer_check(strpos($updated,'původní redakční text.')!==false, 'original body preserved');
offer_check($class::transform($updated)===$updated, 'idempotent');
offer_check($class::transform(str_replace('Když někdo hledá','Když někdo tvrdí',$source))
    ===str_replace('Když někdo hledá','Když někdo tvrdí',$source), 'source prefix drift blocked');
offer_check($class::transform(str_replace('Mrknout na doporučené ubytování','Nové editorovo CTA',$source))
    ===str_replace('Mrknout na doporučené ubytování','Nové editorovo CTA',$source), 'anchor drift blocked');
$oneLink=str_replace('<p><a href="'.$class::CATEGORY.'">Další původní ubytování</a></p>','',$source);
offer_check($class::transform($oneLink)===$oneLink, 'missing second original URL must fail closed');
$threeLinks=$source.'<p><a href="'.$class::CATEGORY.'">Third stale route</a></p>';
offer_check($class::transform($threeLinks)===$threeLinks, 'extra editorial URL must fail closed');
foreach (array('id'=>57014,'slug'=>'changed-slug','url'=>'https://na-ryby.eu/wrong/',
    'site'=>'https://wrong.example/','modified'=>'2026-04-19 12:01:56',
    'type'=>'page','status'=>'draft','raw'=>'other source',
    'single'=>false,'singular'=>false,'admin'=>true,'preview'=>true) as $key=>$change) {
    $old=$GLOBALS['ctx'][$key];$GLOBALS['ctx'][$key]=$change;
    offer_check($class::render($source)===$source, 'post identity gate '.$key);
    $GLOBALS['ctx'][$key]=$old;
}
$class::enqueue();
offer_check(count($GLOBALS['styles'])===0,
    'wrong raw WP SHA must not enqueue new commercial CSS');
// Explicit opt-in test uses only fake WordPress post content, no networking or writes.
$GLOBALS['ctx']['raw'] = str_repeat('Romantický pobyt pro dva s rybařením. ', 110);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['nry_romantic_guard_probe'] = 'source_v1';
$class::render($source);
ob_start();
$class::probe_footer();
offer_check(ob_get_clean()==='', 'invalid raw source cannot generate opt-in authoritative proof');

unset($_GET['nry_romantic_guard_probe']);
ob_start();
$class::probe_footer();
offer_check(ob_get_clean()==='', 'ordinary anonymous page emits no probe');

echo "PASS exact post offer insertion, 2 approved Hauzi links, no content writes, drift blocked\n";
