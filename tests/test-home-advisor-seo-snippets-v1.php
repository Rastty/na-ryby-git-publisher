<?php
/* Standalone regression test: two exact WordPress pages, render-only SEO. */
define('ABSPATH', '/');
$GLOBALS['seo_ctx'] = array(
    'id' => 118641, 'slug' => 'na-ryby-eu-vse-pro-rybare',
    'url' => 'https://na-ryby.eu/', 'site' => 'https://na-ryby.eu/',
    'front' => true, 'page' => true, 'singular' => true,
    'admin' => false, 'preview' => false, 'type' => 'page',
    'status' => 'publish', 'existing_meta' => array(),
);
$GLOBALS['seo_filters'] = array();
function add_filter($hook, $fn, $priority = 10) {$GLOBALS['seo_filters'][$hook] = array($fn, $priority);}
function is_page($id) {return $GLOBALS['seo_ctx']['page'] && $id === $GLOBALS['seo_ctx']['id'];}
function is_singular($type) {return $GLOBALS['seo_ctx']['singular'] && $type === 'page';}
function is_front_page() {return $GLOBALS['seo_ctx']['front'];}
function get_queried_object_id() {return $GLOBALS['seo_ctx']['id'];}
function home_url($path = '/') {return $GLOBALS['seo_ctx']['site'];}
function get_permalink($id) {return $GLOBALS['seo_ctx']['url'];}
function is_admin() {return $GLOBALS['seo_ctx']['admin'];}
function is_preview() {return $GLOBALS['seo_ctx']['preview'];}
function metadata_exists($type, $id, $key) {
    return $type === 'post' && $id === $GLOBALS['seo_ctx']['id']
        && in_array($key, $GLOBALS['seo_ctx']['existing_meta'], true);
}
function get_post($id) {return (object)array(
    'ID' => $id, 'post_type' => $GLOBALS['seo_ctx']['type'],
    'post_status' => $GLOBALS['seo_ctx']['status'],
    'post_name' => $GLOBALS['seo_ctx']['slug'],
);}
function seo_check($ok, $message) {if (!$ok) {fwrite(STDERR, "FAIL $message\n"); exit(1);}}

require dirname(__DIR__).'/home-advisor-seo-snippets-v1.php';
$seo = 'NRY_Home_Advisor_SEO_Snippets_V1';
seo_check(array_keys($GLOBALS['seo_filters']) === array(
    'wpseo_title', 'wpseo_metadesc', 'wpseo_opengraph_title', 'wpseo_opengraph_desc'),
    'exactly four render-time filters');

$examples = array(
    array(118641, 'na-ryby-eu-vse-pro-rybare', 'https://na-ryby.eu/', true,
        'Na-ryby.eu - Vše pro rybáře -',
        'Rybářské vybavení a praktické návody | Na-Ryby.eu'),
    array(119865, 'poradce-vybavy', 'https://na-ryby.eu/poradce-vybavy/', false,
        'Jakou rybářskou výbavu potřebuji? | Poradce Na-Ryby.eu -',
        'Poradce výběrem prutu a navijáku | Na-Ryby.eu'),
);
foreach ($examples as $example) {
    list($id, $slug, $url, $front, $old, $expected) = $example;
    $GLOBALS['seo_ctx']['id'] = $id;
    $GLOBALS['seo_ctx']['slug'] = $slug;
    $GLOBALS['seo_ctx']['url'] = $url;
    $GLOBALS['seo_ctx']['front'] = $front;
    seo_check($seo::title($old) === $expected, 'specific title '.$id);
    seo_check($seo::og_title($old) === $expected, 'specific social title '.$id);
    $desc = $seo::description('');
    seo_check(is_string($desc) && strlen($desc) > 100 && strlen($desc) < 180,
        'bounded description '.$id);
    seo_check($seo::og_description('') === $desc, 'social description blank fallback '.$id);
    seo_check($seo::title('Editor's specific title') === "Editor's specific title",
        'never override changed title '.$id);
    seo_check($seo::description('Editor's specific description') === "Editor's specific description",
        'never override existing description '.$id);
    $meta_keys = array(
        array('_yoast_wpseo_title', 'title', $old),
        array('_yoast_wpseo_metadesc', 'description', ''),
        array('_yoast_wpseo_opengraph-title', 'og_title', $old),
        array('_yoast_wpseo_opengraph-description', 'og_description', ''),
    );
    foreach ($meta_keys as $case) {
        $GLOBALS['seo_ctx']['existing_meta'] = array($case[0]);
        seo_check($seo::{$case[1]}($case[2]) === $case[2],
            'editor Yoast metadata protected: '.$case[0]);
        $GLOBALS['seo_ctx']['existing_meta'] = array();
    }
    foreach (array('id' => 999, 'slug' => 'changed-slug',
        'url' => 'https://na-ryby.eu/wrong/', 'site' => 'https://other-site.example/',
        'front' => !$front, 'page' => false, 'singular' => false,
        'admin' => true, 'preview' => true, 'type' => 'post',
        'status' => 'draft') as $key => $value) {
        $original = $GLOBALS['seo_ctx'][$key];
        $GLOBALS['seo_ctx'][$key] = $value;
        seo_check($seo::title($old) === $old, 'changed context title '.$key.' '.$id);
        seo_check($seo::description('') === '', 'changed context description '.$key.' '.$id);
        $GLOBALS['seo_ctx'][$key] = $original;
    }
}
echo "PASS 2 bounded SEO pages, 4 render hooks, editor overrides, context drift; no writes\n";
