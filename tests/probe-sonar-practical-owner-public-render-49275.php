<?php
/* Anonymous REST rendered HTML fixture only; no WordPress or network. */
define('ABSPATH','/');
function add_filter($n,$c,$p=10){}
require dirname(__DIR__).'/sonar-practical-owner-guide-49275-v1.php';
if ($argc!==2 || !is_file($argv[1])) exit(2);
$source=file_get_contents($argv[1]);
$c='NRY_Sonar_Practical_Owner_49275_V1';
$after=$c::transform($source);
$marker=substr_count($after,$c::MARKER);
$old=(new ReflectionMethod($c,'corrections'))->invoke(null);
$counts=array();
foreach($old as $r) {
    $counts[]=array('old'=>substr_count($source,$r[0]),
                    'new'=>substr_count($after,$r[1]),
                    'old_after'=>substr_count($after,$r[0]));
}
$ok=$marker===1&&$after!==$source&&
    count(array_filter($counts,function($x){
        return $x['old']===1&&$x['new']===1&&$x['old_after']===0;
    }))===count($old);
echo json_encode(['scope'=>'PUBLIC_REST_RENDERED_ONLY_NOT_WP_SOURCE',
 'sourceBytes'=>strlen($source),'candidateChanged'=>$after!==$source,
 'markerCount'=>$marker,'correctionCounts'=>$counts,
 'headingExactCount'=>preg_match_all('~<h2\\b[^>]*>\\s*Jak efektivně používat echolot a sonar na loď nebo člun\\s*</h2>~u',$source),
 'h2Labels'=>preg_match_all('~<h2\\b[^>]*>(.*?)</h2>~isu',$source,$h2Labels)===false
     ? array() : array_map(function($x){return trim(strip_tags($x));},$h2Labels[1]),
 'pillarLiteralPresent'=>strpos($source,$c::PILLAR)!==false,
 'budgetLiteralPresent'=>strpos($source,$c::BUDGET)!==false,
 'wordpressWrites'=>0,'merchantRequests'=>0],JSON_UNESCAPED_UNICODE)."\n";
exit($ok?0:1);
