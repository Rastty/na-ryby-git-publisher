<?php
/**
 * Exact-source, render-only trust and internal-stay handoff for post 57228.
 *
 * Does not alter stored post_content, metadata, affiliate URLs, cookies or
 * booking measurements. Reverts immediately by removing its one require.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Tipy_Stay_Trust_57228_V1 {
    const ID = 57228;
    const SLUG = 'tipy-na-vylety-pro-rybare-s-ubytovanim-v-prirode';
    const URL = 'https://na-ryby.eu/tipy-na-vylety-pro-rybare-s-ubytovanim-v-prirode/';
    const MODIFIED_GMT = '2023-07-07 23:12:56';
    const SOURCE_SHA256 = '2f8f132c42f1cda47781f9693711de108b6fb2411d99382025060bcfe38325e3';
    const STAYS = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const MARKER = 'data-nry-tipy-stay-trust="v1"';

    public static function boot() {
        // The backend writer and its offline mocks must never register this filter.
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), 75);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 28);
        }
    }

    private static function eligible($loop = true) {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id() !== self::ID
            || ($loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post' && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null)
            && hash('sha256', $p->post_content) === self::SOURCE_SHA256;
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-tipy-stay-trust-57228-v1',
            plugins_url('assets/nry-tipy-stay-trust-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $matches) === false) return null;
        return $matches[2];
    }

    public static function transform($html) {
        if (!is_string($html) || strpos($html, self::MARKER) !== false
            || strpos($html, self::STAYS) !== false) return $html;

        $intro = '<section class="nry-tipy-stay-trust" '.self::MARKER
            .' aria-label="Konkrétní pobyty u rybářských lokalit">'
            .'<p class="nry-tipy-stay-trust__label">Pobyty u vody – konkrétní možnosti</p>'
            .'<h2>Vyberte nejdřív revír, potom ubytování</h2>'
            .'<p>V samostatném přehledu najdete například Pension U Kohoutů v oblasti Lipna, '
            .'Pension U Vlčků u Hracholusk a Pension Janata u Slap. Jde o různé objekty '
            .'a partnerské nabídky, nikoli garanci rybolovu nebo volného termínu.</p>'
            .'<p><a class="nry-tipy-stay-trust__cta" href="'.self::STAYS.'">'
            .'Zobrazit konkrétní ubytování pro rybáře</a></p>'
            .'<p><small>Odkazy na cílové stránce mohou být partnerské. '
            .'Před rezervací ověřte polohu, cenu, dostupnost, rybářský lístek, povolenku '
            .'a pravidla zvoleného revíru.</small></p></section>';
        $replacements = array(
            '<p>Dobře vybíráte! Pokud jste rybář a hledáte výlety s perfektním ubytováním v krásné přírodě, jste na správném místě. Nabízíme širokou škálu destinací, které splní vaše rybářské sny.</p>'
                => '<p>Hledáte rybářskou dovolenou s ubytováním blízko vody? Začněte konkrétní lokalitou a ověřte si revír, podmínky lovu i dostupnost ubytování.</p>'.$intro,
            '<h3>Přehrada Vranov &#8211; Zlínský kraj</h3>'
                => '<h3>Vranovská přehrada &#8211; Jihomoravský kraj</h3>',
            'Nachází se ve Zlínském kraji a je známá svými neobyčejně čistými vodami.'
                => 'Leží u Vranova nad Dyjí v Jihomoravském kraji. Podmínky rybolovu ověřte podle konkrétního revíru.',
            'Náš tým je sám vášnivým rybářem a důkladně vybíráme nejlepší destinace, které potěší rybáře všech úrovní dovedností. Bez ohledu na to, zda jste začátečník nebo zkušený profesionál, najdete u nás ideální místo pro své rybářské dobrodružství.'
                => 'Jde o orientační průvodce lokalitami, nikoli o osobně prověřené ubytovací kapacity. Konkrétní tipy na pobyty jsou uvedené samostatně a jejich dostupnost se mění.',
            "<h3>3. Jsou tyto ubytování dostupná po celý rok?</h3>\n<p>Ano, většina rybářských destinací a ubytování v přírodě je dostupná po celý rok. V závislosti na konkrétní lokalitě se může lišit sezóna rybolovu, ale ubytování je obvykle k dispozici celoročně.</p>"
                => "<h3>3. Lze se ubytovat během celého roku?</h3>\n<p>Záleží na konkrétním objektu a volném termínu. Před rezervací ověřte provozní dobu, cenu, dostupnost i místní pravidla rybolovu.</p>",
            "<h3>4. Je nutné mít rybářskou licenci pro tyto výlety?</h3>\n<p>Ano, pro rybolov je nutné mít platnou rybářskou licenci. Tuto licenci je možné zakoupit na příslušných místech, jako jsou turistická centra nebo specializované obchody s rybářským vybavením.</p>"
                => "<h3>4. Jaké doklady potřebuji k rybolovu?</h3>\n<p>Na revírech ČRS se běžně vyžaduje rybářský lístek a příslušná povolenka k lovu. U soukromých vod se podmínky mohou lišit. Před cestou si ověřte pravidla pro konkrétní vodu.</p>",
            'Dalším zajímavým místem je Moravské rybářské jezero, které leží v Jihomoravském kraji. Toto jezero je vhodné pro rybolov jak v létě, tak v zimě, a v jeho okolí najdete různé penziony a hotely s možností ubytování.'
                => 'Starší text zde uváděl neurčené „Moravské rybářské jezero“. Bez přesného názvu a identifikace vodní plochy jej nelze doporučit jako revír ani jako základ pro rezervaci. Pro jižní Moravu začněte konkrétní lokalitou, například Vranovskou přehradou.',
            "<p>3. Rybaření na pobřeží s ubytováním v penzionu</p>\n<p>Pokud preferujete rybaření u moře, vydejte se na pobřeží. Vyhledejte ubytování v penzionu v blízkosti pláže. Budete mít možnost rybařit a zároveň si užívat slunění a koupání. Ochutnejte čerstvé mořské plody v místních restauracích.</p>"
                => "<p>3. Slapy a ubytování v penzionu</p>\n<p>Pokud hledáte českou přehradu s možností přenocování v okolí, zvažte Slapy. V samostatném přehledu pobytů je i Pension Janata. Vzdálenost objektu od vybraného místa lovu a dostupnost termínu si ověřte předem.</p>",
        );
        $old_hrefs = self::hrefs($html);
        if ($old_hrefs === null) return $html;
        $changed = $html;
        foreach ($replacements as $old => $new) {
            // One unexpected editorial/theme change cancels the entire patch.
            if (substr_count($changed, $old) !== 1) return $html;
            $changed = str_replace($old, $new, $changed);
        }
        $new_hrefs = self::hrefs($changed);
        $without_new = array_values(array_filter($new_hrefs ?? array(), function ($url) {
            return $url !== self::STAYS;
        }));
        return ($new_hrefs !== null && $without_new === $old_hrefs
            && count($new_hrefs) === count($old_hrefs) + 1
            && substr_count($changed, self::STAYS) === 1
            && substr_count($changed, self::MARKER) === 1) ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Tipy_Stay_Trust_57228_V1::boot();
