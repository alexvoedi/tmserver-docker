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
class Lists extends rcp_plugin
{
	public  $display		= 'quick';
	public  $title			= 'Lists';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservstatus	= array(3,4,5);
	public  $vpermissions	= array('viewlists');
	public  $apermissions	= array(
		'Ban'				=> 'editplayers',
		'Ignore'			=> 'editplayers',
		'UnIgnore'			=> 'editplayers',
		'UnBan'				=> 'editplayers',
		'AddGuest'			=> 'editlists',
		'AddGuestM'			=> 'editlists',
		'RemoveGuest'		=> 'editlists',
		'BlackList'			=> 'editlists',
		'BlackListM'		=> 'editlists',
		'UnBlackList'		=> 'editlists',
		'CleanBanList'		=> 'editlists',
		'CleanBlackList'	=> 'editlists',
		'CleanIgnoreList'	=> 'editlists',
		'CleanGuestList'	=> 'editlists'
	);
	private $op				= false;
	private $list			= array();

	public function onLoad()
	{
		$this->op = array_key_exists('op', $_REQUEST) ? $_REQUEST['op'] : false;
		$this->addQuickOption('ajax.php?plugin=Lists&op=Ban', '400;0', 'modal', 'banlist', ct_banlist);
		$this->addQuickOption('ajax.php?plugin=Lists&op=Ignore', '400;0', 'modal', 'ignorelist', ct_ignorelist);
		$this->addQuickOption('ajax.php?plugin=Lists&op=Guest', '400;0', 'modal', 'guestlist', ct_guestlist);
		$this->addQuickOption('ajax.php?plugin=Lists&op=Black', '400;0', 'modal', 'blacklist', ct_blacklist);
	}

	public function onExec()
	{
		$this->readlist();
	}

	public function onOutput()
	{
		if(Core::getObject('session')->checkPerm('editlists')) {
			echo "<form action='ajax.php' method='post' name='listslist' id='listslist' class='postcmd' rel='{$this->display}area'>\r\n";
			switch($this->op) {
				case 'Guest':
					echo "<fieldset>";
					echo "<div class='legend'>".pt_playerguest."</div>\r\n";
					echo "<div class='f-row'>\r\n";
					echo "	<label for='guestuser'>".pt_login."</label>\r\n";
					echo "	<div class='f-field'><input type='hidden' name='action' value='AddGuest' /><input type='text' name='guestuser' value='' /></div>\r\n";
					echo "</div>\r\n";
					echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
					echo "</fieldset>\r\n";
				break;

				case 'Black':
					echo "<fieldset>";
					echo "<div class='legend'>".pt_playerblack."</div>\r\n";
					echo "<div class='f-row'>\r\n";
					echo "	<label for='blacklistuser'>".pt_login."</label>\r\n";
					echo "	<div class='f-field'><input type='hidden' name='action' value='BlackList' /><input type='text' name='blacklistuser' value='' /></div>\r\n";
					echo "</div>\r\n";
					echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
					echo "</fieldset>\r\n";
				break;
			}
			echo "<input type='hidden' name='op' value='{$this->op}' />\r\n";
			echo "<input type='hidden' name='plugin' value='{$this->id}' />\r\n";
			echo "</form>\r\n";

			echo "<form action='ajax.php' method='post' name='lists' id='lists' class='postcmd' rel='{$this->display}area'>\r\n";
		}

		switch($this->op) {
			case 'Ban':
				echo "<fieldset>";
				echo "<div class='legend'>".ct_banlist." ";
				if(Core::getObject('session')->checkPerm('editlists'))
					echo "<a href='ajax.php?plugin={$this->id}&action=CleanBanList' rel='{$this->display}area' class='getcmdc'><img src='".Core::getSetting('style')."/icons/del.gif' title='".ct_delete."' alt='".ct_delete."' /></a>";
				echo "</div>\r\n";
			break;

			case 'Ignore':
				echo "<fieldset>";
				echo "<div class='legend'>".ct_ignorelist." ";
				if(Core::getObject('session')->checkPerm('editlists'))
					echo "<a href='ajax.php?plugin={$this->id}&action=CleanIgnoreList' rel='{$this->display}area' class='getcmdc'><img src='".Core::getSetting('style')."/icons/del.gif' title='".ct_delete."' alt='".ct_delete."' /></a>";
				echo "</div>\r\n";
			break;

			case 'Guest':
				echo "<fieldset>";
				echo "<div class='legend'>".ct_guestlist." ";
				if(Core::getObject('session')->checkPerm('editlists'))
					echo "<a href='ajax.php?plugin={$this->id}&action=CleanGuestList' rel='{$this->display}area' class='getcmdc'><img src='".Core::getSetting('style')."/icons/del.gif' title='".ct_delete."' alt='".ct_delete."' /></a>"; 
				echo "</div>\r\n";
			break;

			case 'Black':
				echo "<fieldset>";
				echo "<div class='legend'>".ct_blacklist." ";
				if(Core::getObject('session')->checkPerm('editlists'))
					echo "<a href='ajax.php?plugin={$this->id}&action=CleanBlackList' rel='{$this->display}area' class='getcmdc'><img src='".Core::getSetting('style')."/icons/del.gif' title='".ct_delete."' alt='".ct_delete."' /></a>";
				echo "</div>\r\n";
			break;
		}

		echo "<div class='bg-t'>\r\n";
		echo "<table>\r\n";
		echo "<colgroup>\r\n";
		if(Core::getObject('session')->checkPerm('editlists')) {
			echo "	<col width='10%' />\r\n";
			echo "	<col width='90%' />\r\n";
		} else {
			echo "	<col width='100%' />\r\n";
		}
		echo "</colgroup>\r\n";
		echo "<thead>\r\n";
		echo "	<tr>\r\n";
		if(Core::getObject('session')->checkPerm('editlists'))
			echo "	<th><input type='checkbox' name='listslist[0]' value='' class='checkall checkbox' /></th>\r\n";
		echo "		<th>Player</th>\r\n";
		echo "	</tr>\r\n";
		echo "</thead>\r\n";
		echo "<tbody>\r\n";

		switch($this->op) {
			case 'Ban':
				$i = 0;
				while(true)
				{
					Core::getObject('gbx')->suppressNextError();
					if(!Core::getObject('gbx')->query('GetBanList', 50, $i)) break;
					$i = $i + 50;
					$BanList = Core::getObject('gbx')->getResponse();
					if(empty($BanList)) break;
					foreach($BanList AS $value) {
						echo "<tr>\r\n";
						if(Core::getObject('session')->checkPerm('editlists'))
							echo "<td class='ic'><input type='checkbox' name='listslist[]' value='". urlencode($value['Login']) ."' class='selrow checkbox' /></td>\r\n";
						echo "<td><span title='{$value['IPAddress']}'>{$value['Login']} / {$value['ClientName']}</span></td>\r\n";
						echo "</tr>\r\n";
					}
				}
			break;

			case 'Ignore':
				$i = 0;
				while(true)
				{
					Core::getObject('gbx')->suppressNextError();
					if(!Core::getObject('gbx')->query('GetIgnoreList', 50, $i)) break;
					$i = $i + 50;
					$IgnoreList = Core::getObject('gbx')->getResponse();
					if(empty($IgnoreList)) break;
					foreach($IgnoreList AS $value)
					{
						echo "<tr>\r\n";
						if(Core::getObject('session')->checkPerm('editlists'))
							echo "<td class='ic'><input type='checkbox' name='listslist[]' value='". urlencode($value['Login']) ."' class='selrow checkbox' /></td>\r\n";
						echo "<td><span title='{$value['IPAddress']}'>{$value['Login']}</span></td>\r\n";
						echo "</tr>\r\n";
					}
				}
			break;

			case 'Guest':
				$i = 0;
				while(true)
				{
					Core::getObject('gbx')->suppressNextError();
					if(!Core::getObject('gbx')->query('GetGuestList', 50, $i)) break;
					$i = $i + 50;
					$GuestList = Core::getObject('gbx')->getResponse();
					if(empty($GuestList)) break;
					foreach($GuestList AS $value)
					{
						echo "<tr>\r\n";
						if(Core::getObject('session')->checkPerm('editlists'))
							echo "<td class='ic'><input type='checkbox' name='listslist[]' value='". urlencode($value['Login']) ."' class='selrow checkbox' /></td>\r\n";
						echo "<td>{$value['Login']}</td>\r\n";
						echo "</tr>\r\n";
					}
				}
			break;

			case 'Black':
				$i = 0;
				while(true)
				{
					Core::getObject('gbx')->suppressNextError();
					if(!Core::getObject('gbx')->query('GetBlackList', 50, $i)) break;
					$i = $i + 50;
					$BlackList = Core::getObject('gbx')->getResponse();
					if(empty($BlackList)) break;
					foreach($BlackList AS $value)
					{
						echo "<tr>\r\n";
						if(Core::getObject('session')->checkPerm('editlists'))
							echo "<td class='ic'><input type='checkbox' name='listslist[]' value='". urlencode($value['Login']) ."' class='selrow checkbox' /></td>\r\n";
						echo "<td>{$value['Login']}</td>\r\n";
						echo "</tr>\r\n";
					}
				}
			break;
		}

		echo "</tbody>\r\n";
		echo "</table>\r\n";
		echo "</div>\r\n";
		echo "</fieldset>\r\n";

		if(Core::getObject('session')->checkPerm('editlists')) {
			echo "<div class='bg-t'>\r\n";
			echo "<table>\r\n";
			echo "<tbody>\r\n";
			echo "<tr>\r\n";
			echo "	<td>\r\n";
			echo "	  <select name='action'>\r\n";

			switch($this->op) {
				case 'Ban':
					echo "<option value='UnBan'>".pt_rem."</option>\r\n";
					echo "<option value='BlackListM'>".pt_addblack."</option>\r\n";
					echo "<option value='AddGuestM'>".pt_addguest."</option>\r\n";
				break;

				case 'Ignore':
					echo "<option value='UnIgnore'>".pt_rem."</option>\r\n";
					echo "<option value='BlackListM'>".pt_addblack."</option>\r\n";
					echo "<option value='AddGuestM'>".pt_addguest."</option>\r\n";
				break;

				case 'Guest':
					echo "<option value='RemoveGuest'>".pt_rem."</option>\r\n";
					echo "<option value='BlackListM'>".pt_addblack."</option>\r\n";
				break;

				case 'Black':
					echo "<option value='UnBlackList'>".pt_rem."</option>\r\n";
					echo "<option value='AddGuestM'>".pt_addguest."</option>\r\n";
				break;
			}

			echo "	  </select>\r\n";
			echo "	<input type='hidden' name='op' value='{$this->op}' />\r\n";
			echo "	<input type='hidden' name='plugin' value='{$this->id}' />\r\n";
			echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>\r\n";
			echo "	</td>\r\n";
			echo "</tr>\r\n";
			echo "</tbody>\r\n";
			echo "</table>\r\n";
			echo "</div>\r\n";
			echo "</form>";
		}
	}

	private function readlist()
	{
		if(array_key_exists('listslist', $_POST))
			$players = array_values($_POST['listslist']);

		if (!empty($players)) {
			foreach($players as $value)
			{
				$this->list[] = urldecode($value);
			}
		}
	}

	public function Ban()
	{
		Core::getObject('actions')->add('Ban', urldecode($_REQUEST['banuser']));
	}

	public function Ignore()
	{
		Core::getObject('actions')->add('Ignore', urldecode($_REQUEST['ignoreuser']));
	}

	public function AddGuest()
	{
		Core::getObject('actions')->add('AddGuest', urldecode($_REQUEST['guestuser']));
		Core::getObject('actions')->add('SaveGuestList', (string) Core::getObject('session')->server->lists->guestlist);
	}

	public function AddGuestM()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('AddGuest', $value);
		}
		Core::getObject('actions')->add('SaveGuestList', (string) Core::getObject('session')->server->lists->guestlist);
	}

	public function BlackList()
	{
		Core::getObject('actions')->add('BlackList', urldecode($_REQUEST['blacklistuser']));
		Core::getObject('actions')->add('SaveBlackList', (string) Core::getObject('session')->server->lists->blacklist);
	}

	public function BlackListM()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('BlackList', $value);
		}
		Core::getObject('actions')->add('SaveBlackList', (string) Core::getObject('session')->server->lists->blacklist);
	}

	public function UnIgnore()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('UnIgnore', $value);
		}
	}

	public function UnBan()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('UnBan', $value);
		}
	}

	public function RemoveGuest()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('RemoveGuest', $value);
		}
		Core::getObject('actions')->add('SaveGuestList', (string) Core::getObject('session')->server->lists->guestlist);
	}

	public function UnBlackList()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('UnBlackList', $value);
		}
		Core::getObject('actions')->add('SaveBlackList', (string) Core::getObject('session')->server->lists->blacklist);
	}

	public function CleanBanList()
	{
		Core::getObject('actions')->add('CleanBanList');
	}

	public function CleanBlackList()
	{
		Core::getObject('actions')->add('CleanBlackList');
	}

	public function CleanGuestList()
	{
		Core::getObject('actions')->add('CleanGuestList');
	}

	public function CleanIgnoreList()
	{
		Core::getObject('actions')->add('CleanIgnoreList');
	}
}
?>