<?php
/** PR preflight against anonymous live rendered REST and raw-source HASH proof. */
define('ABSPATH', __DIR__);
require dirname(__DIR__) . '/chair-hub-historical-ratings-60341-v1.php';
function fail_probe($reason) { fwrite(STDERR, "BLOCKED: $reason\n"); exit(1); }
function get_json_probe($url) {
    $context=stream_context_create(array('http'=>array('timeout'=>25,
        'method'=>'GET','header'=>"User-Agent: NRY-ChairHub-Historical-PR-Preflight/1.0\r\nCache-Control: no-cache, no-store\r\n")));
    $raw=@file_get_contents($url,false,$context);
    if (!is_string($raw) || strlen($raw)>750000) fail_probe('PUBLIC_JSON_UNAVAILABLE');
    $obj=json_decode($raw,true);
    if (!is_array($obj)) fail_probe('PUBLIC_JSON_INVALID');
    return $obj;
}
$cls='NRY_Chair_Hub_Historical_Ratings_60341_V1';
$post=get_json_probe('https://na-ryby.eu/wp-json/wp/v2/posts/60341?_fields=id,slug,status,modified_gmt,content.rendered');
if (($post['id']??null)!==60341 || ($post['slug']??'')!==$cls::SLUG
    || ($post['status']??'')!=='publish'
    || ($post['modified_gmt']??'')!=='2026-11-04T10:06:35') fail_probe('PUBLIC_POST_IDENTITY_DRIFT');
$proof=get_json_probe('https://na-ryby.eu/poradce-vybavy/?nry_buyer_hub_source_proof=1');
$row=$proof['rows'][0]??array();
if (($row['id']??null)!==60341
    || ($row['source_sha256']??'')!==$cls::SOURCE_SHA
    || ($row['source_bytes']??null)!==$cls::SOURCE_BYTES
    || ($row['source_modified_gmt']??'')!==$cls::MODIFIED_GMT)
    fail_probe('PINNED_PRIVATE_SOURCE_PROOF_DRIFT');
$html=$post['content']['rendered']??null;
if (!is_string($html) || strlen($html)<15000) fail_probe('PUBLIC_CONTENT_UNAVAILABLE');
$repaired=$cls::transform($html);
if ($repaired===$html || substr_count($repaired,$cls::MARKER)!==1
    || strpos($repaired,'klíčová slova:')!==false
    || strpos($repaired,'⭐')!==false) fail_probe('CANDIDATE_REVIEWED_SPANS_NOT_EXACT');
echo json_encode(array('state'=>'LIVE_REST_SOURCE_PIN_AND_CANDIDATE_VERIFIED',
    'postId'=>60341,'unsupported_star_labels_removed'=>6,
    'keyword_list_removed'=>1,'original_merchant_hrefs_preserved'=>true,
    'wordpressWrites'=>0,'merchantRequests'=>0),JSON_UNESCAPED_UNICODE)."\n";
