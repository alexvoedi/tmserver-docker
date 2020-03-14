<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Builds a chat message starting with the player's nickname.
 * Updated by Xymph
 *
 * Dependencies: none
 */

Aseco::addChatCommand('usbtmfserver', 'Can be used to express emotions');

function chat_usbtmfserver($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/me');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	// replace parameters
	$message = formatText('$ff0To find out more about usbTMFserver, please visit the $l[http://lille79.supersheep.no/]$fffusbTMFserver homepage$l!',
	                      $player->nickname, $command['params']);
	// show chat message
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
}  // chat_usbtmfserver
