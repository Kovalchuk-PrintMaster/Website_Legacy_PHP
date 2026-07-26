# ForPrint Website — Local preview systemd service v0.6.20

## Status

Active local development support.

This service is not a public deployment unit.

## Purpose

Keep the PHP development server alive independently of a short SSH command and provide a stable remote target for a Windows SSH tunnel.

## Unit

```text
/etc/systemd/system/forprint-website-preview.service
```

Effective runtime:

```text
WorkingDirectory=/srv/software_development/forprint-project/forprint_website
ExecStart=/usr/bin/php8.2         -d upload_max_filesize=32M         -d post_max_size=128M         -d max_file_uploads=50         -d memory_limit=512M         -S 127.0.0.1:8098         -t /srv/software_development/forprint-project/forprint_website/base
Restart=on-failure
RestartSec=2
```

## Tunnel direction

```text
Windows localhost:8098
    -> SSH s01
    -> s01 127.0.0.1:8098
```

The tunnel does not own the PHP process. The systemd service does.

## Operator checks

```bash
systemctl is-enabled forprint-website-preview.service
systemctl is-active forprint-website-preview.service
systemctl --no-pager --full status forprint-website-preview.service
ss -ltnp | grep ':8098'
curl -sS -o /dev/null -w 'HTTP %{http_code}\n' http://127.0.0.1:8098/
```

## Confirmed working baseline

- service state: active;
- service enabled: enabled;
- one listener on port 8098;
- repeated HTTP requests returned 200;
- no fatal or startup errors in the inspected journal window.

## Known warnings

The legacy application emits non-fatal PHP 8.2 warnings, mainly `Undefined array key`, from:

```text
base/core/base/models/BaseModelMethods.php
base/core/base/models/BaseModel.php
base/core/base/controllers/BaseMethods.php
```

These warnings are application compatibility debt. They are not evidence that systemd or the SSH tunnel failed.

## Operational boundary

- the service is local/development infrastructure;
- it must listen only on `127.0.0.1`;
- it must not expose development credentials;
- it must not be described as a release or public deployment;
- production runtime requires a separate web-server and deployment decision.
