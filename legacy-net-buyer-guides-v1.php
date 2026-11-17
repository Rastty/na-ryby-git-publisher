<?php
/**
 * Render-only corrections and buyer handoff for two untouched 2023 net guides.
 * Immutable source guards, exact original-text anchors and preservation of
 * every existing href are mandatory. No WordPress post, affiliate or SEO write.
 * Rollback: remove this require from na-ryby-git-publisher.php.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Legacy_Net_Buyer_Guides_V1 {
    const GUIDE = 'https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-podberak-na-kapry/';
    const MARKER = 'data-nry-net-buyer-path="v1"';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), 76);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 29);
        }
    }

    public static function cohorts() {
        return array(
            42642 => array(
                'slug'=>'jak-vybrat-ten-nejlepsi-rybarsky-podberak-na-kapry',
                'modified'=>'2023-04-29 02:20:28',
                'intro'=>'<h2>Jak vybrat ten nejlepší rybářský podběrák na kapry?</h2>',
                'summary'=>'Pro kapry rozhoduje velikost a hloubka sítě, tuhá konstrukce a dosah rukojeti podle břehu. Původní text z roku 2023 obsahoval nepřesná obecná doporučení.',
                'replacements'=>array(
                    'Rozměry by měly být minimálně 60&#215;80 cm.'
                        => 'Zvolte rozměry podle velikosti ryb a hloubky sítě; jedna minimální velikost není správná pro všechny situace.',
                    'Nejčastěji se používá materiál sítě ze skelných vláken.'
                        => 'Pro kapry sledujte hlavně jemnost a šetrnost síťoviny; materiál a pevnost ramen ověřte zvlášť.',
                    'Délka</b> rukojeti podběráku by měla být vhodná pro velikost vaší ruky, aby jste s ním mohli pohodlně manipulovat. Zároveň by měla být dostatečně dlouhá, abyste mohli dosáhnout ke každému místu rybníka.'
                        => 'Délka</b> násady se vybírá podle výšky břehu, překážek a dosahu při podebírání. Úchop má být jistý i s mokrou rukou; násada samozřejmě nedosáhne na libovolné místo rybníka.',
                    'Měla by být dostatečně velká, aby se vám do ní vešla velká kapří ryba, ale zároveň dostatečně malá, aby se ryba nemohla z ní vyklouznout.'
                        => 'Sledujte rozměr a hloubku celé sítě, jemnost ok a riziko zachycení ploutví či háčku, nikoli pouze velikost jednoho oka.',
                    'Existují dva typy podběráků &#8211; plavoucí a potápivé. Potápivé podběráky jsou určené pro hlubší vody. Pokud se chystáte lovit spíše v mělkých vodách, volte plavoucí podběrák.'
                        => 'Podběráky se pro výběr na kapry obvykle porovnávají podle tvaru a velikosti hlavy, materiálu sítě, tuhosti ramen a délky násady. Plovák na násadě je doplněk, nikoli základní druh podběráku pro mělkou či hlubokou vodu.',
                    'Existují dva typy podběráků &#8211; potápivé a plavoucí.'
                        => 'Pro kapry je podstatná bezpečná manipulace s rybou, dostatečná hloubka sítě a ovladatelnost podběráku.',
                    'Zvažte další funkce, jako například trny, které pomohou udržovat rybu v podběráku nebo způsob, jakým se trnuje podběrák ke košíku.'
                        => 'Dbejte na hladké spoje, nepoškozenou síť a šetrnou manipulaci. Ostré výstupky by mohly rybu poranit.',
                ),
            ),
            39296 => array(
                'slug'=>'jak-si-vybrat-spravny-rybarsky-podberak-90x90-pro-vase-potreby',
                'modified'=>'2023-04-18 00:56:27',
                'intro'=>'<h2>Jak si vybrat správný rybářský podberak 90&#215;90 pro vaše potřeby?</h2>',
                'summary'=>'Rozměr 90 × 90 cm posuzujte spolu s hloubkou sítě, délkou násady a velikostí lovených ryb. Původní text z roku 2023 obsahoval několik zavádějících technických rad.',
                'replacements'=>array(
                    'Délka</b> podberaku je rovněž důležitá &#8211; měla by odpovídat délce vaší přívlačové nebo muškařské šňůry.'
                        => 'Délka</b> násady se vybírá podle výšky břehu, překážek a polohy rybáře při podebírání; délka šňůry s ní nesouvisí.',
                    'Pokud chytíte mnoho ryb s malými ústy, měli byste zvolit podberak s jemnou síťovinou, aby se ryby nemohly z něj vyvléknout.'
                        => 'Jemnou síť vybírejte hlavně kvůli šetrnosti k rybě a snížení rizika zachycení ploutví či háčku. Velikost úst ryby není určujícím parametrem sítě.',
                    'Existují dva hlavní typy podberků: podberky s kruhovými sítěmi a podberky s plochými sítěmi. Podberky s kruhovými sítěmi jsou o něco bezpečnější pro ryby, protože nedochází k poškození jejich kůže. Na druhou stranu podberky s plochými sítěmi jsou vhodnější pro větší ryby.'
                        => 'Tvar hlavy (například kruhový nebo trojúhelníkový) sám o sobě nedokazuje šetrnost k rybě. Sledujte velikost a hloubku sítě, její jemnost, pevnost rámu a vhodnost pro konkrétní druh ryby.',
                    'Většina podberků je vyráběna z nylonu nebo síťoviny. Nylonové podberky jsou obvykle lehčí a snadněji se používají, ale síťoviny jsou mnohem pevnější a vhodnější pro chytání větších ryb.'
                        => 'Nylon může být sám materiálem síťoviny. Samostatně porovnejte materiál ramen a násady, rozměr ok, povrch sítě a její odolnost.',
                    'Naopak, pokud máte v plánu chytat větší ryby, tak budete potřebovat těžší podberak.'
                        => 'Na větší ryby potřebujete především dostatečně velký a tuhý podběrák; vyšší hmotnost sama o sobě neznamená vyšší pevnost.',
                    'Tento druh podberaku je velikostí ideální pro lov menších ryb.'
                        => 'Rozměr 90 × 90 cm se používá i pro větší ryby; vhodnost určuje také hloubka a materiál sítě.',
                    'Podberaky mohou být vyrobeny z různých materiálů, ale nejčastěji se používá plast nebo síťovina. Síťovinové podberaky jsou vhodné pro vrhání a tahání ryb z vody. Plastové podberaky jsou o něco těžší a slouží zejména pro vyzvedávání ryb z vody, neboť se s nimi lépe manipuluje.'
                        => 'Při výběru oddělte materiál rámu a násady od materiálu sítě. Rybu do sítě opatrně naveďte a podeberte; vhodnost neurčuje samotné označení plast či síťovina.',
                ),
            ),
        );
    }

    private static function selected($in_loop = true) {
        if (!function_exists('get_queried_object_id')) return false;
        $id = (int)get_queried_object_id();
        $sets = self::cohorts();
        if (!isset($sets[$id]) || !function_exists('is_single') || !is_single($id)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || ($in_loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink')
            || get_permalink($id) !== 'https://na-ryby.eu/'.$sets[$id]['slug'].'/'
            || !function_exists('get_post')) return false;
        $p = get_post($id);
        if (!is_object($p) || (int)($p->ID ?? 0) !== $id
            || ($p->post_type ?? '') !== 'post'
            || ($p->post_status ?? '') !== 'publish'
            || ($p->post_name ?? '') !== $sets[$id]['slug']
            || ($p->post_modified_gmt ?? '') !== $sets[$id]['modified']) return false;
        return $sets[$id];
    }

    public static function enqueue() {
        if (!self::selected(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-legacy-net-buyer-v1',
            plugins_url('assets/nry-legacy-net-buyer-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $m) === false) return null;
        return $m[2];
    }

    public static function transform($html, $set) {
        if (!is_string($html) || strlen($html) < 6000 || !is_array($set)
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::GUIDE) !== false
            || substr_count($html, 'href="https://na-ryby.eu/podberaky"') !== 3
            || substr_count($html, $set['intro']) < 1) return $html;
        $first = strpos($html, $set['intro']);
        if ($first === false || $first > 1500) return $html;
        $before = self::hrefs($html);
        if ($before === null) return $html;
        $next = $html;
        foreach ($set['replacements'] as $old=>$new) {
            if (substr_count($next, $old) !== 1) return $html;
            $next = str_replace($old, $new, $next);
        }
        $at = strpos($next, $set['intro']) + strlen($set['intro']);
        $banner = '<aside class="nry-legacy-net-buyer" '.self::MARKER
            .' aria-label="Pomoc s výběrem podběráku">'
            .'<p class="nry-legacy-net-buyer__tag">Aktuální praktický výběr</p>'
            .'<h3>Nejdřív vyberte vhodný podběrák pro své místo lovu</h3>'
            .'<p>'.$set['summary'].'</p>'
            .'<a href="'.self::GUIDE.'">Přejít na průvodce velikostí, sítí a rukojetí →</a>'
            .'</aside>';
        $next = substr_replace($next, "\n".$banner."\n", $at, 0);
        $after = self::hrefs($next);
        if ($after === null || count($after) !== count($before) + 1) return $html;
        $existing = array_values(array_filter($after, function($href) {
            return $href !== self::GUIDE;
        }));
        return $existing === $before ? $next : $html;
    }

    public static function render($content) {
        $set = self::selected();
        return $set ? self::transform($content, $set) : $content;
    }
}
NRY_Legacy_Net_Buyer_Guides_V1::boot();
