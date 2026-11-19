<?php
/** Offline fail-closed contract; draft repair is intentionally disarmed. */
define('ABSPATH', __DIR__);
$GLOBALS['filters'] = array();
$GLOBALS['actions'] = array();
function add_filter($tag, $callback, $priority) { $GLOBALS['filters'][] = $tag; }
function add_action($tag, $callback, $priority) { $GLOBALS['actions'][] = $tag; }
function is_single($postId = null) { return false; }
function check($condition, $label) { if (!$condition) throw new RuntimeException($label); }
require dirname(__DIR__).'/sterkac-rules-repair-12040-v1.php';
$cls = 'NRY_Sterkac_Rules_12040_V1';
check(!$cls::armed(), 'not armed before source proof');
check(in_array('the_content', $GLOBALS['filters'], true), 'content hook');
check(in_array('template_redirect', $GLOBALS['actions'], true), 'document fallback hook');

$h = $cls::HEADING;
$h .= '<p><strong>Rychlá odpověď:</strong> Sezóna od dubna do listopadu, háčky bez protihrotu; '
    .'je podle pravidel možné si rybu i ponechat.</p>';
$h .= '<div><ul><li><strong>háčky bez protihrotu</strong></li>'
    .'<li><strong>duben až listopad</strong></li></ul></div>';
$h .= '<p>'.str_repeat('Praktické tipy pro rybáře s vybavením a přípravou výpravy. ', 90).'</p>';
$h .= '<h2>Ryby na Štěrkáči: na co se dá zaměřit</h2><p>Ryby, sumce, štiky.</p>';
$h .= '<h2>Ceny povolenek a co z nich reálně plyne</h2><p>denní 300 Kč, noční 300 Kč.</p>';
$h .= '<h2>Pravidla lovu: tady se chyby nevyplácejí</h2>'
    .'<p>pouze s <strong>jednoháčkem bez protihrotu</strong>.</p>';
$h .= '<h2>Jaké vybavení se na Štěrkáč hodí nejvíc</h2><p>Výbava na vodu.</p>';
$h .= '<h2>Služby, zázemí a proč se vyplatí plánovat pobyt jako výpravu, ne jako zastávku</h2>'
    .'<p>Na <a href="https://na-ryby.eu/booking-cesko">rezervaci</a>.</p>';
$h .= '<h2>Pro koho je Štěrkáč Zaječí vhodný a komu může sednout méně</h2><p>Kapraři.</p>';
$h .= '<h2>Rychlé shrnutí před odjezdem</h2><p>háčky bez protihrotu a další rady.</p>';
$h .= $cls::FOOTER.'Archivní datum 14. 4. 2026</div>';
check(substr_count($h, 'bez protihrotu') === 4, 'fixture four old hook claims');
check(substr_count($h, 'listopad') === 2, 'fixture two season claims');
$result = $cls::transform($h);
check($result !== $h, 'corrected stable fixture');
check(substr_count($result, $cls::MARKER) === 1, 'visible panel exactly once');
check(strpos($result, '500 Kč') !== false && strpos($result, '300 Kč') === false, 'current permits');
check(strpos($result, 'celoročně zakázán') !== false, 'no targeting predators');
check(strpos($result, 'booking-cesko') === false && strpos($result, 'listopad') === false, 'stale content removed');
check(strpos($result, 'bez protihrotu') === false, 'obsolete hook advice removed');
check(strpos($result, 'https://vinarstviukaplicky.cz/rybarina/') !== false, 'official source');
check($cls::transform($result) === $result, 'idempotent');
check($cls::render($h) === $h, 'not eligible without confirmed raw source SHA');
$driftPrice = str_replace('300 Kč', '400 Kč', $h);
check($cls::transform($driftPrice) === $driftPrice, 'old price drift blocked');
check($cls::transform(str_replace('booking-cesko', 'other', $h)) === str_replace('booking-cesko', 'other', $h), 'link drift blocked');
$doc = '<html><body>'.str_repeat('navigation ', 2500).$h.'</body></html>';
$updated = $cls::transformDocument($doc);
check($updated !== $doc && substr_count($updated, $cls::MARKER) === 1, 'exact document fallback');
check($cls::transformDocument($updated) === $updated, 'document idempotent');
echo "PASS: Sterkac rules repair is source-disarmed and body transforms fail closed\n";
