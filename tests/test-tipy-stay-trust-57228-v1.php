<?php
/** Only offline mocks: no WordPress write, request, merchant click or redirect. */
define('ABSPATH', __DIR__);
function add_filter($name, $callback, $priority) { return true; }
function add_action($name, $callback, $priority) { return true; }
function is_single($id) { return $id === 57228; }
function is_singular($kind) { return $kind === 'post'; }
function get_queried_object_id() { return 57228; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return 'https://na-ryby.eu/tipy-na-vylety-pro-rybare-s-ubytovanim-v-prirode/'; }
$GLOBALS['tipy_mock'] = (object)array(
    'ID'=>57228, 'post_type'=>'post', 'post_status'=>'publish',
    'post_name'=>'tipy-na-vylety-pro-rybare-s-ubytovanim-v-prirode',
    'post_modified_gmt'=>'2023-07-07 23:12:56',
    // Intentionally no committed copy of the production source. This mock is
    // invalid and confirms that a modified source refuses a frontend patch.
    'post_content'=>'source drift',
);
function get_post($id) { return $GLOBALS['tipy_mock']; }
require dirname(__DIR__) . '/tipy-stay-trust-57228-v1.php';
function check($value, $message) { if (!$value) throw new RuntimeException($message); }

$original = '<p>Dobře vybíráte! Pokud jste rybář a hledáte výlety s perfektním ubytováním v krásné přírodě, jste na správném místě. Nabízíme širokou škálu destinací, které splní vaše rybářské sny.</p>'
    .'<a href="https://na-ryby.eu/stary-odkaz/">Stávající navigace</a>'
    .'<h3>Přehrada Vranov &#8211; Zlínský kraj</h3>'
    .'<p>Nachází se ve Zlínském kraji a je známá svými neobyčejně čistými vodami.</p>'
    .'<p>Náš tým je sám vášnivým rybářem a důkladně vybíráme nejlepší destinace, které potěší rybáře všech úrovní dovedností. Bez ohledu na to, zda jste začátečník nebo zkušený profesionál, najdete u nás ideální místo pro své rybářské dobrodružství.</p>'
    ."<h3>3. Jsou tyto ubytování dostupná po celý rok?</h3>\n<p>Ano, většina rybářských destinací a ubytování v přírodě je dostupná po celý rok. V závislosti na konkrétní lokalitě se může lišit sezóna rybolovu, ale ubytování je obvykle k dispozici celoročně.</p>"
    ."<h3>4. Je nutné mít rybářskou licenci pro tyto výlety?</h3>\n<p>Ano, pro rybolov je nutné mít platnou rybářskou licenci. Tuto licenci je možné zakoupit na příslušných místech, jako jsou turistická centra nebo specializované obchody s rybářským vybavením.</p>"
    .'<p>Dalším zajímavým místem je Moravské rybářské jezero, které leží v Jihomoravském kraji. Toto jezero je vhodné pro rybolov jak v létě, tak v zimě, a v jeho okolí najdete různé penziony a hotely s možností ubytování.</p>'
    ."<p>3. Rybaření na pobřeží s ubytováním v penzionu</p>\n<p>Pokud preferujete rybaření u moře, vydejte se na pobřeží. Vyhledejte ubytování v penzionu v blízkosti pláže. Budete mít možnost rybařit a zároveň si užívat slunění a koupání. Ochutnejte čerstvé mořské plody v místních restauracích.</p>"
    .'<p>Další neměněný obsah</p><a href="https://www.lipno.info/">Původní vnější odkaz</a>';
$class = 'NRY_Tipy_Stay_Trust_57228_V1';
$fixed = $class::transform($original);
check($fixed !== $original, 'all exact anchors apply');
check(substr_count($fixed, $class::MARKER) === 1, 'one labeled box');
check(substr_count($fixed, $class::STAYS) === 1, 'one reviewed internal landing link');
check(strpos($fixed, 'Pension U Kohoutů') !== false, 'local Lipno stay named');
check(strpos($fixed, 'Jihomoravský kraj') !== false, 'Vranov region corrected');
check(strpos($fixed, 'rybářský lístek a příslušná povolenka') !== false, 'permit guidance corrected');
check(strpos($fixed, 'Moravské rybářské jezero, které') === false, 'unverified locality declaimed');
check(strpos($fixed, 'Rybaření na pobřeží') === false, 'irrelevant sea idea removed');
check(strpos($fixed, 'https://www.lipno.info/') !== false, 'original outbound href retained');
check(strpos($fixed, 'https://na-ryby.eu/stary-odkaz/') !== false, 'original internal href retained');
check(strpos($fixed, 'Další neměněný obsah') !== false, 'unaffected text retained');
check($class::transform($fixed) === $fixed, 'idempotent');
check($class::render($original) === $original, 'source SHA mismatch fails closed');
$drift = str_replace('Zlínský kraj', 'Jiný kraj', $original);
check($class::transform($drift) === $drift,
    'drifted visible anchor does not allow partial changes');
check($class::transform($original.$original) === $original.$original,
    'duplicate article blocks all changes');
check($class::transform($original.'<a href="'.$class::STAYS.'">Previously inserted</a>')
    === $original.'<a href="'.$class::STAYS.'">Previously inserted</a>', 'destination pre-existence blocks duplicate CTA');
echo "PASS: 57228 exact-source candidate; existing hrefs preserved; no WP writes/merchant clicks\n";
