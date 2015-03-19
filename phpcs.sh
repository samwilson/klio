#!/bin/bash

LOC=$(dirname $0)
ARGS="--standard=PSR2 --extensions=php $LOC/modules/*/classes $LOC/modules/*/tests"
#$LOC/vendor/bin/phpcbf $ARGS
$LOC/vendor/bin/phpcs $ARGS
