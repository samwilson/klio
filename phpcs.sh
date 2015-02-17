#!/bin/bash

LOC=$(dirname $0)
$LOC/vendor/bin/phpcs --standard=PSR2 --extensions=php $LOC/modules/*/classes $LOC/modules/*/tests
