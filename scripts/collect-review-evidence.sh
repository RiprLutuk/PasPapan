#!/usr/bin/env bash
set -euo pipefail

EVIDENCE_DIR="${EVIDENCE_DIR:-test-results/review-evidence}"
mkdir -p "$EVIDENCE_DIR"

run_gate() {
  local name="$1"
  local log_file="$2"
  shift 2

  echo "==> $name"
  "$@" 2>&1 | tee "$EVIDENCE_DIR/$log_file"
}

run_gate "Rust workspace tests" "cargo-test.log" cargo test --workspace
run_gate "SvelteKit typecheck" "svelte-check.log" bun run check
run_gate "SvelteKit production build" "svelte-build.log" bun run build
run_gate "Web design & PWA tests" "web-tests.log" bun run test:web

{
  echo "# Local Review Evidence (SvelteKit + Rust)"
  echo
  echo "| Gate | Log |"
  echo "| --- | --- |"
  echo "| Rust workspace tests | cargo-test.log |"
  echo "| SvelteKit typecheck | svelte-check.log |"
  echo "| SvelteKit production build | svelte-build.log |"
  echo "| Web design & PWA tests | web-tests.log |"
} > "$EVIDENCE_DIR/summary.md"

echo "Review evidence written to $EVIDENCE_DIR"
