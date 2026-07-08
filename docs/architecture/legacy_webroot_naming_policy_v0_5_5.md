# ForPrint Website — Legacy Webroot Naming Policy v0.5.5

## Status

`legacy_webroot_naming_policy_v0_5_5_recorded`

## Decision

The repository/module may be named:

```text
Website_Legacy_PHP

The internal inherited PHP webroot directory remains:

base/
Reason

base/ is an inherited legacy application directory and is already referenced by:

Makefile checks;
inspection scripts;
launch readiness docs;
.htaccess;
local config path;
runtime assumptions;
legacy PHP include/router flow.

Renaming base/ before launch would be a structural change and could break runtime behavior.

Blueprint interpretation

For Blueprint / project coordination:

Website_Legacy_PHP = repository/module identity
base/ = intentional legacy PHP webroot directory

This is not a naming conflict.

It is an intentional compatibility boundary.

Future migration option

After the temporary public site is safely running, a later controlled refactor may move the legacy webroot to a clearer name such as:

website_legacy_php/

But this is deferred and must be handled as a separate migration checkpoint.

Current rule

Do not rename base/ during the current launch-preparation phase.
