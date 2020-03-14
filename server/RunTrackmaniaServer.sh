#!/bin/sh

echo "Launching Server"
exec ./TrackmaniaServer /dedicated_cfg=dedicated_cfg.txt /game_settings=MatchSettings/custom_game_settings.txt /nodaemon
