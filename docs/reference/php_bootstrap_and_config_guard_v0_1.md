# ForPrint PHP bootstrap and `config.php` guard v0.1

- **ID:** `FP-WEB-REF-PHP-BOOTSTRAP-001`
- **Date:** 2026-08-06
- **Status:** active reference
- **Scope:** trusted PHP entrypoints, CLI inspections, deployment diagnostics, and `base/config.php`

## 1. Purpose

`base/config.php` is not a standalone entrypoint. It is a protected legacy
configuration include and must be loaded only after the application bootstrap
guard has been established.

The current guard is:

```php
defined('VG_ACCESS') or die('Access denied');
```

A direct include without the guard terminates the PHP process with:

```text
Access denied
```

This behavior is intentional. It prevents accidental direct execution of the
configuration file. It is not a replacement for filesystem permissions,
secret management, web-server rules, or authentication.

## 2. Canonical bootstrap contract

Trusted application entrypoints define `VG_ACCESS` before loading
`base/config.php`.

Webroot entrypoint:

```php
<?php

define('VG_ACCESS', true);

require_once __DIR__ . '/config.php';
```

Repository-root CLI inspection:

```php
<?php

declare(strict_types=1);

define('VG_ACCESS', true);

require_once __DIR__ . '/base/config.php';
```

The order is mandatory:

1. establish the trusted execution context;
2. define `VG_ACCESS`;
3. load `config.php` with `require_once`;
4. use configuration constants without printing their values.

Defining the guard after `require` is ineffective.

## 3. Existing trusted entrypoints

The current public application follows this contract in:

- `base/index.php`;
- `base/communication-request.php`.

Any new standalone PHP endpoint that intentionally uses `base/config.php`
must follow the same order and must have its own request-validation and
security review.

## 4. CLI inspection rule

A repository inspection or maintenance script must not include
`base/config.php` blindly.

Before loading it, the tool must:

- verify that it is running from the expected repository;
- verify that the intended configuration file exists;
- explicitly define `VG_ACCESS` only inside that trusted process;
- avoid echoing database constants, passwords, tokens, DSNs, SMTP values, or
  other secrets;
- state whether the operation is read-only or mutating;
- use read-only SQL for an inspection;
- report capability and schema facts rather than configuration values.

Safe example:

```php
<?php

declare(strict_types=1);

$root = realpath(__DIR__);

if ($root === false || !is_file($root . '/base/config.php')) {
    fwrite(STDERR, "Repository configuration is unavailable
");
    exit(1);
}

if (!defined('VG_ACCESS')) {
    define('VG_ACCESS', true);
}

require_once $root . '/base/config.php';

echo json_encode([
    'config_loaded' => true,
    'config_guard' => 'VG_ACCESS',
], JSON_THROW_ON_ERROR);
```

## 5. Diagnostic clue

When a CLI check exits successfully but produces only:

```text
Access denied
```

the first suspicion must be a missing bootstrap guard, not a database outage.

The current literal is 13 bytes without a newline, but tools must not treat
the byte count as a permanent API contract. They should identify the bootstrap
order and inspect the guarded file safely.

Do not suppress or strip the message and continue. Correct the bootstrap
sequence.

## 6. Security boundary

`VG_ACCESS` is an include guard, not a secret.

Therefore:

- do not store it in environment variables;
- do not replace it with a token;
- do not accept it from HTTP request data;
- do not use `$_GET`, `$_POST`, cookies, or headers to decide whether to define
  it;
- do not define it globally in PHP configuration;
- do not remove the guard to simplify tests;
- do not expose configuration constants in diagnostics;
- keep `base/config.php` excluded from normal deployment payloads;
- keep production configuration owned by the production environment.

Filesystem permissions and hosting configuration remain separate controls.

## 7. Deployment and production diagnostics

Normal application deployment must not overwrite production
`base/config.php`.

A production communication readiness check has two different layers:

- SSH/CLI verification may use the explicit `VG_ACCESS` bootstrap contract;
- web-runtime verification must run through a protected diagnostic endpoint or
  the real application request path so it observes the production PHP SAPI and
  its effective environment.

CLI success alone does not prove that the production web SAPI receives
Telegram, SMTP, or other runtime environment variables.

Any temporary web diagnostic must:

- be non-public or guarded by an operator-controlled one-time capability;
- never print secret values;
- report only presence, schema, extension, and contract facts;
- be removed or disabled after evidence is captured.

## 8. Communication-system implications

The communication endpoint depends on the same bootstrap contract.

A non-sending readiness check should verify facts such as:

```text
config_loaded=true
config_guard=VG_ACCESS
db_connected=true
communication_requests table exists
communication_buttons table exists
quantity_requested column exists
email and Telegram button rows are visible and configured
vendor/autoload.php exists
PHPMailer class is available
```

A live delivery check is separate and must inspect application delivery
evidence such as:

```text
delivery_completed
delivery_status
```

HTTP `200` alone is not delivery evidence.

## 9. Review checklist

Before accepting a new PHP inspection, endpoint, or deployment diagnostic:

- expected repository/webroot verified;
- `VG_ACCESS` defined before `config.php`;
- `require_once` uses an explicit path;
- no secret values printed;
- read-only versus mutating mode declared;
- database queries match that mode;
- CLI and web-runtime responsibilities are not confused;
- production `config.php` remains outside normal release payload;
- PHP lint and focused runtime checks pass;
- documentation is updated when the bootstrap contract changes.

## 10. Change rule

If the guard name, entrypoint order, configuration location, or bootstrap
ownership changes, update together:

- `base/index.php`;
- `base/communication-request.php`;
- relevant inspection and maintenance tools;
- `docs/reference/php_bootstrap_and_config_guard_v0_1.md`;
- `docs/decisions/architecture_decision_register_v0_1.md`;
- `docs/reference/repository_map_v0_1.md`.

Do not introduce a second competing bootstrap convention.
