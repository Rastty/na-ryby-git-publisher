<?php
// Standalone read-only WordPress source-proof contract; raw content never printed.
define('ABSPATH', __DIR__);
$GLOBALS['sources'] = array();
$GLOBALS['meta'] = array();
function get_post_meta($id,$key,$single=true) {
    return $GLOBALS['meta'][$id][$key]??'';
}
function metadata_exists($type,$id,$key) {
    return $type==='post' && array_key_exists($key,$GLOBALS['meta'][$id]??array());
}
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
foreach(array(
  'echolot-boat'=>array(60637,'jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare'),
  'echolot-historic'=>array(36518,'porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023'),
) as $scope=>$idSlug) {
    list($id,$slug)=$idSlug;
    $GLOBALS['sources'][$id]=(object)array(
      'ID'=>$id,'post_type'=>'post','post_status'=>'publish',
      'post_name'=>$slug,'post_modified_gmt'=>'2026-10-22 12:48:00',
      'post_content'=>str_repeat('<p>Historický průvodce výběrem echolotu.</p>',150),
    );
    $proof=NRY_Git_Publisher::buyer_source_proof_snapshot($scope);
    must($proof['publication_authorized']===false&&$proof['wordpress_writes']===0,
       $scope.' metadata scope never writes WordPress');
    must(count($proof['rows'])===1&&$proof['rows'][0]['id']===$id,
       $scope.' only exact fixed post');
    must($proof['rows'][0]['source_sha256']===hash('sha256',$GLOBALS['sources'][$id]->post_content),
       $scope.' source SHA exact raw content');
    must($proof['rows'][0]['source_modified_gmt']==='2026-10-22 12:48:00',
       $scope.' exact modified GMT');
    must(!isset($proof['rows'][0]['post_content'])&&!isset($proof['rows'][0]['rendered'])
      &&!isset($proof['rows'][0]['cj_urls']),
      $scope.' no editable raw source, merchant URL or user data released');
    $GLOBALS['sources'][$id]->post_status='draft';
    must($proof['rows'][0]['state']==='SOURCE_SHA_AVAILABLE_NOT_PUBLICATION_AUTHORITY',
      $scope.' published row original');
    $blocked=NRY_Git_Publisher::buyer_source_proof_snapshot($scope);
    must($blocked['rows'][0]['state']==='BLOCKED_IDENTITY_OR_SOURCE',
       $scope.' draft cannot be claimed public');
    $GLOBALS['sources'][$id]->post_status='publish';
    $GLOBALS['sources'][$id]->post_name='arbitrary-article';
    $blocked=NRY_Git_Publisher::buyer_source_proof_snapshot($scope);
    must($blocked['rows'][0]['state']==='BLOCKED_IDENTITY_OR_SOURCE',
       $scope.' slug drift cannot be claimed public');
    $GLOBALS['sources'][$id]->post_name=$slug;
}
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
$GLOBALS['sources'][118455]->post_content='<p>v době přípravy článku skladem za 4 509 Kč</p>'
 .'<li>aktuálně 10 542 Kč a skladem 1 ks</li>'
 .'<p>v době přípravy článku skladem za 13 889 Kč</p>'
 .str_repeat('<p>Rybářský nákupní průvodce.</p>',250);
$prices=NRY_Git_Publisher::buyer_source_proof_snapshot('echolot-budget');
must(count($prices['rows'])===1,'exact budget post remains scoped');
must($prices['rows'][0]['source_sha256']===hash('sha256',$GLOBALS['sources'][118455]->post_content),
 'price fixture hashes full raw WordPress source');
must($prices['rows'][0]['historical_offer_raw_counts']===
 array('eagle4x_old_offer'=>1,'hook5_old_offer'=>1,'eagle7_old_offer'=>1),
 'three exact historic price + stock statements counted');
must($prices['rows'][0]['historical_offer_safe_plain_counts']===
 array('eagle4x_old_offer'=>1,'hook5_old_offer'=>1,'eagle7_old_offer'=>1),
 'three statements visible safely outside protected href or attributes');
must(!isset($prices['rows'][0]['post_content'])
  && !isset($prices['rows'][0]['hrefs'])
  && $prices['wordpress_writes']===0 && $prices['publication_authorized']===false,
  'never expose post body or grant edits');
$GLOBALS['sources'][118455]->post_content='<a href="https://na-ryby.eu/?q='
  .'aktuálně 10 542 Kč a skladem 1 ks'.'">See offer</a>'
  .str_repeat('<p>Historický průvodce.</p>',250);
$guarded=NRY_Git_Publisher::buyer_source_proof_snapshot('echolot-budget');
must($guarded['rows'][0]['historical_offer_raw_counts']['hook5_old_offer']===1
  && $guarded['rows'][0]['historical_offer_safe_plain_counts']['hook5_old_offer']===0,
  'text inside anchor attribute cannot be treated as safe visible prose');
$GLOBALS['sources'][118455]->post_content=
    '<p>Nízká cena, GPS, jednoduchost, bonus baterie.</p>'
    .'<li>baterie zdarma v balení</li>'
    .'<p>Chyť a pusť tu má rozumnou aktuální nabídku včetně baterie a nabíječky.</p>'
    .'<p>V Chyť a pusť aktuálně dává větší smysl i díky přibalené baterii a nabíječce.</p>'
    .'<h3>Lowrance Eagle 4X se sondou Bullet + baterie ZDARMA</h3>'
    .'<p>Lowrance Eagle 4X se sondou Bullet + baterie ZDARMA</p>'
    .'<h3>Lowrance Eagle 7 se sondou SplitShot HD + baterie + nabíječka ZDARMA</h3>'
    .'<p>Lowrance Eagle 7 se sondou SplitShot HD + baterie + nabíječka ZDARMA</p>'
    .str_repeat('<p>Praktický výběr echolotu.</p>',250);
$claimProof=NRY_Git_Publisher::buyer_source_proof_snapshot('echolot-budget');
$expectedClaims=array(
    'eagle4x_false_gps'=>1,'eagle4x_battery_assertion'=>1,
    'eagle7_current_bundle'=>1,'eagle7_current_cta'=>1,
    'eagle4x_bonus_title'=>2,'eagle7_bonus_title'=>2,
);
must($claimProof['rows'][0]['fixed_offer_claim_raw_counts']===$expectedClaims,
     'fixed six buyer claims counted in raw source');
must($claimProof['rows'][0]['fixed_offer_claim_safe_plain_counts']===$expectedClaims,
     'only plain readable text counted as safe');
must($claimProof['wordpress_writes']===0
    && $claimProof['publication_authorized']===false
    && !isset($claimProof['rows'][0]['post_content'])
    && !isset($claimProof['rows'][0]['hrefs']),
    'six claim audit cannot expose private HTML or publish');
$GLOBALS['sources'][118455]->post_content=
    '<a href="https://merchant.invalid/?q=Nízká cena, GPS, jednoduchost, bonus baterie.">Shop</a>'
    .str_repeat('<p>Praktický výběr echolotu.</p>',250);
$attributeProof=NRY_Git_Publisher::buyer_source_proof_snapshot('echolot-budget');
must($attributeProof['rows'][0]['fixed_offer_claim_raw_counts']['eagle4x_false_gps']===1
    && $attributeProof['rows'][0]['fixed_offer_claim_safe_plain_counts']['eagle4x_false_gps']===0,
    'untrusted merchant URL attribute not eligible for prose editing');
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
$GLOBALS['sources'][35755]=(object)array(
  'ID'=>35755,'post_type'=>'post','post_status'=>'publish',
  'post_name'=>'pipaky-na-ryby-prakticke-rady-pro-zacatecniky',
  'post_modified_gmt'=>'2026-10-23 07:09:00',
  'post_content'=>str_repeat('<p>Ověřený praktický obsah pro začátečníky.</p>',300)
);
$GLOBALS['meta'][35755]=array(
  '_yoast_wpseo_title'=>'Pípáky na ryby: praktické rady pro začátečníky',
  '_yoast_wpseo_metadesc'=>'Pípáky pro začátečníky, praktické rady.'
);
$beginner=NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-beginner');
must(count($beginner['rows'])===1&&$beginner['rows'][0]['id']===35755,
    'only exact beginner article source');
$b=$beginner['rows'][0];
must($b['state']==='SOURCE_SHA_AVAILABLE_NOT_PUBLICATION_AUTHORITY'
  && $b['source_sha256']===hash('sha256',$GLOBALS['sources'][35755]->post_content),
  'exact raw WordPress content fingerprint');
must($b['raw_yoast_title_sha256']===hash('sha256',$GLOBALS['meta'][35755]['_yoast_wpseo_title'])
  && $b['raw_yoast_description_sha256']===hash('sha256',$GLOBALS['meta'][35755]['_yoast_wpseo_metadesc']),
  'only metadata hashes, never raw metatags');
must($b['raw_yoast_title_exists']===true
  && $b['raw_yoast_description_exists']===true,
  'detect actual raw Yoast field existence');
must(!isset($b['post_content'])&&!isset($b['yoast_title'])&&!isset($b['yoast_description'])
  && $beginner['wordpress_writes']===0&&$beginner['publication_authorized']===false,
  'no source leakage or authorization');
$GLOBALS['sources'][35755]->post_status='draft';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-beginner')['rows'][0]['state']
  ==='BLOCKED_IDENTITY_OR_SOURCE','draft blocked');
$GLOBALS['sources'][35755]->post_status='publish';
$GLOBALS['sources'][35755]->post_name='wrong-slug';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-beginner')['rows'][0]['state']
  ==='BLOCKED_IDENTITY_OR_SOURCE','wrong slug blocked');
$GLOBALS['sources'][35755]->post_name='pipaky-na-ryby-prakticke-rady-pro-zacatecniky';
$GLOBALS['meta'][35755]['_yoast_wpseo_title']=array('bad');
must(NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-beginner')['rows'][0]['state']
  ==='BLOCKED_NON_STRING_SEO_METADATA','invalid Yoast meta blocked');
$GLOBALS['sources'][36702]=(object)array(
 'ID'=>36702,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>'pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu',
 'post_modified_gmt'=>'2026-10-23 06:35:08',
 'post_content'=>str_repeat('<p>Historické srovnání pípáků vyžaduje kontrolu.</p>',140)
);
$GLOBALS['meta'][36702]=array(
 '_yoast_wpseo_title'=>'Pípáky na ryby pro profesionály: přehled nejlepších značek a modelů',
 '_yoast_wpseo_metadesc'=>'Původní meta popis neověřených tvrzení'
);
$professional=NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-professional');
must(count($professional['rows'])===1 && $professional['rows'][0]['id']===36702,
 'professional proof is single fixed post');
$p=$professional['rows'][0];
must($p['state']==='SOURCE_SHA_AVAILABLE_NOT_PUBLICATION_AUTHORITY'
 && $p['source_sha256']===hash('sha256',$GLOBALS['sources'][36702]->post_content)
 && $p['source_modified_gmt']==='2026-10-23 06:35:08',
 'professional proof uses exact raw WordPress SHA and date');
must($p['raw_yoast_title_sha256']===hash('sha256',$GLOBALS['meta'][36702]['_yoast_wpseo_title'])
 && $p['raw_yoast_description_sha256']===hash('sha256',$GLOBALS['meta'][36702]['_yoast_wpseo_metadesc'])
 && $p['raw_yoast_title_exists']===true && $p['raw_yoast_description_exists']===true,
 'only source-bound SEO hashes, no title or description disclosure');
must(!isset($p['post_content'],$p['yoast_title'],$p['yoast_description'],$p['hrefs'])
 && $professional['wordpress_writes']===0
 && $professional['publication_authorized']===false
 && $professional['gsc_protection_checked']===false,'proof must not leak or authorize');
// All five public price examples must be counted without disclosing raw HTML
// or any external/tracking href. A price in an HTML attribute is NOT safe.
$old_professional_source=$GLOBALS['sources'][36702]->post_content;
$price_strings=array('Cena: od 1000 Kč','Cena: od 800 Kč',
    'Cena: od 3000 Kč','Cena: od 1200 Kč','Cena: od 2000 Kč');
$GLOBALS['sources'][36702]->post_content=str_repeat('<p>Zdrojový obsah.</p>',85)
    .'<p>'.implode('</p><p>',$price_strings).'</p>'
    .'<a href="https://example.invalid/" title="Cena: od 1000 Kč">Neověřený odkaz</a>'
    .'<a href="https://rybarske-potreby-na-zaclony.cz/">Odkaz</a>'
    .'<a href="https://rybarske-potreby-na-lukavecu.cz/">Odkaz</a>';
$prof_prices=NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-professional');
$pr=$prof_prices['rows'][0];
must($pr['legacy_price_raw_literal_counts']===array(
    1000=>2,800=>1,3000=>1,1200=>1,2000=>1),
    'counts include unsafe HTML-attribute price occurrences');
must($pr['legacy_price_safe_plain_counts']===array(
    1000=>1,800=>1,3000=>1,1200=>1,2000=>1),
    'safe plain-text counts omit the price in href-adjacent markup');
must($pr['legacy_external_host_literal_counts']===array(
    'zaclony'=>1,'lukavecu'=>1),'legacy external host counts only');
must($prof_prices['publication_authorized']===false
    &&$prof_prices['wordpress_writes']===0
    &&!isset($pr['post_content'],$pr['hrefs'],$pr['links']),
    'no source body/links nor publication authority in diagnostics');
$GLOBALS['sources'][36702]->post_content=$old_professional_source;
$GLOBALS['sources'][36702]->post_status='draft';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-professional')['rows'][0]['state']
 ==='BLOCKED_IDENTITY_OR_SOURCE','draft professional cannot disclose hash');
$GLOBALS['sources'][36702]->post_status='publish';
$GLOBALS['sources'][36702]->post_name='unapproved-other-post';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-professional')['rows'][0]['state']
 ==='BLOCKED_IDENTITY_OR_SOURCE','wrong professional slug fails closed');
$GLOBALS['sources'][36702]->post_name='pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu';
$GLOBALS['meta'][36702]['_yoast_wpseo_title']=array('invalid');
must(NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-professional')['rows'][0]['state']
 ==='BLOCKED_NON_STRING_SEO_METADATA','non-string professional SEO meta fails closed');
$GLOBALS['sources'][42401]=(object)array(
 'ID'=>42401,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>'jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne',
 'post_modified_gmt'=>'2026-10-23 06:34:54',
 'post_content'=>str_repeat('<p>Ověřený návod na použití hlásiče.</p>',330)
);
$GLOBALS['meta'][42401]=array(
 '_yoast_wpseo_title'=>'Pípáky na ryby: jak vybrat, nastavit a používat',
 '_yoast_wpseo_metadesc'=>'Pípáky na ryby jednoduše a bezpečně.'
);
$main=NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-main');
must(count($main['rows'])===1 && $main['rows'][0]['id']===42401
 && $main['rows'][0]['state']==='SOURCE_SHA_AVAILABLE_NOT_PUBLICATION_AUTHORITY'
 && $main['rows'][0]['source_sha256']===
   hash('sha256',$GLOBALS['sources'][42401]->post_content)
 && $main['rows'][0]['source_modified_gmt']==='2026-10-23 06:34:54',
 'one static live main alarm guide SHA and source date');
must($main['rows'][0]['raw_yoast_title_sha256']===
   hash('sha256',$GLOBALS['meta'][42401]['_yoast_wpseo_title'])
 && $main['rows'][0]['raw_yoast_description_sha256']===
   hash('sha256',$GLOBALS['meta'][42401]['_yoast_wpseo_metadesc'])
 && $main['rows'][0]['raw_yoast_title_exists']===true
 && $main['rows'][0]['raw_yoast_description_exists']===true,
 'main SEO title/meta hashed only, values not exposed');
must($main['wordpress_writes']===0 && $main['publication_authorized']===false
 && !isset($main['rows'][0]['post_content'],$main['rows'][0]['hrefs'],
    $main['rows'][0]['yoast_title'],$main['rows'][0]['yoast_description']),
 'main article proof never exposes source or any merchant links');
$GLOBALS['sources'][42401]->post_status='draft';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-main')['rows'][0]['state']
 ==='BLOCKED_IDENTITY_OR_SOURCE','draft #42401 blocked');
$GLOBALS['sources'][42401]->post_status='publish';
$GLOBALS['sources'][42401]->post_name='wrong-slug';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-main')['rows'][0]['state']
 ==='BLOCKED_IDENTITY_OR_SOURCE','wrong main article slug blocked');
$GLOBALS['sources'][42401]->post_name=
 'jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne';
$GLOBALS['meta'][42401]['_yoast_wpseo_title']=array('unexpected');
must(NRY_Git_Publisher::buyer_source_proof_snapshot('pipaky-main')['rows'][0]['state']
 ==='BLOCKED_NON_STRING_SEO_METADATA','wrong SEO metadata type blocked');
echo "PASS: bounded source and Yoast hashes for beginner, no private body, zero writes, fail closed\n";
