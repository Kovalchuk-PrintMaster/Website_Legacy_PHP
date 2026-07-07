# ForPrint_Web_Site_Base — Config Example and Secret Scan v0.4.3 Report

## Status

`config_example_secret_scan_v0_4_3_prepared`

## Purpose

Create a safe example configuration file for the inherited PHP website base without exposing real local configuration values.

## Completed

Created:

```text
base/config.example.php

The example file was generated from the structure of base/config.php by extracting constant names only.

Real values from base/config.php were not copied into the report or the example file.

Config policy

Ignored local files remain:

base/config.php
base/config.local.php
base/mail.local.php

Trackable example files:

base/config.example.php
base/mail.example.php
Safety rules
No production DB credentials are allowed in tracked files.
No production SMTP credentials are allowed in tracked files.
No API tokens are allowed in tracked files.
git add base/ remains forbidden.
Public launch remains blocked.
Checks to run
php -l base/config.example.php
make check
git diff --check
Next recommended step

ForPrint_Web_Site_Base — Selected Base Source Checkpoint v0.4.4

Recommended scope:

stage only approved source/example files;
do not stage local config;
do not stage runtime logs;
do not stage uploads;
do not stage vendor unless policy changes;
keep public launch blocked.
