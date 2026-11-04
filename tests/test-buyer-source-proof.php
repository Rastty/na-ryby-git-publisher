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
echo "PASS: three allowlisted SHA/GMT proofs, no private body, no write, fail closed\n";
