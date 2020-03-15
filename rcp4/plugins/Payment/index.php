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
class Payment extends rcp_plugin
{
	public  $display		= 'side';
	public  $title			= 'Payment';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservstatus	= array(2,3,4,5);
	public  $vpermissions	= array('payment');
	public  $apermissions	= array(
		'pay'	=> 'payment',
		'bill'	=> 'payment'
	);
   
	public function onOutput()
	{
		if(Core::getObject('gbx')->query('GetServerCoppers')) {
			$Coppers = Core::getObject('gbx')->getResponse();

			$i = 0;
			$PlayerList = array();
			while(true)
			{
				Core::getObject('gbx')->suppressNextError();
				if(!Core::getObject('gbx')->query('GetPlayerList', 50, $i, 1)) break;
				$i = $i + 50;
				$Players = Core::getObject('gbx')->getResponse();
				if(empty($Players)) break;
				$PlayerList = array_merge($PlayerList, $Players);
			}

			echo "<form action='ajax.php' method='post' name='Payment' id='Payment' rel='{$this->display}area' class='postcmd'>";
			echo "<fieldset>";
			echo "<div class='legend'>".pt_pay."</div>";
			echo "  <div class='f-row'>
					<label>".pt_acoppers."</label>
					<div class='f-field'>{$Coppers}</div>
				</div>";
			echo "  <div class='f-row'>";
			echo "		<label for='payplayer'>".pt_player."</label>";
			echo "		<div class='f-field'>";
			echo "			<select name='payplayer' class='f-dw'>";
			echo "				<option value=''>".pt_sel."</option>";
			foreach($PlayerList AS $value)
			{
			echo "				<option value='{$value['Login']}'>". Core::getObject('tparse')->stripCode($value['NickName']) ."</option>";
			}
			echo "			</select>";
			echo "		</div>";
			echo "	</div>";
			echo "  <div class='f-row'>
					<label for='paycoppers'>".pt_coppers."</label>
					<div class='f-field'><input type='text' name='paycoppers' id='paycoppers' /></div>
				</div>";
			echo "  <div class='f-row'>
					<label for='payinfo'>".pt_info."</label>
					<div class='f-field'><input type='text' name='payinfo' id='payinfo' /></div>
				</div>";
			echo "<input type='hidden' name='plugin' value='{$this->id}' />";
			echo "<input type='hidden' name='action' value='pay' />";
			echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
			echo "</fieldset>";
			echo "</form>";

			echo "<form action='ajax.php' method='post' name='Payment2' id='Payment2' rel='{$this->display}area' class='postcmd'>";
			echo "<fieldset>";
			echo "<div class='legend'>".pt_bill."</div>";
			echo "  <div class='f-row'>";
			echo "		<label for='receivingplayer'>".pt_rplayer."</label>";
			echo "		<div class='f-field'>";
			echo "			<select name='receiveingplayer' class='f-dw'>";
			echo " 				<option value=''>".pt_serverac."</option>";
			foreach($PlayerList AS $value)
			{
			echo "				<option value='{$value['Login']}'>". Core::getObject('tparse')->stripCode($value['NickName']) ."</option>";
			}
			echo "			</select>";
			echo "		</div>";
			echo "	</div>";
			echo "  <div class='f-row'>";
			echo "		<label for='payplayer'>".pt_pplayer."</label>";
			echo "		<div class='f-field'>";
			echo "			<select name='payplayer' class='f-dw'>";
			echo "				<option value=''>".pt_sel."</option>";
			foreach($PlayerList AS $value)
			{
			echo "				<option value='{$value['Login']}'>". Core::getObject('tparse')->stripCode($value['NickName']) ."</option>";
			}
			echo "			</select>";
			echo "		</div>";
			echo "	</div>";
			echo "  <div class='f-row'>
					<label for='paycoppers'>".pt_coppers."</label>
					<div class='f-field'><input type='text' name='paycoppers' id='paycoppers' /></div>
				</div>";
			echo "  <div class='f-row'>
					<label for='payinfo'>".pt_info."</label>
					<div class='f-field'><input type='text' name='payinfo' id='payinfo' /></div>
				</div>";
			echo "<input type='hidden' name='plugin' value='{$this->id}' />";
			echo "<input type='hidden' name='action' value='bill' />";
			echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
			echo "</fieldset>";
			echo "</form>";

			echo "<form action='ajax.php' method='post' name='Payment3' id='Payment3' rel='{$this->display}area' class='postcmd'>";
			echo "<fieldset>";
			echo "<div class='legend'>".pt_donate."</div>";
			echo "  <div class='f-row'>
					<label>".pt_acoppers."</label>
					<div class='f-field'>{$Coppers}</div>
				</div>";
			echo "  <div class='f-row'>";
			echo "		<label>".pt_author."</label>";
			echo "		<div class='f-field'><input type='hidden' name='payplayer' value='sascha' />sascha</div>";
			echo "	</div>";
			echo "  <div class='f-row'>
					<label for='paycoppers'>".pt_coppers."</label>
					<div class='f-field'><input type='text' name='paycoppers' id='paycoppers' /></div>
				</div>";
			echo "  <div class='f-row'>
					<label for='payinfo'>".pt_info."</label>
					<div class='f-field'><input type='text' name='payinfo' id='payinfo' /></div>
				</div>";
			echo "<input type='hidden' name='plugin' value='{$this->id}' />";
			echo "<input type='hidden' name='action' value='pay' />";
			echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
			echo "</fieldset>";
			echo "</form>";
		}
	}

	public function pay()
	{
		if($_REQUEST['payplayer'] && $_REQUEST['paycoppers'] && $_REQUEST['payinfo'] )
			Core::getObject('actions')->add('Pay', (string) $_REQUEST['payplayer'], (int) $_REQUEST['paycoppers'], (string) $_REQUEST['payinfo']);
		else
			trigger_error(pt_payerr);
	}

	public function bill()
	{
		if($_REQUEST['payplayer'] && $_REQUEST['paycoppers'] && $_REQUEST['payinfo'])
			Core::getObject('actions')->add('SendBill', (string) $_REQUEST['payplayer'], (int) $_REQUEST['paycoppers'], (string) $_REQUEST['payinfo'], (string) $_REQUEST['receiveingplayer']);
		else
			trigger_error(pt_billerr);
	}
}
?>