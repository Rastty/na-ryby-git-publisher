<?php
/** Consent-aware measurement must not mutate content or track other articles. */
define('ABSPATH', __DIR__);
$GLOBALS['queue']=array();
$GLOBALS['id']=42401;
$GLOBALS['path']='https://na-ryby.eu/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/';
$GLOBALS['singular']=true;
function add_action($a,$callback,$priority=10) {
    $GLOBALS['action']=array($a,$callback,$priority);
}
function is_singular($type){return $type==='post'&&$GLOBALS['singular'];}
function get_queried_object_id(){return $GLOBALS['id'];}
function get_permalink($id){return $GLOBALS['path'];}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_script($handle,$src,$deps,$version,$footer){
    $GLOBALS['queue'][]=compact('handle','src','deps','version','footer');
}
function must($good,$m){if(!$good){fwrite(STDERR,'FAIL: '.$m."\n");exit(1);}}
require dirname(__DIR__).'/buyer-click-telemetry-v1.php';
must($GLOBALS['action']===array('wp_enqueue_scripts','nry_buyer_click_enqueue',24),'correct hook');
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===1,'real pípáky buyer instrumented');
must($GLOBALS['queue'][0]['footer']===true,'async footer');
must($GLOBALS['queue'][0]['deps']===array(),'no third-party tracker introduced');
must(str_ends_with($GLOBALS['queue'][0]['src'],'/assets/nry-buyer-clicks-v1.js'),'only bundled local asset');
$GLOBALS['queue']=array();
$GLOBALS['id']=118218;
$GLOBALS['path']='https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/';
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===1,'buyer guide is approved');
// Six additional source-verified live buyer articles use the same consent-aware,
 // read-only script without introducing another data or WordPress write path.
$more=array(
  35755 => '/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/',
  36702 => '/pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu/',
  46573 => '/pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare/',
  118455 => '/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/',
  118553 => '/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/',
  60637 => '/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/',
  36518 => '/porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023/',
  49275 => '/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/',
  61257 => '/zavazeci-lodky-pro-rybare/',
  118541 => '/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/',
  60341 => '/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/',
  11901 => '/jak-vybrat-rybarske-kreslo/',
  118388 => '/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/',
  119575 => '/nejlepsi-rybarske-bivaky-do-10-000-kc/',
  59891 => '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/',
  14517 => '/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/',
  12166 => '/rybarska-dovolena-jizni-morava/',
  59271 => '/rodinna-dovolena-u-vody-kam-na-ryby-s-detmi/',
  92205 => '/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/',
  85384 => '/nejlepsi-mista-pro-sportovni-rybolov-s-ubytovanim-v-ceske-republice/'
);
foreach($more as $id=>$route) {
  $GLOBALS['queue']=array();
  $GLOBALS['id']=$id;
  $GLOBALS['path']='https://na-ryby.eu'.$route;
  nry_buyer_click_enqueue();
  must(count($GLOBALS['queue'])===1,'exact released buyer must have script');
  must(nry_buyer_click_approved_scope($id,$route),'whitelisted ID/path binding');
  must($GLOBALS['queue'][0]['handle']==='nry-buyer-offer-click-v1','same local JS');
  must($GLOBALS['queue'][0]['version']==='1.11.5','new script version');
}
must(!nry_buyer_click_approved_scope(59891,'/rodinna-dovolena-u-vody-kam-na-ryby-s-detmi/'),
    'stay measurement cannot be bound to a different article');
must(!nry_buyer_click_approved_scope(14517,'/soukromy-revir-sterkac-zajeci/'),
    'Dyje 6 measurement must not leak to other waters');
must(!nry_buyer_click_approved_scope(12166,'/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/'),
    'Jizni Morava regional landing cannot impersonate the Dyje 6 revír');
must(!nry_buyer_click_approved_scope(59271,'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'),
    'family stay ID cannot impersonate the full stay guide');
must(!nry_buyer_click_approved_scope(92205,'/rodinna-dovolena-u-vody-kam-na-ryby-s-detmi/'),
    'Stredni Cechy click source cannot impersonate the family page');
must(!nry_buyer_click_approved_scope(85384,'/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/'),
    'nationwide stays click source cannot impersonate Stredni Cechy');
must(!nry_buyer_click_approved_scope(60341,'/jak-vybrat-rybarske-kreslo/'),'never bind hub id to evergreen');
must(!nry_buyer_click_approved_scope(11901,'/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/'),'never bind evergreen id to hub');
must(!nry_buyer_click_approved_scope(118388,'/nejlepsi-rybarske-bivaky-do-10-000-kc/'),'bivak post ID cannot be swapped');
must(!nry_buyer_click_approved_scope(119575,'/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/'),'budget post ID cannot be swapped');
$GLOBALS['queue']=array();
$GLOBALS['id']=119865;
$GLOBALS['path']='https://na-ryby.eu/poradce-vybavy/';
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===0,'advisor owns separate funnel; no duplicate instrumentation');
$GLOBALS['queue']=array();
$GLOBALS['id']=999999;
$GLOBALS['path']='https://na-ryby.eu/neschvaleny-novy-clanek/';
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===0,'unregistered buyer post blocked');
$GLOBALS['id']=42401;
$GLOBALS['path']='https://other.example/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/';
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===0,'outside host blocked');
$GLOBALS['path']='https://na-ryby.eu/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/';
$GLOBALS['singular']=false;
nry_buyer_click_enqueue();
must(count($GLOBALS['queue'])===0,'nonpost context blocked');
must(nry_buyer_click_approved_scope(42401,'/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/'),'exact approved canonical');
must(!nry_buyer_click_approved_scope(42401,'/signalizatory/'),'no arbitrary target');
echo "PASS: exact buyer guides and baitboat handoff, no advisor duplication or new tracker\n";
