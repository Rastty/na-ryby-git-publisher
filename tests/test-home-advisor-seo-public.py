"""Offline regression checks for the independent, GET-only readback parser."""
import sys
from html import escape
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
import importlib

seo = importlib.import_module("verify-home-advisor-seo-public")


def fixture(url, title, desc, robots="index, follow"):
    return ("<html><head><title>" + escape(title) + "</title>"
            + '<meta name="robots" content="' + robots + '">'
            + '<meta name="description" content="' + escape(desc, quote=True) + '">'
            + '<link rel="canonical" href="' + url + '">'
            + '</head><body><p>Article body is never mutated.</p></body></html>')


def blocks(fn):
    try:
        fn()
    except AssertionError:
        return
    raise AssertionError("guard did not reject corrupted public HTML")


for url, (title, desc) in seo.PAGES.items():
    html = fixture(url, title, desc)
    assert seo.verify_html(url, html)["description_present"]
    assert seo.verify_html(url, html, after_deploy=False)["url"] == url
    blocks(lambda: seo.verify_html(url, fixture(url, "Old title", desc)))
    blocks(lambda: seo.verify_html(url, fixture(url, title, "Wrong summary")))
    blocks(lambda: seo.verify_html(url, fixture(url, title, desc, "noindex")))
    blocks(lambda: seo.verify_html(url, html.replace('rel="canonical"', 'rel="alternate"')))
    blocks(lambda: seo.verify_html(url, html.replace('href="' + url + '"', 'href="https://other.example/"')))
print("PASS exact two-page title, description, canonical, indexability, tampering rejection")
