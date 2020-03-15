#!/bin/bash
#
# linux shell script to start/stop/restart trackmania server/server-controller
# Author: hal.ko.sascha
# Date: 12.11.2009

#==================== config =====================
PHPPATH=/usr/lib/cgi-bin/php
SERVERPATH=/var/www/virtual/team-hal.de/remotecp/htdocs

#===================== vars ======================
TYPEA=0
TYPEB=0
LABELA=0

#=============== check parameters ================
# $1		execution typeA (controller, server)
# $2		execution typeB (start, stop, restart, status)
# $3		serverlogin
# $4		remoteCP serverid
SERVERLOGIN=$3
SERVERID=$4
case "$1" in
	server)
		TYPEA=server
		LABELA=Server
	;;
	controller)
		TYPEA=controller
		LABELA=Server-controller
	;;
	*)
		echo "Invalid 1st parameter: "$1" {server|controller}"
		exit 1
esac

case "$2" in
	start)
		TYPEB=start
	;;
	stop)
		TYPEB=stop
	;;
	restart)
		TYPEB=restart
	;;
	status)
		TYPEB=status
	;;
	status2)
		TYPEB=status2
	;;
	*)
		echo "Invalid 2nd parameter: "$2" {start|stop|restart|status}"
		exit 1
esac

#==================== script =====================
PIDFILE=$SERVERPATH/$SERVERLOGIN.pid
case $TYPEB in
	start)
		echo "Starting $LABELA daemon: "$SERVERLOGIN
		cd /
		cd /$SERVERPATH
		TEST="$PHPPATH live.php -- $SERVERID http://domain.com/ </dev/null >live.log 2>&1 &"
		echo " - $TEST"
		$PHPPATH live.php -- $SERVERID http://domain.com/ </dev/null >live.log 2>&1 &
		echo " - Writing PID-File: $PIDFILE"
		echo $! > $PIDFILE
		echo "$LABELA daemon started: "$SERVERLOGIN
	;;

	stop)
		echo "Stopping $LABELA daemon: "$SERVERLOGIN
		echo " - Server: $SERVERLOGIN"
		kill `cat $PIDFILE`
		rm -f $PIDFILE
		echo "$LABELA daemon stopped: "$SERVERLOGIN
	;;

	restart)
		echo "Restarting $LABELA daemon: "$SERVERLOGIN
		$0 $TYPEA stop $SERVERLOGIN $SERVERID
		sleep 1
		$0 $TYPEA start $SERVERLOGIN $SERVERID
		echo "$LABELA daemon restarted: "$SERVERLOGIN
	;;

	status)
		echo "Checking $LABELA status: "$SERVERLOGIN
		if [ -f $PIDFILE ] ; then
			echo " - PID-file ok!"
			if kill -0 `cat $PIDFILE` 2>/dev/null ; then
				cd /
				cd /$SERVERPATH
				echo " - $SERVERLOGIN running with pid `cat $PIDFILE`."
				cd /
			else
				echo " - $SERVERLOGIN unable to get status, invalid PID in PID-File"
			fi
		else
			echo " - $SERVERLOGIN was stopped by operator, no action."
		fi
		echo "$LABELA status check done: "$SERVERLOGIN
	;;

	status2)
		if [ -f $PIDFILE ] ; then
			if kill -0 `cat $PIDFILE` 2>/dev/null ; then
				cd /
				cd /$SERVERPATH
				echo "1"
				cd /
			else
				echo "0"
			fi
		else
			echo "0"
		fi
	;;

	*)
		echo "Unknown error"
		exit 1
esac

#===================== exit ======================
exit 0