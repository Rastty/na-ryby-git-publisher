#!/usr/bin/env python3
"""Offline exact-meta semantic contract, no production HTTP in regression tests."""
import importlib.util
from html import escape
from pathlib import Path

source = Path(__file__).resolve().parents[1] / 'scripts/verify-romantic-stay-meta-public.py'
spec = importlib.util.spec_from_file_location('romantic_meta', source)
romantic = importlib.util.module_from_spec(spec)
spec.loader.exec_module(romantic)


def document(description, og=None, title=romantic.TITLE, canonical=romantic.URL,
             robots='index, follow', offers=True):
    css = '<link rel="stylesheet" href="' + romantic.CSS_URL + '">' if offers else ''
    if offers:
        body = ('<section data-nry-romantic-stay-offers="v1">'
                '<article data-nry-romantic-offer="hauzi-rybarsky-srub">'
                '<a href="' + escape(romantic.HAUZI_SRUB, quote=True)
                + '" rel="sponsored nofollow noopener" target="_blank">Srub</a></article>'
                '<article data-nry-romantic-offer="hauzi-pro-dva-wellness">'
                '<a href="' + escape(romantic.HAUZI_MORE, quote=True)
                + '" rel="sponsored nofollow noopener" target="_blank">Další</a></article>'
                '<a href="' + romantic.CATEGORY + '">Přehled</a></section>')
    else:
        body = '<p>Mrknout na doporučené ubytování</p>'
    return ('<html><head><title>' + escape(title) + '</title>'
            + '<meta name="robots" content="' + escape(robots) + '">'
            + '<meta name="description" content="' + escape(description, quote=True) + '">'
            + '<meta property="og:description" content="'
            + escape(og if og is not None else description, quote=True) + '">'
            + '<link rel="canonical" href="' + escape(canonical, quote=True) + '">'
            + css + '</head><body>' + body
            + '<h2>Co si pod tím lidé obvykle představují</h2></body></html>')


def rejects(html, preflight=False):
    try:
        romantic.verify(html, preflight)
    except AssertionError:
        return
    raise AssertionError('unsafe readback passed')


assert romantic.verify(document(romantic.OLD, offers=False), preflight=True) == 'BASELINE_PUBLIC_VERIFIED'
assert romantic.verify(document(romantic.NEW)) == 'UPDATED_META_PUBLIC_VERIFIED'
rejects(document(romantic.OLD, offers=False))
rejects(document(romantic.NEW), preflight=True)
rejects(document(romantic.OLD, offers=True), preflight=True)
rejects(document(romantic.NEW, offers=False))
rejects(document(romantic.NEW, og=romantic.OLD))
rejects(document(romantic.NEW, title='Changed title'))
rejects(document(romantic.NEW, canonical='https://wrong.example/'))
rejects(document(romantic.NEW, robots='noindex,nofollow'))
rejects(document(romantic.NEW).replace('rel="canonical"', 'rel="alternate"'))
rejects(document(romantic.NEW).replace('</head>',
       '<meta name="description" content="duplicate"></head>'))
rejects(document(romantic.NEW).replace(escape(romantic.HAUZI_SRUB, quote=True), 'https://wrong.example/'))
rejects(document(romantic.NEW).replace('rel="sponsored nofollow noopener"', 'rel="nofollow"'))
rejects(document(romantic.NEW).replace(romantic.CSS_URL, 'https://wrong.example/style.css'))
rejects(document(romantic.NEW).replace('data-nry-romantic-offer="hauzi-rybarsky-srub"', 'data-nry-romantic-offer="wrong"'))
rejects(document(romantic.NEW).replace('data-nry-romantic-stay-offers="v1"', 'data-nry-romantic-stay-offers="v2"'))
print('PASS romantic baseline/new meta, 2 exact Hauzi CTAs, style, title, canonical, noindex guards')
