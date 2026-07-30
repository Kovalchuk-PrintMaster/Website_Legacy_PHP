# ForPrint Website — production release and recovery runbook v0.1

**ID:** `FP-WEB-WORKFLOW-RELEASE-001`
**Version:** `0.1`
**Date:** `2026-07-30`
**Status:** `active`
**Decision:** `FP-WEB-ADR-2026-07-30-001`
**Scope:** code release, production mirror, validation, rollback and recovery

## 1. Purpose

This is the canonical operator runbook for publishing ForPrint Website code.

It answers the questions a developer or assistant must be able to answer after
a long pause:

- where the authoritative code lives;
- where the local preview runs;
- how production is reached;
- which directory is the public production webroot;
- what is and is not mirrored;
- how a release is built from a reviewed Git commit;
- how the production baseline is verified;
- where backups and release reports are stored;
- what to do when SSH, upload, lint, smoke tests or rollback fail;
- how to resume safely without guessing.

## 2. Non-negotiable source-of-truth model

### 2.1 Versioned code and documentation

The authoritative source for versioned website code and documentation is:

```text
host: s01
repository:
/srv/software_development/forprint-project/forprint_website
branch: main
webroot in repository: base/
```

The production hosting copy is a controlled mirror of a reviewed Git commit.

Rules:

1. Make code changes on `s01`.
2. Validate them through the local preview.
3. Stage exact paths, never `git add .`.
4. Commit the reviewed local state.
5. Build a release archive from that commit.
6. Deploy only through a controlled release script.
7. Do not edit production PHP/CSS/JS files manually.

### 2.2 State that is not mirrored by a code release

The phrase “s01 is the source of truth” applies to versioned code,
documentation, migrations and release tooling.

A file release does **not** automatically synchronize:

- live database records;
- admin-panel content changes;
- customer requests and operational records;
- uploaded media under `userfiles/`;
- production-only secrets and runtime configuration;
- DNS records;
- mail-server state.

Before any DB or media synchronization, create a separate plan, backups and
migration/import validation. Never overwrite production state merely because
the code repository is authoritative.

## 3. Environment coordinates

### 3.1 Local development and validation

```text
repository:
/srv/software_development/forprint-project/forprint_website

local preview service:
forprint-website-preview.service

local preview:
http://127.0.0.1:8098

document root:
base/
```

Basic checks from the repository root:

```bash
git status --short
systemctl is-active forprint-website-preview.service
curl -sS -o /dev/null -w 'HTTP %{http_code}\n' http://127.0.0.1:8098/
```

### 3.2 Production website

```text
canonical origin:
https://forprint.net.ua

SSH target:
825163-nikolay.k@185.86.76.182

production webroot:
/var/www/825163-nikolay.k/data/www/forprint.net.ua

private release staging root:
/var/www/825163-nikolay.k/data/.forprint-releases

private backup root:
/var/www/825163-nikolay.k/data/.forprint-backups
```

### 3.3 SSH identity

The current `s01` client identity path is:

```text
/root/.ssh/id_ed25519
```

Known public-key fingerprint:

```text
SHA256:ef7wa41EgDUFAlInDqZ53DyE8AxRPBn5X3ohHy/7CNg
```

Known production ED25519 host-key fingerprint:

```text
SHA256:82BkLOpOKyTWPzheuWiass3Fdu09Y+M1MxQArH2Gr/o
```

Never store or copy the private-key contents into Git, documentation, reports,
tickets or chat.

Read-only connection test:

```bash
ssh -o BatchMode=yes -o ConnectTimeout=20 825163-nikolay.k@185.86.76.182 true
```

Detailed diagnostic test:

```bash
ssh -vvv -o BatchMode=yes -o ConnectTimeout=20 825163-nikolay.k@185.86.76.182 true 2>&1 | tee tmp/ssh-kex-debug.log
```

### 3.4 Production-only runtime configuration

Communication runtime configuration is outside the public webroot:

```text
/var/www/825163-nikolay.k/data/.forprint-secrets/communication_runtime.php
```

Expected mode:

```text
0600
```

This file is not a release payload and must not be placed in Git.

### 3.5 DNS authority

Public DNS authority is managed at Bestname:

```text
ns1.bestname.com.ua
ns2.bestname.com.ua
ns3.bestname.com.ua
```

Do not treat a DNS zone shown in another hosting panel as authoritative unless
public nameserver delegation has changed and that change is documented.

## 4. Standard controlled release sequence

### Step 1 — local implementation

Change only local files under the `s01` repository.

Do not change production code during implementation.

### Step 2 — local validation

Run focused checks appropriate to the change, for example:

```bash
php -l path/to/file.php
python3 scripts/inspection/check_website_route_metadata_contract.py
python3 scripts/inspection/check_website_structured_data_contract.py
git diff --check -- exact/path/one exact/path/two
```

Validate rendered behavior through `http://127.0.0.1:8098`.

### Step 3 — exact commit

```bash
git add exact/path/one exact/path/two
git diff --cached --check
git diff --cached --name-only
git commit -m "scope: describe reviewed change"
```

Never include `tmp.py`, temporary reports, secrets or unrelated files.

### Step 4 — read-only release packaging

The packaging script must:

- require an empty staged area;
- verify the reviewed commit is contained in `HEAD`;
- verify each worktree payload equals the reviewed commit;
- rerun validators and language-specific lint;
- package only the intended production files;
- create `manifest.json`, `SHA256SUMS` and release instructions;
- perform no production or Git mutation.

Release archives are created under:

```text
tmp/releases/
```

### Step 5 — deployment plan

Run the deployment script without `--apply`.

The plan must verify:

- release archive SHA256;
- reviewed commit identity;
- release manifest and payload hashes;
- reusable SSH connectivity;
- exact production baseline;
- existence or intentional absence of each target file.

A baseline mismatch is a blocker. Do not overwrite the unexpected production
state.

### Step 6 — private backup

Immediately before the first production file mutation, create a timestamped
private backup under:

```text
/var/www/825163-nikolay.k/data/.forprint-backups/<release>_<timestamp>
```

For every target file, record whether it previously existed. This allows
rollback to restore an old file or remove a newly created file.

### Step 7 — exact deployment

Deploy only the files listed in the reviewed release manifest.

The current preferred transport uses one reusable SSH control connection
(`ControlMaster`) to avoid unnecessary repeated SSH handshakes.

For every deployed file:

1. verify staged payload SHA256;
2. install with the intended file mode;
3. verify production SHA256;
4. run PHP syntax validation when applicable.

### Step 8 — production validation

Validation must run against the canonical HTTPS origin, not the local preview.

At minimum verify:

- expected HTTP status;
- final URL without an unexpected redirect;
- canonical URL;
- document language;
- title, description and H1 contract;
- feature-specific behavior;
- the complete current sitemap URL set when the release affects shared
  rendering or SEO.

For the structured-data release dated `2026-07-30`, the accepted production
contract was:

```text
production URLs: 116
unique titles: 116
unique descriptions: 116
pages with H1: 116
BreadcrumbList pages: 115
WebSite pages: 1
LocalBusiness pages: 2
eligible Product pages: 89
Product schema pages: 89
request-price product pages without invented Offer: 3
availability emitted: 0
currency: UAH
```

These numbers are a dated baseline, not a permanent hard-coded business rule.
When the sitemap or catalog changes, create a new snapshot and update the
release validator intentionally.

### Step 9 — release evidence

Every controlled deployment produces a safe ZIP report under:

```text
tmp/releases/
```

Record at least:

- release checkpoint;
- reviewed commit;
- release archive SHA256;
- production backup path;
- apply status;
- validation result;
- rollback status;
- report SHA256.

Reports are execution evidence. This runbook explains the process.

### Step 10 — rollback

Rollback is automatic when deployment or production smoke validation fails
after mutation starts.

The rollback script must:

- restore every previously existing file from the private backup;
- remove every file that did not exist before release;
- avoid DB, DNS, systemd and Git changes;
- record whether rollback succeeded.

If rollback fails, stop all further releases and escalate immediately. Do not
attempt another release over an uncertain production state.

## 5. Known obstacles and response

### 5.1 `kex_exchange_identification: Connection closed by remote host`

Meaning: the server closed the connection before normal authentication
completed. It may be transient or related to hosting connection limits.

Response:

1. Do not repeatedly reconnect in a tight loop.
2. Run one verbose read-only SSH test.
3. If the log later shows `Server accepts key` and `Authenticated ... using
   publickey`, do not reactivate or replace the key.
4. Wait before retrying.
5. Use one reusable SSH control connection for multi-step releases.
6. Contact hosting support with the timestamp and verbose log if the condition
   persists.

### 5.2 Public key rejected

Response:

1. Confirm the client uses `/root/.ssh/id_ed25519`.
2. Compare the public fingerprint, not private-key contents.
3. Confirm the public key is authorized through the hosting control panel.
4. Add a new public key only through a controlled key-rotation procedure.
5. Never send the private key through tickets or chat.

### 5.3 Production baseline mismatch

Meaning: one or more production files do not match the expected pre-release
hash or existence state.

Response:

1. Stop.
2. Do not force deployment.
3. Create a read-only source/hash inventory.
4. Determine whether the difference is a previous approved release, an
   emergency restore, a hosting change or an unauthorized manual edit.
5. Reconcile the desired state on `s01`.
6. Commit and package a new reviewed release.

### 5.4 Upload or staging failure

Response:

- no production mutation means no rollback is needed;
- retain the report;
- inspect permissions and the private staging root;
- retry only after read-only preflight passes.

### 5.5 PHP syntax failure

Response:

- rollback automatically;
- reproduce locally with the same PHP major/minor version when possible;
- correct on `s01`, commit and create a new release;
- never patch the production file in place.

### 5.6 Production crawl or smoke failure

Response:

- rollback automatically;
- preserve the failed release report and backup;
- compare local and production runtime assumptions;
- inspect caching, environment-specific paths and external services;
- fix locally and issue a new commit/release.

### 5.7 Rollback failure

Response:

1. Freeze all deployments.
2. Preserve the release stage, backup and logs.
3. Inspect target and backup hashes read-only.
4. Restore only from the timestamped backup with an explicit recovery plan.
5. Perform the full production validation before reopening releases.

### 5.8 Database or admin-content mismatch

Code deployment does not repair DB divergence.

Response:

- back up both relevant states;
- identify the authoritative record set;
- prepare a migration/export/import plan;
- validate counts, keys and visible routes;
- record the DB operation separately from the file release.

### 5.9 DNS verification or records not visible

Confirm changes are made at the authoritative Bestname zone. Do not edit the
inactive GMHOST DNS zone as a workaround.

## 6. One-year recovery checklist

Start on `s01`:

```bash
pwd
git status --short
git log --oneline -10
systemctl is-active forprint-website-preview.service
curl -sS -o /dev/null -w 'HTTP %{http_code}\n' http://127.0.0.1:8098/
```

Then read:

```text
docs/workflow/production_release_and_recovery_runbook_v0_1.md
docs/decisions/2026-07-30__s01_source_of_truth_and_controlled_production_mirror.md
docs/status/snapshots/2026-07-30_production_release_state_v0_1.md
```

Check SSH read-only:

```bash
ssh -o BatchMode=yes -o ConnectTimeout=20 825163-nikolay.k@185.86.76.182 true
```

Locate recent evidence:

```bash
find tmp/releases -maxdepth 1 -type f -name '*.zip' -printf '%TY-%Tm-%Td %TH:%TM %p\n' | sort
```

Locate production backups read-only:

```bash
ssh -o BatchMode=yes 825163-nikolay.k@185.86.76.182 \
'find /var/www/825163-nikolay.k/data/.forprint-backups -mindepth 1 -maxdepth 1 -type d -printf "%TY-%Tm-%Td %TH:%TM %p\n" | sort'
```

Before any new publication:

1. confirm repository and branch;
2. confirm the local preview;
3. run persistent validators;
4. confirm no staged unrelated files;
5. identify the intended reviewed commit;
6. create a read-only release archive;
7. run deployment plan;
8. apply only after `Blockers: none`.

## 7. Emergency boundary

The allowed production emergency action is restoration from an existing
private backup through a documented recovery script.

Ad-hoc editing of production source files is not an accepted recovery method.
Any emergency state must be reconciled back into the `s01` repository before
the next release.

## 8. Updating this document

This file is versioned. Do not rewrite it silently when hosting coordinates,
paths, state ownership or the release process materially changes.

Create a new version, mark the old version superseded and update the
documentation index and ADR register.
