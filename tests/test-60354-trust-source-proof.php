<?php
/** Fixed public-claim counts for existing ranking #60354; never a write. */
define('ABSPATH', __DIR__);
class WP_Error {public function __construct($code,$message='') {}}
function is_wp_error($x){return $x instanceof WP_Error;}
function add_action($hook,$cb,$priority=10) {}
$GLOBALS['sources']=array();
function get_post($id){return $GLOBALS['sources'][$id]??null;}
function get_permalink($id){$p=get_post($id);return $p?'https://na-ryby.eu/'.$p->post_name.'/':'';}
function must($condition,$reason){if(!$condition)throw new RuntimeException($reason);}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$slug='nejlepsi-rybarske-hlasice-a-signalizatory';
$visible='<p>Pokud jste vášnivý rybář, určitě oceníte naše produkty.</p>'
 .'<h2>Nejlepší poměr cena/výkon ⭐⭐⭐⭐⭐</h2>'
 .'<a href="https://www.tkqlhce.com/click?sid=legacy"><span>Top produkt na trhu</span></a>'
 .'<ul><li>100% vodotěsné</li><li>Voděodolnost zaručena</li></ul>'
 .'<p>Pojďme se společně podívat na ty nejlepší signalizátory, kterou jsou momentálně na našem trhu.</p>';
$body=$visible.str_repeat('<p>Další historický text.</p>',180);
$GLOBALS['sources'][60354]=(object)array('ID'=>60354,'post_type'=>'post',
 'post_status'=>'publish','post_name'=>$slug,
 'post_modified_gmt'=>'2026-10-23 06:35:08','post_content'=>$body);
$proof=NRY_Git_Publisher::buyer_source_proof_snapshot('alarm-ranking');
must($proof['publication_authorized']===false && $proof['wordpress_writes']===0,'GET never permits writes');
must(count($proof['rows'])===1,'exact single existing post');
$r=$proof['rows'][0];
must($r['id']===60354 && $r['slug']===$slug
 && $r['source_sha256']===hash('sha256',$body),'real raw fingerprint');
must(!isset($r['post_content']) && !isset($r['hrefs']) && !isset($r['merchant_urls']),
 'no raw content or link disclosures');
must($r['legacy_rank_raw_literal_counts']['five_stars']===1,
 'raw count exactly one');
must($r['legacy_rank_safe_plain_counts']['five_stars']===1,
 'visible five stars count');
must($r['legacy_rank_raw_literal_counts']['unproven_top_winner']===1
 && $r['legacy_rank_safe_plain_counts']['unproven_top_winner']===0,
 'anchor text is protected, not considered safe to change');
must($r['legacy_rank_safe_plain_counts']['unproven_waterproof']===1
 && $r['legacy_rank_safe_plain_counts']['false_shop_claim']===1,
 'visible static text recognized');
must($r['legacy_rank_safe_plain_counts']['four_stars']>=1,
 'overlapping four-star sequence is detected before any corrections');
$GLOBALS['sources'][60354]->post_status='draft';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('alarm-ranking')['rows'][0]['state']
  ==='BLOCKED_IDENTITY_OR_SOURCE','draft fails closed');
$GLOBALS['sources'][60354]->post_status='publish';
$GLOBALS['sources'][60354]->post_name='other-signalizatory';
must(NRY_Git_Publisher::buyer_source_proof_snapshot('alarm-ranking')['rows'][0]['state']
 ==='BLOCKED_IDENTITY_OR_SOURCE','slug drift fails closed');
must(NRY_Git_Publisher::buyer_source_proof_snapshot('random')['state']
 ==='BLOCKED_UNSUPPORTED_PROOF_SCOPE','no arbitrary source lookup');
echo "PASS #60354 only: SHA, exact raw/safe counts, links protected, no writes or body leak\n";
