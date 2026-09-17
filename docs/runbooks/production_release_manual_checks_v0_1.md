# ForPrint Website — Production Release Manual Checks v0.1

**ID:** `FP-WEB-RUNBOOK-PRODUCTION-MANUAL-001`
**Version:** 0.1
**Date:** 2026-09-06
**Status:** active

This runbook contains browser/provider checks that should remain explicit.

Do not paste cookies, CSRF values, message text, contact data, credentials or
provider secrets into reports.

## Google Ads measurement / consent

Open:

```text
https://forprint.net.ua/
```

DevTools → Console:

```js
(() => {
  const c = window.ForPrintMeasurementConfig || {};
  return {
    enabled: c.enabled,
    testMode: c.testMode,
    provider: c.provider,
    googleTagId: c.googleTagId,
    conversionDestination: c.conversionDestination,
    storageKey: c.consentStorageKey,
    consent: localStorage.getItem(
      c.consentStorageKey || 'fp_measurement_consent_v1'
    ),
    ready: window.ForPrintGoogleTagReady,
    granted: window.ForPrintGoogleTagConsentGranted,
    gtag: typeof window.gtag,
    loader: !!document.getElementById('fp-google-tag-loader')
  };
})()
```

Expected production configuration:

```text
enabled: true
testMode: false
provider: google-tag
googleTagId: AW-959055246
conversionDestination: AW-959055246/3ccOCP6mntocEI6LqMkD
```

After choosing **Прийняти cookies**:

```text
consent: granted
ready: true
granted: true
gtag: function
loader: true
```

If `enabled` is false, do not create a duplicate Google tag. Diagnose the
hosting-owned production activation owner.

## Google Ads lead conversion E2E

Only after explicit approval:

1. enable measurement consent;
2. open the intended enquiry form;
3. submit one clearly identified controlled test request;
4. require a server-confirmed success response;
5. use Tag Assistant / Google Ads diagnostics;
6. confirm the same request id is not counted twice.

The project conversion owner intentionally fires after successful server
acceptance, not on a generic submit-button click.

## Telegram / Email manual delivery

Routine release health uses non-sending readiness acceptance.

If a real delivery test is explicitly required:

1. use one clearly identified controlled test enquiry;
2. test Telegram and Email separately;
3. record only delivery status and timestamp;
4. do not use repeated real sends as an ordinary deployment smoke test.

Automated non-sending readiness:

```text
make hosting-communication-check
```

## Release health

Standalone production health:

```text
make hosting-health-check
```

Evidence:

```text
tmp/operator_reports/hosting_health/
```

Google measurement owners:

```text
base/templates/default/include/header.php
base/templates/default/assets/js/forprint-consent.js
base/templates/default/assets/js/forprint-measurement.js
docs/workflow/website_release_health_contract_v0_1.md
```

Communication owners:

```text
scripts/inspection/check_website_communication_runtime.py
scripts/inspection/check_website_communication_acceptance.py
docs/workflow/communication_release_safety_and_recovery_v0_1.md
```

<!-- FP_BROWSER_MEASUREMENT_ACCEPTANCE_2026_09_06 -->
## Accepted browser measurement checkpoint — 2026-09-06

Production browser verification confirmed after consent:

```text
enabled=true
testMode=false
provider=google-tag
googleTagId=AW-959055246
conversionDestination=AW-959055246/3ccOCP6mntocEI6LqMkD
consent=granted
ready=true
granted=true
gtag=function
loader=true
```

This confirms the consent-to-loader path. It does **not** record a real lead
conversion test.

Affirmative consent copy is intentionally familiar and neutral:

```text
Прийняти cookies
```

The detailed banner copy remains the transparency owner for Google Ads
measurement disclosure.
<!-- /FP_BROWSER_MEASUREMENT_ACCEPTANCE_2026_09_06 -->
