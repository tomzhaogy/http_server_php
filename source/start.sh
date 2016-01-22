#!/bin/bash
echo $# 
echo $2
echo $1
##php_cmd="/usr/local/webserver/php/bin/php "
php_cmd="/usr/bin/php "
echo $php_cmd

##start work default
if [ "$1"  = "start" ]
then
    work="http"
    log=$work.out 
    nohup $php_cmd http_server.php  $work &>$log&

    exit
fi 

##stop work
if [ "$1"  = "stop" ]
then
    ps -ef  |grep http_server.php  |awk '{print $2}'  |while read pid
        do
            kill -9 $pid
        done  
fi 

if [ "$1"  = "restart" ]
then
    ps -ef  |grep http_server.php  |awk '{print $2}'  |while read pid
        do
            kill -9 $pid
        done  

    work="http"
    log=$work.out 
    nohup $php_cmd http_server.php  $work &>$log&
fi 
##user work start
