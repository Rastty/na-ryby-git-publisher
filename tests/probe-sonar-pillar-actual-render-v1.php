<?php
/** CLI only; anonymized check of the exact already-public REST rendered article. */
define('ABSPATH', __DIR__);
require __DIR__.'/../sonar-pillar-practical-handoff-118553-v1.php';
$raw = isset($argv[1]) ? file_get_contents($argv[1]) : false;
if (!is_string($raw) || strlen($raw) < 600 || strlen($raw) > 300000) {
    echo "BLOCKED invalid bounded public body\n";
    exit(1);
}
$c='NRY_Sonar_Pillar_Practical_Handoff_118553_V1';
$marker = $c::SOURCE_MARKER;
$start = strpos($raw, '<section '.$marker);
$close = $start===false ? false : strpos($raw,'</section>', $start);
$section=($start!==false && $close!==false)
    ? substr($raw, $start, $close-$start) : '';
$next = $close===false ? '' : substr($raw, $close+strlen('</section>'),400);
$safe=array(
  'scope'=>'PUBLIC_REST_RENDERED_ONLY_NOT_WP_SOURCE',
  'markerCount'=>substr_count($raw,$marker),
  'sectionOpenExact'=>$start!==false,
  'sectionCloseBounded'=>$close!==false && $close-$start<4500,
  'fourCases'=>substr_count($section,'<li>'),
  'alreadyOwnLanguage'=>strpos($section,'další nákup není nutný')!==false,
  'priceCaution'=>strpos($section,'aktuální konečnou cenu kompletu')!==false,
  'h2NextMatch'=>(bool) preg_match('~<h2\b[^>]*>\s*Jak vybrat echolot pro rybaření:~i',$next),
  'previousPracticalHref'=>strpos($raw,$c::PRACTICAL)!==false,
  'pureTransformAddsMarker'=>false,
  'wordpressWrites'=>0,'merchantRequests'=>0,
);
$result=$c::transform($raw);
$safe['pureTransformAddsMarker']=substr_count($result,$c::MARKER)===1;
$safe['pureTransformChanged']=$raw!==$result;
echo json_encode($safe,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if (!$safe['pureTransformAddsMarker']) exit(1);
