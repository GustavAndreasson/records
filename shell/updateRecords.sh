#!/bin/sh

cd /var/www/html/records/shell

PIDFILE=updateRecords.pid

echo "$(date +'%F %T') BEGIN"

if [ -f ${PIDFILE} ]; then
    PID=`cat ${PIDFILE}`
    if ps -p $PID > /dev/null ; then
        echo "$(date +'%F %T') PID file exists and PID $PID is running"
        exit 0
    else
        echo "$(date +'%F %T') PID file exists but PID $PID is not running. Removing PID-file."
        rm ${PIDFILE}
    fi
fi

echo $$ > ${PIDFILE}
echo "$(date +'%F %T') Created PID-file and running update"

php updateRecords.php >> /var/www/html/records/var/logs/updateRecords.log

rm ${PIDFILE}
echo "$(date +'%F %T') DONE"

