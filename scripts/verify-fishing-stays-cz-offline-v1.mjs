#!/usr/bin/env node
// Site-only evidence contract; Product/Offer matching and economic ranking belong in Core.
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import {readFileSync} from 'node:fs';

const data = JSON.parse(readFileSync(new URL('../editorial/fishing-stays-cz-20261118.json', import.meta.url), 'utf8'));
assert.equal(data.schema_version, 1);
assert.equal(data.site, 'https://na-ryby.eu');
assert.equal(data.status, 'review_only_not_for_publication');
assert.equal(data.core_contract_owner, 'Rastty/prometheus-growth-os');
assert.equal(data.search_console.page_query_metrics, null);
assert.equal(data.search_console.economic_ranking, null);
assert.equal(data.affiliate.slevomat_affil_id, 'j3d7tsgc');
assert.equal(data.affiliate.slevomat_utm_domain, 'narybyCZ');
assert.equal(data.affiliate.hportal_dognet_program, 'owner_reports_approved');
assert.equal(data.affiliate.hportal_dognet_chid, '5QoXiSqx');
assert.equal(data.affiliate.hauzi_program, 'owner_reports_pending_affiliateport_approval');

const pageIds = new Set();
for (const page of data.target_pages) {
  assert(Number.isInteger(page.post_id) && page.post_id > 0);
  assert(!pageIds.has(page.post_id), 'duplicate post');
  pageIds.add(page.post_id);
  const parsed = new URL(page.url);
  assert.equal(parsed.origin, data.site);
  assert.equal(parsed.search, '');
  assert.equal(parsed.hash, '');
  assert.equal(page.gsc_priority, null, 'unobserved GSC score must not be ranked');
}
const waters = new Map();
for (const water of data.waters) {
  assert(/^[a-z_]+$/.test(water.water_key));
  assert(!waters.has(water.water_key), 'duplicate water identity');
  waters.set(water.water_key, water);
  assert.equal(water.water_type, 'merchant_described_private_pond');
  // A merchant-advertised private pond is not a verified public ČRS revír.
  assert.equal(water.public_fishery_id, null);
  assert.equal(water.existing_site_revir_url, null);
  const source = new URL(water.source);
  assert.equal(source.hostname, 'www.slevomat.cz');
}
const areas = new Map();
for (const area of data.fishing_areas) {
  assert(/^[a-z_]+$/.test(area.area_key));
  assert(!areas.has(area.area_key), 'duplicate fishing area');
  areas.set(area.area_key, area);
  assert.equal(area.specific_public_fishery_id, null, 'locality is not a licensed revír identity');
  if (area.existing_site_context_url !== null) {
    assert.equal(new URL(area.existing_site_context_url).origin, data.site);
  }
}
const offers = new Map();
for (const offer of data.stay_offers) {
  assert(/^(?:\d{6,9}|hportal-[a-z-]+)$/.test(offer.offer_id));
  assert(!offers.has(offer.offer_id), 'duplicate offer');
  offers.set(offer.offer_id, offer);
  const direct = new URL(offer.direct_url);
  assert.equal(direct.search, '');
  assert.equal(direct.hash, '');
  assert.equal(offer.publishable, false, 'a tracked URL alone is not a public release receipt');
  assert.equal(typeof offer.offer_review_state, 'string');
  assert(offer.facts.length > 0);
  if (offer.merchant === 'slevomat.cz') {
    assert(/^\d{6,9}$/.test(offer.offer_id));
    assert.equal(direct.hostname, 'www.slevomat.cz');
    assert.equal(offer.direct_url.startsWith(`https://www.slevomat.cz/akce/${offer.offer_id}-`), true);
    assert(Date.parse(offer.merchant_validity_until) > Date.parse(data.reviewed_on), 'offer expired');
    if (offer.offer_id === '2462030') {
      const tracked = new URL(offer.affiliate_url);
      assert.equal(tracked.origin + tracked.pathname, direct.origin + direct.pathname);
      assert.equal(tracked.searchParams.get('utm_source'), 'affiliate');
      assert.equal(tracked.searchParams.get('utm_medium'), 'cpc');
      assert.equal(tracked.searchParams.get('utm_campaign'), `dis_akv_gen_cze_all_buy_${data.affiliate.slevomat_affil_id}_${data.affiliate.slevomat_utm_domain}`);
      assert.equal(tracked.searchParams.get('utm_term'), data.affiliate.slevomat_affil_id);
      assert.equal(tracked.searchParams.get('utm_content'), 'stay_59891_jedraz');
      assert.equal(tracked.searchParams.get('a_box'), data.affiliate.slevomat_affil_id);
      assert.equal([...tracked.searchParams.keys()].length, 6, 'unexpected tracking parameters');
      assert.match(offer.affiliate_evidence, /generator_utm_shape/);
    } else {
      assert.equal(offer.affiliate_url, null, 'blocked or ambiguous offers get no tracked CTA');
      assert.equal(offer.affiliate_evidence, null);
    }
  } else {
    assert.equal(offer.merchant, 'hportal.cz');
    assert.match(offer.offer_id, /^hportal-/);
    assert.equal(direct.hostname, 'hportal.cz');
    assert.match(direct.pathname, /^\/cs\/[a-z-]+\/[a-z-]+\/$/);
    assert.equal(offer.water_key, null, 'merchant fishing amenity is not a verified revír or included fishing right');
    assert.equal(offer.offer_review_state, 'area_amenity_rights_not_included_or_confirmed');
    assert(areas.has(offer.area_key), 'unknown water area');
    assert.equal(areas.get(offer.area_key).region, offer.region, 'wrong region for accommodation');
    // Construct locally from the owner's exact example; never issue an HTTP request to this URL.
    const expected = `https://go.dognet.com/?chid=${data.affiliate.hportal_dognet_chid}&url=${encodeURIComponent(offer.direct_url)}`;
    assert.equal(offer.affiliate_url, expected);
    assert.match(offer.affiliate_evidence, /redirect_not_clicked/);
  }
  if (offer.water_key !== null) {
    assert(waters.has(offer.water_key), 'unverified water identity');
    assert.equal(waters.get(offer.water_key).region, offer.region, 'wrong geographic match');
  } else if (offer.merchant === 'slevomat.cz') {
    assert.equal(offer.offer_review_state, 'fishing_rights_need_confirmation');
  }
  if (offer.affiliate_url !== null) {
    assert(!/VASEID|DOMENACZ|cokoliv/i.test(offer.affiliate_url), 'placeholder in live candidate link');
  }
}
for (const page of data.target_pages) {
  for (const offerId of page.offer_ids_for_review) {
    assert(offers.has(offerId), `unknown offer ${offerId}`);
    const offer = offers.get(offerId);
    if (page.intent === 'ubytovani_v_regionu_jizni_morava') {
      assert.equal(offer.region, 'Jižní Morava', 'do not match unrelated region');
    }
  }
}
assert(data.excluded_offers.some(e => e.offer_id === '2459432'));
assert(data.excluded_offers.some(e => e.offer_id === '1512080'));
assert.equal(offers.size, 7);
assert.equal(pageIds.size, 4);
assert.equal(waters.size, 2);
assert.equal(areas.size, 4);

// Site-specific editorial HTML only: no dynamic matcher, redirector or synthetic click beacon.
const html = readFileSync(new URL('../editorial/fishing-stays-cz-20261118-recommendation-blocks.html', import.meta.url), 'utf8');
const primary = html.match(/<section data-nry-editorial-edit="fishing-stays-cz-59891-20261118-v1"[\s\S]*?<\/section>/);
assert(primary, 'missing primary offer block');
const source = data.target_pages.find(page => page.post_id === 59891);
const binding = source.authenticated_source_binding;
assert.equal(binding.method, 'connected_wordpress_rest_context_edit_read_only');
assert.equal(binding.raw_post_content_bytes, 14101);
assert.match(binding.raw_post_content_sha256, /^[0-9a-f]{64}$/);
assert.equal(binding.candidate_block_marker, 'fishing-stays-cz-59891-20261118-v1');
assert.equal(binding.candidate_block_bytes, Buffer.byteLength(primary[0]));
assert.equal(binding.candidate_block_sha256, createHash('sha256').update(primary[0]).digest('hex'));
assert.equal(binding.release_state, 'needs_private_wordpress_rollback_authorized_external_link_guard_and_public_readback');
const markers = [...html.matchAll(/data-nry-editorial-edit="([^"]+)"/g)].map(m => m[1]);
assert.deepEqual(markers, ['fishing-stays-cz-59891-20261118-v1', 'fishing-stays-cz-hracholusky-20261118-v1']);
assert(!/<script\b|\bonclick\s*=|<iframe\b|VASEID|DOMENACZ/i.test(html));
const links = [...html.matchAll(/<a href="([^"]+)"([^>]*)>/g)].map(m => ({href: m[1].replaceAll('&amp;', '&'), attrs: m[2]}));
const tracked = links.filter(link => /(?:go\.dognet\.com|slevomat\.cz)/.test(new URL(link.href).hostname));
assert.equal(tracked.length, 6, 'one primary card for each of 5 offers plus Hracholusky local alternative');
for (const link of links) {
  const origin = new URL(link.href).origin;
  if (origin === data.site) {
    assert(areas.size > 0, 'internal water/area guide references were not indexed');
    continue;
  }
  assert(['https://go.dognet.com', 'https://www.slevomat.cz'].includes(origin), 'unapproved external offer domain');
  assert.match(link.attrs, /rel="sponsored nofollow noopener"/);
  assert.match(link.attrs, /target="_blank"/);
  assert(data.stay_offers.some(offer => offer.affiliate_url === link.href), 'displayed outbound CTA is not a reviewed owner-derived link');
}
for (const offer of data.stay_offers.filter(x => x.affiliate_url !== null)) {
  assert(tracked.some(link => link.href === offer.affiliate_url), `candidate missing from reviewed HTML: ${offer.offer_id}`);
}
assert(!html.includes('Rybník Rožmberk'), 'do not mix Rožmberk nad Vltavou with Třeboň pond');
console.log('FISHING_STAYS_CZ_OFFLINE_EVIDENCE_VALID (4 pages, 1 authenticated post source binding, 2 private ponds, 4 locality areas, 7 review-only offers, 5 syntax-checked affiliate routes; 6 CTAs; 0 public releases)');
