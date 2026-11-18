<?php
/** Offline exact-render fixture, no WordPress writes or merchant requests. */
define('ABSPATH', __DIR__);
function add_filter($n,$cb,$p) { $GLOBALS['dyje_filter_priority']=$p; return true; }
function add_action($n,$cb,$p) { return true; }
function is_single($id) { return $id === 14517; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return 14517; }
function is_main_query() { return $GLOBALS['dyje_main_query'] ?? true; }
function in_the_loop() { return $GLOBALS['dyje_in_loop'] ?? true; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return 'https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/'; }
$GLOBALS['dyje_mock'] = (object)array('ID'=>14517,'post_type'=>'post','post_status'=>'publish',
  'post_name'=>'vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj',
  'post_modified_gmt'=>'2026-04-18 14:01:51','post_content'=>'source drift');
function get_post($id) { return $GLOBALS['dyje_mock']; }
require dirname(__DIR__) . '/dyje6-stay-trust-14517-v1.php';
function check($ok,$msg) { if (!$ok) throw new RuntimeException($msg); }

$price = "<p>Podle dostupných informací se na Věstonické nádrži prodávají denní i týdenní povolenky, a to i ve zvýhodněné variantě pro mládež. Uvedené ceny byly:</p>\n"
  ."<ul>\n<li>denní povolenka: 200 korun</li>\n<li>denní povolenka pro mládež do 15 let: 100 korun</li>\n"
  ."<li>týdenní povolenka: 800 korun</li>\n<li>týdenní povolenka pro mládež: 400 korun</li>\n</ul>\n"
  ."<p>Beru to jako orientační údaj, ne jako konečné pravidlo na věčné časy. Ceny a podmínky se mohou změnit, takže před výpravou je rozumné ověřit aktuální informace u správce revíru.</p>\n";
$bivak = '<li>prostor pro <a href="'.NRY_Dyje6_Stay_Trust_14517_V1::OLD_BIVAK_GUIDE.'" data-internallinksmanager029f6b8e52c="14" title="bivak">bivak</a>, lehátko nebo plachtu</li>';
$stay = "<div style=\"background:#f8f9fa;border:1px solid #d9e2ec;border-radius:10px;padding:18px 20px;margin:24px 0;\"><strong>Mrknout na doporučené ubytování:</strong></p>\n"
  ."<p style=\"margin:10px 0 14px 0;\">Pokud chcete spojit rybaření s přespáním v okolí, podívejte se na přehled možností pro pobyty u vody.</p>\n"
  ."<p><a href=\"https://na-ryby.eu/booking-cesko\" style=\"display:inline-block;background:#0056b3;color:#fff;text-decoration:none;padding:11px 16px;border-radius:6px;font-weight:700;\">Najít ubytování pro rybáře</a></div>\n";
$good = '<p>Revír v Jihomoravském kraji.</p><a href="https://na-ryby.eu/stary-clanek/">Starší interní odkaz</a>'
  .$price.'<h2>Kde chytat</h2><ul>'.$bivak.'</ul>'
  .'<li>ochrana proti větru a dešti, pokud zůstáváte přes noc</li>'
  .$stay.'<p>Další odborný text a starší odkazy.</p>';
$class='NRY_Dyje6_Stay_Trust_14517_V1';
check(($GLOBALS['dyje_filter_priority'] ?? null)===PHP_INT_MAX,'render after WP autop and internal-link manager');
$new=$class::transform($good);
check($new !== $good,'approved exact rendered template transforms');
check(substr_count($new,$class::FACTS_MARKER)===1,'one sourced facts panel');
check(substr_count($new,$class::OFFERS_MARKER)===1,'one offer panel');
check(strpos($new,'denní 350 Kč, týdenní 1 380 Kč')!==false,'2026 price source');
check(strpos($new,'platný státní rybářský lístek')!==false,'actual permit holder');
check(strpos($new,'Lov jen na určeném úseku jižní hráze')!==false,'restricted nature reserve');
check(strpos($new,'denní povolenka: 200 korun')===false,'stale prices gone');
check(strpos($new,'prostor pro <a')===false,'misleading bivak suggestion gone');
check(strpos($new,'https://na-ryby.eu/stary-clanek/')!==false,'other href kept');
check(strpos($new,$class::EXISTING_CATEGORY)!==false,'pre-existing partner category preserved');
check(strpos($new,htmlspecialchars($class::SLEVOMAT,ENT_QUOTES,'UTF-8'))!==false,'exact Slevomat deeplink');
check(strpos($new,htmlspecialchars($class::HPORTAL,ENT_QUOTES,'UTF-8'))!==false,'exact Dognet deeplink');
check(strpos($new,htmlspecialchars($class::HAUZI,ENT_QUOTES,'UTF-8'))!==false,'exact approved Hauzi AffiliatePort deeplink');
check(substr_count($new,'data-nry-offer-id=')===3,'three independent reviewed stay offers');
check($class::transform($new)===$new,'idempotent');
check($class::render($good)===$good,'unapproved raw SHA blocks render');
$drift=str_replace('denní povolenka: 200 korun','denní povolenka: 350 korun',$good);
check($class::transform($drift)===$drift,'price anchor drift blocks whole patch');
$drift=str_replace('Najít ubytování pro rybáře','Něco jiného',$good);
check($class::transform($drift)===$drift,'merchant block drift blocks whole patch');
check($class::transform($good.$good)===$good.$good,'duplicate article blocks patch');
$drift=$good.'<a href="'. $class::SLEVOMAT .'">Injected partner</a>';
check($class::transform($drift)===$drift,'pre-existing destination blocks duplicate tracking');
$drift=$good.'<a href="'. $class::HAUZI .'">Injected Hauzi partner</a>';
check($class::transform($drift)===$drift,'pre-existing Hauzi destination blocks duplicate tracking');

// The verbatim authenticated published source is archived only as a local,
// read-only fixture; one appended LF belongs to the text fixture, not WP.
$raw=file_get_contents(__DIR__.'/fixtures/dyje6-14517-source-20261118.html');
check(substr($raw,-1)==="\n",'fixture has one formatting newline');
$raw=substr($raw,0,-1);
check(hash('sha256',$raw)===$class::SOURCE_SHA256,'approved WP post source SHA fixture');
$GLOBALS['dyje_mock']->post_content=$raw;
check($class::render($good)!==$good,'source-locked render in normal loop');
$GLOBALS['dyje_main_query']=false;
$GLOBALS['dyje_in_loop']=false;
check($class::render($good)!==$good,'Newspaper off-main-loop post content still renders');
$GLOBALS['dyje_mock']->post_content='source drift';
check($class::render($good)===$good,'source drift blocks off-main-loop render');

// Unlike the synthetic $good, this is the exact *fully filtered* WordPress
// REST rendered HTML from the authenticated source. WordPress stores compact
// markup and dynamically injects internal link markup + wpautop paragraphs.
$rendered=file_get_contents(__DIR__.'/fixtures/dyje6-14517-wp-rendered-20261118.html');
check(substr($rendered,-1)==="\n",'actual rendered fixture has original trailing LF');
check(substr_count($rendered,'denní povolenka: 200 korun')===1,'actual rendered source has stale price');
check(substr_count($rendered,'<li>prostor pro <a href="'.$class::OLD_BIVAK_GUIDE.'" data-internallinksmanager029f6b8e52c')===1,'actual rendered source auto-links bivak exactly once');
$actual=$class::transform($rendered);
check($actual!==$rendered,'actual WP fully rendered HTML transforms');
check(substr_count($actual,$class::FACTS_MARKER)===1,'actual rendered adds one official facts block');
check(substr_count($actual,$class::OFFERS_MARKER)===1,'actual rendered adds one offer block');
check(substr_count($actual,'data-nry-offer-id=')===3,'actual WordPress rendered has exactly three vetted offers');
check($class::transform($actual)===$actual,'actual rendered is idempotent');
echo "PASS: Dyje 6 exact-source conservation, safety and three approved partner routes; no writes\n";
