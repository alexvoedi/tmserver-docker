#!/bin/sh

echo "Launching Server"
exec ./TrackmaniaServer /dedicated_cfg=dedicated_cfg.txt /game_settings=MatchSettings/custom_game_settings.txt /nodaemon &

echo "Start MySQL"
service mysql start
mysql -e "create database if not exists usbtmnaseco1;"
mysql -e "create user if not exists 'alex'@'%' identified by 'alexseinpasswort;'"
mysql -e "grant all privileges on *.* to 'alex'@'%';"
mysql usbtmnaseco1 < /opt/xaseco/localdb/aseco.sql
mysql usbtmnaseco1 < /opt/xaseco/localdb/extra.sql

echo "Start Xaseco"
sleep 5
cd /opt/xaseco
php aseco.php
