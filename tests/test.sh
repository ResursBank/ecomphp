#!/bin/bash

phpunit=`which phpunit`
if [ "$phpunit" = "" ] ; then
	if [ -f "./phpunit.phar" ] ; then
		phpunit="./phpunit.phar"
	fi
fi

if [ "$phpunit" != "" ] ; then
	$phpunit ecomphp-1.2.php
else
	echo "No phpunit.phar found"
fi
