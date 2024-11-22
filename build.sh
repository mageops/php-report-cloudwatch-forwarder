#!/usr/bin/env bash
set -euo pipefail
DIR="$(realpath "$(dirname "${BASH_SOURCE[0]}")")"
cd "$DIR"

docker build -t aws-execfwd-build -f build.dockerfile .
docker run --rm -it -e COMPOSER_PROCESS_TIMEOUT=0 -w $PWD -v $PWD:$PWD aws-execfwd-build install
docker run --rm -it -e COMPOSER_PROCESS_TIMEOUT=0 -w $PWD -v $PWD:$PWD aws-execfwd-build build
