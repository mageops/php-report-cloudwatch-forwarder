# Pin the build to the minimum supported PHP so the artifact
# (aws-excfwd-php81) is always built and smoke-tested on its floor version
FROM php:8.1-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# Install the BCMath extension
RUN docker-php-ext-install bcmath

# Composer binary from the official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure git to trust any directory
RUN git config --global --add safe.directory '*'

# build with: docker build -t aws-execfwd-build -f build.dockerfile .
# Execute with: docker run --rm -it -e COMPOSER_PROCESS_TIMEOUT=0 -w $PWD -v $PWD:$PWD aws-execfwd-build build
