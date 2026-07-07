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