# Marketing measurement control plane

**Status:** current

Canonical contract:

`config/marketing/measurement/event_contract_v0_1.yaml`

The event contract owns approved website measurement event names, conversion
semantics, allowed/forbidden parameters and fail-closed privacy rules.

The legacy `seo/config/measurement_event_contract_v0_1.yaml` remains a
migration input until MARKETING.03E/04 explicitly retires or archives it. It is
not the current control-plane owner.

Operational exports, raw request data, credentials, cookies and personal data
do not belong in this directory or in Git.
