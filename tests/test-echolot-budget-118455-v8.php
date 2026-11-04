<?php
/** Exact production-reviewed, source-pinned 15k echolot editorial release. No real WP. */
define('ABSPATH', __DIR__);
class WP_Error { public function __construct($code, $message='') {} }
function is_wp_error($value) { return $value instanceof WP_Error; }
function add_action($name,$cb,$priority=10) {return true;}
function wp_kses($html,$allowed,$protocols) {return $html;}
function must($condition,$message) {
  if (!$condition) { fwrite(STDERR,"FAIL: ".$message."\n"); exit(1); }
}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(dirname(__DIR__).'/manifest.json'),true);
must(is_array($m),'valid JSON');
must($m['schema_version']===8 && $m['site']==='https://na-ryby.eu','site/schema');
must($m['batch_id']==='echolot-budget-118455-20261104-v1','reviewed immutable batch');
must(count($m['edits'])===1,'bounded single edit');
$e=$m['edits'][0];
must($e['post_id']===118455,'exact verified WP ID');
must($e['slug']==='nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz','exact existing URL');
must($e['source_modified_gmt']==='2026-03-19 20:04:31','exact WordPress source date');
must($e['source_sha256']==='8da806d88b7af2f8c7530c7c1b4994fcc3d86c0146aa79763636c22beea2372a','verified raw source SHA');
must($e['source_min_bytes']===36556 && $e['source_max_bytes']===36557,'exact source bytes');
must($e['placement']==='prepend' && !isset($e['text_corrections'])
  && !isset($e['cleanup_ops']), 'append-only preservation of links/media');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)), 'publisher rejects safe v8');
$h=$e['insert_html'];
$marker='data-nry-editorial-edit="echolot-budget-decision-118455-v1"';
must(strpos($h,'<section '.$marker.'>')===0 && substr_count($h,$marker)===1,'single unique editorial section');
must(strpos($h,'cenu kompletu')!==false && strpos($h,'nepředstavují dnes ověřený ceník')!==false,
    'not falsely confirming active prices');
must(strpos($h,'nemusíte kupovat nové')!==false,'solve need before commerce');
must(substr_count($h,'<tr>')===5,'scenario table required');
must(substr_count($h,'<li>')===5,'purchase checks required');
preg_match_all('~href="([^"]+)"~',$h,$links);
$expected=array(
  'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/',
  'https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/',
  'https://na-ryby.eu/poradce-vybavy/',
);
must($links[1]===$expected,'exact cross-links, no external or unreviewed destinations');
must(!preg_match('~<(?:script|iframe|form|img|style)\b|\bonclick\s*=|http://~iu',$h),'no active or third-party markup');
echo "PASS: 118455 exact WordPress SHA, source-preserving buyer guide, useful decision and three internal links\n";
