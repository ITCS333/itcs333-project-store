#!/usr/bin/env bash
# ITCS333 Project — local test runner (mirrors CI). All suites use SQLite.
set -euo pipefail
cd "$(dirname "$0")"

if [ ! -d tests/vendor ]; then
  echo "Installing test dependencies (first run only)..."
  composer install --working-dir=tests --no-interaction --quiet
fi

MODE="${1:---all}"
PHPUNIT="php tests/vendor/bin/phpunit -c tests/phpunit.xml"
PORT_BASE="${PROJ_PORT_BASE:-8600}"

run_module() {
  local m="$1" port=$((PORT_BASE + RANDOM % 400))
  bash tests/use_stubs.sh --except "$m" >/dev/null
  PROJ_PORT=$port $PHPUNIT --testsuite "$m"
}

case "$MODE" in
  --module)
    run_module "${2:?--module needs m1_auth|m2_catalog|m3_cart|m4_orders|m5_admin}"
    ;;
  --all-modules)
    for m in m1_auth m2_catalog m3_cart m4_orders m5_admin; do run_module "$m"; done
    ;;
  --integration)
    bash tests/use_stubs.sh --all-real >/dev/null
    PROJ_PORT=$((PORT_BASE + RANDOM % 400)) $PHPUNIT --testsuite integration
    ;;
  --degradation)
    bash tests/use_stubs.sh --set m2_catalog=missing >/dev/null
    PROJ_PORT=$((PORT_BASE + RANDOM % 400)) $PHPUNIT --testsuite degradation
    bash tests/use_stubs.sh --all-real >/dev/null
    ;;
  --lint)
    bash tests/lint.sh
    ;;
  --all)
    bash tests/use_stubs.sh --all-stub >/dev/null
    for m in m1_auth m2_catalog m3_cart m4_orders m5_admin; do run_module "$m"; done
    bash tests/use_stubs.sh --all-real >/dev/null
    PROJ_PORT=$((PORT_BASE + RANDOM % 400)) $PHPUNIT --testsuite integration
    bash tests/use_stubs.sh --set m2_catalog=missing >/dev/null
    PROJ_PORT=$((PORT_BASE + RANDOM % 400)) $PHPUNIT --testsuite degradation
    bash tests/use_stubs.sh --all-real >/dev/null
    bash tests/lint.sh
    ;;
  *)
    echo "Usage: $0 --module <m> | --all-modules | --integration | --degradation | --lint | --all" >&2
    exit 1
    ;;
esac
