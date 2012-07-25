#!/bin/sh

## DO NOT EDIT
## This file is under PUPPET control

#################################
###                           ###
### Script for get statistics ###
### from redis                ###
###                           ###
#################################

### OPTIONS VERIFICATION
if [ -z "$1" ];then
	cat <<EOF
Usage:  $0 <metric> [hostname] [port]

Options:
	metric   -- statistics metric
	hostname -- redis hostname (default 127.0.0.1)
	port     -- redis port (default 6379)
EOF
	exit 1
fi

### PARAMETERS
METRIC="$1"
HOSTNAME="${2:-127.0.0.1}"
PORT="${3:-6379}"
CACHE_TTL="1"		# TTL min
CACHE_FILE="/tmp/`basename $0`.cache"

### RUN
## Check cache file
CACHE_FIND=`find $CACHE_FILE -mmin -$CACHE_TTL 2>/dev/null`
if [ -z "$CACHE_FIND" ] || ! [ -s "$CACHE_FILE" ];then
	redis-cli -h $HOSTNAME -p $PORT info > $CACHE_FILE 2>/dev/null || exit 1
fi

## output metrics
grep "^$METRIC:" $CACHE_FILE | cut -d ":" -f 2
