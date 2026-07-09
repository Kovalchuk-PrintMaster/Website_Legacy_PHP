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
