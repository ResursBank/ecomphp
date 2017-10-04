#!/bin/bash

phpver=(`php -v|grep "built"|awk '{printf $2}'| sed 's/\./ /g'`)
pharfile=""
major="${phpver[0]}"
minor="${phpver[1]}"
build="${phpver[2]}"
unitver="5.7"
altver=""
testThis="ecomphp-composerized.php"

echo -n "Testing phpunit.phar: "

if [ "$major" = "7" ] && [ $minor = "0" ] ; then
	echo "PHP 7.${minor}/phpunit-6.3.phar"
	unitver="6.3"
fi
if [ "$major" = "7" ] && [ $minor = "1" ] ; then
	echo "PHP 7.${minor}/phpunit-6.3.phar"
	unitver="6.3"
fi
findphar=`which phpunit-${unitver}.phar`
if [ "$findphar" = "" ] ; then
	if [ "$unitver" = "6.3" ] ; then
		altver="5.7"
	else
		altver="6.3"
	fi
	findphar=`which phpunit-${altver}.phar`
	if [ "$findphar" != "" ] ; then
		echo "No. Failover, found phpunit-${altver}"
		pharfile=$findphar
	else
		echo -n "No. Failover test (phpunit.phar): "
		findphar=`which phpunit.phar`
		if [ "$findphar" = "" ] ; then
			echo "No. I failed."
			exit 1
		fi
		pharfile=$findphar
		echo "Yes. Now I don't know what version I'm running..."
	fi
else
	pharfile="phpunit-${unitver}.phar"
fi

if [ "$pharfile" != "" ] ; then
	echo "Primary test: $pharfile (testing if everything is OK)..."
	firstphar=$pharfile
	$firstphar $testThis >/dev/null 2>&1
	if [ "$?" != "0" ] ; then
		if [ "$unitver" = "6.3" ] ; then
			altver="5.7"
		else
			altver="6.3"
		fi
		findphar=`which phpunit-${altver}.phar`
		if [ "$findphar" != "" ] ; then
			pharfile=$findphar
			if [ "$pharfile" = "" ] ; then
				$pharfile $testThis >/dev/null 2>&1
				if [ "$?" = "0" ] ; then
					echo "It seems that the current unittester ($unitver) can not run, but the alternative ($altver) works fine. Running instead:"
					$pharfile $testThis
					exit 0
				fi
				# When the second alternative fails, run the first again to show the errors
				$firstphar $testThis
			else
				echo "The current unittester ($unitver) failed and I can't find any alternative tester..."
				# No second alternative? Show the first with errors
				$firstphar $testThis
			fi
		else
			# No second tester found, so I'll run the first again, but more visible
			$firstphar $testThis
		fi
	else
		echo "Seems find. Showing result..."
		$firstphar $testThis
	fi
else
	echo "No phpunit found!"
fi
