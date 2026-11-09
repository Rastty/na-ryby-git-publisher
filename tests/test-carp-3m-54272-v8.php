<?php
/** Source-bound 54272 editorial trust repair. Local mocks only; no network or WP. */
define('ABSPATH',__DIR__);
class WP_Error {public function __construct($code,$message='') {}}
function is_wp_error($x){return $x instanceof WP_Error;}
function add_action($name,$fn,$priority=10){}
function wp_kses($html,$allowed,$protocols){return $html;}
function must($v,$message){if(!$v) throw new RuntimeException($message);}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(__DIR__.'/v8-carp-3m-54272-20261109-frozen.json'),true);
must(is_array($m)&&count($m['edits'])===1&&$m['edits'][0]['post_id']===54272,'immutable one post');
must(count($m['edits'][0]['text_corrections'])===9,'exact nine corrections');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'reviewed source-bound release refused');
$edit=$m['edits'][0];
$source='<h2>'.str_repeat('Historický přehled prutů a možností. ',200).'</h2>';
foreach($edit['text_corrections'] as $rule) {
  $source.='<p>'.$rule['old_text'].'</p>';
}
$original='<a href="https://www.example.test/legacy?item=old">Archiv</a>'.
          '<img src="https://na-ryby.eu/wp-content/uploads/archive.webp" alt="Prut">'.
          '[su_button url="https://na-ryby.eu/pruty" background="#f00"]Pruty[/su_button]';
$source.=$original;
$builder=new ReflectionMethod('NRY_Git_Publisher','build_v5_candidate');
$built=$builder->invoke(null,$source,$edit);
must(isset($built['candidate']),'nine safe prose changes cannot be built');
$candidate=$built['candidate'];
must(strpos($candidate,$edit['insert_html']."\n")===0,'editorial decision panel not first');
must(substr_count($candidate,$original)===1,'original merchant/media/shortcode drift');
foreach($edit['text_corrections'] as $rule) {
  must(substr_count($candidate,$rule['old_text'])===0,'stale claim survived');
  must(substr_count($candidate,$rule['new_text'])===1,'reviewed replacement absent');
}
must(($builder->invoke(null,$candidate,$edit)['already']??false)===true,'idempotent rewrite refused');
$conflict=$source.'<p>'.$edit['text_corrections'][0]['old_text'].'</p>';
must(isset($builder->invoke(null,$conflict,$edit)['error']),'duplicated old phrase did not block');
$inside=$source;
$inside=str_replace('<p>'.$edit['text_corrections'][0]['old_text'].'</p>',
  '<a href="https://merchant.example/?old='.$edit['text_corrections'][0]['old_text'].'">Merchant</a>',$inside);
must(isset($builder->invoke(null,$inside,$edit)['error']),'merchant href span was not protected');
$bad=$m;$bad['batch_id']='unreviewed-54272';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'wrong batch accepted');
$bad=$m;$bad['edits'][0]['post_id']=48584;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'KEEP article scope expansion accepted');
$bad=$m;$bad['edits'][0]['source_sha256']=str_repeat('0',64);
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'wrong SHA accepted');
$bad=$m;$bad['edits'][0]['insert_html'].='<p>extra</p>';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'unreviewed panel accepted');
$bad=$m;$bad['edits'][0]['text_corrections'][0]['new_text']='Kupte hned';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'unreviewed correction accepted');
echo "PASS #54272: nine exact corrections, decision panel, no merchant/media drift, no KEEP, idempotent\n";
