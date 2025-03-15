#!/bin/bash -i

php83 ./vendor/bin/php-cs-fixer fix --show-progress=dots "$@"

php83 ./vendor/bin/phplint