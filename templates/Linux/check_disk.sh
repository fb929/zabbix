#!/bin/bash

###################################################
###                                             ###
### Script for monitoring disks state in zabbix ###
###                                             ###
###################################################

### Vars
METRIC="${1:-io}"
DISK="${2:-sda}"
CACHE_TTL="1"	# TTL min
CACHE_FILE="/tmp/check_disk.cache"

### Functions
do_usage(){
	cat <<EOF

Script for monitoring disks state in zabbix

Usage:
	$0 <metric> <disk>
	$0 -h
EOF
}

### Get options
while getopts h Opts; do
	case $Opts in
		h|?)
			do_usage
			exit 1
			;;
	esac
done

### Check options
if [ -z "$METRIC" ] || [ -z "$DISK" ]; then
	do_usage
	exit 1
fi

### Check cache file
CACHE_FIND=`find $CACHE_FILE -mmin $CACHE_TTL 2> /dev/null`
if [ -z "$CACHE_FIND" ] || ! [ -s "$CACHE_FILE" ];then
	iostat -x 1 2 > $CACHE_FILE 2>/dev/null || exit 1
fi

### Get metrics
case $METRIC in
	io)
		grep -P "^$DISK\s" $CACHE_FILE | tail -n 1 | awk '{print $NF}' | sed -e 's|\.[0-9]\+||'
		;;
	*)
		echo "Metric $METRIC not supperted"
		exit 1
		;;
esac
