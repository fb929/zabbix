#!/bin/sh

## DO NOT EDIT
## This file is under PUPPET control

#################################
###                           ###
### Script for get statistics ###
### from local nginx          ###
###                           ###
#################################

### OPTIONS VERIFICATION
if [ -z "$1" ];then
	cat <<EOF
Usage:  $0 <metric> [url]

Options:
	metric   -- statistics metric
	url      -- nginx statistics url (default http://127.0.0.1/server-status)
EOF
	exit 1
fi

### PARAMETERS
METRIC="$1"
STATS_URL="${2:-http://127.0.0.1/server-status}"
CURL="curl"
CACHE_TTL="1"		# TTL min
CACHE_FILE="/tmp/`basename $0`.cache"

### RUN
## Check cache file
CACHE_FIND=`find $CACHE_FILE -mmin -$CACHE_TTL 2>/dev/null`
if [ -z "$CACHE_FIND" ] || ! [ -s "$CACHE_FILE" ];then
	$CURL $STATS_URL > $CACHE_FILE 2>/dev/null || exit 1
fi

## output metrics
case $METRIC in
	active)
		grep "Active connections" $CACHE_FILE | cut -d':' -f2
		;;
	accepts)
		sed -n '3p' $CACHE_FILE | cut -d" " -f2
		;;
	handled)
		sed -n '3p' $CACHE_FILE | cut -d" " -f3
		;;
	requests)
		sed -n '3p' $CACHE_FILE | cut -d" " -f4
		;;
	reading)
		grep "Reading" $CACHE_FILE | cut -d':' -f2 | cut -d' ' -f2
		;;
	writing)
		grep "Writing" $CACHE_FILE | cut -d':' -f3 | cut -d' ' -f2
		;;
	waiting)
		grep "Waiting" $CACHE_FILE | cut -d':' -f4 | cut -d' ' -f2
		;;
	*)
		echo "Unsupported metric $METRIC"
		exit 1
	;;
esac

exit 0
