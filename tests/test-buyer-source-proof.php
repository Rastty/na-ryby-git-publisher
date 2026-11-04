<?php
// Standalone read-only WordPress source-proof contract; raw content never printed.
define('ABSPATH', __DIR__);
$GLOBALS['sources'] = array();
function add_action($n,$cb,$priority=10) {}
function get_post($id) { return $GLOBALS['sources'][$id]??null; }
function get_permalink($id) {
    $p=get_post($id);
    return $p ? 'https://na-ryby.eu/'.$p->post_name.'/' : '';
}
function must($value,$why) { if (!$value) {fwrite(STDERR,"FAIL: ".$why."\n");exit(1);} }
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$slugs=array(
  34858=>'jak-vybrat-detske-rybarske-kreslo-pro-pohodlne-rybareni',
  34953=>'jak-vychytat-nejlepsi-rybarske-kreslo-na-nadchazejici-akci',
  34969=>'jak-si-vybrat-levne-a-pohodlne-rybarske-kreslo',
);
foreach($slugs as $id=>$slug) {
    $GLOBALS['sources'][$id]=(object)array('ID'=>$id,'post_type'=>'post',
       'post_status'=>'publish','post_name'=>$slug,'post_modified_gmt'=>'2026-11-02 15:00:00',
       'post_content'=>str_repeat('<p>Validní text.</p>',200));
}
$proof=NRY_Git_Publisher::buyer_source_proof_snapshot();
must($proof['publication_authorized']===false,'never grants release');
must($proof['gsc_protection_checked']===false,'never guesses GSC protection');
must($proof['wordpress_writes']===0,'never writes');
must(count($proof['rows'])===3,'exact scope');
foreach($proof['rows'] as $i=>$r) {
    must($r['state']==='SOURCE_SHA_AVAILABLE_NOT_PUBLICATION_AUTHORITY','source metadata available');
    must($r['source_sha256']===hash('sha256',get_post($r['id'])->post_content),'hash matches raw bytes');
    must(!isset($r['post_content'])&&!isset($r['rendered'])&&!isset($r['cj_urls']),'no private source leaks');
    must($r['source_modified_gmt']==='2026-11-02 15:00:00','exact GMT');
}
$GLOBALS['sources'][34858]->post_status='draft';
$blocked=NRY_Git_Publisher::buyer_source_proof_snapshot();
must($blocked['rows'][0]['state']==='BLOCKED_IDENTITY_OR_SOURCE','draft never exposes content SHA');
$GLOBALS['sources'][34858]->post_status='publish';
$GLOBALS['sources'][34953]->post_name='wrong-slug';
$blocked=NRY_Git_Publisher::buyer_source_proof_snapshot();
must($blocked['rows'][1]['state']==='BLOCKED_IDENTITY_OR_SOURCE','unexpected canonical fails closed');
must(!isset($blocked['rows'][1]['source_sha256']),'blocked row no hash');
$GLOBALS['sources'][11901]=(object)array(
    'ID'=>11901,'post_type'=>'post','post_status'=>'publish',
    'post_name'=>'jak-vybrat-rybarske-kreslo',
    'post_modified_gmt'=>'2017-06-29 16:58:04',
    'post_content'=>str_repeat('<p>Historický nákupní průvodce.</p>',200)
);
$evergreen=NRY_Git_Publisher::buyer_source_proof_snapshot('evergreen');
must(count($evergreen['rows'])===1,'evergreen proof has one exact post');
must($evergreen['rows'][0]['id']===11901,'evergreen exact post ID');
must($evergreen['rows'][0]['state']==='SOURCE_SHA_AVAILABLE_NOT_PUBLICATION_AUTHORITY',
    'evergreen source hash available');
must($evergreen['rows'][0]['source_sha256']===hash('sha256',
    $GLOBALS['sources'][11901]->post_content),'evergreen SHA is raw post content');
must(!isset($evergreen['rows'][0]['post_content'])
    && !isset($evergreen['rows'][0]['cj_urls'])
    && $evergreen['wordpress_writes']===0
    && $evergreen['publication_authorized']===false,
    'evergreen proof is metadata-only with zero WordPress writes');
$GLOBALS['sources'][11901]->post_status='draft';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('evergreen')['rows'][0]['state']
    ==='BLOCKED_IDENTITY_OR_SOURCE','evergreen draft blocked');
$GLOBALS['sources'][11901]->post_status='publish';
$GLOBALS['sources'][11901]->post_name='unexpected-other-slug';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('evergreen')['rows'][0]['state']
    ==='BLOCKED_IDENTITY_OR_SOURCE','evergreen slug drift blocked');
must(NRY_Git_Publisher::buyer_source_proof_snapshot('arbitrary')['state']
    ==='BLOCKED_UNSUPPORTED_PROOF_SCOPE','arbitrary source lookup blocked');
$GLOBALS['sources'][60341]=(object)array(
  'ID'=>60341, 'post_type'=>'post', 'post_status'=>'publish',
  'post_name'=>'nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby',
  'post_modified_gmt'=>'2023-09-23 05:35:38',
  'post_content'=>str_repeat('<p>Editorial mock content.</p>', 600),
);
$hub=NRY_Git_Publisher::buyer_source_proof_snapshot('buyer-hub');
must(count($hub['rows'])===1 && $hub['rows'][0]['id']===60341, 'exact buyer hub scope');
must($hub['rows'][0]['source_sha256']===hash('sha256',$GLOBALS['sources'][60341]->post_content),
  'buyer hub proof uses raw WordPress content');
must($hub['rows'][0]['source_modified_gmt']==='2023-09-23 05:35:38',
  'buyer hub GMT exact');
must(!isset($hub['rows'][0]['post_content']) && !isset($hub['rows'][0]['cj_urls'])
  && $hub['publication_authorized']===false && $hub['wordpress_writes']===0,
  'proof does not expose body or authorize write');
$GLOBALS['sources'][60341]->post_status='draft';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('buyer-hub')['rows'][0]['state']
  ==='BLOCKED_IDENTITY_OR_SOURCE','buyer hub drafts blocked');
$GLOBALS['sources'][60341]->post_status='publish';
$GLOBALS['sources'][60341]->post_name='wrong-article';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('buyer-hub')['rows'][0]['state']
  ==='BLOCKED_IDENTITY_OR_SOURCE','buyer hub slug drift blocked');
$GLOBALS['sources'][118455]=(object)array(
    'ID'=>118455, 'post_type'=>'post','post_status'=>'publish',
    'post_name'=>'nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz',
    'post_modified_gmt'=>'2026-03-19 20:04:31',
    'post_content'=>str_repeat('<p>Veřejně psaný rozpočtový průvodce sonary.</p>',300)
);
$echolot=NRY_Git_Publisher::buyer_source_proof_snapshot('echolot-budget');
must($echolot['publication_authorized']===false && $echolot['wordpress_writes']===0,
    'echolot hash does not grant write');
must(count($echolot['rows'])===1 && $echolot['rows'][0]['id']===118455,
    'echolot scope exact ID');
must($echolot['rows'][0]['source_sha256']===hash('sha256',
    $GLOBALS['sources'][118455]->post_content), 'echolot raw SHA');
must($echolot['rows'][0]['source_modified_gmt']==='2026-03-19 20:04:31',
    'echolot exact WordPress GMT');
must(!isset($echolot['rows'][0]['post_content'])
    && !isset($echolot['rows'][0]['rendered'])
    && !isset($echolot['rows'][0]['cj_urls']),
    'echolot raw HTML and commercial URLs withheld');
$GLOBALS['sources'][118455]->post_status='draft';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('echolot-budget')['rows'][0]['state']
    ==='BLOCKED_IDENTITY_OR_SOURCE', 'echolot draft rejected');
$GLOBALS['sources'][118455]->post_status='publish';
$GLOBALS['sources'][118455]->post_name='other-sonar-guide';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('echolot-budget')['rows'][0]['state']
    ==='BLOCKED_IDENTITY_OR_SOURCE', 'echolot redirect/slugs rejected');
$GLOBALS['sources'][118553]=(object)array(
  'ID'=>118553,'post_type'=>'post','post_status'=>'publish',
  'post_name'=>'jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce',
  'post_modified_gmt'=>'2026-10-22 06:33:24',
  'post_content'=>str_repeat('<p>Test bez vyzrazení zdrojového obsahu.</p>',400)
);
$pillar=NRY_Git_Publisher::buyer_source_proof_snapshot('echolot-pillar');
must(count($pillar['rows'])===1 && $pillar['rows'][0]['id']===118553,
  'pillar has only exact source ID');
must($pillar['rows'][0]['source_sha256']===hash('sha256',
  $GLOBALS['sources'][118553]->post_content),'pillar hashes actual WordPress raw source');
must($pillar['rows'][0]['source_modified_gmt']==='2026-10-22 06:33:24',
  'pillar source date is actual WP GMT');
must($pillar['wordpress_writes']===0 && $pillar['publication_authorized']===false
  && !isset($pillar['rows'][0]['post_content']) && !isset($pillar['rows'][0]['cj_urls']),
  'pillar proof returns metadata, no body, no affiliate routes and no write');
$GLOBALS['sources'][118553]->post_status='draft';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('echolot-pillar')['rows'][0]['state']
  ==='BLOCKED_IDENTITY_OR_SOURCE', 'pillar draft blocked');
$GLOBALS['sources'][118553]->post_status='publish';
$GLOBALS['sources'][118553]->post_name='unexpected-sonar-guide';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('echolot-pillar')['rows'][0]['state']
  ==='BLOCKED_IDENTITY_OR_SOURCE', 'pillar slug drift blocked');
$GLOBALS['sources'][118218]=(object)array(
  'ID'=>118218,'post_type'=>'post','post_status'=>'publish',
  'post_name'=>'nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu',
  'post_modified_gmt'=>'2026-10-23 06:34:54',
  'post_content'=>str_repeat('<p>Zdroj pro přesně vymezenou kontrolu nabídky.</p>',260)
);
$alarm=NRY_Git_Publisher::buyer_source_proof_snapshot('alarm-guide');
must(count($alarm['rows'])===1 && $alarm['rows'][0]['id']===118218,
  'exact alarm guide');
must($alarm['rows'][0]['source_sha256']===hash('sha256',
  $GLOBALS['sources'][118218]->post_content),'raw WP source SHA for alarm guide');
must($alarm['wordpress_writes']===0 && $alarm['publication_authorized']===false,
  'source proof never grants write authority');
must(!isset($alarm['rows'][0]['post_content']) && !isset($alarm['rows'][0]['hrefs']),
  'no private content or affiliate merchant URLs');
must(isset($alarm['rows'][0]['legacy_table_score_literal_counts']),
  'only six fixed public score count keys exposed');
must(array_keys($alarm['rows'][0]['legacy_table_score_literal_counts'])
   ===array('8,1 / 10','8,6 / 10','8,9 / 10','9,2 / 10','9,1 / 10','9,4 / 10'),
  'exact six documented numeric scores');
must(array_sum($alarm['rows'][0]['legacy_table_score_literal_counts'])===0,
  'zero matches when source fixture has no historical scores');
must($alarm['rows'][0]['buyer_warning_marker_count']===0
  && $alarm['rows'][0]['historical_score_warning_count']===0,
  'exact warning and section counts never forged');
$GLOBALS['sources'][118218]->post_content.='<p>8,1 / 10, 8,6 / 10, 8,9 / 10, 9,2 / 10, 9,1 / 10, 9,4 / 10</p>';
$withScores=NRY_Git_Publisher::buyer_source_proof_snapshot('alarm-guide');
must(array_sum($withScores['rows'][0]['legacy_table_score_literal_counts'])===6,
  'six exact score strings counted from actual raw source');
must($withScores['rows'][0]['source_sha256']===hash('sha256',
  $GLOBALS['sources'][118218]->post_content), 'source proof remains SHA correct');
must(!isset($withScores['rows'][0]['post_content'])
  && $withScores['wordpress_writes']===0,
  'no raw content leak and no write');
$GLOBALS['sources'][118218]->post_status='draft';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('alarm-guide')['rows'][0]['state']
  ==='BLOCKED_IDENTITY_OR_SOURCE','alarm guide draft rejected');
$GLOBALS['sources'][118218]->post_status='publish';
$GLOBALS['sources'][118218]->post_name='unknown-other';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('alarm-guide')['rows'][0]['state']
  ==='BLOCKED_IDENTITY_OR_SOURCE','alarm guide slug drift rejected');
echo "PASS: three allowlisted SHA/GMT proofs, no private body, no write, fail closed\n";
