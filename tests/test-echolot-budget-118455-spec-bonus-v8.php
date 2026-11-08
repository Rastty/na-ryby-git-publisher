<?php
/** Exact immutable source preflight for factual Eagle 4X GPS and six bonuses; no live WP writes. */
define('ABSPATH',__DIR__);
class WP_Error {public function __construct($c,$m=''){}}
function is_wp_error($x){return $x instanceof WP_Error;}
function add_action($n,$cb,$priority=10){}
function must($x,$msg){if(!$x){fwrite(STDERR,"FAIL: ".$msg."\n");exit(1);}}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(__DIR__.'/v8-echolot-budget-118455-spec-bonus-frozen.json'),true);
must(is_array($m)&&count($m['edits'])===1&&count($m['edits'][0]['text_corrections'])===6,'frozen six only');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'immutable hash-bound manifest accepted');
$edit=$m['edits'][0];
$rules=$edit['text_corrections'];
$source='<h2>Historické srovnání – nejde o aktuální nabídky</h2>';
foreach($rules as $r){
 for($i=0;$i<$r['count'];$i++) $source.='<p>'.$r['old_text'].'</p>';
}
$original='<a href="https://go.dognet.com/?private=affiliate">Prodejce</a>'
    .'<img src="https://na-ryby.eu/wp-content/uploads/eagle4.jpg" alt="Echolot">'
    .'[commercial_form id="118455"]';
$source.=$original;
$reflection=new ReflectionMethod('NRY_Git_Publisher','build_sonar_spec_bonus_candidate');
$built=$reflection->invoke(null,$source,$edit);
must(isset($built['candidate']),'exact source-safe six corrections built');
foreach($rules as $r){
 must(substr_count($built['candidate'],$r['new_text'])===$r['count'],'safe replacements exact');
 must(substr_count($built['candidate'],$r['old_text'])===0,'no obsolete claim after correction');
}
must(strpos($built['candidate'],$original)!==false,'merchant href/image/shortcode exact parity');
$repeat=$reflection->invoke(null,$built['candidate'],$edit);
must(($repeat['already']??false)===true,'idempotent replay');
$bad=$m;$bad['edits'][0]['post_id']=118553;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'wrong post blocks');
$bad=$m;$bad['edits'][0]['source_sha256']=str_repeat('0',64);
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'unverified source hash blocks');
$bad=$m;$bad['edits'][0]['text_corrections'][0]['new_text']='Kupte hned';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'unreviewed merchant text blocks');
$badSource=str_replace('<p>'.$rules[0]['old_text'].'</p>',
    '<a href="https://merchant.invalid/?q='.$rules[0]['old_text'].'">Odkaz</a>',
    $source);
$blocked=$reflection->invoke(null,$badSource,$edit);
must(isset($blocked['error']),'merchant attribute not modified');
$duplicated=$source.'<p>'.$rules[0]['old_text'].'</p>';
must(isset($reflection->invoke(null,$duplicated,$edit)['error']),'duplicate prose claim must block');
echo "PASS 118455: exact six source-locked GPS and bonus facts, guarded HTML, replay idempotent, preserved affiliate tokens\n";
