#!/bin/sh

namespace="$1"
file="$2"

if [ "" = "$2" ] ; then
	echo "Usage: $0 <namespaceName> <fileName.php>"
	exit
fi

sed -i "s/namespace TorneLIB/namespace $namespace/" $file
