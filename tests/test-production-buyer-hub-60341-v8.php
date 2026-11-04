<?php
/** Immutable production-reviewed buyer hub repair manifest; no WordPress writes. */
define('ABSPATH',__DIR__);
class WP_Error {public function __construct($code,$message='') {}}
function is_wp_error($x) {return $x instanceof WP_Error;}
function add_action($name,$cb,$priority=10) {return true;}
function wp_kses($html,$allowed,$protocols) {return $html;}
function must($ok,$msg) {if (!$ok) {fwrite(STDERR,"FAIL: ".$msg."\n");exit(1);}}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(dirname(__DIR__).'/manifest.json'),true);
must(is_array($m)&&$m['schema_version']===8,'schema8 only');
must($m['batch_id']==='chair-buyer-trust-60341-20261104-v1','immutable batch');
must($m['site']==='https://na-ryby.eu'&&count($m['edits'])===1,'exact scope');
$e=$m['edits'][0];
must($e['post_id']===60341 && $e['slug']==='nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby','public ID/slug');
must($e['source_sha256']==='155e367a6dfa0296b3a993d9db3d385c865e4a616af68542073793c282545e25','verified live source SHA');
must($e['source_modified_gmt']==='2023-09-23 05:35:38','verified source GMT');
must($e['source_min_bytes']===18172 && $e['source_max_bytes']===18173,'verified raw byte count');
must($e['placement']==='prepend','only prepend');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'publisher rejects reviewed canary');
$cor=$e['text_corrections'];
must(count($cor)===3,'exact 3 source-bound corrections');
$old=[
 'Naše společnost se specializuje na výrobu a prodej nejlepších rybářských křesel na trhu.',
 'Jsme si jisti, že si u nás vyberete židličku na ryby, která splní vaše očekávání a zvýší vaši radost z rybaření.',
 'Naše rybářská křesla jsou také vybavena',
];
foreach($cor as $i=>$row) {
  must($row['old_text']===$old[$i],'unreviewed correction changed');
  must(strlen($row['new_text'])>=25&&strpos($row['new_text'],'<')===false,
    'unsafe or empty replacement');
}
$h=$e['insert_html'];
$marker='data-nry-editorial-edit="chair-hub-trust-60341-v1"';
must(strpos($h,'<section '.$marker.'>')===0&&substr_count($h,$marker)===1,'top decision marker once');
must(strpos($h,'2023')!==false&&strpos($h,'nezávislý test')!==false,'archived rating caution');
must(strpos($h,'nemusíte kupovat novou')!==false,'user-before-commerce option');
must(substr_count($h,'<tr>')>=4&&substr_count($h,'<li>')===5,'useful comparison');
preg_match_all('~href="([^"]+)"~',$h,$links);
must(count($links[1])===2,'two supporting internal links');
foreach($links[1] as $url) must((bool)preg_match('~^https://na-ryby\\.eu/[a-z0-9-]+/$~D',$url),'no added merchant link');
must(!preg_match('~<(?:script|iframe|form|img)\\b|\\bonclick\\s*=|http://~iu',$h),'no active content');
echo "PASS: source-pinned 60341 reviewed trust corrections, buyer decision, zero writes\n";
