#!/bin/bash

## DO NOT EDIT
## This file is under PUPPET control

##############################################
###                                        ###
### Script for monitoring puppet in zabbix ###
###                                        ###
##############################################

# Puppet state file, default /var/lib/puppet/state/state.yaml
STATE_FILE=${1:-/var/lib/puppet/state/state.yaml}
# TTL in minutes, default 30 min
TTL=${2:-30}

# Check state file
CHECK_RUN=`find $STATE_FILE -mmin -$TTL 2>/dev/null`

if [ -z "$CHECK_RUN" ];then
	# status fail
	echo 1
else
	# status ok
	echo 0
fi
