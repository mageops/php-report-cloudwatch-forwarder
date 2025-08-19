#!/usr/bin/env bash
set -euo pipefail
DIR="$(realpath "$(dirname "${BASH_SOURCE[0]}")")"
cd "$DIR"

# Build the Docker image
docker build -t aws-execfwd-build -f build.dockerfile .

# Create a container with long sleep to keep it alive
CONTAINER=$(docker create aws-execfwd-build sleep 7200)
docker cp . $CONTAINER:/app/

# Start container and run commands
docker start $CONTAINER
docker exec $CONTAINER composer install --working-dir=/app
docker exec $CONTAINER composer build --working-dir=/app

# Copy the build artifact back
mkdir -p build
docker cp $CONTAINER:/app/build/aws-excfwd ./build/

# Clean up
docker rm -f $CONTAINER
