# ForPrint Website Current Status

## Current phase

`launch_readiness_inspection_v0_2`

## Website base

```text
base/
Status

Repository control and deep read-only inspection are in progress.

Boundaries

Website is a channel only.

It must not own canonical:

products;
prices;
clients;
orders;
payments;
stock;
accounting;
1C data.
Current known blockers
public launch not approved;
config/secrets handling not verified;
DB schema not confirmed;
admin/session security not verified;
upload safety not verified;
dynamic SQL paths need review;
public web-root exposure risks need review.