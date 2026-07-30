# ForPrint Website — production release state 2026-07-30 v0.1

**ID:** `FP-WEB-SNAPSHOT-PRODUCTION-2026-07-30-001`
**Version:** `0.1`
**Date:** `2026-07-30`
**Status:** `historical snapshot`

## Purpose

This snapshot records the verified production-release baseline after technical
SEO and structured-data stabilization. It is evidence of the state on this
date, not a mutable runbook.

## Source authority

```text
authoritative repository:
s01:/srv/software_development/forprint-project/forprint_website

branch:
main

production role:
controlled mirror
```

## Relevant reviewed commits

```text
eb6bbb5  seo: establish canonical crawl contract
1cc5a47  seo: add route-aware metadata and headings
f5e4580  seo: add business and product structured data
```

Full structured-data commit:

```text
f5e4580c2803b78bee4e5bbadef88d6b72e110bc
```

## Production coordinates

```text
origin:
https://forprint.net.ua

SSH:
825163-nikolay.k@185.86.76.182

webroot:
/var/www/825163-nikolay.k/data/www/forprint.net.ua
```

## Verified search baseline

```text
sitemap URLs: 116
HTTP 200: 116
unique titles: 116
unique descriptions: 116
pages with H1: 116
document language: uk
canonical origin: https://forprint.net.ua
```

## Verified structured-data baseline

```text
BreadcrumbList pages: 115
WebSite pages: 1
LocalBusiness pages: 2
eligible Product pages: 89
Product schema pages: 89
request-price product pages without invented Offer: 3
availability emitted: 0
currency: UAH
```

## Production backups recorded during stabilization

```text
SEO crawl contract:
/var/www/825163-nikolay.k/data/.forprint-backups/seo_crawl_20260729_175419

116-URL sitemap:
/var/www/825163-nikolay.k/data/.forprint-backups/sitemap_116_20260729_181121

route metadata:
/var/www/825163-nikolay.k/data/.forprint-backups/metadata_v2_20260729_190424

structured data:
/var/www/825163-nikolay.k/data/.forprint-backups/schema_20260730_113845
```

## Safe deployment reports

```text
route metadata report SHA256:
8d778ac6f53145c929e8cc6d1a565c50292ddbd48af9f282bbe4133b77ba4314

structured-data report:
tmp/releases/controlled_structured_data_deployment_release_schema02_20260730_113853.zip

structured-data report SHA256:
67d47391540845f72257d05cbd6c9b7b5935c3f77abde0e0e89bdafe8c086658
```

## SSH baseline

```text
client public-key fingerprint:
SHA256:ef7wa41EgDUFAlInDqZ53DyE8AxRPBn5X3ohHy/7CNg

production ED25519 host-key fingerprint:
SHA256:82BkLOpOKyTWPzheuWiass3Fdu09Y+M1MxQArH2Gr/o
```

The private key is not part of this snapshot.

## External pending item

Google Search Console domain verification is waiting for the delegated DNS TXT
change at the authoritative Bestname DNS zone.

This pending external item does not block local code, content or technical SEO
work.
