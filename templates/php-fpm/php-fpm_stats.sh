#!/bin/sh

## DO NOT EDIT
## This file is under PUPPET control

#################################
###                           ###
### Script for get statistics ###
### from local php-fpm        ###
###                           ###
#################################

### OPTIONS VERIFICATION
if [ -z "$1" ];then
	cat <<EOF
Usage:  $0 <metric> [pool port] [url]

Options:
	metric   	-- statistics metric
	pool port	-- fpm pool port, default 9001
	url     	-- php-fpm statistics url or ping url, default http://localhost/fpm-status?port=9001 and http://localhost/fpm-ping?port=9001
EOF
	exit 1
fi

### PARAMETERS
METRIC="$1"
POOL_PORT="${2:-9001}"
STATS_URL="${3:-http://localhost/fpm-status?port=$POOL_PORT}"
PING_URL="${3:-http://localhost/fpm-ping?port=$POOL_PORT}"
CURL="curl"
CACHE_TTL="1"		# TTL min
CACHE_FILE="/tmp/`basename $0`.cache_$POOL_PORT"

### for ping 
if [ x"$METRIC" = x"ping" ]; then
	[ `$CURL $PING_URL 2>/dev/null | grep pong` ] && echo 0 || echo 1
	exit 0
fi

### RUN
## Check cache file
CACHE_FIND=`find $CACHE_FILE -mmin -$CACHE_TTL 2> /dev/null`
if [ -z "$CACHE_FIND" ] || ! [ -s "$CACHE_FILE" ];then
	$CURL $STATS_URL > $CACHE_FILE 2>/dev/null || exit 1
fi

## output metrics
case $METRIC in
	pool)
		grep "^pool:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	process_manager)
		grep "^process manager:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	start_time)
		grep "^start time:" $CACHE_FILE | sed 's|^start time:\s\+||'
		;;
	start_since)
		grep "^start since:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	accepted_conn)
		grep "^accepted conn:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	listen_queue)
		grep "^listen queue:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	max_listen_queue)
		grep "^max listen queue:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	listen_queue_len)
		grep "^listen queue len:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	idle_processes)
		grep "^idle processes:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	active_processes)
		grep "^active processes:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	total_processes)
		grep "^total processes:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	max_active_processes)
		grep "^max active processes:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	max_children_reached)
		grep "^max children reached:" $CACHE_FILE | cut -d ":" -f 2 | sed 's|^\s\+||'
		;;
	*)
		echo "Unsupported metric $METRIC"
		exit 1
	;;
esac

exit 0
