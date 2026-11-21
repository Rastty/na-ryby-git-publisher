"""Offline fixtures for source-pinned homepage process-copy public verification."""
from importlib.util import module_from_spec, spec_from_file_location
from pathlib import Path

script = Path(__file__).resolve().parents[1] / "scripts/verify-home-visitor-copy-public.py"
spec = spec_from_file_location("nry_home_visitor_copy", script)
module = module_from_spec(spec)
spec.loader.exec_module(module)


def home(edited=False):
    pairs = module.SENTENCES
    marker = ('<span hidden data-nry-home-visitor-copy="v1"></span>'
              if edited else "")
    return ('<!doctype html><html><head><link rel="canonical" href="' +
            module.HOME + '"></head><body><main>' +
            '<span hidden data-nry-home-trust="source-bound-118641-20261109-v1"></span>' +
            marker + '<nav data-nry-home-problem-shortcuts="v5">' +
            '<a ' + module.ROUTE_STAYS + '>Pobyty</a>' +
            '<a ' + module.ROUTE_WATERS + '>Revíry</a></nav>' +
            ''.join('<p>' + pair[1 if edited else 0] + '</p>' for pair in pairs) +
            '<p>' + 'Nadbytečný původní obsah bez změny. ' * 160 + '</p>' +
            '</main></body></html>')


def reject(doc, preflight, code):
    try:
        module.verify(doc, preflight)
    except ValueError as e:
        assert str(e) == code, (code, e)
    else:
        raise AssertionError("Expected to fail closed: " + code)


old = home()
new = home(True)
assert module.verify(old, True)["status"] == "HOME_VISITOR_COPY_PREFLIGHT_VERIFIED"
assert module.verify(new)["status"] == "HOME_VISITOR_COPY_PUBLIC_VERIFIED"
assert module.verify(new)["reviewed_sentences"] == 6
reject(new, True, "HOME_COPY_MARKER_NOT_IN_EXPECTED_STATE")
reject(old, False, "HOME_COPY_MARKER_NOT_IN_EXPECTED_STATE")
reject(new.replace('data-nry-home-visitor-copy="v1"', ""), False,
       "HOME_COPY_MARKER_NOT_IN_EXPECTED_STATE")
reject(new.replace('data-nry-home-visitor-copy="v1"',
                   'data-nry-home-visitor-copy="v1" data-nry-home-visitor-copy="v1"'),
       False, "HOME_COPY_MARKER_NOT_IN_EXPECTED_STATE")
reject(new.replace('<span hidden data-nry-home-visitor-copy="v1"></span>',
                   '<span hidden data-nry-home-visitor-copy="v1"></span>' * 2),
       False, "HOME_COPY_MARKER_NOT_IN_EXPECTED_STATE")
reject(new.replace(module.SENTENCES[2][1], "Změněný článek"), False,
       "HOME_COPY_MISSING_OR_DUPLICATE_3")
reject(new.replace(module.SENTENCES[1][1],
                   module.SENTENCES[1][1] + " " + module.SENTENCES[1][1]), False,
       "HOME_COPY_MISSING_OR_DUPLICATE_2")
reject(new.replace(module.SENTENCES[3][1],
                   module.SENTENCES[3][1] + module.SENTENCES[3][0]), False,
       "HOME_OLD_AND_NEW_COPY_BOTH_PRESENT_4")
reject(new.replace(module.HOME, 'https://another.example/', 1), False,
       "HOME_CANONICAL_MISMATCH")
reject(new.replace(module.ROUTE_STAYS, 'href="/fake-stay/"'), False,
       "VERIFIED_STAY_OR_WATER_PATH_MISSING")
reject(new.replace(module.ROUTE_WATERS, 'href="/fake-water/"'), False,
       "VERIFIED_STAY_OR_WATER_PATH_MISSING")
reject(new.replace("Nadbytečný původní", "DOPLNIT-URL"), False,
       "UNFINISHED_EDITORIAL_NOTE_REAPPEARED")
reject(new.replace(module.SOURCE, "source marker removed"), False,
       "SOURCE_PROOF_MISSING_OR_DUPLICATE")
reject(new[:1000], False, "BOUNDED_HOME_HTML_REQUIRED")
print("PASS: six public visitor copy replacements, exact source/target/canonical, preflight and fail-closed")
