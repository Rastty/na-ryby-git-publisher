#!/usr/bin/env python3
"""Offline unit tests: a URL elsewhere on the page must never prove release."""
import importlib.util
from pathlib import Path

SOURCE = Path(__file__).resolve().parents[1] / 'scripts/verify-revir-menu-discovery-public.py'
spec = importlib.util.spec_from_file_location('nry_revir_menu_readback', SOURCE)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


def fixture(routes=None, parent=None, child_marker=True, nested=True):
    routes = module.ROUTES if routes is None else routes
    parent = module.PARENT if parent is None else parent
    children = ''.join('<li class="menu-item '+(module.MARKER if child_marker else '')+'">'
                       '<a href="'+url+'">'+title+'</a></li>'
                       for title, url in routes)
    if not nested:
        return ('<ul><li><a href="'+parent[1]+'">'+parent[0]
                +'</a></li>'+children+'</ul>')
    return ('<nav><ul><li class="menu-item menu-item-has-children"><a href="'
            +parent[1]+'">'+parent[0]+'</a><ul class="sub-menu">'
            +children+'</ul></li></ul></nav>')


def check(condition, message):
    if not condition:
        raise AssertionError(message)


def run():
    fn = module.verify_public_html
    check(fn(fixture())['status'] == 'PUBLIC_VERIFIED_ALL', 'exact nested menu accepted')
    check(fn(fixture()*2)['verified_menus'] == 2, 'desktop/mobile duplicates okay')
    check(fn(fixture(nested=False))['status'] != 'PUBLIC_VERIFIED_ALL', 'loose links cannot prove release')
    check(fn(fixture(child_marker=False))['status'] != 'PUBLIC_VERIFIED_ALL', 'marker required')
    wrong = list(module.ROUTES)
    wrong[1] = (wrong[1][0], 'https://example.org/fishing')
    check(fn(fixture(routes=wrong))['status'] != 'PUBLIC_VERIFIED_ALL', 'wrong destination rejected')
    check(fn(fixture(routes=module.ROUTES[:2]))['status'] != 'PUBLIC_VERIFIED_ALL', 'missing child rejected')
    check(fn(fixture(routes=module.ROUTES+(module.ROUTES[0],)))['status'] != 'PUBLIC_VERIFIED_ALL', 'duplicate child rejected')
    check(fn(fixture(parent=('Revíry', 'https://example.org/')))['status'] != 'PUBLIC_VERIFIED_ALL', 'parent URL preserved')
    check(fn('<h1>Revíry</h1><a href="'+module.ROUTES[0][1]+'">Jižní Morava</a>')['status'] != 'PUBLIC_VERIFIED_ALL', 'article links cannot prove menu')
    check(fn(None)['status'] == 'INVALID_HTML_INPUT', 'invalid input fails closed')
    print('PASS: exact nested menu public verifier rejects missing, duplicate and off-site routes')


if __name__ == '__main__':
    run()
