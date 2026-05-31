#!/bin/bash
# SessionStart-hook för Wexoe-monorepot (Claude Code on the web).
# Gör en ny session redo att KÖRA & TESTA direkt:
#   - installerar builder-deps (Next.js/TS) → tsc/lint/vitest funkar
#   - installerar PHP-deps om composer.json finns → pest/phpcs funkar
#   - synkar schemakopiorna ur packages/schema (så väktaren är grön)
# Idempotent och non-interaktiv. Synkront läge (inga race conditions).
set -euo pipefail

ROOT="${CLAUDE_PROJECT_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
cd "$ROOT"

# Kör tyngre installationssteg bara i molnet; lokalt sköter du det själv.
if [ "${CLAUDE_CODE_REMOTE:-}" = "true" ]; then
  # 1. Builder-beroenden (cache:as mellan sessioner).
  if [ -f apps/builder/package.json ]; then
    (cd apps/builder && npm install --no-audit --no-fund)
  fi

  # 2. PHP-beroenden (PHPCS/Pest) — bara om composer.json finns (FAS 6+).
  if [ -f apps/wordpress/composer.json ] && command -v composer >/dev/null 2>&1; then
    (cd apps/wordpress && composer install --no-interaction --no-progress)
  fi
fi

# 3. Synka schemakopiorna ur originalet (snabbt, beroendefritt, alltid).
if [ -f tools/schema-sync.mjs ]; then
  node tools/schema-sync.mjs || true
fi

echo "wexoe session-start: klar (builder-deps + schema-synk)."
