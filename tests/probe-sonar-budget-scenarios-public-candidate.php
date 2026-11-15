<?php
/* Anonymous public HTML preflight: never fetch raw WordPress post or write. */
define('ABSPATH','/');
function add_filter($name,$callback,$priority=10){}
require dirname(__DIR__).'/sonar-budget-scenario-jumps-118455-v1.php';
if ($argc!==2 || !is_file($argv[1])) exit(2);
$html=file_get_contents($argv[1]);
$class='NRY_Sonar_Budget_Scenario_Jumps_118455_V1';
$updated=$class::transform($html);
$ready=$updated!==$html && substr_count($updated,$class::MARKER)===1;
$headlineStats=[];
preg_match_all('~<h([234])\b[^>]*>.*?</h\1>~is',$html,$headings);
foreach (['Lowrance Eagle 4X se sondou Bullet (bonusy ověřte)',
          'Lowrance Hook Reveal 5, 50/200 HDI ROW',
          'Lowrance Eagle 7 se sondou SplitShot HD (balení ověřte)'] as $target) {
    $headlineStats[$target]=[];
    foreach ($headings[0] as $heading) {
        $label=trim(preg_replace('/[\s\x{00A0}\x{202F}]+/u',' ',
            html_entity_decode(strip_tags($heading),ENT_QUOTES|ENT_HTML5,'UTF-8')));
        if ($label===$target) $headlineStats[$target][]=substr($heading,0,3);
    }
}
preg_match_all('~<aside\b(?=[^>]*'.preg_quote($class::OWNER_MARKER,'~')
    .')[^>]*>.*?</aside>~is',$html,$asides);
foreach (['nry-budget-eagle4x','nry-budget-hook5','nry-budget-eagle7'] as $id) {
    $ready=$ready && substr_count($updated,'href="#'.$id.'"')===1
        && substr_count($updated,'id="'.$id.'"')===1;
}
echo json_encode(['source'=>'ANONYMOUS_LIVE_PUBLIC_HTML',
    'candidate_ready'=>$ready,'new_jump_links'=>$ready?3:0,
    'owner_marker_count'=>substr_count($html,$class::OWNER_MARKER),
    'trust_marker_count'=>substr_count($html,$class::TRUST_MARKER),
    'matching_owner_asides'=>count($asides[0]),
    'matched_heading_tags'=>$headlineStats,
    'h3_total'=>substr_count(strtolower($html),'<h3'),
    'merchant_requests'=>0,'wordpress_writes'=>0])."\n";
exit($ready?0:1);
