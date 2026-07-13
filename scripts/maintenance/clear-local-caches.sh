#!/usr/bin/env bash

set -euo pipefail

rm -rf "${HOME}/.cache/ms-playwright"
rm -rf "${HOME}/.cache/pip"
rm -rf "${HOME}/.npm/_cacache"
rm -rf ".cache/playwright"
