#!/usr/bin/env bash
set -euo pipefail

ACTION="${1:-up}"

ensure_podman_machine() {
  if ! command -v podman >/dev/null 2>&1; then
    echo "Error: podman is not installed. Install with: brew install podman podman-compose" >&2
    exit 1
  fi

  if [[ "$(uname -s)" == "Darwin" ]]; then
    if ! podman machine inspect >/dev/null 2>&1; then
      echo "==> Initializing Podman Machine..."
      podman machine init --cpus 4 --memory 4096 --disk-size 30
    fi

    local state
    state="$(podman machine inspect --format '{{.State}}' 2>/dev/null || echo 'stopped')"
    if [[ "$state" != "running" ]]; then
      echo "==> Starting Podman Machine..."
      podman machine start
    fi
  fi
}

ensure_env_files() {
  [[ -f apps/api/.env ]] || cp apps/api/.env.example apps/api/.env
  [[ -f apps/web/.env ]] || cp apps/web/.env.example apps/web/.env
}

case "$ACTION" in
  init)
    ensure_podman_machine
    ensure_env_files
    echo "Podman Machine is running and service .env files are ready."
    ;;
  up)
    ensure_podman_machine
    ensure_env_files
    podman compose -f compose.yaml up --build -d
    echo "PasPapan services started:"
    echo "  - Web (FE): http://localhost:4173"
    echo "  - API (BE): http://localhost:3000/health"
    ;;
  down)
    podman compose -f compose.yaml down
    ;;
  logs)
    podman compose -f compose.yaml logs -f
    ;;
  ps|status)
    podman compose -f compose.yaml ps
    ;;
  *)
    echo "Usage: $0 {init|up|down|logs|status}" >&2
    exit 1
    ;;
esac
