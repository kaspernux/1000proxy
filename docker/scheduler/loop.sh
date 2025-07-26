#!/bin/sh
# Laravel scheduler loop script for Docker

while [ true ]
do
    php artisan schedule:run --verbose --no-interaction
    sleep 60
done
