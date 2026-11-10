<?php
/* GET-only public REST HTML probe, never a WordPress raw source or write. */
define('ABSPATH','/');
function add_filter($name,$fn,$p=10){}
require dirname(__DIR__).'/sonar-budget-owner-first-118455-v1.php';
if ($argc!==2 || !is_file($argv[1])) exit(2);
$html=file_get_contents($argv[1]);
$c='NRY_Budget_Owner_First_118455_V1';
$updated=$c::transform($html);
$result=[
 'scope'=>'PUBLIC_REST_RENDERED_NOT_RAW_WP_SOURCE',
 'changed'=>$updated!==$html,
 'old_marker_count'=>substr_count($html,$c::TRUST),
 'new_marker_count'=>substr_count($updated,$c::MARKER),
 'original_first_intro_count'=>substr_count($html,$c::INTRO),
 'original_owner_href_count'=>substr_count($html,$c::PRACTICAL),
 'new_owner_href_count'=>substr_count($updated,$c::PRACTICAL),
 'merchant_requests'=>0,'wordpress_writes'=>0,
];
echo json_encode($result,JSON_UNESCAPED_UNICODE)."\n";
$candidateReady=$result['changed'] && $result['old_marker_count']===1
    && $result['new_marker_count']===1 && $result['original_owner_href_count']===0
    && $result['new_owner_href_count']===1;
$alreadyPresent=!$result['changed'] && $result['old_marker_count']===1
    && $result['new_marker_count']===1 && $result['original_owner_href_count']===1
    && $result['new_owner_href_count']===1;
echo 'state='.($candidateReady?'CANDIDATE_READY':($alreadyPresent?'ALREADY_VISIBLE_REQUIRES_PUBLIC_PROOF':'BLOCKED'))."\n";
exit($candidateReady||$alreadyPresent?0:1);
