/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
function ChatSubmitCallback() {
	$('ChatText').set('value', '');
}

(function(){
	if($defined($('chatcontent'))) {
		rcp_Actions.PeriodicalUpdate('ajax.php?plugin=Chat&op=load', 'chatupd', 'chatcontent', false);
	}
}).periodical(5000);