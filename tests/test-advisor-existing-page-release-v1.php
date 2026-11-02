<?php
error_reporting(E_ALL);
define('ABSPATH', __DIR__);
$fixtureDir=sys_get_temp_dir().'/nry-advisor-contract-'.bin2hex(random_bytes(5));
mkdir($fixtureDir,0700,true);
copy(__DIR__.'/../advisor-existing-page-release-v1.php',$fixtureDir.'/advisor-existing-page-release-v1.php');
$GLOBALS['options']=array();
$GLOBALS['post']=(object)array('ID'=>119865,'post_type'=>'page','post_status'=>'publish',
    'post_name'=>'poradce-vybavy','post_content'=>'',
    'post_modified_gmt'=>'2026-10-14 12:00:00');
$GLOBALS['mutations']=0;
function home_url($x){return 'https://na-ryby.eu/';}
function get_post($id){return $id===119865?clone $GLOBALS['post']:null;}
function get_permalink($id){return 'https://na-ryby.eu/poradce-vybavy/';}
function get_option($key,$default=false){return array_key_exists($key,$GLOBALS['options'])?$GLOBALS['options'][$key]:$default;}
function add_option($key,$value,$x='',$autoload=false){if(array_key_exists($key,$GLOBALS['options']))return false;$GLOBALS['options'][$key]=$value;return true;}
function update_option($key,$value,$autoload=false){$GLOBALS['options'][$key]=$value;return true;}
function delete_option($key){unset($GLOBALS['options'][$key]);return true;}
// Simulate core's wp_insert_post unslashing of wp_update_post(array) input.
function wp_slash($value){return is_string($value)?addslashes($value):$value;}
function wp_update_post($args,$error=false){$GLOBALS['mutations']++;$GLOBALS['post']->post_content=stripslashes($args['post_content']);$GLOBALS['post']->post_modified_gmt='2026-11-01 16:00:00';return $args['ID'];}
function is_wp_error($x){return false;}
$GLOBALS['can_edit']=true;
function current_user_can($cap,$id=null){return $GLOBALS['can_edit'];}
function add_action($x,$y){}
function ok($value,$label){if(!$value){fwrite(STDERR,"FAIL $label\n");exit(1);}}
require $fixtureDir.'/advisor-existing-page-release-v1.php';
$c='NRY_Advisor_Existing_Page_Release_V1';
ok($c::inspect()['reason']==='reviewed_manifest_or_candidate_not_staged','missing candidate blocks');
// Positive cross-contract check: accept precisely the guard produced by
// Prometheus PR #1207, while preserving the existing UI script byte-for-byte.
$scriptGate=new ReflectionMethod($c,'scripts_preserved_except_expiry_guard');
$scriptGate->setAccessible(true);
$expiryAnchor='    const fresh=Date.now()<Date.parse(PRICE_VISIBLE_UNTIL);';
$expiryGuard="\n    // nry-expired-offer-hard-stop-v1: stale evidence cannot authorize a merchant clickout."
    ."\n    if(!fresh)return {offers:[],status:'Konkrétní modely nyní nezobrazujeme: poslední ověření nabídek z '+PRICE_SNAPSHOT+' je starší než 7 dní. Výběr typu výbavy a kontrolní seznam zůstávají k dispozici; cenu, dostupnost a vhodnost konkrétního produktu ověřte u prodejce.'};";
$oldScripts='<script data-nry-engine="nry-advisor-live-v1">'.$expiryAnchor.'</script>'.
    '<script data-nry-ui="nry-advisor-live-v1">const ui=1;</script>';
$newScripts=str_replace($expiryAnchor,$expiryAnchor.$expiryGuard,$oldScripts);
ok($scriptGate->invoke(null,$oldScripts,$newScripts)===true,
    'exact known V4 guard insertion accepted');
ok($scriptGate->invoke(null,$oldScripts,str_replace('const ui=1;','const ui=2;',$newScripts))===false,
    'modified existing UI script rejected');
ok($scriptGate->invoke(null,$oldScripts,str_replace('offers:[]','offers:[1]',$newScripts))===false,
    'tampered expiry guard rejected');

$original='<!-- wp:html --><div data-nry-affiliate-disclosure="advisor-v3" data-nry-results>'.
    'https://na-ryby.eu/click-8287231-15734253</div><!-- nry-advisor-live-v1 -->'.
    '<script data-nry-engine="nry-advisor-live-v1">const PRICE_VISIBLE_UNTIL="2026-10-21";'."\n".$expiryAnchor.'</script>'.
    '<span data-link="/click-8287231-15734253?url=exact">tracked</span>'.
    '<code data-test-regex="\\d+">JS escape must survive WordPress</code>'.
    str_repeat('Source guidance ',700);
$candidate=str_replace($expiryAnchor,$expiryAnchor.$expiryGuard,$original);
$GLOBALS['post']->post_content=$original;
ok(strpos($original,'\\d+')!==false,'fixture contains literal JS backslash for slashing regression');
// Read-only exact source export preflight works WITHOUT a staged manifest,
// never changes WordPress, and refuses unauthorized or mismatched pages.
$source=$c::source_snapshot();
ok($source['status']==='ready','admin-only source snapshot available');
ok($source['content']===$original,'source export preserves exact post_content bytes');
ok($source['source_sha256']===hash('sha256',$original),'source export SHA matches bytes');
ok($source['source_modified_gmt']==='2026-10-14 12:00:00','source modified timestamp exact');
ok($source['source_bytes']===strlen($original),'source byte length exact');
ok($source['cj_route_count']===2,'source CJ route occurrence count explicit');
ok($GLOBALS['mutations']===0,'source capture performs no WordPress writes');
$GLOBALS['can_edit']=false;
ok($c::source_snapshot()['reason']==='missing_source_capture_capability',
    'unprivileged source capture refused');
$GLOBALS['can_edit']=true;
$GLOBALS['post']->post_name='wrong-slug';
ok($c::source_snapshot()['reason']==='page_identity','wrong page identity blocks source export');
$GLOBALS['post']->post_name='poradce-vybavy';
$assetDir=$fixtureDir.'/assets';mkdir($assetDir);
$manifest=array('schema'=>1,'page_id'=>119865,'slug'=>'poradce-vybavy',
    'source_sha256'=>hash('sha256',$original),'candidate_sha256'=>hash('sha256',$candidate),
    'source_modified_gmt'=>'2026-10-14 12:00:00','approval'=>'admin_apply_required',
    'offer_safety'=>array('candidate_sha256'=>hash('sha256',$candidate),
      'scenarios_tested'=>108,'expired_offer_clickouts'=>0,
      'tested_at_utc'=>gmdate('Y-m-d\TH:i:s\Z'),
      'evidence_url'=>'https://github.com/Rastty/prometheus-growth-os/actions/runs/123456'));
file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
file_put_contents($assetDir.'/advisor-existing-page-v1.html',$candidate);
ok($c::inspect()['status']==='ready','source/candidate exact SHA ready');
// A corrupt or plugin-modified backup option must fail closed rather than
// throwing PHP 8 TypeError in hash() or risking an unreviewed restoration.
$GLOBALS['options'][$c::BACKUP]=array(
    'page_id'=>119865,
    'candidate_sha256'=>hash('sha256',$original),
    'source_sha256'=>hash('sha256',$original),
    'source_content'=>array('not'=>'valid HTML'));
ok($c::inspect()['reason']==='immutable_backup_conflict',
    'malformed stored backup blocks inspect without fatal');
$GLOBALS['post']->post_content=$candidate;
ok($c::rollback()['reason']==='backup_or_live_content_drift',
    'malformed stored backup blocks rollback without PHP TypeError');
ok($GLOBALS['mutations']===0,'corrupt backup cannot cause WordPress writes');
$GLOBALS['post']->post_content=$original;
unset($GLOBALS['options'][$c::BACKUP]);
ok($c::inspect()['status']==='ready','valid state recovered after test cleanup');
// Malformed, relative, offset and impossible receipt dates must fail closed.
// In particular an array must never cause a PHP 8 TypeError in strtotime().
foreach (array(
    array('now','relative timestamp'),
    array(array('unexpected'=>'type'),'JSON array timestamp'),
    array(gmdate('Y-m-d\TH:i:sP'),'non-UTC-offset timestamp'),
    array('2026-02-30T00:00:00Z','impossible calendar date')
) as $bad) {
    $manifest['offer_safety']['tested_at_utc']=$bad[0];
    file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
    ok($c::inspect()['reason']==='fresh_candidate_bound_108_case_receipt_required',
        $bad[1].' blocked without PHP fatal');
}
$manifest['offer_safety']['tested_at_utc']=gmdate('Y-m-d\TH:i:s\Z');
file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
ok($c::inspect()['status']==='ready','valid strict UTC receipt still accepted');
$manifest['offer_safety']['expired_offer_clickouts']=1;
file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
ok($c::inspect()['reason']==='fresh_candidate_bound_108_case_receipt_required','expired offer exposure blocks');
$manifest['offer_safety']['expired_offer_clickouts']=0;
$manifest['offer_safety']['tested_at_utc']='2026-10-01T00:00:00Z';
file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
ok($c::inspect()['reason']==='fresh_candidate_bound_108_case_receipt_required','stale receipt blocks');
$manifest['offer_safety']['tested_at_utc']=gmdate('Y-m-d\TH:i:s\Z');
file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
$GLOBALS['post']->post_modified_gmt='2026-10-14 12:00:01';
ok($c::inspect()['reason']==='source_sha_or_modified_drift','modified drift blocks');
$GLOBALS['post']->post_modified_gmt='2026-10-14 12:00:00';
file_put_contents($assetDir.'/advisor-existing-page-v1.html',str_replace('click-8287231','click-9999999',$candidate));
ok($c::inspect()['reason']==='candidate_hash_or_structure','tampered candidate blocks');
$modified=str_replace('click-8287231','click-9999999',$candidate);
$manifest['candidate_sha256']=hash('sha256',$modified);
$manifest['offer_safety']['candidate_sha256']=hash('sha256',$modified);
file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
ok($c::inspect()['reason']==='external_destination_drift','tracking destination drift blocks');
// Relative CJ route changes must also be refused even when absolute URLs match.
$relativeChanged=str_replace('?url=exact','?url=wrong',$candidate);
file_put_contents($assetDir.'/advisor-existing-page-v1.html',$relativeChanged);
$manifest['candidate_sha256']=hash('sha256',$relativeChanged);
$manifest['offer_safety']['candidate_sha256']=hash('sha256',$relativeChanged);
file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
ok($c::inspect()['reason']==='cj_route_or_property_drift','relative CJ route drift blocked');
// A local script src with whitespace bypassed the old literal check while
// preserving all tracked URLs. The exact-candidate manifest alone is insufficient.
foreach (array(
    array('<script data-extra="x" src = "/unreviewed.js"></script>','relative script src'),
    array('<a href="javascript:alert(1)">unsafe</a>','javascript href'),
    array('<a href="jav&#x61;script:alert(1)">unsafe</a>','entity-encoded javascript href'),
    array('<img src="/image.png" onerror="alert(1)">','event handler on image'),
    array('<div onclick="alert(1)">unsafe</div>','event handler on div'),
    array('<iframe srcdoc="<script>alert(1)</script>"></iframe>','embedded iframe document'),
    array('<form action="/submit"><input name="email"></form>','unreviewed form submission'),
    array('<svg onload="alert(1)"></svg>','inline svg execution'),
    array('<img src="data:text/html,unsafe">','data URI image source'),
    array('<a href="java&#10;script:alert(1)">unsafe</a>','entity-encoded newline in javascript scheme'),
    array('<a href="java&#x09;script:alert(1)">unsafe</a>','entity-encoded tab in javascript scheme'),
    array("<a href=\"java\nscript:alert(1)\">unsafe</a>",'literal newline in javascript scheme'),
    array('<img srcset="data:text/html,unsafe">','data URI srcset'),
    array('<a href="vbscript:msgbox(1)">unsafe</a>','vbscript scheme'),
    array('<video poster="da&#x09;ta:text/html,unsafe"></video>','obfuscated data poster'),
    array('<button formaction="javascript:alert(1)">go</button>','javascript form action')
) as $case) {
    $unsafe=$candidate.$case[0];
    file_put_contents($assetDir.'/advisor-existing-page-v1.html',$unsafe);
    $manifest['candidate_sha256']=hash('sha256',$unsafe);
    $manifest['offer_safety']['candidate_sha256']=hash('sha256',$unsafe);
    file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
    ok($c::inspect()['reason']==='candidate_hash_or_structure',$case[1].' blocked before WP write');
}
// An encoded external scheme or protocol-relative URL is invisible to the
// legacy literal https?:// extractor; reject new outbound HTML destinations.
foreach (array(
    '<a href="//unreviewed.example/offer">unreviewed</a>',
    '<a href="&#47;&#47;unreviewed.example/offer">encoded slashes</a>',
    '<a href="https&#58;//unreviewed.example/offer">encoded scheme</a>',
    '<img srcset="//unreviewed.example/pic.png 1x">',
    '<video poster="https&#58;//unreviewed.example/cover.png"></video>'
) as $newDestination) {
    $unsafe=$candidate.$newDestination;
    file_put_contents($assetDir.'/advisor-existing-page-v1.html',$unsafe);
    $manifest['candidate_sha256']=hash('sha256',$unsafe);
    $manifest['offer_safety']['candidate_sha256']=hash('sha256',$unsafe);
    file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
    ok($c::inspect()['reason']==='external_attribute_destination_drift',
        'new encoded/protocol-relative outbound destination blocked');
}
// A reviewed candidate cannot add a third inline script or mutate the
// existing UI/analytics script while preserving URL/CJ counts and valid SHAs.
foreach (array(
    array($candidate.'<script>window.__unreviewed=1;</script>','new inline script'),
    array(str_replace('const PRICE_VISIBLE_UNTIL=', 'window.__unreviewed=1;const PRICE_VISIBLE_UNTIL=', $candidate),
        'existing inline script modified')
) as $case) {
    $unsafe=$case[0];
    file_put_contents($assetDir.'/advisor-existing-page-v1.html',$unsafe);
    $manifest['candidate_sha256']=hash('sha256',$unsafe);
    $manifest['offer_safety']['candidate_sha256']=hash('sha256',$unsafe);
    file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
    ok($c::inspect()['reason']==='unreviewed_inline_script_change',
        $case[1].' rejected before WordPress write');
}
// Even if the browser receipt and SHA are reissued, an expiry-only release
// must not silently change helpful prose, markup or noncommercial CTAs.
foreach (array(
    array(str_replace('Source guidance ','Changed guidance ',$candidate),'editorial prose drift'),
    array($candidate.'<p>Unreviewed promotional claim</p>','added non-script markup'),
    array(str_replace('data-nry-results','data-nry-results data-unreviewed="yes"',$candidate),
        'existing DOM attribute drift')
) as $case) {
    $unsafe=$case[0];
    file_put_contents($assetDir.'/advisor-existing-page-v1.html',$unsafe);
    $manifest['candidate_sha256']=hash('sha256',$unsafe);
    $manifest['offer_safety']['candidate_sha256']=hash('sha256',$unsafe);
    file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
    ok($c::inspect()['reason']==='unreviewed_inline_script_change',
        $case[1].' blocked before WordPress write');
}
file_put_contents($assetDir.'/advisor-existing-page-v1.html',$candidate);
$manifest['candidate_sha256']=hash('sha256',$candidate);
$manifest['offer_safety']['candidate_sha256']=hash('sha256',$candidate);
file_put_contents($fixtureDir.'/advisor-existing-page-v1.json',json_encode($manifest));
$GLOBALS['can_edit']=false;
ok($c::execute()['reason']==='missing_unfiltered_editor_capability','unprivileged apply blocked');
ok($GLOBALS['mutations']===0,'no unauthorized WordPress writes');
$GLOBALS['can_edit']=true;
add_option($c::LOCK,'other-admin');
ok($c::execute()['reason']==='writer_lock','concurrent writer blocks');
ok($GLOBALS['mutations']===0,'zero pre-approval writes');
delete_option($c::LOCK);
$receipt=$c::execute();
ok($receipt['status']==='applied_pending_independent_public_readback','local readback never claims deployed');
ok($GLOBALS['mutations']===1,'one bounded WordPress write');
ok($GLOBALS['post']->post_content===$candidate,'WordPress-style unslash retains exact candidate bytes');
ok($c::inspect()['status']==='already_applied_pending_public_readback','applied state detected');
ok($c::execute()['status']==='blocked','repeat apply blocks');
ok($GLOBALS['mutations']===1,'no duplicate writes');
$GLOBALS['post']->post_content.=' concurrent editor';
ok($c::rollback()['reason']==='backup_or_live_content_drift','concurrent editor protected');
ok($GLOBALS['mutations']===1,'no overwrite on drift');
$GLOBALS['post']->post_content=$candidate;
ok($c::rollback()['status']==='rolled_back_local_verified','rollback verified');
ok($GLOBALS['post']->post_content===$original,'original restored exactly including backslashes');
ok($GLOBALS['mutations']===2,'one rollback write');
ok($c::inspect()['reason']==='immutable_backup_conflict','backup immutable after rollback');
unlink($fixtureDir.'/advisor-existing-page-v1.json');
unlink($assetDir.'/advisor-existing-page-v1.html');rmdir($assetDir);
unlink($fixtureDir.'/advisor-existing-page-release-v1.php');rmdir($fixtureDir);
echo "PASS: exact existing advisor SHA, 108-case safety receipt, URL preservation, lock, local readback and rollback\n";
