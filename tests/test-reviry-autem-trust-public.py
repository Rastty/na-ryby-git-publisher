"""Offline public-readback contract: exact source, routes, styles and corrections."""
import runpy
from pathlib import Path

m = runpy.run_path(str(Path(__file__).resolve().parents[1] / "scripts" /
                       "verify-reviry-autem-source.py"))
verify = m["verify_public"]
PAGE, STYLE = m["PAGE"], m["STYLE_URL"]
SOURCE = {"sourceSha256": m["EXPECTED_RAW_SHA256"]}
CSS = b"x" * 800
LINKS = [
    "https://na-ryby.eu/rybareni-s-ubytovanim-jihocesky-kraj/",
    "https://na-ryby.eu/kde-prespat-u-orliku-na-rybarsky-vikend/",
    "https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/",
    "https://na-ryby.eu/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/",
]


def html(links=LINKS, css=STYLE, claim="Orlická přehrada leží na Vltavě."):
    return (
        '<html><head><link rel="canonical" href="' + PAGE + '">'
        '<link rel="stylesheet" href="' + css + '"></head><body class="postid-56493">'
        '<h2>Kam se dostanete autem</h2>'
        '<section data-nry-reviry-autem-trust="v1">'
        'Podyjí a Šumava jsou chráněná území. '
        'Ubytování není automaticky povolenkou k rybolovu.'
        + "".join('<a href="' + u + '">Další</a>' for u in links)
        + '</section>'
        + claim
        + '<h3>Podyjí: lov jen na konkrétně povolených úsecích</h3>'
        + '<h3>Šumava: nejprve ověřte konkrétní revír a ochranu přírody</h3>'
        + '</body></html>')


result = verify(html(), CSS, SOURCE, CSS)
assert result["status"] == "NRY_REVIRY_AUTEM_56493_PUBLIC_VERIFIED"
assert result["internalRoutes"] == 4 and result["wordpressWrites"] == 0
for candidate, css in (
    (html(links=LINKS[:-1]), CSS),
    (html(css="https://other.example/theme.css"), CSS),
    (html(claim=m["ANCHORS"]["orlíkWrongGeo"]), CSS),
    (html().replace('data-nry-reviry-autem-trust="v1"', "no-trust"), CSS),
    (html(), b"wrong"),
):
    try:
        verify(candidate, css, SOURCE, CSS)
    except ValueError:
        continue
    raise AssertionError("UNSAFE_PUBLIC_PROOF_ACCEPTED")
print("REVIRY_AUTEM_56493_PUBLIC_READBACK_CONTRACT_OK")
