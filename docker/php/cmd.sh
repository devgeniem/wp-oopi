#!/bin/bash
# The CMD script executed by default on container start.

echo 'Run initial tests..'

# Run tests once.
./vendor/bin/phpunit

echo 'Run pywatch to watch changes in ./tests/Test*.php..'

# Run watch to continue testing after changes.
pywatch "php ./vendor/bin/phpunit" ./tests/*.php
