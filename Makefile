.PHONY: help check php-syntax inspect-structure inspect-security-grep

help:
	@echo "ForPrint Website make targets:"
	@echo "  make check                 - run safe local checks"
	@echo "  make php-syntax            - run PHP syntax check for base/*.php files"
	@echo "  make inspect-structure     - print project structure overview"
	@echo "  make inspect-security-grep - print high-risk grep overview without secret values"

check: php-syntax inspect-structure inspect-security-grep

php-syntax:
	@echo "== PHP syntax check =="
	@find base -type f -name "*.php" -not -path "base/vendor/*" -print0 | xargs -0 -n1 php -l

inspect-structure:
	@echo "== Root =="
	@pwd
	@echo
	@echo "== Top-level directories =="
	@find . -maxdepth 2 -type d | sort
	@echo
	@echo "== Important files =="
	@find . -maxdepth 4 -type f \( \
		-name "*.php" -o \
		-name "*.json" -o \
		-name "*.lock" -o \
		-name ".htaccess" -o \
		-name ".gitignore" -o \
		-name "README.md" -o \
		-name "Makefile" \
	\) | sort

inspect-security-grep:
	@echo "== Config / DB / mail keyword files only =="
	@grep -RIlE "(DB_|database|mysqli|PDO|mysql_connect|password|passwd|secret|token|api[_-]?key|smtp|mail)" base \
		--include="*.php" \
		--include="*.env*" \
		--include="*.ini" \
		--include="*.conf" \
		2>/dev/null | sort || true
	@echo
	@echo "== Direct request/session/file usage =="
	@grep -RInE '\$$_(GET|POST|REQUEST|COOKIE|FILES|SESSION)' base --include="*.php" 2>/dev/null | head -250 || true
	@echo
	@echo "== SQL construction usage =="
	@grep -RInE "(SELECT|INSERT|UPDATE|DELETE|mysqli_query|->query|prepare\(|bindParam|bindValue)" base --include="*.php" 2>/dev/null | head -300 || true
	@echo
	@echo "== Upload handling usage =="
	@grep -RInE '(\$$_FILES|move_uploaded_file|mime_content_type|pathinfo|upload)' base --include="*.php" 2>/dev/null | head -250 || true

# =============================================================================
# Website local runtime shortcuts START
# =============================================================================

WEBSITE_LOCAL_HOST ?= 127.0.0.1
WEBSITE_LOCAL_PORT ?= 8098
WEBSITE_LOCAL_URL ?= http://$(WEBSITE_LOCAL_HOST):$(WEBSITE_LOCAL_PORT)/
WEBSITE_WEBROOT ?= base
WEBSITE_HTTP_ROUTER ?= scripts/inspection/local_http_smoke_router.php
WEBSITE_HTTP_SMOKE ?= scripts/inspection/run_website_local_http_smoke.py

# Purpose: start the legacy PHP website locally for browser review.
# Result: PHP built-in server starts on 127.0.0.1:8098.
.PHONY: site-start
site-start:
	@echo "Starting ForPrint Website at $(WEBSITE_LOCAL_URL)"
	php -S $(WEBSITE_LOCAL_HOST):$(WEBSITE_LOCAL_PORT) -t $(WEBSITE_WEBROOT) $(WEBSITE_HTTP_ROUTER)

# Purpose: print the local website URL.
# Result: local browser URL is shown.
.PHONY: site-url
site-url:
	@echo "$(WEBSITE_LOCAL_URL)"

# Purpose: run local HTTP smoke for the legacy PHP website.
# Result: route smoke passes or returns non-zero.
.PHONY: site-smoke
site-smoke:
	.venv_website/bin/python $(WEBSITE_HTTP_SMOKE)

# Purpose: show the SSH tunnel command for Windows/local workstation access.
# Result: operator can copy the tunnel command.
.PHONY: site-tunnel-command
site-tunnel-command:
	@echo "ssh -N -L $(WEBSITE_LOCAL_PORT):127.0.0.1:$(WEBSITE_LOCAL_PORT) s01"

# Purpose: compatibility alias for local runtime start.
# Result: delegates to site-start.
.PHONY: website-start
website-start:
	$(MAKE) site-start

# Purpose: compatibility alias for local HTTP smoke.
# Result: delegates to site-smoke.
.PHONY: website-smoke
website-smoke:
	$(MAKE) site-smoke

# =============================================================================
# Website local runtime shortcuts FINISH
# =============================================================================

# Local development PHP server with upload limits.
#
# Usage:
#   make site-serve
#   make site-serve FP_WEB_LOCAL_HTTP_PORT=8098
#   make site-serve FP_WEB_LOCAL_HTTP_HOST=127.0.0.1
#
# Notes:
# - These limits apply to PHP built-in server mode only.
# - Production/staging PHP-FPM limits must be configured on the server.
FP_WEB_LOCAL_HTTP_HOST ?= 0.0.0.0
FP_WEB_LOCAL_HTTP_PORT ?= 8098
FP_WEB_UPLOAD_MAX_FILESIZE ?= 32M
FP_WEB_POST_MAX_SIZE ?= 128M
FP_WEB_MAX_FILE_UPLOADS ?= 50
FP_WEB_MEMORY_LIMIT ?= 512M

.PHONY: site-serve
site-serve:
	php \
		-d upload_max_filesize=$(FP_WEB_UPLOAD_MAX_FILESIZE) \
		-d post_max_size=$(FP_WEB_POST_MAX_SIZE) \
		-d max_file_uploads=$(FP_WEB_MAX_FILE_UPLOADS) \
		-d memory_limit=$(FP_WEB_MEMORY_LIMIT) \
		-S $(FP_WEB_LOCAL_HTTP_HOST):$(FP_WEB_LOCAL_HTTP_PORT) \
		-t base

.PHONY: site-serve-local
site-serve-local:
	$(MAKE) site-serve FP_WEB_LOCAL_HTTP_HOST=127.0.0.1

.PHONY: site-serve-limits-cli
site-serve-limits-cli:
	php -i | grep -E "upload_max_filesize|post_max_size|max_file_uploads|memory_limit"

# == ForPrint website preview env server start ==
SITE_PREVIEW_HOST ?= 127.0.0.1
SITE_PREVIEW_PORT ?= 8098
SITE_PREVIEW_DOCROOT ?= base
SITE_PREVIEW_ENV ?= .env.website.local
SITE_PREVIEW_PID ?= /tmp/forprint_website_php8098.pid
SITE_PREVIEW_LOG ?= /tmp/forprint_website_php8098.log

.PHONY: site-preview-env-init site-preview-env-check site-preview-stop site-preview-start site-preview-restart site-preview-status site-preview-smoke

site-preview-env-init:
	@if [ ! -f "$(SITE_PREVIEW_ENV)" ]; then \
		cp .env.website.local.example "$(SITE_PREVIEW_ENV)"; \
		chmod 600 "$(SITE_PREVIEW_ENV)"; \
		echo "[OK] created $(SITE_PREVIEW_ENV) from example"; \
		echo "[NEXT] edit $(SITE_PREVIEW_ENV) and paste Telegram token/chat id there"; \
	else \
		echo "[OK] $(SITE_PREVIEW_ENV) already exists"; \
	fi

site-preview-env-check:
	@set -e; \
	ENV_PATH="$(SITE_PREVIEW_ENV)"; \
	case "$$ENV_PATH" in /*|./*|../*) ;; *) ENV_PATH="./$$ENV_PATH";; esac; \
	if [ -f "$$ENV_PATH" ]; then \
		set -a; . "$$ENV_PATH"; set +a; \
		echo "env_file=$$ENV_PATH"; \
	else \
		echo "env_file=missing: $$ENV_PATH"; \
	fi; \
	echo "FP_WEB_ENABLE_PHP_MAIL=$${FP_WEB_ENABLE_PHP_MAIL:-0}"; \
	echo "FP_WEB_ENABLE_SMTP=$${FP_WEB_ENABLE_SMTP:-0}"; \
	echo "FP_WEB_TELEGRAM_BOT_TOKEN=$${FP_WEB_TELEGRAM_BOT_TOKEN:+set}"; \
	echo "FP_WEB_TELEGRAM_CHAT_ID=$${FP_WEB_TELEGRAM_CHAT_ID:+set}"

site-preview-stop:
	@set -e; \
	if [ -f "$(SITE_PREVIEW_PID)" ]; then \
		PID="$$(cat "$(SITE_PREVIEW_PID)" 2>/dev/null || true)"; \
		if [ -n "$$PID" ] && kill -0 "$$PID" 2>/dev/null; then \
			kill "$$PID" 2>/dev/null || true; \
			sleep 1; \
			kill -0 "$$PID" 2>/dev/null && kill -9 "$$PID" 2>/dev/null || true; \
			echo "[OK] stopped pid $$PID from $(SITE_PREVIEW_PID)"; \
		fi; \
		rm -f "$(SITE_PREVIEW_PID)"; \
	fi; \
	PIDS="$$(ss -ltnp 2>/dev/null | grep ':$(SITE_PREVIEW_PORT) ' | sed -n 's/.*pid=\([0-9][0-9]*\).*//p' | sort -u)"; \
	if [ -n "$$PIDS" ]; then \
		for PID in $$PIDS; do \
			if kill -0 "$$PID" 2>/dev/null; then \
				kill "$$PID" 2>/dev/null || true; \
				sleep 1; \
				kill -0 "$$PID" 2>/dev/null && kill -9 "$$PID" 2>/dev/null || true; \
				echo "[OK] stopped listener pid $$PID on port $(SITE_PREVIEW_PORT)"; \
			fi; \
		done; \
	fi; \
	echo "[OK] preview server stopped on $(SITE_PREVIEW_HOST):$(SITE_PREVIEW_PORT)"

site-preview-start:
	@set -e; \
	ENV_PATH="$(SITE_PREVIEW_ENV)"; \
	case "$$ENV_PATH" in /*|./*|../*) ;; *) ENV_PATH="./$$ENV_PATH";; esac; \
	if [ -f "$$ENV_PATH" ]; then \
		set -a; . "$$ENV_PATH"; set +a; \
	else \
		echo "[WARN] $$ENV_PATH not found; starting without local env"; \
	fi; \
	if ss -ltnp 2>/dev/null | grep -q ':$(SITE_PREVIEW_PORT) '; then \
		echo "[ERROR] port $(SITE_PREVIEW_PORT) is already in use"; \
		ss -ltnp | grep ':$(SITE_PREVIEW_PORT) ' || true; \
		exit 1; \
	fi; \
	echo "[INFO] mail=$${FP_WEB_ENABLE_PHP_MAIL:-0} smtp=$${FP_WEB_ENABLE_SMTP:-0} telegram_token=$${FP_WEB_TELEGRAM_BOT_TOKEN:+set} telegram_chat=$${FP_WEB_TELEGRAM_CHAT_ID:+set}"; \
	php -S "$(SITE_PREVIEW_HOST):$(SITE_PREVIEW_PORT)" -t "$(SITE_PREVIEW_DOCROOT)" >"$(SITE_PREVIEW_LOG)" 2>&1 & \
	echo $$! >"$(SITE_PREVIEW_PID)"; \
	sleep 1; \
	tail -10 "$(SITE_PREVIEW_LOG)"

site-preview-restart: site-preview-stop site-preview-start site-preview-status

site-preview-status:
	@echo "== preview status =="
	@echo "pid_file=$(SITE_PREVIEW_PID)"
	@if [ -f "$(SITE_PREVIEW_PID)" ]; then \
		echo "pid=$$(cat "$(SITE_PREVIEW_PID)")"; \
	else \
		echo "pid=missing"; \
	fi
	@ss -ltnp | grep ":$(SITE_PREVIEW_PORT) " || true
	@curl -s -I --max-time 10 "http://$(SITE_PREVIEW_HOST):$(SITE_PREVIEW_PORT)/" | sed -n '1,10p' || true

site-preview-smoke:
	@echo "== email smoke =="
	@curl -s --max-time 20 -X POST "http://$(SITE_PREVIEW_HOST):$(SITE_PREVIEW_PORT)/communication-request.php" \
		-d 'mode=email' \
		-d 'product_id=0' \
		-d 'product_name=Make Smoke Email' \
		-d 'primary_contact=test@example.com' \
		-d 'phone=0990000000' \
		-d 'quantity_requested=100 шт' \
		-d 'message=Make smoke email request'
	@echo
	@echo "== telegram smoke =="
	@curl -s --max-time 20 -X POST "http://$(SITE_PREVIEW_HOST):$(SITE_PREVIEW_PORT)/communication-request.php" \
		-d 'mode=telegram' \
		-d 'product_id=0' \
		-d 'product_name=Make Smoke Telegram' \
		-d 'primary_contact=test_user' \
		-d 'phone=0990000000' \
		-d 'quantity_requested=100 шт' \
		-d 'message=Make smoke telegram request'
	@echo
	@echo "== log tail =="
	@tail -60 "$(SITE_PREVIEW_LOG)" 2>/dev/null || true
# == ForPrint website preview env server end ==
