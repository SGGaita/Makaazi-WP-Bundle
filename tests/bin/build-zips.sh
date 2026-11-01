#!/usr/bin/env bash
# Build installable zips of the theme and plugins into dist/.
set -euo pipefail
cd "$(dirname "$0")/../.."
rm -rf dist && mkdir -p dist
EXCLUDES=( -x '*.DS_Store' -x '*/node_modules/*' -x '*/.git*' )
( cd plugins && zip -qr ../dist/makaazi-core.zip makaazi-core "${EXCLUDES[@]}" )
( cd plugins && zip -qr ../dist/makaazi-hero-slider.zip makaazi-hero-slider "${EXCLUDES[@]}" )
( cd themes && zip -qr ../dist/makaazi-theme.zip makaazi-theme "${EXCLUDES[@]}" )
ls -la dist
