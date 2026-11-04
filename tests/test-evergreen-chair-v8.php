<?php
/** 2017 evergreen chair guide: exact one-post source-bound editorial canary. */
define('ABSPATH', __DIR__);
class WP_Error { public function __construct($code, $message='') {} }
function is_wp_error($value) { return $value instanceof WP_Error; }
function add_action($name,$callback,$priority=10) { return true; }
function wp_kses($html,$allowed,$protocols) { return $html; }
function must($ok,$why) { if (!$ok) {fwrite(STDERR,"FAIL: ".$why."\n");exit(1);} }
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(dirname(__DIR__).'/manifest.json'),true);
must(is_array($m) && $m['schema_version']===8, 'schema8 only');
must($m['batch_id']==='chair-evergreen-trust-11901-20261104-v1', 'batch identity');
must(count($m['edits'])===1,'exact one post');
$e=$m['edits'][0];
must($e['post_id']===11901 && $e['slug']==='jak-vybrat-rybarske-kreslo','only reviewed evergreen guide');
must($e['source_modified_gmt']==='2017-06-29 16:58:04','real original modified time');
must($e['source_sha256']==='11caa4d950a48bf80f34d7946510acaac4964671b26bd2f1a59b5d77c8a3c664','production source SHA');
must($e['source_min_bytes']===8730 && $e['source_max_bytes']===8731,'exact byte bounds');
must($e['placement']==='prepend' && !is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'safe prepend-only manifest');
$h=$e['insert_html'];$marker='data-nry-editorial-edit="evergreen-chair-legacy-prices-11901-v1"';
must(strpos($h,'<section '.$marker.'>')===0 && substr_count($h,$marker)===1,'exact marker');
must(strpos($h,'2017')!==false && strpos($h,'nejsou ověřenými aktuálními cenami')!==false,'legacy prices clearly disclaimed');
must(strpos($h,'Nákup nového křesla není nutný')!==false,'problem before commerce');
must(substr_count($h,'<tr>')>=4,'useful decision contexts');
must(substr_count($h,'<li>')===5,'five practical checks');
preg_match_all('~href="([^"]+)"~',$h,$links);
must(count($links[1])===2,'only two internal navigation links');
foreach ($links[1] as $url) {
 must((bool)preg_match('~^https://na-ryby\\.eu/[a-z0-9-]+/$~D',$url),'same-origin guides only');
}
must(!preg_match('~<(?:script|style|iframe|img|form)\\b|\\bonclick\\s*=|http://~iu',$h),'no scripts/images/outbound commerce');
$bad=$m;$bad['edits'][0]['post_id']=38064;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'frozen measurement stays protected');
$bad=$m;$bad['edits'][0]['insert_html'].='<script>alert(1)</script>';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'active content rejected');
echo "PASS: exact live SHA/GMT buyer guide, 2017 price disclaimer, no merchant URL change, one safe post\n";
