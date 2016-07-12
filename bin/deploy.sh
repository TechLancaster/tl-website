#!/bin/bash

php composer.phar install
php app/console doctrine:schema:validate
php app/console cache:clear --env=prod
chown -R www-data app/cache app/logs
