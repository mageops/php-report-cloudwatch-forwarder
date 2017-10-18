#!/usr/bin/env bash

while :; do
    clear
    rsync -r -a -v --exclude ".idea" . ec2-user@18.194.165.79:/home/ec2-user/mlogs
    sleep 5
done
