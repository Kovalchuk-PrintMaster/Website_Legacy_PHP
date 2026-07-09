# ForPrint Website — Local Python Environment v0.5.7

## Status

`local_python_environment_v0_5_7_completed`

## Purpose

Use a module-local Python virtual environment for Website tooling instead of relying on Blueprint or another module environment.

## Local venv

```text
.venv_website/
Rule

All Python inspection/check scripts for this repository should be run through:

.venv_website/bin/python

or after:

. .venv_website/bin/activate
Environment check script
scripts/inspection/check_website_python_environment.py
Expected status
WEBSITE_LOCAL_PYTHON_ENV_OK
Safety boundary
.venv_website/ is local only.
.venv_website/ must not be committed.
Website tooling must not depend on Blueprint venv.
No production deploy.
No production DB connection.
No local secret commit.
Next recommended checkpoint

ForPrint_Website — Local HTTP Smoke v0.5.8
