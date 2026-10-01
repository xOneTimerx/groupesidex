#!/usr/bin/env bash
# Parité à plusieurs largeurs : bash run-parity.sh <url> <nom> [largeurs]
url=$1; name=$2; widths=${3:-390 768 1024 1280 1440 1920}
cd "$(dirname "$0")"
for w in $widths; do
  node parity.js "$url" "$w" "out/parity/$name-$w" --shots >/dev/null && python3 parity.py "out/parity/$name-$w" 3 --quiet > "out/parity/$name-$w/report.txt"
  echo "$w px : $(head -1 out/parity/$name-$w/report.txt) — $(tail -1 out/parity/$name-$w/report.txt)"
done
