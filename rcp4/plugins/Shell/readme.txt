/**
 * CustomPoints Plugin
 * @package remoteCP
 * @author hal.sascha
 * @copyright (c) 2006-2009
 * @version 1
 */

Requirements:
-------------
The plugin need at least remoteCP 4.0.3.6 or newer

Plugin:
--------
This plugin allows an admin to view the current remoteCP Live status and supports to start/stop/restart remoteCP Live.
The plugin currently works only on linux machines!!!
The admin needs the "globalmaintenance" permission to see and use this plugin.

Install:
--------
1. Copy all folders to your remoteCP-4 /plugins/ directory
2. Open your /xml/settings/default/settings.xml and add inside <plugins></plugins>:

	<plugin>Shell</plugin>

3. Open your remoteCP-4 and start using the new plugin :)

Config:
--------
1. open file shell.script.tpl with your favorite texteditor
2. change the values for

	PHPPATH=/usr/lib/cgi-bin/php
	SERVERPATH=/var/www/remotecp/htdocs