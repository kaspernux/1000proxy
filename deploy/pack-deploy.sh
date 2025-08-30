#!/usr/bin/env bash
# Packs the deploy/ directory into a base64-encoded tar.gz suitable for embedding in cloud-init
set -euo pipefail
OUT=${1:-deploy-bundle.b64}
tmpfile=$(mktemp)
tar -czf "$tmpfile" -C "$(dirname "$0")" "$(basename "$0")/.." 2>/dev/null || true
echo "Note: packaging current directory; ensure you run this from repo root"
tar -czf "$tmpfile" deploy
base64 "$tmpfile" > "$OUT"
echo "Wrote $OUT"
rm -f "$tmpfile"
