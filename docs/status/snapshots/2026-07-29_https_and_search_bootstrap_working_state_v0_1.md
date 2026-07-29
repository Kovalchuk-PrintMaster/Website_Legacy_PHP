# HTTPS and search bootstrap working state — 2026-07-29

**ID:** `FP-WEB-SEO-STATUS-2026-07-29-BOOTSTRAP-001`
**Status:** current working snapshot

## Confirmed

- Let's Encrypt certificate is active for `forprint.net.ua` and
  `www.forprint.net.ua`;
- HTTP redirects to HTTPS;
- HTTPS `www` redirects to the primary non-`www` host;
- representative home, about and cart routes are reachable;
- email and Telegram form submissions work through HTTPS;
- mixed-content findings were not observed on the audited representative
  pages;
- local Git remains the application source of truth.

## Known limitations

- current sitemap contains legacy `http://cpa.fvds.ru` URLs;
- current sitemap must not be submitted;
- HTTP redirects currently include an explicit `:443`;
- HTTP `www` has an additional redirect hop;
- HSTS remains disabled;
- Google Ads has an account/payments blocker;
- durable SEO changes are not yet deployed.

## Operating decision

Start discovery through Search Console URL Inspection while local technical
SEO work proceeds. Submit a sitemap and expand paid promotion only after the
corresponding gates pass.
