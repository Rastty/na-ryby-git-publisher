<?php
/** Production-reviewed exact-source echolot pillar release; no live WP writes. */
define('ABSPATH', __DIR__);
class WP_Error { public function __construct($code,$message='') {} }
function is_wp_error($v) { return $v instanceof WP_Error; }
function add_action($name,$cb,$priority=10) {return true;}
function wp_kses($html,$allowed,$protocols) {return $html;}
function must($ok,$why) {if(!$ok){fwrite(STDERR,"FAIL: ".$why."\n");exit(1);}}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(__DIR__.'/v8-echolot-pillar-118553-frozen.json'),true);
must(is_array($m)&&$m['schema_version']===8,'schema v8');
must($m['site']==='https://na-ryby.eu'
    && $m['batch_id']==='echolot-pillar-answer-first-118553-20261104-v1',
    'reviewed target site and batch');
must(count($m['edits'])===1,'single bounded edit');
$e=$m['edits'][0];
must($e['post_id']===118553
    && $e['slug']==='jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce',
    'exact source WordPress ID and canonical');
must($e['source_sha256']==='e53faedc69b7cbea9dad74a06c505476a912a87c5656d510e38df20b8e3c5b0a',
    'source raw SHA from production proof');
must($e['source_modified_gmt']==='2026-10-22 06:33:24',
    'source modified GMT from WordPress');
must($e['source_min_bytes']===43846 && $e['source_max_bytes']===43847,
    'exact production source byte count');
must($e['placement']==='prepend'&&!isset($e['cleanup_ops'])&&!isset($e['text_corrections']),
    'leave original post content, links and embeds byte-identical');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),
    'publisher schema 8 denies new copy');
$h=$e['insert_html'];
$marker='data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"';
must(strpos($h,'<section '.$marker.'>')===0 && substr_count($h,$marker)===1,
    'unique marker at top');
must(substr_count($h,'<li>')===4 && strpos($h,'další nákup není nutný')!==false,
    'scenarios plus no-buy option');
must(strpos($h,'sondu, napájení a aktuální konečnou cenu kompletu')!==false,
    'explicit exact offer due diligence');
preg_match_all('~<a href="([^"]+)">~',$h,$links);
$expected=array(
  'https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/',
  'https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/',
  'https://na-ryby.eu/poradce-vybavy/'
);
must($links[1]===$expected,'exact 3 internal routes, zero external offers');
must(!preg_match('~<(?:script|style|iframe|img|form)\\b|\\bonclick\\s*=|http://~iu',$h),
    'no unsafe active code, third-party offers or media');
echo "PASS: exact 118553 source bound, four real-use scenarios, no-buy and 3 same-origin links\n";
