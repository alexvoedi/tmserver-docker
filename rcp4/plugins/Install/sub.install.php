<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/

//Secure file against direct call
$forceError = Core::getObject('tparse')->toHTML('muhahaha');

//Some functions...
function importSQLFilesFromPath($type, $dirseperator = '/')
{
	$path = './'.$type.'/';
	if(!is_dir($path)) return;

	//Read directory
	$dir = opendir($path);
	while($element = readdir($dir))
	{
		if(is_dir($path.$element) && $element != '.' && $element != '..') {
			//Check if plugin has sql file
			$filepath = $path.$element.$dirseperator.'mysql_'.$element.'.sql';
			if(!is_file($filepath)) continue;
			Core::getObject('messages')->add('Imported SQL File: '. $filepath);
			Core::getObject('db')->fileImport($element, $type);
		}
	}
	closedir($dir);
}

//sub.install
$completed = false;
if(!empty($_POST)) {
	$valid				= true;
	$database_dsn		= isset($_POST['database_dsn'])			? $_POST['database_dsn']		: '';
	$database_username	= isset($_POST['database_username'])	? $_POST['database_username']	: '';
	$database_password	= isset($_POST['database_password'])	? $_POST['database_password']	: '';
	$tm_login			= isset($_POST['tm_login'])				? $_POST['tm_login']			: '';
	$tm_servername		= isset($_POST['tm_servername'])		? $_POST['tm_servername']		: '';
	$tm_port			= isset($_POST['tm_port'])				? $_POST['tm_port']				: '';
	$communitycode		= isset($_POST['communitycode'])		? $_POST['communitycode']		: '';
	$superadmin_password= isset($_POST['superadmin_password'])	? $_POST['superadmin_password']	: '';

	// DB form check
	if($_REQUEST['db']) {
		if(empty($database_dsn)) {
			Core::getObject('messages')->add('Database connection \'dsn\' can not be empty! Please re-enter.');
			$valid = false;
		}
		if(empty($database_username)) {
			Core::getObject('messages')->add('Database username can not be empty! Please re-enter.');
			$valid = false;
		}

		//Check if connection is valid
		if($valid) {
			//check here...
			$connection = Core::getObject('db')->newConnection(
				'install',
				$database_dsn,
				$database_username,
				$database_password
			);
		}
	}

	// Server form check
	if(empty($tm_login)) {
		Core::getObject('messages')->add('Login can not be empty! Please re-enter.');
		$valid = false;
	}
	if(empty($tm_servername)) {
		Core::getObject('messages')->add('Servername can not be empty! Please re-enter.');
		$valid = false;
	}
	if(empty($tm_port)) {
		Core::getObject('messages')->add('Port can not be empty! Please re-enter.');
		$valid = false;
	}
	if(empty($superadmin_password)) {
		Core::getObject('messages')->add('SuperAdmin Password can not be empty! Please re-enter.');
		$valid = false;
	}
	if(empty($communitycode)) {
		Core::getObject('messages')->add('Communitycode can not be empty! Please re-enter.');
		$valid = false;
	}

	//Save configuration
	if($valid) {
		//Load / write config file
		$config_file = file_get_contents('./plugins/Install/ressources/servers.db'.$_REQUEST['db'].'.txt');
		$config_file = str_replace('_DB_DSN_', $database_dsn, $config_file);
		$config_file = str_replace('_DB_USER_', $database_username, $config_file);
		$config_file = str_replace('_DB_PASSWORD_', $database_password, $config_file);
		$config_file = str_replace('_TM_LOGIN_', $tm_login, $config_file);
		$config_file = str_replace('_TM_SNAME_', $tm_servername, $config_file);
		$config_file = str_replace('_TM_PORT_', $tm_port, $config_file);
		$config_file = str_replace('_TM_SADMINPW_', $superadmin_password, $config_file);
		$config_file = str_replace('_TM_CCODE_', $communitycode, $config_file);
		$file = @fopen('./xml/servers.xml', 'w+');
		if(@fwrite($file, $config_file) > 0) {
			//Chmod
			@chmod('./xml/servers.xml', 0771);
			$completed = true;

			//Write database
			if($_REQUEST['db']) {
				importSQLFilesFromPath('plugins');
				importSQLFilesFromPath('live');
			}
			@fclose($file);

			//Write installed dummy-file
			$file = @fopen('./cache/installed', 'w+');
			if(@fwrite($file, 'installed') > 0) {
				@fclose($file);
			}
		} else {
			Core::getObject('messages')->add('Can not generate configuration file. Please chmod 771 xml-folder.');
		}
	}
}

// Output
if($completed) {
?>
	<div>
		<h3>Step 2. Installation Completed</h3>
	</div>
	{dumpmsgs}

	<br />

	<div>
		<div>The /xml/servers.xml file was sucessfully created.</div>
		<div style='color:#bb5500;'>!!! For security reasons, please remove install.php folder from your server.</div>
		<br />
		<input type="button" class="installbutton" name="btn_cancel" value="Proceed to login page" onclick="document.location.href='index.php'" />
	</div>
<?php
} else {
?>
	<div>
		<h3>Step 1. Settings</h3>
	</div>
	{dumpmsgs}

	<br />

	<form method="post" action="index.php">
	<input type="hidden" name="page" value="install" />
	<input type="hidden" name="sub" value="install" />
	<input type="hidden" name="db" value="<?php echo $_REQUEST['db']; ?>" />
	<?php
	// DB Check start
	if($_REQUEST['db']) {
	?>
	<fieldset>
	<div class='legend'>Database</div>
	<div class='f-row'>
		<label>Database DSN (<a href="http://de.php.net/manual/de/ref.pdo-mysql.connection.php" target="_blank">help</a>)</label>
		<div class='f-field'><input type="text" name="database_dsn" value='mysql:dbname=remotecp;host=localhost' size="30" /></div>
	</div>
	<div class='f-row'>
		<label>Database Username</label>
		<div class='f-field'><input type="text" name="database_username" size="30" value="<?php echo $database_username; ?>" /></div>
	</div>
	<div class='f-row'>
		<label>Database Password</label>
		<div class='f-field'><input type="text" name="database_password" size="30" value="<?php echo $database_password; ?>" /></div>
	</div>
	</fieldset>
	<?php
	// DB Check end
	}
	?>
	<fieldset>
	<div class='legend'>Server</div>
	<div class='f-row'>
		<label>Serverlogin</label>
		<div class='f-field'><input type="text" name="tm_login" size="30" value="<?php echo $tm_login; ?>" /></div>
	</div>
	<div class='f-row'>
		<label>TM-Servername</label>
		<div class='f-field'><input type="text" name="tm_servername" size="30" value="<?php echo $tm_servername; ?>" /></div>
	</div>
	<div class='f-row'>
		<label>XMLRPC-Port</label>
		<div class='f-field'><input type="text" name="tm_port" size="30" value="<?php echo $tm_port; ?>" /></div>
	</div>
	<div class='f-row'>
		<label>SuperAdmin Password</label>
		<div class='f-field'><input type="text" name="superadmin_password" size="30" value="<?php echo $superadmin_password; ?>" /></div>
	</div>
	<div class='f-row'>
		<label>Communitycode</label>
		<div class='f-field'><input type="text" name="communitycode" size="30" value="<?php echo $communitycode; ?>" /></div>
	</div>
	</fieldset>
	<input type="button" class="installbutton" name="btn_cancel" value="Cancel" onclick="document.location.href='index.php'" /> <input type="submit" class="installbutton" name="btn_submit" value="Continue" />
	</form>
<?php
}
?>