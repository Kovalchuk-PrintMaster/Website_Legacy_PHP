# Google Ads launch gates

**State:** owner-authorized scheduled click probe
**Authorization date:** 2026-08-01
**Authorized flight:** 2026-08-03 through 2026-08-09
**Account time zone:** GMT+03:00

## Authorization boundary

The project owner has authorized a limited Search click probe without verified
`generate_lead` measurement.

This exception applies only to campaigns using:

- Search network only;
- Maximize clicks;
- an explicit maximum CPC limit;
- an explicitly approved campaign total budget;
- controlled phrase and exact keyword targeting.

Conversion-based bidding remains prohibited until `generate_lead` is verified.

## Account and billing

Before automatic activation:

- the selected Google Ads account remains active;
- all inherited E-Machine campaigns remain Paused;
- sufficient funds are visible in Available funds;
- no payment or account suspension is active;
- no unrelated campaign is Enabled.

## Campaign readiness

Every campaign authorized for the probe must have:

- an approved campaign name;
- the correct Search campaign type;
- Google Search Network only;
- Search Partners disabled;
- Display Network disabled;
- AI Max disabled;
- broad match expansion disabled;
- automatically created assets disabled;
- correct language targeting;
- correct geographical targeting;
- Presence targeting for the selected territory;
- approved campaign total budget;
- approved maximum CPC;
- start date 2026-08-03;
- end date 2026-08-09;
- correct Final URL suffix;
- campaign-level negative keywords reviewed.

## Landing page

Every Final URL must:

- be a confirmed production URL;
- return HTTP 200;
- use the canonical HTTPS origin;
- match the ad group and advertisement;
- contain a truthful offer and call to action;
- work on mobile;
- support the actual stated delivery or installation geography.

A broad category URL is accepted only when it contains the promoted service
and provides a relevant path to enquiry.

## Ads and targeting

Before activation:

- the campaign is reviewed while Paused;
- each required ad group is Enabled;
- positive keywords are Enabled;
- positive keywords use phrase or exact match;
- negative keywords are applied to the real campaign;
- at least one responsive search ad is Enabled;
- the ad is Eligible or approved;
- there is no Destination not working or other blocking policy status.

## Automatic activation

A campaign left Paused will not start automatically.

For authorized automatic activation:

1. complete the readiness review;
2. change the campaign from Paused to Enabled before 2026-08-03;
3. keep the future start date set to 2026-08-03;
4. verify that ad groups, ads and keywords remain Enabled;
5. confirm account funds before the start date.

The future start date prevents serving before the authorized date.
The campaign end date stops the campaign at the end of 2026-08-09.

Automated rules may be used only as a fallback because their execution time
may be delayed.

## Measurement during the click probe

The probe measures:

- impressions;
- clicks;
- CTR;
- average CPC;
- cost;
- search terms;
- device and geography data;
- manually recorded leads.

UTM parameters are mandatory.

Until `generate_lead` is verified:

- do not use Maximize conversions;
- do not interpret the Google Ads Conversions column as complete lead data;
- record form, phone, email and messaging enquiries manually;
- do not claim keyword-level lead attribution when it cannot be proven.

## Stop conditions

Pause the affected campaign when:

- spend or CPC behaves outside the approved boundary;
- irrelevant search terms dominate;
- the destination stops working;
- an ad receives a blocking policy status;
- geography is incorrect;
- an inherited campaign becomes active;
- the offer shown in the ad is not supported by the landing page.

## Execution sequence

```text
research complete
→ URL verified
→ landing page reviewed
→ paused campaign created
→ manual review
→ ads approved
→ budget and billing confirmed
→ campaign Enabled before future start
→ automatic activation
→ daily search-term review
→ automatic end
→ result review
```
Keyword Planner modelled conversions are never treated as launch evidence.

<!-- FP-GOOGLE-ADS-MEASUREMENT-FOLLOW-UP-V0_1 -->
## Deferred measurement follow-up

Google Ads website measurement remains planned work.

Canonical implementation plan:

Current authorities: `config/marketing/measurement/event_contract_v0_1.yaml` and `docs/marketing/policies/marketing_api_automation_policy_v0_1.md`. Google Ads account-state facts are re-read through the MARKETING.05 read connector rather than preserved as static truth from legacy documentation. GTM/bootstrap readiness is tracked separately as deferred measurement integration state.

The click-only probe does not authorize duplicate Google tags, enhanced
conversions, transmission of personal data or conversion-based bidding.
