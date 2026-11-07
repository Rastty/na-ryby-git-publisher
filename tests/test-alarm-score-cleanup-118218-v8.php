<?php
/** Complete isolated source-bound alarm scores correction-only release. */
define('ABSPATH', __DIR__);
class WP_Error {public function __construct($code,$message='') {}}
function is_wp_error($v){return $v instanceof WP_Error;}
function add_action($event,$cb,$priority=10){return true;}
function wp_kses($html,$allowed,$protocols){return $html;}
function must($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL: ".$msg."\n");exit(1);}}
$GLOBALS['posts']=array();$GLOBALS['options']=array();$GLOBALS['writes']=0;
function get_post($id){return $GLOBALS['posts'][$id]??null;}
function get_permalink($id){return 'https://na-ryby.eu/'.$GLOBALS['posts'][$id]->post_name.'/';}
function add_option($key,$value,$deprecated='',$autoload='no'){
 if(array_key_exists($key,$GLOBALS['options']))return false;
 $GLOBALS['options'][$key]=$value;return true;
}
function get_option($key,$default=null){return $GLOBALS['options'][$key]??$default;}
function wp_update_post($data,$return_error=true){
 $id=(int)$data['ID'];$GLOBALS['writes']++;
 $GLOBALS['posts'][$id]->post_content=$data['post_content'];return $id;
}
require dirname(__DIR__).'/na-ryby-git-publisher.php';

$manifest=json_decode(file_get_contents(__DIR__.'/v8-alarm-score-correction-118218-20261104-frozen.json'),true);
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($manifest)),'immutable historical alarm correction fixture');
$edit=$manifest['edits'][0];
must($manifest['schema_version']===8
  && $manifest['batch_id']==='alarm-buyer-score-method-118218-20261104-v2'
  && count($manifest['edits'])===1 && $edit['post_id']===118218
  && $edit['placement']==='corrections_only'
  && !isset($edit['insert_html'], $edit['cleanup_ops']),
  'only correction-only reviewed 118218 release');
must($edit['source_sha256']==='93c352ae0a953877a640c52225ac956d162b744f34c084c2224fadc299d642df'
  && $edit['source_modified_gmt']==='2026-11-04 13:04:54'
  && $edit['source_min_bytes']===32805 && $edit['source_max_bytes']===32806,
  'historical source hash/date/byte proof pinned');
$corrections=$edit['text_corrections'];
must(count($corrections)===7,'six exact ratings and one warning edit');
$expected=array('8,1 / 10','8,6 / 10','8,9 / 10','9,2 / 10','9,1 / 10','9,4 / 10');
foreach($expected as $i=>$score) {
  must($corrections[$i]['old_text']===$score
    && $corrections[$i]['new_text']==='Bez doloženého skóre',
    'no arbitrary rating changes');
}
must(strpos($corrections[6]['old_text'],'číselná hodnocení typu 8,9/10')!==false
  && strpos($corrections[6]['new_text'],'Bodová hodnocení jsme z tabulky odstranili')!==false,
  'warning updated without false price claim');
$legacy='<section data-nry-editorial-edit="alarm-buyer-prices-trust-118218-v1"><p>'
  .$corrections[6]['old_text'].'</p>'
  .'<a href="https://na-ryby.eu/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/">Návod</a>'
  .'</section>';
$table='<table><thead><tr><th>Hodnocení</th></tr></thead><tbody>';
foreach($expected as $score) $table.='<tr><td>'.$score.'</td></tr>';
$table.='</tbody></table>';
$media='<a href="https://www.tkqlhce.com/click-123?sid=frozen">Zjistit cenu</a>'
  .'<img src="https://na-ryby.eu/wp-content/model.jpg" alt="Model">'
  .'[approved_media_card id="123"]';
$source=$legacy.$table.$media.str_repeat('<p>Původní užitečný text a technické popisy hlásičů bez dalších editů.</p>',120);
$mock=$edit;$mock['source_modified_gmt']='2026-11-04 13:04:54';
$mock['source_min_bytes']=strlen($source);$mock['source_max_bytes']=strlen($source)+1;
$mock['source_sha256']=hash('sha256',$source);
$m=$manifest;$m['edits'][0]=$mock;
$GLOBALS['posts'][118218]=(object)array('ID'=>118218,'post_type'=>'post','post_status'=>'publish',
  'post_name'=>$mock['slug'],'post_modified_gmt'=>$mock['source_modified_gmt'],
  'post_content'=>$source);
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'valid bounded mock');
$planned=NRY_Git_Publisher::plan($m);
must($planned[0]['status']==='ready','all six unique ratings in safe plain spans');
$c=$planned[0]['candidate'];
must(substr_count($c,'Bez doloženého skóre')===6
  && substr_count($c,'Bodová hodnocení jsme z tabulky odstranili')===1
  && strpos($c,$media)!==false,'ratings corrected, affiliate/media untouched');
foreach($expected as $score)must(strpos($c,$score)===false,'numeric rating left behind');
must(substr_count($c,'<td>')===6,'HTML cells intact');
must(substr_count($c,'<a href=')===2,'no links added or removed');
must($GLOBALS['writes']===0,'read-only plan did not write WordPress');
$bad=$m;$bad['edits'][0]['source_sha256']=str_repeat('0',64);
must(NRY_Git_Publisher::plan($bad)[0]['status']==='blocked','source SHA drift blocked');
$bad=$m;$bad['edits'][0]['text_corrections'][0]['new_text']='9,9 / 10';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'unreviewed arbitrary score forbidden');
$bad=$m;$bad['edits'][0]['post_id']=38064;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'protected article blocked');
$bad=$m;$bad['batch_id']='different-batch';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'only one immutable batch');
$bad=$m;$bad['edits'][0]['insert_html']='<script>bad</script>';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'no surprise HTML permitted');
$GLOBALS['posts'][118218]->post_content=str_replace('8,1 / 10','9,8 / 10',$source);
must(NRY_Git_Publisher::plan($m)[0]['status']==='blocked','missing score hard stop');
$GLOBALS['posts'][118218]->post_content=$source;
$receipt=NRY_Git_Publisher::execute($m);
must($receipt['status']==='applied_and_verified' && $receipt['writes']===1,
  'backed up exact candidate not applied');
must($GLOBALS['writes']===1 && count($GLOBALS['options'])===1,
  'single WordPress write and immutable backup');
must($GLOBALS['options'][array_key_first($GLOBALS['options'])][0]['source']===$source,
  'original full content backed up');
$again=NRY_Git_Publisher::execute($m);
must($again['writes']===0 && $GLOBALS['writes']===1,'idempotent re-run');
echo "PASS: 6 unsupported scores removed in place, original price/links/media kept, SHA+backup+replay\n";
