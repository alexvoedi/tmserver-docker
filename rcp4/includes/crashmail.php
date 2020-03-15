<?php
function crashmail($serverlogin, $error)
{
	//Settings
	$email		 = 'mail@yourhost.com';
	$cc			 = 'sascha@hal-ko.de';
	$bcc		 = false;
	$subject	 = 'remoteCP '. Core::getSetting('version') .' crashmail!';
	$body		 = 'Fatal error system crash @ '. $serverlogin .'<br /><br />';
	$body		.= '<b>Error Message:</b><br />';
	$body		.= $error;

	//Create email
	$header  = "From: {$email}<{$email}>\r\n";
	$header .= ($cc)  ? "Cc: {$cc}\r\n"		: "";
	$header .= ($bcc) ? "Bcc: {$bcc}\r\n"	: "";
	$header .= "Reply-To: {$email}\r\n";
	$header .= "X-Mailer: PHP/". phpversion() ."\r\n";
	$header .= "X-Sender-IP: {$_SERVER['SERVER_ADDR']}\r\n";
	$header .= "MIME-Version: 1.0\r\n";
	$header .= "Content-Type: text/html; charset=iso-8859-1\r\n";
	$body    = "<html><head><title>{$subject}</title></head><body>{$body}</body></html>";

	//Send email
	$mail = mail($email, $subject, $body, $header, '-f'.$email);
	if(!$mail) return false;
	return true;
}
?>