#!/usr/bin/env sh
# SPDX-License-Identifier: AGPL-3.0-or-later
set -eu
cd "$(dirname "$0")/.."
php scripts/lint.php
php scripts/test.php
php scripts/browser_generator.php
mdbook build browser
printf '%s\n' 'Reference built in browser/book. Nothing was published or pushed.'
