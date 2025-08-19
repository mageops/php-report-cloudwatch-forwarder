# Use the official Composer image as the base image
FROM composer:2

# Install the BCMath extension
RUN docker-php-ext-install bcmath

# Configure git to trust any directory
RUN git config --global --add safe.directory '*'

# build with: docker build -t aws-execfwd-build -f build.dockerfile .
# Execute with: docker run --rm -it -e COMPOSER_PROCESS_TIMEOUT=0 -w $PWD -v $PWD:$PWD aws-execfwd-build build
