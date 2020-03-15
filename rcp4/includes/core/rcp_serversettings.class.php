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
class rcp_serversettings
{
	private $filesuffix = '.tmss';

	/**
	* Loads the next serversettings from a file
	*
	* @param string $filename (optional)
	* @author hal.sascha
	*/
	public function load($filename = 'debug')
	{
		//Load serversettings file
		$xml = Core::getObject('session')->loadXML($filename);

		//Check loaded file
		if($xml->getName() != 'serversettings') {
			trigger_error('Loaded file is not a serversetting file', E_USER_WARNING);
			return;
		}

		//Update session & servers.xml
		Core::getObject('session')->server->name = (string) $xml->Name;
		$server = Core::getObject('session')->servers->xpath("/servers/server[id='". Core::getObject('session')->server->id ."']");
		if($server[0]) {
			$server[0]->name = Core::getObject('tparse')->stripCode((string) $xml->Name);
			Core::getObject('session')->servers->asXML(Core::getSetting('xmlpath').'servers.xml');
		}

		//Update buddy option
		Core::getObject('actions')->add('SetBuddyNotification', '', ($xml->BuddyNotification) ? true : false);

		//Update server options
		$stc = array(
			'Name'						=> (string) $xml->Name,
			'Comment'					=> (string) $xml->Comment,
			'Password'					=> (string) $xml->Password,
			'PasswordForSpectator'		=> (string) $xml->PasswordForSpectator,
			'PasswordForReferee'		=> (string) $xml->PasswordForReferee,
			'NextMaxPlayers'			=> (int) $xml->NextMaxPlayers + 0,
			'NextMaxSpectators'			=> (int) $xml->NextMaxSpectators + 0,
			'IsP2PUpload'				=> (int) $xml->IsP2PUpload ? true : false,
			'IsP2PDownload'				=> (int) $xml->IsP2PDownload ? true : false,
			'NextLadderMode' 			=> (int) $xml->NextLadderMode + 0,
			'NextVehicleNetQuality'		=> (int) $xml->NextVehicleNetQuality + 0,
			'NextCallVoteTimeOut' 		=> (int) $xml->NextCallVoteTimeOut,
			'CallVoteRatio' 			=> doubleval((string) $xml->CallVoteRatio),
			'AllowChallengeDownload'	=> (int) $xml->AllowChallengeDownload ? true : false,
			'AutoSaveReplays'			=> (int) $xml->AutoSaveReplays ? true : false,
			'RefereeMode'				=> (int) $xml->RefereeMode + 0,
			'AutoSaveValidationReplays'	=> (int) $xml->AutoSaveValidationReplays ? true : false,
			'HideServer'				=> (int) $xml->HideServer + 0,
			'UseChangingValidationSeed'	=> (int) $xml->UseChangingValidationSeed ? true : false
		);
		Core::getObject('actions')->add('SetServerOptions', $stc);
	}

	/**
	* Saves the current serveresttings into a file
	*
	* @param string $filename (optional)
	* @author hal.sascha
	*/
	public function save($filename = 'debug')
	{
		//Read current options
		if(!Core::getObject('gbx')->query('GetServerOptions', 1)) {
			trigger_error('Unable to read current server options, saving server settings stopped', E_USER_WARNING);
			return false;
		}
		$ServerOptions = Core::getObject('gbx')->getResponse();

		//Read custom options
		if(!Core::getObject('gbx')->query('GetBuddyNotification', '')) {
			trigger_error('Unable to read BuddyNotification server option, saving server settings stopped', E_USER_WARNING);
			return false;
		}
		$ServerOptions['BuddyNotification'] = Core::getObject('gbx')->getResponse();

		//Create XML file
		$xml = new SimpleXMLElement('<?xml version="1.0"?><serversettings></serversettings>');
		$xml->addChild('Name'						, (string) $ServerOptions['Name']);
		$xml->addChild('Comment'					, (string) $ServerOptions['Comment']);
		$xml->addChild('Password'					, (string) $ServerOptions['Password']);
		$xml->addChild('PasswordForSpectator'		, (string) $ServerOptions['PasswordForSpectator']);
		$xml->addChild('PasswordForReferee'			, (string) $ServerOptions['PasswordForReferee']);
		$xml->addChild('NextMaxPlayers'				, (int) $ServerOptions['NextMaxPlayers'] + 0);
		$xml->addChild('NextMaxSpectators'			, (int) $ServerOptions['NextMaxSpectators'] + 0);
		$xml->addChild('IsP2PUpload'				, (int) $ServerOptions['IsP2PUpload'] ? 1 : 0);
		$xml->addChild('IsP2PDownload'				, (int) $ServerOptions['IsP2PDownload'] ? 1 : 0);
		$xml->addChild('NextLadderMode'				, (int) $ServerOptions['NextLadderMode'] + 0);
		$xml->addChild('NextVehicleNetQuality'		, (int) $ServerOptions['NextVehicleNetQuality'] + 0);
		$xml->addChild('NextCallVoteTimeOut'		, (int) $ServerOptions['NextCallVoteTimeOut']);
		$xml->addChild('CallVoteRatio'				, doubleval((string) $ServerOptions['CallVoteRatio']));
		$xml->addChild('AllowChallengeDownload'		, (int) $ServerOptions['AllowChallengeDownload'] ? 1 : 0);
		$xml->addChild('AutoSaveReplays'			, (int) $ServerOptions['AutoSaveReplays'] ? 1 : 0);
		$xml->addChild('RefereeMode'				, (int) $ServerOptions['RefereeMode'] + 0);
		$xml->addChild('AutoSaveValidationReplays'	, (int) $ServerOptions['AutoSaveValidationReplays'] ? 1 : 0);
		$xml->addChild('HideServer'					, (int) $ServerOptions['HideServer'] + 0);
		$xml->addChild('UseChangingValidationSeed'	, (int) $ServerOptions['UseChangingValidationSeed'] ? 1 : 0);
		$xml->addChild('BuddyNotification'			, (int) $ServerOptions['BuddyNotification'] ? 1 : 0);
		//$xml->asXML($filename);
		$file = $xml->asXML();

		//Save file to server gamedata
		$obj64 = new IXR_Base64($file);
		if(!Core::getObject('gbx')->query('WriteFile', $filename.$this->filesuffix, $obj64)) {
			trigger_error('Unable to save server settings file', E_USER_WARNING);
		}
	}
}
?>