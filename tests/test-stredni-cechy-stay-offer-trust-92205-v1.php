<?php
/** Offline transform contract; never invokes a merchant endpoint or WP writer. */
define('ABSPATH', __DIR__);
$GLOBALS['sc_post'] = (object)array(
    'ID' => 92205, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => 'zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach',
    'post_modified_gmt' => '2024-10-07 12:15:48',
    // The real raw content cannot be reconstructed from anonymous rendered REST.
    // This fixture must FAIL the positive raw SHA gate; transform is tested separately.
    'post_content' => 'unapproved synthetic source',
);
$GLOBALS['sc_styles'] = array();
$GLOBALS['sc_wrong'] = false;
function add_filter($name,$call,$priority) { $GLOBALS['sc_filter'] = array($name,$call,$priority); }
function add_action($name,$call,$priority) { return true; }
function is_single($id) { return !$GLOBALS['sc_wrong'] && $id===92205; }
function is_singular($type) { return $type==='post'; }
function get_queried_object_id() { return $GLOBALS['sc_wrong'] ? 0 : 92205; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return 'https://na-ryby.eu/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/'; }
function get_post($id) { return $GLOBALS['sc_post']; }
function plugins_url($path,$file) { return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path; }
function wp_enqueue_style($name,$url,$deps,$version) { $GLOBALS['sc_styles'][] = array($name,$url,$version); }
function check($ok,$msg) { if (!$ok) throw new RuntimeException($msg); }
require dirname(__DIR__).'/stredni-cechy-stay-offer-trust-92205-v1.php';
$class='NRY_Stredni_Cechy_Stay_Offer_92205_V1';
// Exact first 512 bytes of anonymous REST content.rendered on 18 Nov 2026.
$prefix=base64_decode('PHAgc3R5bGU9InRleHQtYWxpZ246IGNlbnRlcjsiPjxhIGhyZWY9Imh0dHBzOi8vbmEtcnlieS5ldS9sb3Yta2FwcmEiIGNsYXNzPSJzdS1idXR0b24gc3UtYnV0dG9uLXN0eWxlLWRlZmF1bHQiIHN0eWxlPSJjb2xvcjojZmZmZmZmO2JhY2tncm91bmQtY29sb3I6I2ZhMDEyNztib3JkZXItY29sb3I6I2M4MDEyMDtib3JkZXItcmFkaXVzOjE1cHgiIHRhcmdldD0iX3NlbGYiPjxzcGFuIHN0eWxlPSJjb2xvcjojZmZmZmZmO3BhZGRpbmc6MTFweCAzNnB4O2ZvbnQtc2l6ZToyNnB4O2xpbmUtaGVpZ2h0OjM5cHg7Ym9yZGVyLWNvbG9yOiNmYzRlNjg7Ym9yZGVyLXJhZGl1czoxNXB4O3RleHQtc2hhZG93Om5vbmUiPjxpIGNsYXNzPSJzdWkgc3VpLWFycm93LXJpZ2h0IiBzdHlsZT0iZm9udC1zaXplOjI2cHg7Y29sb3I6I0ZGRkZGRiI+PC9pPiBWxaFlIG5hIEtBUFJZIHYgQUtDSTwvc3Bhbj48L2E+CjxwPjwvcD4KPGgxPlrDocW+aXRrb3bDqSByeWJhxZllbsOtIHMgdWJ5dG92w6Fuw61tIHZlIHN0xZk=');
check(strlen($prefix)===512,'reviewed prefix bytes');
check(hash('sha256',$prefix)===$class::PREFIX_SHA256,'reviewed prefix SHA');
$original=$prefix.$class::ANCHOR
    .'<p>s možností ubytování přímo na břehu rybníka.</p>'
    .'<p>Mnoho rybníků nabízí i možnost výuky od zkušených instruktorů, kteří vám rádi poradí a pomohou s vaším lovem.</p>'
    .'<h3>Co zahrnuje pobyt?</h3>'
    .'<p>Zážitkové rybaření s ubytováním zahrnuje profesionálního průvodce, rybářské vybavení, ubytování a stravování v krásné přírodě středních Čech.</p>'
    .'<p>Ubytování je možné pro skupiny o velikosti od 2 do 6 osob, v závislosti na dostupnosti.</p>'
    .'<p>Nabízíme nezapomenutelné zážitkové rybaření s možností ubytování ve středních Čechách&#8230;.</p>'
    .'<h3>Tip na zajímavý rybářský soukromý rybářský areál v Česku</h3>'
    .'<p>Pokud hledáte ideální místo pro soukromé rybaření s ubytováním v České republice, doporučujeme navštívit <a href="'.$class::MISLOCATED_WIKI.'">Jindřichovice pod Smrkem</a>. Tento malebný rybářský areál nabízí krásné ubytování u vody a možnost rybaření v soukromí.</p>'
    .'<div style="display: flex; justify-content: center; align-items: center; height: 100vh;">'
    .'    <a href="'.$class::LEGACY_BOOKING.'"><br />'
    .'        <img decoding="async" src="https://na-ryby.eu/wp-content/uploads/2023/08/Screenshot_960.png" alt=""><br />'
    .'    </a>'
    .'</div>'
    .'<p>'.str_repeat('Nezměněný užitečný text o rybaření. ', 70).'</p>'
    .'<a href="https://na-ryby.eu/preserved-existing-link/">Původní interní odkaz</a>';
check(strlen($original)>2500,'sufficient article length');
$changed=$class::transform($original);
check($changed!==$original,'source-rendered match changes visible copy');
check(substr_count($changed,$class::MARKER)===1,'one new offer section');
check(substr_count($changed,'data-nry-sc-offer=')===2,'two exact-property offer identities');
check(strpos(html_entity_decode($changed,ENT_QUOTES,'UTF-8'),$class::HPORTAL_SLAPY)!==false,'approved unchanged partner href');
check(strpos(html_entity_decode($changed,ENT_QUOTES,'UTF-8'),$class::HAUZI_SLAPY)!==false,'approved exact Slapy Hauzi href');
parse_str(parse_url($class::HAUZI_SLAPY,PHP_URL_QUERY),$hauzi_query);
check(parse_url($class::HAUZI_SLAPY,PHP_URL_HOST)==='tracking.affiliateport.eu'
    && $hauzi_query['o']==='1990' && $hauzi_query['a']==='1736'
    && $hauzi_query['sub_id5']==='https://www.hauzi.cz/chata-slapy-skalice',
    'approved Hauzi deep link points to exact verified Slapy property');
check(strpos($changed,$class::STAYS)!==false,'internal comparison');
check(strpos($changed,'rel="sponsored nofollow noopener"')!==false,'sponsorship');
check(strpos($changed,'height: 100vh')===false,'obsolete oversized banner removed');
check(strpos($changed,'Nabízíme nezapomenutelné')===false,'false owner promise removed');
check(strpos($changed,'https://na-ryby.eu/preserved-existing-link/')!==false,'unrelated existing link preserved');
check($class::transform($changed)===$changed,'idempotent');
check($class::transform('z'.substr($original,1))==='z'.substr($original,1),'rendered prefix drift fails closed');
check($class::transform(str_replace($class::ANCHOR,$class::ANCHOR.$class::ANCHOR,$original))===str_replace($class::ANCHOR,$class::ANCHOR.$class::ANCHOR,$original),'duplicate heading fails closed');
$duplicatedPartner=$original.'<a href="'.$class::HPORTAL_SLAPY.'">Old partner CTA</a>';
check($class::transform($duplicatedPartner)===$duplicatedPartner,'existing partner href blocks duplicates');
$duplicatedHauzi=$original.'<a href="'.$class::HAUZI_SLAPY.'">Old Hauzi CTA</a>';
check($class::transform($duplicatedHauzi)===$duplicatedHauzi,'existing Hauzi href blocks duplicates');
$driftedWiki=str_replace('Jindřichovice pod Smrkem</a>','jiná lokalita</a>',$original);
check($class::transform($driftedWiki)===$driftedWiki,'unsourced editorial change blocks entire overlay');
check($class::render($original)===$original,'wrong raw SHA blocks all writes and overlays');
$class::enqueue();
check(count($GLOBALS['sc_styles'])===0,'no style on unapproved raw source');
echo "PASS: exact source lock, two reviewed offers, bounded trust repair, legacy removal and no writes\n";
