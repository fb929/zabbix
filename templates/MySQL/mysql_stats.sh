#!/bin/sh

## DO NOT EDIT
## This file is under PUPPET control

#############################################
###                                       ###
### Script for monitoring MySQL in zabbix ###
###                                       ###
#############################################

### OPTIONS VERIFICATION
if [ -z "$1" ]; then
	cat <<EOF
Usage:  $0 <metric> [user] [password] [path_to_mysqlsocket]

Options:
	metric		-- statistics metric
	user		-- MySQL user (default root)
	password	-- password for mysql user
	path_to_mysqlsocket	-- path to mysql socket file
EOF
	exit 1
fi

### PARAMETERS
METRIC="$1"
USER="${2:-root}"
PASSWD="${3:-password}"
MYSQL_SOCKET="$4"

MYSQL="mysql -u$USER -p$PASSWD"
MYSQLADMIN="mysqladmin -u$USER -p$PASSWD"
CACHE_TTL="1"	# minutes
CACHE_FILE="/tmp/`basename $0`.cache"

## if exits $MYSQL_SOCKET then set unique name for cache file
if ! [ -z "$MYSQL_SOCKET" ]; then
	MD5=`echo $MYSQL_SOCKET | md5sum | awk '{print $1}'`
	CACHE_FILE="${CACHE_FILE}_$MD5"
	MYSQL="$MYSQL -S $MYSQL_SOCKET"
	MYSQLADMIN="$MYSQLADMIN -S $MYSQL_SOCKET"
fi

### Run
## Check cache file
CACHE_FIND=`find $CACHE_FILE -mmin -$CACHE_TTL 2> /dev/null`
if [ -z "$CACHE_FIND" ] || ! [ -s "$CACHE_FILE" ];then
	$MYSQLADMIN extended-status > "$CACHE_FILE" || exit 1
	$MYSQL -e "show slave status\G" | grep -v '\*' | sed 's/\(^\) *\(.*\):\(.*\)$/\1| \2 | \3 |/g' >> "$CACHE_FILE" || exit 1
fi

## output metrics
case $1 in
	alive)
		$MYSQLADMIN ping | grep alive | wc -l
		;;
	version)
		$MYSQL -V
		;;
	Slave_IO_Running|Slave_SQL_Running)
		grep -iw "$METRIC" $CACHE_FILE | cut -d '|' -f3 | sed -e 's|Yes|1|g' -e 's|No|0|g'
		;;
	*)
		grep -iw "$METRIC" $CACHE_FILE | cut -d '|' -f3
		;;
esac
