<?php
/** Exact content and source contract for already published alarm buying guide. */
define('ABSPATH', __DIR__);
class WP_Error { public function __construct($code,$message='') {} }
function is_wp_error($v) { return $v instanceof WP_Error; }
function add_action($name,$cb,$priority=10) {return true;}
function wp_kses($html,$allowed,$protocols) {return $html;}
function must($ok,$why) {if(!$ok){fwrite(STDERR,"FAIL: ".$why."\n");exit(1);}}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(dirname(__DIR__).'/manifest.json'),true);
must(is_array($m)&&$m['schema_version']===8,'schema v8');
must($m['site']==='https://na-ryby.eu'
  && $m['batch_id']==='alarm-buyer-trust-118218-20261104-v1','immutable reviewed release');
must(count($m['edits'])===1,'single post only');
$e=$m['edits'][0];
must($e['post_id']===118218
  && $e['slug']==='nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu',
  'exact source WordPress ID and slug');
must($e['source_sha256']==='6f6f5d6e62c0e5a8fcd03b5b7219d2fbd626c7a023a1b11736c3318068a36481',
  'same 30k raw WP source as production readback');
must($e['source_modified_gmt']==='2026-10-23 06:35:08',
  'exact source WordPress modified GMT');
must($e['source_min_bytes']===30551&&$e['source_max_bytes']===30552,
  'exact byte length');
must($e['placement']==='prepend'&&!isset($e['cleanup_ops'])&&!isset($e['text_corrections']),
  'all existing article text, images, affiliate urls and markup untouched');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),
  'publisher validates reviewed schema v8');
$h=$e['insert_html'];
$marker='data-nry-editorial-edit="alarm-buyer-prices-trust-118218-v1"';
must(strpos($h,'<section '.$marker.'>')===0 && substr_count($h,$marker)===1,
  'one top-level unique marker');
must(substr_count($h,'<li>')===4 && strpos($h,'novou kupovat nemusíte')!==false,
  'four differentiated choices and no-buy option');
must(strpos($h,'číselná hodnocení typu 8,9/10')!==false,
  'clearly unverified existing numerical ratings');
must(strpos($h,'nezávisle ověřenými cenami')!==false
  && strpos($h,'historické orientační údaje')!==false
  && strpos($h,'akce 1+1 může skončit')!==false,
  'price and promotion disclaimers before unverified offers');
preg_match_all('~<a href="([^"]+)">~',$h,$matches);
must($matches[1]===array(
  'https://na-ryby.eu/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/',
  'https://na-ryby.eu/baterie-do-signalizatoru-ktere-vydrzi-nejdele-a-proc-nekupovat-levne/'),
  'links restricted to two context-specific existing guides');
must(!preg_match('~<(?:script|iframe|style|form|img)\b|\bonclick\s*=|http://~iu',$h),
  'no active content or third-party assets');
echo "PASS: exact 118218 source; honest 1+1, scores and prices; two internal links; no affiliate rewrites\n";
