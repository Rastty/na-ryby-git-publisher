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
foreach (['nry-budget-eagle4x','nry-budget-hook5','nry-budget-eagle7'] as $id) {
    $ready=$ready && substr_count($updated,'href="#'.$id.'"')===1
        && substr_count($updated,'id="'.$id.'"')===1;
}
echo json_encode(['source'=>'ANONYMOUS_LIVE_PUBLIC_HTML',
    'candidate_ready'=>$ready,'new_jump_links'=>$ready?3:0,
    'merchant_requests'=>0,'wordpress_writes'=>0])."\n";
exit($ready?0:1);
