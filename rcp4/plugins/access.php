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
$username = array_key_exists('username', $_REQUEST) ? $_REQUEST['username']  : '';
if(array_key_exists('logout', $_REQUEST)) {
	Core::getObject('messages')->add(ct_accesslogout);
} elseif(array_key_exists('changeserver', $_REQUEST)) {
	Core::getObject('messages')->add(ct_accesschanged);
	redirect('index.php');
} elseif(array_key_exists('refused', $_REQUEST)) {
	trigger_error(ct_accessrefused);
	if(array_key_exists('usr', $_REQUEST)) trigger_error(ct_accessrefusedusr);
	if(array_key_exists('psw', $_REQUEST)) trigger_error(ct_accessrefusedpsw);
} elseif(!file_exists('./cache/installed')) {
	redirect('index.php?page=install');
}

?>
<div style='margin-top:20px; width:100%; height:100px; background:url(<?php echo Core::getSetting('style'); ?>/logo.jpg) left center no-repeat;'></div>
{dumpmsgs}
<div class='boxtt'>
	<div class='boxbb'>
		<div class='boxll'>
			<div class='boxrr'>
				<div class='boxtr'>
					<div class='boxtl'>
						<div class='boxbr'>
							<div class='boxbl'>
								<div class='boxh'>ACCESS</div>
								<div class='boxc'>
									<form action='index.php' method='post'>
									<fieldset>
										<div class='legend'>
										<?php
										echo ct_accesslogin;
										if(Core::getSetting('register')) echo " | <a href='index.php?page=register' style='float:none'>Register new account</a>";
										?>
										</div>
										<div class='f-row'>
											<label for='username'><?php echo ct_accessusername; ?></label>
											<div class='f-field'><input type='text' name='username' id='username' value='<?php echo $username; ?>' tabindex='1' /></div>
										</div>

										<div class='f-row'>
											<label for='password'><?php echo ct_accesspassword; ?></label>
											<div class='f-field'><input type='password' name='password' id='password' value='' tabindex='2' /></div>
										</div>

										<div class='f-row'>
											<label for='serverid'><?php echo ct_accessserver; ?></label>
											<div class='f-field'>
												<select name='serverid' id='serverid' tabindex='3'>
													<?php
													if(Core::getObject('session')->servers) {
														foreach(Core::getObject('session')->servers->children() as $node)
														{
															echo "<option value='". (string) $node->id ."'> ". (string) $node->name ." (". (string) $node->connection->host ." - ". (string) $node->connection->port .")</option>";
														}
													} else {
														echo "<option> ".ct_accessnoserver." </option>";
													}
													?>
												</select>
											</div>
										</div>
									</fieldset>
									<fieldset><button class='wide' type='submit' title='<?php echo ct_submit; ?>' tabindex='5'><?php echo ct_submit; ?></button></fieldset>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>