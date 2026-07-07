# ForPrint Website

## Purpose

This repository controls the existing ForPrint public website codebase.

The current inherited PHP website implementation lives under:

```text
base/
Strategic boundary

This website may be used as an early public channel for:

public business presence;
local SEO;
basic service/product presentation;
contact forms;
simple requests;
early advertising landing pages.

This website must not become the canonical owner of:

ForPrint product catalog;
Calculator Engine pricing rules;
global clients;
orders;
payments;
stock;
accounting;
1C data.

Future intended direction:

Website -> Integration Gateway -> Operational Registry / Calculator / Library

This direction is architectural only. No production integration is active at this stage.

Current status

Current phase:

launch_readiness_inspection_v0_2

The project is under safe inspection and repository-control preparation.

Non-goals

Do not:

rewrite the website during inspection;
deploy publicly;
connect production database;
add production credentials;
connect payments;
connect 1C;
connect ForPrint core modules;
treat website cart/order/catalog data as canonical ForPrint business data.
Runtime notes

Initial inspection observed PHP CLI 8.2.23 on the server.

Composer metadata exists in base/composer.json and base/composer.lock, and base/vendor/ exists, but the global composer command was not available during initial inspection.

Safety rules
Do not commit real secrets.
Redact credentials in reports.
Keep config inspection local.
Prefer read-only checks before patches.
Prefer small, reversible changes.