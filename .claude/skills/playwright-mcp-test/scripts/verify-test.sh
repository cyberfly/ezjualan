#!/usr/bin/env bash
# Runs a generated Playwright Test spec headless and reports pass/fail.
# Usage: verify-test.sh <path-to-spec-file>
set -euo pipefail

SPEC_PATH="${1:?Usage: verify-test.sh <path-to-spec-file>}"

if [ ! -f "$SPEC_PATH" ]; then
  echo "Error: spec file not found: $SPEC_PATH" >&2
  exit 1
fi

if ! command -v npx >/dev/null 2>&1; then
  echo "Error: npx not found. Install Node.js first." >&2
  exit 1
fi

CONFIG_FOUND=""
for cfg in playwright.config.ts playwright.config.js playwright.config.mjs; do
  if [ -f "$cfg" ]; then
    CONFIG_FOUND="$cfg"
    break
  fi
done

if [ -z "$CONFIG_FOUND" ]; then
  echo "Error: no playwright.config.* found in $(pwd)." >&2
  echo "Follow Step 1 in SKILL.md to set one up before verifying." >&2
  exit 1
fi

echo "Running: npx playwright test \"$SPEC_PATH\" --reporter=list"
npx playwright test "$SPEC_PATH" --reporter=list
