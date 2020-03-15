/**
 * remoteCP 4
 * ütf-8 release
 *
 * @package remoteCP
 * @author hal.sascha
 * @copyright (c) 2006-2009
 * @version 4.0.3.6
 */
For installation help and further informations visit: http://www.tmbase.de/V6/docs/
Read this file carefully if you update from an previous remoteCP installation.

Important: This remoteCP release needs at least the Trackmania Dedicated Server Version from 2009-11-19!

------------------------------
 * Important (!!!)

It is possible to open and read all XML Files (in some case also .bat and .sh) in any webbrowser, by
directly calling the URL of that file. For example:
 - http://www.yourdomain.com/remoteCP4/xml/servers.xml

This will cause a major security problem if you don't use the .htaccess file delivered with the remoteCP-4 download.
You shouldn't delete or change that file in any case, if you don't know what your doing.

Please read this for further informations about htaccess:
 - http://httpd.apache.org/docs/1.3/howto/htaccess.html


/**
 * Update from previous versions
 */
You have to add some new values to your xml config files.
Please make sure you added everything before you report new bugs.
I recommend to setup everything from scratch.

------------------------------
 * Update from Version 4.0.3.5

 - delete database table rcp_players_cache (only remoteCP[Live])
 - delete database table rcp_ladder_(serverid) (only remoteCP[Live])

------------------------------
 * Update from Version 4.0.3.4

 - delete database table rcp_players_cache (only remoteCP[Live])
 - File: /xml/groups.xml
   Add inside every <permissions></permissions>: <offlinelogin>false</offlinelogin>
 - File: /xml/servers.xml
   Add inside every <connection></connection>: <communitycode></communitycode>
   Replace inside <connection></connection>: <ip></ip> by <host></host>

------------------------------
 * Update from Version 4.0.3.3

 - remoteCP[Live] startup has changed!
   rcplive username and password are obsolete, remove this values from your .bat/.sh file
   (the default rcplive useraccont in your admins.xml is still required, don't remove it)
 - Database: remove/delete table rcp_ladder if available
 - File: /xml/settings/<settingset>/settings.xml
   Remove: <register></register>, <deflanguage></deflanguage>, <defstyle></defstyle>

------------------------------
 * Update from Version 4.0.3.2

 - execute /update_4032.php
 - File: /xml/admins.xml
   Remove <group></group>
 - File: /xml/admins.xml
   Change all <id></id> inside <servers></servers> to the following format: <server id='1' group='1' />
 - File: /xml/settings/default/live.xml
   Remove inside <settings><settings>: <interfacestyle></interfacestyle>
 - File: /xml/servers.xml
   Remove inside <connection></connection>: <account></account>
   Remove inside <sql></sql>: <type></type>, <host></host> and <dbname></dbname>
   Add inside <sql></sql>: <dsn>mysql:dbname=remotecp;host=localhost</dsn>
   See also http://de.php.net/manual/de/ref.pdo-mysql.connection.php for mysql-DSN configuration

------------------------------
 * Update from Version 4.0.3.1

 - no config file changes

------------------------------
 * Update from Version 4.0.3.0

 - no config file changes

------------------------------
 * Update from Version 4.0.2.7

 - File: /xml/settings/default/live.xml
   Add inside <settings><settings>: <interfacestyle>1</interfacestyle>
 - File: /xml/settings/default/live.xml
   Add inside <settings><settings>: <usesu>true</usesu>
 - File: /xml/admins.xml
   Remove all <permissions></permissions> parts
 - File: /xml/admins.xml
   Add <group>1</group> for every admin
 - File: /xml/admins.xml
   Remove <serverids></serverids> parts
 - File: /xml/admins.xml
   Add <servers><id>1</id><id>2</id>...</servers> for every admin
 - File: /xml/servers.xml
   Add for every server: <lists><guestlist>guestlist.txt</guestlist><blacklist>blacklist.txt</blacklist></lists>
 - File: /xml/servers.xml
   Change all <serverid></serverid> to <id></id>
 - File: /xml/servers.xml
   Add inside every <sql></sql>: <type>mysql</type>

------------------------------
 * Update from Version 4.0.2.6

 - File: /xml/servers.xml
   Add inside every <server></server>: <login></login>
   The value from login should be the login account name of the dedicated server

------------------------------
 * Update from Version 4.0.2.5

 - no config file changes

------------------------------
 * Update from Version 4.0.2.4

 - no config file changes

------------------------------
 * Update from Version 4.0.2.3

 - no config file changes

------------------------------
 * Update from Version 4.0.2.2

 - no config file changes

------------------------------
 * Update from Version 4.0.2.1

 - no config file changes

------------------------------
 * Update from Version 4.0.2.0

 - File: /xml/settings/default/live.xml
   Do not update this file, use the new one from version 4.0.2.2, because there are heavy changes

 - File: /xml/settings/default/settings.xml
   Add inside <settings></settings>: <register>false</register>

 - File: /xml/admins.xml
   Add for all admin: <active>true</active>
   Without that, you will get a "account inactive" message @ login

------------------------------
 * Update from Version 4.0.1.3 or earlier

 - File: /xml/servers.xml
   Remove from all servers: <filemode></filemode>

 - File: /xml/servers.xml
   Change for all servers: <ftp> to <ftp enabled='true/false'>

 - File: /xml/servers.xml
   Change for all servers: <sql> to <sql enabled='true/false'>

 - File: /xml/servers.xml
   Add inside <ftp></ftp>: <port></port>

 - File: /xml/servers.xml
   Add inside <server></server>: <settingset></settingset>

 - File: /xml/servers.xml
   Add before <ip></ip>: <connection>

 - File: /xml/servers.xml
   Add after <password></password>: </connection>