#!/bin/sh

echo "Launching Server"
exec ./TrackmaniaServer /dedicated_cfg=dedicated_cfg.txt /game_settings=MatchSettings/custom_game_settings.txt /nodaemon &

echo "Database"
service mysql start
mysql -e "create database usbtmnaseco1;"
mysql usbtmnaseco1 < /opt/xaseco/localdb/aseco.sql
mysql usbtmnaseco1 < /opt/xaseco/localdb/extra.sql

echo "Start Xaseco"
sleep 5
cd /opt/xaseco
php aseco.php
