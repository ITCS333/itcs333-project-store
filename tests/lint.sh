#!/usr/bin/env bash
# PROVIDED — portable-SQL + PHP lint for the project. Do not modify.
set -euo pipefail
cd "$(dirname "$0")/.."
fail=0

echo "== PHP syntax check (modules, stubs, public, src) =="
while IFS= read -r f; do
  if ! php -l "$f" >/dev/null 2>&1; then
    echo "PHP SYNTAX ERROR: $f"
    php -l "$f"
    fail=1
  fi
done < <(find modules stubs public src -name '*.php' 2>/dev/null)

echo "== Portable-SQL keyword scan (module code) =="
PATTERN='AUTO_INCREMENT|ON DUPLICATE KEY|\bENUM[[:space:]]*\(|IFNULL[[:space:]]*\(|NOW[[:space:]]*\([[:space:]]*\)|LIMIT [0-9]+,[0-9]+'
if grep -rInE "$PATTERN" modules/ 2>/dev/null; then
  echo "PORTABILITY WARNING: engine-specific SQL found in modules/ (see README 'Portable SQL')"
  fail=1
fi

if [ "$fail" -eq 0 ]; then echo "lint: OK"; else echo "lint: FAIL"; fi
exit "$fail"
