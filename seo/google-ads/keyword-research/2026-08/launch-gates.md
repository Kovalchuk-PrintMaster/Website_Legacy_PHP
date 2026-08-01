# Google Ads launch gates

**State:** not ready for spend

A first controlled campaign may move from research to paused draft only after
all gates below pass.

## Account and campaign

- the selected Google Ads account remains active;
- all inherited campaigns remain paused;
- the new campaign is created in `Paused` state;
- no Smart campaign is used for the first controlled test;
- no funds are added solely to complete research.

## Measurement

- GTM or the approved Google tag is publicly active;
- the `generate_lead` conversion is created;
- the conversion fires only after confirmed server success;
- duplicate lead events are not counted;
- no name, phone, email, username, message or request identifier is sent;
- a controlled test is visible in Google Ads diagnostics.

## Campaign settings

- Search network only;
- location matches the research plan;
- location option uses presence in the target territory;
- positive keywords start with phrase/exact control;
- campaign negative keywords are reviewed;
- search partners are reviewed separately;
- daily budget is explicitly approved;
- final URLs return HTTP 200.

## Landing page

- the page matches the ad group;
- title, H1, description, offer and CTA are consistent;
- mobile lead submission works;
- delivery and installation geography are truthful;
- no unsupported price or deadline is promised.

## Launch authorization

```text
research complete
→ landing page ready
→ measurement verified
→ paused campaign created
→ manual review
→ budget approved
→ controlled activation
```

Keyword Planner modelled conversions are never treated as launch evidence.
