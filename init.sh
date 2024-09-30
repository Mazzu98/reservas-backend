#!/bin/sh

composer install
php artisan migrate --force
php artisan storage:link
php artisan db:seed --class=MasterSeeder