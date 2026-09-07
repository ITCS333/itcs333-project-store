#!/usr/bin/env bash
#
# PROVIDED — do not modify.
# Mark which modules run as the REAL handler vs the contract-compliant STUB.
# Writes tests/.active_modules, read by public/api.php.
#
# Usage:
#   tests/use_stubs.sh --except m3_cart     # m3 real, others stub (default grading mode)
#   tests/use_stubs.sh --all-real           # integration: every module real
#   tests/use_stubs.sh --all-stub           # everything stub
#   tests/use_stubs.sh --set m1_auth=stub m2_catalog=real ...
#
set -euo pipefail
cd "$(dirname "$0")/.."
FILE="tests/.active_modules"
MODULES=(m1_auth m2_catalog m3_cart m4_orders m5_admin)

write_all() {
  : > "$FILE"
  for m in "${MODULES[@]}"; do
    echo "$m=$1" >> "$FILE"
  done
}

case "${1:-}" in
  --except)
    EXCEPT="${2:?--except needs a module name}"
    : > "$FILE"
    for m in "${MODULES[@]}"; do
      if [[ "$m" == "$EXCEPT" ]]; then echo "$m=real" >> "$FILE"; else echo "$m=stub" >> "$FILE"; fi
    done
    ;;
  --all-real)
    write_all real
    ;;
  --all-stub)
    write_all stub
    ;;
  --set)
    shift
    # base = stub for everything, then apply the overrides (real/stub/missing).
    write_all stub
    for kv in "$@"; do
      m="${kv%%=*}"; v="${kv##*=}"
      grep -v "^$m=" "$FILE" > "$FILE.tmp" || true
      mv "$FILE.tmp" "$FILE"
      echo "$m=$v" >> "$FILE"
    done
    ;;
  *)
    echo "Usage: $0 --except <module> | --all-real | --all-stub | --set m=v ..." >&2
    exit 1
    ;;
esac

echo "Active module map:"
cat "$FILE"
