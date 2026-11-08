<?php
/** Two exact freshly authenticated existing sonar pages, no WordPress writes. */
define('ABSPATH',__DIR__);
class WP_Error {public function __construct($code,$description=''){}}
function is_wp_error($x){return $x instanceof WP_Error;}
function add_action($n,$fn,$priority=10){return true;}
function wp_kses($html,$allow,$protocol){return $html;}
function test_ok($yes,$msg){if(!$yes){fwrite(STDERR,"FAIL: ".$msg."\n");exit(1);}}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(__DIR__.'/v8-echolot-boat-historic-two-guides-20261108-frozen.json'),true);
$expected=array(
 60637=>array('slug'=>'jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare','bytes'=>15472,'gmt'=>'2026-10-22 12:48:00','sha'=>'9bb01284048286d24943bef620c7c0b1932cc1bdf3a23ba6f2efc8fa2b23a53f','links'=>3),
 36518=>array('slug'=>'porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023','bytes'=>16155,'gmt'=>'2026-04-15 18:01:23','sha'=>'9beca265ca965aaea75649e69aadbcef3def1007bb3f4285752e9bf20b394940','links'=>4),
);
test_ok($m['schema_version']===8&&$m['site']==='https://na-ryby.eu','scope');
test_ok($m['batch_id']==='sonar-boat-historic-two-guide-decision-20261108-v1','reviewed batch id');
test_ok(array_column($m['edits'],'post_id')===array(60637,36518),'exact reviewed 2-post order');
test_ok(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'native v8 validates paired content');
$reflect=new ReflectionMethod(NRY_Git_Publisher::class,'build_v5_candidate');
foreach($m['edits'] as $e){
  $id=$e['post_id'];$want=$expected[$id];$html=$e['insert_html'];
  test_ok($e['slug']===$want['slug']&&$e['source_sha256']===$want['sha'],'live raw WordPress identity');
  test_ok($e['source_modified_gmt']===$want['gmt'],'authenticated source GMT');
  test_ok($e['source_min_bytes']===$want['bytes']
    &&$e['source_max_bytes']===$want['bytes']+1,'tight raw source byte fence');
  test_ok($e['placement']==='prepend'&&!isset($e['text_corrections']),'no prior body rewrite');
  $marker='data-nry-editorial-edit="'.$e['edit_id'].'"';
  test_ok(strpos($html,'<section '.$marker.'>')===0&&substr_count($html,$marker)===1,'one bounded decision section');
  test_ok(strlen($html)>=1400&&strlen($html)<3500,'substantive but concise advice');
  test_ok(substr_count($html,'<ul>')===1&&substr_count($html,'<li>')>=4,'distinct use cases');
  test_ok(strpos($html,'nemusíte kupovat')!==false
    ||strpos($html,'nový echolot nepotřebujete')!==false,'no-buy decision option');
  test_ok(strpos($html,'kompatibil')!==false,'specific sonar transducer compatibility constraint');
  test_ok(!preg_match('~<(?:script|style|img|iframe|form)\b|onload\s*=|onclick\s*=|http://~ui',$html),
    'no active HTML, HTTP or embedded third-party content');
  preg_match_all('~href="([^"]+)"~',$html,$links);
  test_ok(count($links[1])===$want['links'],'exact reviewed same-site link count');
  test_ok(count(array_unique($links[1]))===count($links[1]),'no duplicate internal links');
  foreach($links[1] as $url){
    test_ok(preg_match('~^https://na-ryby\.eu/[a-z0-9-]+/$~D',$url)===1,
      'new links are verified canonical paths on same site');
  }
  test_ok(strpos($html,'Cena: od')===false&&strpos($html,'skladem za')===false,
    'does not invent fresh price or stock');
  $prior='<p>Existing guide content</p><a href="https://example.com/legacy?aid=xyz">Old affiliate</a>'
        .'<img src="https://na-ryby.eu/uploads/old.jpg">[affiliate id="12"]';
  $built=$reflect->invoke(null,$prior,$e);
  test_ok(!isset($built['error'])&&$built['candidate']===$html."\n".$prior,
    'prepend-only candidate must preserve entire original content and affiliate bytes');
  $replay=$reflect->invoke(null,$built['candidate'],$e);
  test_ok(($replay['already']??false)===true,'replay idempotence');
}
$bad=$m;$bad['edits'][0]['post_id']=38064;
test_ok(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'protected article rejects batch');
$bad=$m;$bad['edits'][0]['insert_html'].='<script>alert(1)</script>';
test_ok(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'script injection is denied');
$bad=$m;$bad['edits'][1]['insert_html']=str_replace(
 'https://na-ryby.eu/poradce-vybavy/','https://merchant.example/promo/',$bad['edits'][1]['insert_html']);
test_ok(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'external merchant destination cannot be inserted');
echo "PASS 2 sonar guides: exact WP SHA/GMT/bytes, 7 original internal links, reversible prepend, preserved affiliate body, no WordPress writes\n";
