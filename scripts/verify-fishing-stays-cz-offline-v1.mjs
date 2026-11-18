#!/usr/bin/env node
// Site-only evidence contract; Product/Offer matching and economic ranking belong in Core.
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const data = JSON.parse(readFileSync(new URL('../editorial/fishing-stays-cz-20261118.json', import.meta.url), 'utf8'));
assert.equal(data.schema_version, 1);
assert.equal(data.site, 'https://na-ryby.eu');
assert.equal(data.status, 'review_only_not_for_publication');
assert.equal(data.core_contract_owner, 'Rastty/prometheus-growth-os');
assert.equal(data.search_console.page_query_metrics, null);
assert.equal(data.search_console.economic_ranking, null);
assert.equal(data.affiliate.slevomat_affil_id, null);
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
const offers = new Map();
for (const offer of data.stay_offers) {
  assert(/^\d{6,9}$/.test(offer.offer_id));
  assert(!offers.has(offer.offer_id), 'duplicate offer');
  offers.set(offer.offer_id, offer);
  assert.equal(offer.merchant, 'slevomat.cz');
  assert.equal(offer.direct_url.startsWith(`https://www.slevomat.cz/akce/${offer.offer_id}-`), true);
  const direct = new URL(offer.direct_url);
  assert.equal(direct.hostname, 'www.slevomat.cz');
  assert.equal(direct.search, '');
  assert.equal(direct.hash, '');
  assert.equal(offer.publishable, false);
  assert.equal(offer.affiliate_url, null, 'no guessed affiliate tracking URL');
  assert.equal(offer.affiliate_evidence, null, 'no guessed ownership proof');
  assert(offer.facts.length > 0);
  assert(offer.offer_review_state.length > 0);
  assert(Date.parse(offer.merchant_validity_until) > Date.parse(data.reviewed_on), 'offer expired');
  if (offer.water_key !== null) {
    assert(waters.has(offer.water_key), 'unverified water identity');
    assert.equal(waters.get(offer.water_key).region, offer.region, 'wrong geographic match');
  } else {
    assert.equal(offer.offer_review_state, 'fishing_rights_need_confirmation');
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
assert.equal(offers.size, 3);
assert.equal(pageIds.size, 4);
assert.equal(waters.size, 2);
console.log('FISHING_STAYS_CZ_OFFLINE_EVIDENCE_VALID (4 pages, 2 merchant-described waters, 3 review-only offers, 2 exclusions; 0 published affiliate URLs)');
