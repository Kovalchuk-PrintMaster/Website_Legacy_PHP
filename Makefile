.DEFAULT_GOAL := help

SHELL := /bin/bash
.SHELLFLAGS := -eu -o pipefail -c

ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
PHP ?= $(shell command -v php8.2 2>/dev/null || command -v php 2>/dev/null)
PYTHON ?= $(shell if [ -x "$(ROOT)/.venv_website/bin/python" ]; then printf '%s\n' "$(ROOT)/.venv_website/bin/python"; else command -v python3 2>/dev/null; fi)

PREVIEW_SERVICE ?= forprint-website-preview.service
PREVIEW_URL ?= http://127.0.0.1:8098/
PREVIEW_SMOKE ?= scripts/inspection/run_website_local_http_smoke.py

DEPLOY_TOOL ?= scripts/maintenance/deploy_website_to_hosting.py
DEPLOY_ENV ?= .runtime/env/website.deploy
DEPLOY_ENV_EXAMPLE ?= config/env/website.deploy.example
DEPLOY_REPORT_DIR ?= tmp/deployments
DEPLOY_MANIFEST ?= config/deployment/mobile_portrait_phase_1_v0_1.manifest
COMMUNICATION_CHECK_TOOL ?= scripts/inspection/check_website_communication_runtime.py

.PHONY: help check makefile-check php-syntax inspect-security communication-check \
	preview-url preview-status preview-start preview-stop preview-restart preview-smoke \
	db-status deploy-init deploy-check deploy-dry-run deploy deploy-latest-report

help:
	@printf '%s\n' \
		"ForPrint Website operator commands" \
		"" \
		"Validation:" \
		"  make check             PHP syntax, tool syntax and local smoke" \
		"  make php-syntax        lint project-owned PHP" \
		"  make inspect-security  bounded read-only security grep" \
		"  make communication-check  protected non-sending production runtime check" \
		"" \
		"Canonical local preview:" \
		"  make preview-url       print http://127.0.0.1:8098/" \
		"  make preview-status    show systemd and HTTP state" \
		"  make preview-start     start canonical preview" \
		"  make preview-stop      stop canonical preview" \
		"  make preview-restart   restart and verify HTTP" \
		"  make preview-smoke     safe route smoke" \
		"" \
		"Database:" \
		"  make db-status         service/listener/config presence only" \
		"" \
		"Hosting:" \
		"  make deploy-init       create ignored runtime deployment config" \
		"  make deploy-check      validate local/SSH/remote prerequisites" \
		"  make deploy-dry-run    build exact manifest payload; do not upload" \
		"  make deploy            exact manifest release with communication checks" \
		"  make deploy-latest-report  print newest safe deployment report"

check: makefile-check php-syntax preview-smoke

makefile-check:
	@test -n "$(PHP)" || { echo "ERROR: PHP CLI not found" >&2; exit 1; }
	@test -n "$(PYTHON)" || { echo "ERROR: Python 3 not found" >&2; exit 1; }
	@test -f "$(DEPLOY_TOOL)" || { echo "ERROR: missing $(DEPLOY_TOOL)" >&2; exit 1; }
	@test -f "$(COMMUNICATION_CHECK_TOOL)" || { echo "ERROR: missing $(COMMUNICATION_CHECK_TOOL)" >&2; exit 1; }
	@test -f "$(DEPLOY_MANIFEST)" || { echo "ERROR: missing $(DEPLOY_MANIFEST)" >&2; exit 1; }
	@"$(PYTHON)" -m py_compile "$(DEPLOY_TOOL)" "$(COMMUNICATION_CHECK_TOOL)"
	@$(MAKE) --no-print-directory -n preview-status communication-check deploy-check >/dev/null
	@echo "[OK] Makefile and deployment tool syntax"

php-syntax:
	@test -n "$(PHP)" || { echo "ERROR: PHP CLI not found" >&2; exit 1; }
	@find base -type f -name '*.php' \
		-not -path 'base/vendor/*' \
		-not -path 'base/log/*' \
		-not -path 'base/temp/*' \
		-print0 | xargs -0 -r -n1 "$(PHP)" -l

inspect-security:
	@echo "== Files containing configuration/security keywords =="
	@grep -RIlE '(DB_|database|mysqli|PDO|password|passwd|secret|token|api[_-]?key|smtp)' base \
		--include='*.php' --include='*.env*' --include='*.ini' --include='*.conf' \
		--exclude-dir=vendor --exclude-dir=userfiles 2>/dev/null | sort || true
	@echo
	@echo "== Direct PHP request/session/file access =="
	@grep -RInE '\$$_(GET|POST|REQUEST|COOKIE|FILES|SESSION)' base \
		--include='*.php' --exclude-dir=vendor 2>/dev/null | head -250 || true

communication-check:
	@test -n "$(PYTHON)" || { echo "ERROR: Python 3 not found" >&2; exit 1; }
	@test -f "$(COMMUNICATION_CHECK_TOOL)" || { echo "ERROR: missing $(COMMUNICATION_CHECK_TOOL)" >&2; exit 1; }
	@"$(PYTHON)" "$(COMMUNICATION_CHECK_TOOL)"

preview-url:
	@echo "$(PREVIEW_URL)"

preview-status:
	@systemctl is-enabled "$(PREVIEW_SERVICE)"
	@systemctl is-active "$(PREVIEW_SERVICE)"
	@systemctl --no-pager --full status "$(PREVIEW_SERVICE)" | sed -n '1,18p'
	@curl --fail --silent --show-error --output /dev/null \
		--write-out 'HTTP %{http_code} %{url_effective}\n' "$(PREVIEW_URL)"

preview-start:
	@systemctl start "$(PREVIEW_SERVICE)"
	@$(MAKE) --no-print-directory preview-status

preview-stop:
	@systemctl stop "$(PREVIEW_SERVICE)"
	@echo "[OK] stopped $(PREVIEW_SERVICE)"

preview-restart:
	@systemctl restart "$(PREVIEW_SERVICE)"
	@for attempt in $$(seq 1 20); do \
		if curl --fail --silent --output /dev/null "$(PREVIEW_URL)"; then \
			echo "[OK] preview ready after attempt $$attempt"; exit 0; \
		fi; sleep 0.25; \
	done; echo "ERROR: preview did not return HTTP success" >&2; exit 1

preview-smoke:
	@test -n "$(PYTHON)" || { echo "ERROR: Python 3 not found" >&2; exit 1; }
	@test -f "$(PREVIEW_SMOKE)" || { echo "ERROR: missing $(PREVIEW_SMOKE)" >&2; exit 1; }
	@"$(PYTHON)" "$(PREVIEW_SMOKE)"

db-status:
	@if systemctl is-active --quiet mariadb.service; then \
		echo "mariadb.service: active"; \
	elif systemctl is-active --quiet mysql.service; then \
		echo "mysql.service: active"; \
	else echo "ERROR: MariaDB/MySQL is not active" >&2; exit 1; fi
	@ss -ltn | grep -E ':(3306|3307)[[:space:]]' \
		|| { echo "ERROR: database listener not found" >&2; exit 1; }
	@test -f base/config.php || { echo "ERROR: base/config.php missing" >&2; exit 1; }
	@printf 'base/config.php: present, mode %s\n' "$$(stat -c '%a' base/config.php)"
	@echo "No database credentials or queries were used."

deploy-init:
	@test -f "$(DEPLOY_ENV_EXAMPLE)" || { echo "ERROR: missing $(DEPLOY_ENV_EXAMPLE)" >&2; exit 1; }
	@mkdir -p "$$(dirname "$(DEPLOY_ENV)")"
	@if [ -e "$(DEPLOY_ENV)" ]; then \
		echo "[UNCHANGED] $(DEPLOY_ENV) already exists"; \
	else \
		cp "$(DEPLOY_ENV_EXAMPLE)" "$(DEPLOY_ENV)"; chmod 600 "$(DEPLOY_ENV)"; \
		echo "[CREATED] $(DEPLOY_ENV)"; \
		echo "Fill it once, then run: make deploy-check"; \
	fi

deploy-check:
	@test -n "$(PYTHON)" || { echo "ERROR: Python 3 not found" >&2; exit 1; }
	@"$(PYTHON)" "$(DEPLOY_TOOL)" --check --env "$(DEPLOY_ENV)" --manifest "$(DEPLOY_MANIFEST)"

deploy-dry-run:
	@test -n "$(PYTHON)" || { echo "ERROR: Python 3 not found" >&2; exit 1; }
	@"$(PYTHON)" "$(DEPLOY_TOOL)" --dry-run --env "$(DEPLOY_ENV)" --manifest "$(DEPLOY_MANIFEST)"

deploy:
	@test -n "$(PYTHON)" || { echo "ERROR: Python 3 not found" >&2; exit 1; }
	@"$(PYTHON)" "$(DEPLOY_TOOL)" --deploy --env "$(DEPLOY_ENV)" --manifest "$(DEPLOY_MANIFEST)"

deploy-latest-report:
	@latest="$$(find "$(DEPLOY_REPORT_DIR)" -maxdepth 2 -type f -name report.json 2>/dev/null \
		-printf '%T@ %p\n' | sort -nr | head -1 | cut -d' ' -f2-)"; \
	test -n "$$latest" || { echo "No deployment reports found."; exit 1; }; \
	echo "$$latest"; cat "$$latest"

# FP-HOSTING-LOCAL-MIRROR-V0-1-START
HOSTING_RESET_PYTHON ?= $(if $(wildcard .venv_website/bin/python3),.venv_website/bin/python3,python3)
HOSTING_RESET_TOOL ?= scripts/operations/hosting_mirror_operator.py reset
HOSTING_PARITY_TOOL ?= scripts/operations/hosting_mirror_operator.py parity

.PHONY: hosting-parity-check hosting-reset-from-local

# Purpose: compare hosting with the deployment ownership policy.
# Result: read-only parity report; no upload or database write.
hosting-parity-check:
	@$(HOSTING_RESET_PYTHON) $(HOSTING_PARITY_TOOL)

# Purpose: rebuild hosting according to the accepted deployment ownership policy.
# Result: full webroot/database backup, clean mirror, acceptance or rollback.
hosting-reset-from-local:
	@FP_HOSTING_RESET_ALLOWED=1 $(HOSTING_RESET_PYTHON) $(HOSTING_RESET_TOOL)
# FP-HOSTING-LOCAL-MIRROR-V0-1-END

# FP-HOSTING-DEPLOYMENT-PROFILES-V0-1-START
HOSTING_RELEASE_TOOL ?= scripts/operations/hosting_release_authorized.py
HOSTING_DATABASE_SYNC_TOOL ?= scripts/maintenance/sync_hosting_database_from_local.py
MANIFEST ?=

.PHONY: hosting-deploy-help \
	hosting-deploy-full \
	hosting-deploy-code hosting-deploy-code-dry-run \
	hosting-deploy-frontend hosting-deploy-frontend-dry-run \
	hosting-deploy-backend hosting-deploy-backend-dry-run \
	hosting-deploy-dependencies hosting-deploy-dependencies-dry-run \
	hosting-deploy-database hosting-deploy-database-dry-run \
	hosting-deploy-media hosting-deploy-media-dry-run \
	hosting-deploy-manifest hosting-deploy-manifest-dry-run

hosting-deploy-help:
	@printf '%s\n' \
		"ForPrint hosting deployment profiles" \
		"" \
		"  make hosting-deploy-full" \
		"      full application + vendor + userfiles + policy-aware database sync" \
		"  make hosting-deploy-code" \
		"      application code + vendor; database unchanged" \
		"  make hosting-deploy-frontend" \
		"      templates/CSS/JS/frontend media only; database/backend unchanged" \
		"  make hosting-deploy-backend" \
		"      PHP core/libraries/endpoint only; database unchanged" \
		"  make hosting-deploy-dependencies" \
		"      vendor/composer only" \
		"  make hosting-deploy-database" \
		"      policy-aware logical database sync; operational rows preserved" \
		"  make hosting-deploy-media" \
		"      frontend-owned userfiles only" \
		"  make hosting-deploy-manifest MANIFEST=..." \
		"      exact custom path manifest" \
		"" \
		"Append -dry-run to code/frontend/backend/dependencies/database/media/manifest."

hosting-deploy-full:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile full --action deploy

hosting-deploy-code:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile code --action deploy
hosting-deploy-code-dry-run:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile code --action dry-run

hosting-deploy-frontend:
	$(CURDIR)/.venv_website/bin/python3 scripts/inspection/check_hosting_storage_capacity.py --prepare
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile frontend --action deploy
	$(CURDIR)/.venv_website/bin/python3 scripts/inspection/check_hosting_storage_capacity.py --cleanup-release-storage
hosting-deploy-frontend-dry-run:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile frontend --action dry-run

hosting-deploy-backend:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile backend --action deploy
hosting-deploy-backend-dry-run:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile backend --action dry-run

hosting-deploy-dependencies:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile dependencies --action deploy
hosting-deploy-dependencies-dry-run:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile dependencies --action dry-run

hosting-deploy-database:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile database --action deploy
hosting-deploy-database-dry-run:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile database --action dry-run

hosting-deploy-media:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile media --action deploy
hosting-deploy-media-dry-run:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile media --action dry-run

hosting-deploy-manifest:
	@test -n "$(MANIFEST)" || { echo "ERROR: MANIFEST=... is required" >&2; exit 1; }
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile manifest --action deploy --manifest "$(MANIFEST)"
hosting-deploy-manifest-dry-run:
	@test -n "$(MANIFEST)" || { echo "ERROR: MANIFEST=... is required" >&2; exit 1; }
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile manifest --action dry-run --manifest "$(MANIFEST)"
# FP-HOSTING-DEPLOYMENT-PROFILES-V0-1-END

# FP_COMMUNICATION_ACCEPTANCE_MAKE_TARGET_V0_1
.PHONY: hosting-communication-check
hosting-communication-check:
	@.venv_website/bin/python3 scripts/inspection/check_website_communication_acceptance.py
# FP_COMMUNICATION_ACCEPTANCE_MAKE_TARGET_V0_1_END

# FP_OPERATIONAL_DB_MAKE_TARGETS_V0_1
.PHONY: hosting-deploy-full-destructive hosting-deploy-full-destructive-dry-run hosting-deploy-database-destructive hosting-deploy-database-destructive-dry-run hosting-diagnostic-hygiene hosting-diagnostic-hygiene-clean

hosting-deploy-full-destructive:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile full-destructive --action deploy
hosting-deploy-full-destructive-dry-run:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile full-destructive --action dry-run

hosting-deploy-database-destructive:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile database-destructive --action deploy
hosting-deploy-database-destructive-dry-run:
	@$(HOSTING_RESET_PYTHON) "$(HOSTING_RELEASE_TOOL)" --profile database-destructive --action dry-run

hosting-diagnostic-hygiene:
	@$(HOSTING_RESET_PYTHON) scripts/maintenance/cleanup_hosting_diagnostic_artifacts.py
hosting-diagnostic-hygiene-clean:
	@FP_HOSTING_DIAGNOSTIC_CLEANUP_ALLOWED=1 $(HOSTING_RESET_PYTHON) scripts/maintenance/cleanup_hosting_diagnostic_artifacts.py --apply
# FP_OPERATIONAL_DB_MAKE_TARGETS_V0_1_END

# FP_HOSTING_CAPACITY_OFFHOST_BACKUP_V1
.PHONY: hosting-storage-check hosting-storage-prepare hosting-clean-release-storage hosting-backup-local-dry-run hosting-backup-local

hosting-storage-check:
	$(CURDIR)/.venv_website/bin/python3 scripts/inspection/check_hosting_storage_capacity.py --check

hosting-storage-prepare:
	$(CURDIR)/.venv_website/bin/python3 scripts/inspection/check_hosting_storage_capacity.py --prepare

hosting-clean-release-storage:
	$(CURDIR)/.venv_website/bin/python3 scripts/inspection/check_hosting_storage_capacity.py --cleanup-release-storage

hosting-backup-local-dry-run:
	$(CURDIR)/.venv_website/bin/python3 scripts/maintenance/backup_hosting_to_local.py --dry-run

hosting-backup-local:
	$(CURDIR)/.venv_website/bin/python3 scripts/maintenance/backup_hosting_to_local.py
# /FP_HOSTING_CAPACITY_OFFHOST_BACKUP_V1

# FP_CANONICAL_FULL_HOSTING_SYNC_V1
HOSTING_BACKUP ?= latest

.PHONY: hosting-sync-full-dry-run hosting-sync-full hosting-restore-local-backup-dry-run hosting-restore-local-backup

hosting-sync-full-dry-run:
	$(CURDIR)/.venv_website/bin/python3 scripts/maintenance/sync_local_to_hosting_full.py --dry-run

hosting-sync-full:
	$(CURDIR)/.venv_website/bin/python3 scripts/inspection/check_hosting_full_sync_contract.py
	$(CURDIR)/.venv_website/bin/python3 scripts/maintenance/sync_local_to_hosting_full.py --apply

hosting-restore-local-backup-dry-run:
	$(CURDIR)/.venv_website/bin/python3 scripts/maintenance/restore_hosting_from_local_backup.py --backup "$(HOSTING_BACKUP)" --dry-run

hosting-restore-local-backup:
	$(CURDIR)/.venv_website/bin/python3 scripts/maintenance/restore_hosting_from_local_backup.py --backup "$(HOSTING_BACKUP)" --apply
# /FP_CANONICAL_FULL_HOSTING_SYNC_V1

# FP_HOSTING_FULL_SYNC_HARDENING_V1
.PHONY: hosting-sync-contract-check

hosting-sync-contract-check:
	$(CURDIR)/.venv_website/bin/python3 scripts/inspection/check_hosting_full_sync_contract.py
# /FP_HOSTING_FULL_SYNC_HARDENING_V1
