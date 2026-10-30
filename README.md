# Na-Ryby Git Publisher

Source: curated release from the private Prometheus growth repository.
This public repository contains only PHP plugin code and a bounded editorial manifest.
No WordPress passwords, API tokens, publisher IDs, affiliate credentials or private project files.

Install/update this repository as a WordPress plugin using Deployer for Git.
Branch: main; plugin folder: na-ryby-git-publisher.

Deploying plugin code is NOT equivalent to editing the WordPress database.
The plugin deliberately requires an admin to review the rows at
Tools > Na-Ryby Git Publisher and click Apply once after a new manifest.
No article mutation on activation, Git pull, webhook or plugin update.
Legacy schema v1 is immutable (posts 52975 and 118517 already applied).
Schema v2 supports 1-25 reviewed source-SHA-bound internal guidance inserts.
Schema v3 adds bounded context-specific decisions and plain-text factual corrections.
Schema v4 adds source-and-candidate-SHA-bound structural cleanup in batches of up to 25 posts.
Protected measurement IDs 38064 and 42671 are always excluded.

Future content batches must have exact reviewed IDs, slugs and safe anchors.
No public write endpoints or unattended editorial mutations are enabled.

## V5 — dva nákupní průvodce bez WPVibe

Manifest `buyer-rescue-two-50011-53630-20261025-v1` je uzamčen pouze na články
`50011` (starší kaprové pruty z roku 2020) a `53630` (výběr růžového prutu).

Git Publisher v0.5 získává původní obsah přímo přes `get_post()` **na serveru WordPressu**,
bez WPVibe nebo vnějších REST zápisů. Kontroluje stav publikace, slug, identitu,
datum poslední změny a přesnou bytovou délku původního textu. Jakýkoli nesoulad
blokuje **celou dvoučlánkovou dávku**, nic se nepublikuje. Před zápisem uloží
neměnnou zálohu obou původních HTML textů do neveřejné WP option
`nry_gp_backup_<md5(batch_id)>` (autoload vypnuto). Každý zápis přepočítá SHA
aktuálního serverového zdroje; po `wp_update_post()` ověří SHA výsledku.
V případě neúspěchu se pokusí obnovit původní obsah.

Obsah je jen předřazen na začátek existujícího článku; původní text, affiliate
odkazy, obrázky a shortcodes zůstávají doslova beze změny. Nové odkazy smějí
směřovat pouze na články v `https://na-ryby.eu/`. Žádné skripty, formuláře,
externí cíle, nepovolené HTML, změna URL ani automatický cron publishing.

**Nasazení není publikace.** Po aktualizaci repozitáře WordPress pluginu
z GitHub `main` otevřete ve správě WordPressu
`Nástroje > Na-Ryby Git Publisher`
(`/wp-admin/tools.php?page=nry-git-publisher`).
U obou položek musí být `ready`, až poté klikněte
`Zapsat ověřené změny do WordPressu`.
Po úspěšném zápisu je stav `applied_and_verified`, při dalším zobrazení
obě položky `already_applied`. Při `blocked` nezkoušejte vynucený zápis;
nejdřív zkontrolujte změnu původního článku a schválený manifest.

Zveřejnění v této verzi je úmyslně podmíněno jediným kliknutím administrátora.

## V6 — čtyři odlišné nákupní otázky k rybářským křeslům

Manifest `chair-decision-four-20261025-v1` je pevně omezen na články:
`34882` feederové křeslo, `34905` ověřená nosnost 200 kg, `34921`
podnožka proti samostatnému lehátku a `35026` bezpečné elektrické vyhřívání.
Žádné již publikované články z předchozích dávek ani ochranné kohorty nejsou součástí.

Získání původního `post_content` a zápis probíhají pouze uvnitř WordPressu.
Veřejný REST poskytuje jen renderovaný obsah, ne věrohodný přesný počet bytů
původního HTML. Proto v6 místo uhodnutého `source_bytes` používá
identitu + přesné `post_modified_gmt` z veřejného WordPress API +
předem určený bezpečný interval 6000–25000 bytů; samotný původní obsah
a jeho SHA se uloží při serverovém preflightu a znovu ověří bezprostředně
před každým zápisem. Záloha do neveřejné WordPress option je povinná
a musí se ověřit před prvním zápisem. Všechny čtyři články se
odmítnou jako celek, pokud jediný nesplní preflight.

Nový obsah se jen předřazuje na začátek, původní odkazy, obrázky,
shortcodes i obchodní tracking zůstávají beze změny. Editor smí vkládat
pouze bezpečné HTML s interními odkazy. Publikaci nadále spouští
správce na `Nástroje > Na-Ryby Git Publisher`; samotný merge/deploy
ani GitHub test **nepíší do databáze WordPressu**.

V4/V5 jsou zachovány pro zpětnou kompatibilitu. Unit testy regresí
v5 a test nové v6 dávky se spouštějí v GitHub Actions.

## V7 — čtyři další konkrétní nákupní situace (bez WPVibe)

Pevně omezené post ID: 34913 (nosnost 120 kg), 34977 (nosnost 160 kg),
35003 (vysoké opěradlo a příliš silné sliby o bolesti zad),
35011 (Mivardi — přesná modelová řada a kompatibilita).
Všechny čtyři byly zjištěny veřejným WordPress REST jako publikované,
bez předchozího redakčního markeru, se změnou z roku 2023; historicky
`UPDATE_EXPAND_REVIEW` podle ověřené route-specific evidence 9/2026.

Schéma 7 používá stejné přísné on-server preflight, ochranu původního
`post_content`, zpětnou obnovu a soukromou zálohu jako v6.
Přesně kontroluje identitu, datum poslední změny a bezpečný interval
velikosti zdroje. Jestli jediný článek neprojde, nepublikuje žádný.
Interní prolinkování cílí na ověřené praktické průvodce místo
historicky chráněného obchodního hubu, kde byly zjištěny neověřené
výrobní/prodejní sliby. Žádná původní komerční URL není přepsána.

**GitHub merge není publikování článků.** Po aktualizaci pluginu přes
Deployer for Git otevřete `/wp-admin/tools.php?page=nry-git-publisher`,
ověřte čtyři stavy `ready`, potom pouze jednou stiskněte Apply.
Dřívější v5/v6 dávky zůstávají nezměněné. Přesnou publikaci
dokládá nový nezávislý public marker audit.

## V0.8 — jeden měřitelný SEO snippet experiment bez WPVibe

Nová samostatná obrazovka **Nástroje > Na-Ryby SEO CTR pilot**
(`/wp-admin/tools.php?page=nry-seo-ctr-pilot`) je pevně svázána s post ID
`42401` a slugem
`jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne`.

Podklad z aktuálního GSC (24. 7.–21. 10. 2026):
799 page-level impressions / 1 click, průměrná pozice 8,22.
Dominantní GSC query+page: „pípáky na ryby“ 383/0, „pipaky na ryby“
103/0, „pípák na ryby“ 59/0; podrobnosti v issue
`Rastty/prometheus-growth-os#997`. GSC query report neobsahuje
anonymizované dotazy a nelze jeho počty zaměňovat za celkové.

**Změna pouze Yoast SEO polí:**
`_yoast_wpseo_title` na
`Pípáky na ryby: jak vybrat, nastavit a používat` a
`_yoast_wpseo_metadesc` na stručný věcný popis nastavení,
citlivosti a sady s příposlechem. Žádná změna WordPress
`post_title`, URL, `post_content`, canonical, obrázků ani href.

Před zápisem přímou serverovou WP funkcí `get_post()` ověřit přesné
post ID, slug, stav published, předchozí datum změny
`2026-10-23 06:34:54`, původní Yoast meta hodnoty, typ a SHA
obsahu. Před zápisem se uloží nezaměnitelná, neveřejná záloha původního
obsahu (SHA) a metadat jako WP option s vypnutým autoloadem.
Při neočekávaném stavu blokace nebo rollback.
Opravdu nic se nepublikuje automaticky při GitHub merge ani deploy.

Samostatný administrační formulář s capability `manage_options`
a nonce spouští jednorázový Apply, potom zobrazuje
`applied_and_verified` nebo přesný blokující stav.
Opakované kliknutí je idempotentní. Dřívější HTML vydání v0.7
zůstává beze změny. **Nezaměňujte GitHub CI/merge za skutečný
SEO snippet na webu**: po Apply je nutno ověřit veřejné Yoast
`yoast_head_json.title` / `description` a samotné renderované meta.
Google může ve výsledcích zobrazit upravený vlastní snippet.

## V0.9 — samostatný checklist (nová URL, explicitní admin Apply)

**Release candidate, not live until verified.** Product source: private
`Rastty/prometheus-growth-os` merged PR #1085; technical handoff #1086.
The self-contained HTML is pinned by SHA-256 **at the time of admin approval**.
The page is served at `https://na-ryby.eu/co-sbalit-na-ryby/` only after the
administrator approves its exact installed bytes.

The publisher v0.9 adds `checklist-release-v1.php` and
`assets/checklist-standalone.html`. There is **no WordPress post write**,
theme change, affiliate change, homepage replacement, automatic activation
or REST write. Existing editorial manifest and admin Apply remain unchanged.
No new route is exposed on Git pull until explicit approval.

Release checklist (technical owner only, not permission to skip gates):

1. Review and merge only with all required CI green. Install/update plugin
   through the existing **authorized** Deployer for Git path, not via
   ad-hoc REST retries. Check plugin v0.9 is actually active.
2. Confirm that neither WP pages nor posts own the slug
   `co-sbalit-na-ryby`, and the installed origin is exactly
   `https://na-ryby.eu`. The server-side preflight blocks a collision.
3. In WordPress, open **Tools → Na-Ryby Checklist Release**
   (`/wp-admin/tools.php?page=nry-checklist-release`). Review the displayed
   SHA-256, absence of collision, and exact target. Administrator
   `manage_options` + nonce must click **Apply**. No scheduled job,
   webhook, Git merge or remote REST call may impersonate this step.
4. The admin receipt after Apply is **approved_pending_public_readback**,
   *not* deployed/verified. Independently request the public URL and
   compare the `X-NRY-Checklist-Asset-SHA256` header to the approved SHA,
   HTML marker, canonical, script integrity, and a real browser at
   360/390/768/1280. Test storage, print, share query, keyboard and
   accessibility. Only then issue a dated production receipt.
5. If public verification fails, use **Rollback: vypnout checklist URL**
   on the same admin screen; check the route is no longer served and record
   the receipt. The rollback does not touch existing posts or affiliate links.

An update to the HTML changes its SHA and **automatically fails closed**
until rollback and a new admin Apply. Route interception is exact-path only,
including optional share query parameters; existing URLs remain untouched.
The response disallows network requests and external scripts via CSP and
does not collect email or transmit fishing location. This release is separate
from editorial batches and does not clear intermittent REST 401 issue #865.

