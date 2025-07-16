#!/usr/bin/env bash
set -euo pipefail
DIR="$(realpath "$(dirname "${BASH_SOURCE[0]}")")"
cd "$DIR"

docker build -t aws-execfwd-build -f build.dockerfile .
docker run --rm -it -e COMPOSER_PROCESS_TIMEOUT=0 -w $PWD -v $PWD:$PWD aws-execfwd-build composer update
docker run --rm -it -e COMPOSER_HOME=/tmp/composer_home -e COMPOSER_PROCESS_TIMEOUT=0 -w $PWD -v $PWD:$PWD aws-execfwd-build /bin/sh -c "composer global require macfja/phar-builder && php -d phar.readonly=0 /tmp/composer_home/vendor/bin/phar-builder package"
