#!/bin/bash

## DO NOT EDIT
## This file is under PUPPET control

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
RAID_STATUS_FILE="/tmp/check_disk.raid_status"

### Functions
do_usage(){
	cat <<EOF

Script for monitoring disks state in zabbix

Usage:
	$0 io <disk>
	$0 raid
	$0 -h

Options:
	io		-- get statistics load disk in %
	raid	-- get status raid
	-h		-- print this help page
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

### Functions
## IO wait status
do_io(){
	CHECK_DISK="$1"	

	# Check cache file
	CACHE_FIND=`find $CACHE_FILE -mmin -$CACHE_TTL 2> /dev/null`
	if [ -z "$CACHE_FIND" ] || ! [ -s "$CACHE_FILE" ];then
		iostat -x 1 2 > $CACHE_FILE 2>/dev/null || exit 1
	fi

	# Get data
	grep -P "^$CHECK_DISK\s" $CACHE_FILE | tail -n 1 | awk '{print $NF}' | sed -e 's|\.[0-9]\+||'
}
## Raid status
do_raid(){
	# clean raid status file
	echo -n "" > $RAID_STATUS_FILE

	# check if the hardware raid controllers
	RAID_CONTROLLERS=`lspci | grep "RAID bus controller" | cut -d ':' -f 3 | sed 's|\s||g'`

	# check if the software raid
	if [ -f /proc/mdstat ]; then
		RAID_CONTROLLERS="$RAID_CONTROLLERS mdraid"
	fi

	# get status controllers
	for RAID_CONTROLLER in $RAID_CONTROLLERS; do
		case $RAID_CONTROLLER in
			AdaptecAAC-RAID*)
				if arcconf GETCONFIG 1 AL | grep -P "State|Controller Status|Status of logical device" | grep -q -vP "Optimal|Online"; then
					echo "$RAID_CONTROLLER state ERROR" >> $RAID_STATUS_FILE
				else
					echo "$RAID_CONTROLLER state OK" >> $RAID_STATUS_FILE
				fi
				;;
			LSILogic/SymbiosLogicMegaRAIDSAS2108*)
				if megacli -CfgDsply -aALL | grep -P "^Firmware state:|^State:" | grep -q -vP "Optimal|Online"; then
					echo "$RAID_CONTROLLER state ERROR" >> $RAID_STATUS_FILE
				else
					echo "$RAID_CONTROLLER state OK" >> $RAID_STATUS_FILE
				fi
				;;
			mdraid)
				ACTIVE_LIST=`grep "^md.*: active" /proc/mdstat | cut -f 1 -d ' '`
				for DEV in $ACTIVE_LIST; do
					ARRAY_STATE=`cat /sys/block/$DEV/md/array_state`
					SYNC_ACTION=`cat /sys/block/$DEV/md/sync_action`
					if [ "$ARRAY_STATE" = clean ] && [ "$SYNC_ACTION" = idle ]; then
						echo "$RAID_CONTROLLER state OK" >> $RAID_STATUS_FILE
					else
						echo "$RAID_CONTROLLER state ERROR, device $DEV" >> $RAID_STATUS_FILE
					fi
				done
				;;
			*)
				echo "ERROR - Raid Controller $RAID_CONTROLLER not supported" >> $RAID_STATUS_FILE
				;;
		esac
	done

	# exit if not found raid controllers
	if [ -z "$RAID_CONTROLLERS" ]; then
		echo "Raid not found" >> $RAID_STATUS_FILE
	fi

	# check raid status file and print status for zabbix
	if grep -q "ERROR" $RAID_STATUS_FILE; then
		echo 1
	else
		echo 0
	fi
}

### Get metrics
case $METRIC in
	io)
		do_io $DISK
		;;
	raid)
		do_raid
		;;
	*)
		echo "Metric $METRIC not supperted"
		exit 1
		;;
esac
