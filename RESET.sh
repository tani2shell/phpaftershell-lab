#!/usr/bin/env bash
# phpaftershell-lab reset — deterministic: down, rebuild, re-seed flag, health-gate.
set -euo pipefail
cd "$(dirname "$0")"

# optional flag rotation: copy .env.example to .env and set FLAG=...
if [ -f .env ]; then
  set -a
  # shellcheck disable=SC1091
  . ./.env
  set +a
fi
if [ -n "${FLAG:-}" ]; then
  printf '%s\n' "$FLAG" > flags/flag.txt
fi

docker compose down -v --remove-orphans >/dev/null 2>&1 || true
docker compose up -d --build >/dev/null

for i in $(seq 1 40); do
  ok=1
  for p in 19042 19043; do
    curl -fsS -o /dev/null "http://127.0.0.1:$p/index.php" 2>/dev/null || ok=0
  done
  if [ "$ok" = 1 ]; then
    echo "RESET-OK vuln http://127.0.0.1:19042  patched http://127.0.0.1:19043"
    exit 0
  fi
  sleep 2
done
echo "RESET-DEGRADED portal not answering after 80s — check: docker compose ps" >&2
exit 1
