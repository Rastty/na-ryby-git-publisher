<?php
/** 60341 bounded, exact-source text cleanup contract. No real WP or network. */
define('ABSPATH',__DIR__);
class WP_Error { public function __construct($code,$message='') {} }
function is_wp_error($x) { return $x instanceof WP_Error; }
function add_action($name,$callback,$priority=10) { return true; }
function wp_kses($html,$allowed,$protocols) { return $html; }
function must($ok,$msg) { if (!$ok) throw new RuntimeException($msg); }
$posts=[];$options=[];$writes=0;
function get_post($id) { return $GLOBALS['posts'][$id]??null; }
function add_option($k,$v,$d='',$autoload='no') {
    if (array_key_exists($k,$GLOBALS['options'])) return false;
    $GLOBALS['options'][$k]=$v;return true;
}
function get_option($k,$fallback=null) {return $GLOBALS['options'][$k]??$fallback;}
function wp_update_post($data,$error=true) {
    $id=(int)$data['ID'];
    $GLOBALS['writes']++;
    $GLOBALS['posts'][$id]->post_content=$data['post_content'];
    return $id;
}
function get_permalink($id) {
    return 'https://na-ryby.eu/'.$GLOBALS['posts'][$id]->post_name.'/';
}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$legacy=[
 'Naše společnost se specializuje na výrobu a prodej nejlepších rybářských křesel na trhu.',
 'Jsme si jisti, že si u nás vyberete židličku na ryby, která splní vaše očekávání a zvýší vaši radost z rybaření.',
 'klíčová slova: rybářské křeslo, židlička na ryby, pohodlí, ergonomický design, opěrky pro záda, odolnost, úložný prostor, rybářský náčiní'
];
$source='<h2>Nejlepší rybářské křeslo – vybíráme židličku na ryby</h2>'
.'<p>'.$legacy[0].'</p><p>'.$legacy[1].'</p><p>'.$legacy[2].'</p>'
.'<a href="https://www.an.example/path?offer=123">Starý srovnávací odkaz</a>'
.'<img src="https://na-ryby.eu/wp-content/example.webp" alt="Židle">'
.str_repeat('<p>Další redakční archiv bez dalších tvrzení.</p>',150);
$markup='<section data-nry-editorial-edit="chair-hub-trust-60341-v1">'
.'<h2>Jak vybírat křeslo podle výpravy</h2>'
.'<p>'.str_repeat('Srovnejte pohodlí, nosnost, hmotnost a přepravní rozměry podle skutečných potřeb rybáře. ',14).'</p>'
.'<a href="https://na-ryby.eu/jak-vybrat-rybarske-kreslo/">Průvodce výběrem</a>'
.'</section>';
$replacements=[
 ['old_text'=>$legacy[0],'new_text'=>'Jsme redakční rybářský magazín a rybářská křesla nevyrábíme ani neprodáváme.'],
 ['old_text'=>$legacy[1],'new_text'=>'Nákup vybavení doporučujeme zvažovat podle reálných potřeb, přesné varianty a údajů výrobce.'],
 ['old_text'=>$legacy[2],'new_text'=>'K důležitým kritériím patří stabilita rámu, posed, bezpečné nohy, nosnost a doprava.']
];
$edit=['post_id'=>60341,'slug'=>'nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby',
 'source_modified_gmt'=>'2023-09-23 05:35:38','source_min_bytes'=>strlen($source),
 'source_max_bytes'=>strlen($source)+1,'source_sha256'=>hash('sha256',$source),
 'edit_id'=>'chair-hub-trust-60341-v1','placement'=>'prepend',
 'insert_html'=>$markup,'text_corrections'=>$replacements];
$manifest=['schema_version'=>8,'batch_id'=>'chair-buyer-trust-60341-20261104-v1',
 'site'=>'https://na-ryby.eu','edits'=>[$edit]];
$posts[60341]=(object)['ID'=>60341,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>$edit['slug'],'post_modified_gmt'=>$edit['source_modified_gmt'],
 'post_content'=>$source];
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($manifest)),'schema rejected');
$planned=NRY_Git_Publisher::plan($manifest);
must($planned[0]['status']==='ready','original should be eligible');
foreach($legacy as $old) must(!str_contains($planned[0]['candidate'],$old),'legacy text not corrected');
must(strpos($planned[0]['candidate'],'href="https://www.an.example/path?offer=123"')!==false,'external href altered');
must(strpos($planned[0]['candidate'],'<img src=')!==false,'original image altered');
$proof=NRY_Git_Publisher::buyer_source_proof_snapshot('buyer-hub');
must($proof['rows'][0]['trust_correction_targets']['false_manufacturer_claim']===1,'raw source target detection');
must($proof['rows'][0]['trust_correction_targets']['sales_claim']===1,'raw sales target detection');
must($proof['rows'][0]['trust_correction_targets']['visible_keyword_line']===1,'raw keyword target detection');
$negative=$manifest;
$negative['edits'][0]['post_id']=38064;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($negative)),'protected page allowed');
$negative=$manifest;
$negative['edits'][0]['post_id']=61001;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($negative)),'scope expansion allowed');
$negative=$manifest;
$negative['batch_id']='unauthorized-other-batch';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($negative)),'different batch allowed');
$negative=$manifest;
$negative['edits'][0]['text_corrections'][0]['new_text']='<script>bad</script>';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($negative)),'active correction allowed');
$negative=$manifest;
$negative['edits'][0]['text_corrections'][0]['old_text']='a different long false source statement';
must(NRY_Git_Publisher::plan($negative)[0]['status']==='blocked','missing exact old source allowed');
$negative=$manifest;
$negative['edits'][0]['source_sha256']=str_repeat('f',64);
must(NRY_Git_Publisher::plan($negative)[0]['status']==='blocked','source SHA drift allowed');
must($writes===0,'tests made unsanctioned writes');
$receipt=NRY_Git_Publisher::execute($manifest);
must($receipt['status']==='applied_and_verified'&&$receipt['writes']===1,'one-post verified release failed');
must($writes===1&&count($options)===1,'pre-write backup missing');
$replay=NRY_Git_Publisher::execute($manifest);
must($replay['writes']===0&&$writes===1,'idempotent replay failed');
echo "PASS: exact bounded trusted text correction, protected merchant/media, backup, replay, no network\n";
