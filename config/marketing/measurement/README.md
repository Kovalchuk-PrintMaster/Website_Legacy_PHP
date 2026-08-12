# Marketing measurement control-plane migration

**Status:** migration pending

No canonical measurement event contract is declared in this directory yet.

The existing legacy measurement contract under:

```text
seo/config/measurement_event_contract_v0_1.yaml
```

is an input to MARKETING.03 inventory/classification.

It must be reviewed and reconciled before a canonical successor is created
under `config/marketing/measurement/`.

Until that migration is accepted, an empty placeholder must not be treated as
the authoritative event contract.
