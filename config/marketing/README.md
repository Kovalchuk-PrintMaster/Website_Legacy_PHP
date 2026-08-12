# Marketing control plane

`config/marketing/` is the machine-readable control plane for marketing
automation.

It stores stable IDs, lifecycle state, relationships, capabilities,
measurement contracts, and symbolic credential references.

Never store OAuth refresh tokens, client secrets, developer tokens, passwords,
API secrets, cookies, or private provider payloads here.

Credentials remain outside Git and are referenced only through
`credential_ref`.

Machine-generated timestamps use RFC 3339 / ISO 8601 with timezone.
Stable project IDs are never reused.
