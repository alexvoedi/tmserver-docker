<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 7
*/
class Messages extends rcp_plugin
{
	public  $display		= 'main';
	public  $title			= 'Messages';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '1';
	public  $nsqlcon		= true;
	public  $vpermissions	= array('globalmaintenance');
	private $op				= false;
	private $edit			= false;
	private $format			= false;

	public function onLoadSettings($settings)
	{
		$this->format = (string) $settings->format;
	}

	public function onLoad()
	{
		Core::getObject('db')->fileImport($this->id);
		$this->op	= array_key_exists('op', $_REQUEST)		? $_REQUEST['op']	: false;
		$this->edit	= array_key_exists('edit', $_REQUEST)	? $_REQUEST['edit']	: false;
	}

	public function onOutput()
	{
		//Get db connection
		$db = Core::getObject('db')->getConnection();
		switch($this->op) {
			default:
				echo "<fieldset><div class='legend'><a href='ajax.php?plugin={$this->id}&op=create' style='float:none' class='getcmd' rel='{$this->display}area'>".pt_add."</a></div></fieldset>";
				$select = $db->query("
					SELECT n.id, n.login, n.serverid, n.text, DATE_FORMAT(n.date, '%Y-%m-%d') AS date, n.status
					FROM rcp_web_messages AS n
					ORDER BY n.date desc"
				);

				echo "<div class='bg-t'>";
				echo "<table>";
				echo "<colgroup>";
				echo "	<col width='10%' />";
				echo "	<col width='10%' />";
				echo "	<col width='10%' />";
				echo "	<col width='15%' />";
				echo "	<col width='35%' />";
				echo "	<col width='20%' />";
				echo "</colgroup>";
				echo "<thead>";
				echo "<tr>";
				echo "	<th class='ic'>". pt_status ."</th>";
				echo "	<th class='il'>". pt_date ."</th>";
				echo "	<th class='il'>". pt_author ."</th>";
				echo "	<th class='il'>". pt_server ."</th>";
				echo "	<th class='il'>". pt_text ."</th>";
				echo "	<th>Options</th>";
				echo "</tr>";
				echo "</thead>";
				if($select->columnCount()) {
					echo "<tbody>";
					while($message = $select->fetch(PDO::FETCH_OBJ))
					{
						$status = ($message->status) ? "<a href='ajax.php?plugin={$this->id}&op=unsend&id={$message->id}' class='getcmd' rel='{$this->display}area'>".pt_send."</a>" : "<a href='ajax.php?plugin={$this->id}&op=send&id={$message->id}' class='getcmd bold' rel='{$this->display}area'>".pt_unsend."</a>";
						echo "<tr>";
						echo "	<td class='ic'>". $status ."</td>";
						echo "	<td>{$message->date}</td>";
						echo "	<td>{$message->login}</td>";
						echo "	<td>". $this->GetServerName($message->serverid) ."</td>";
						echo "	<td>". substr(Core::getObject('tparse')->stripCode($message->text),0,15) ."</td>";
						echo "	<td class='ic'><a href='ajax.php?plugin={$this->id}&op=create&id={$message->id}&edit=true' class='getcmd' rel='{$this->display}area'><img src='".Core::getSetting('style')."/icons/edit.gif' alt='".ct_edit."' title='".ct_edit."' /></a> <a href='ajax.php?plugin={$this->id}&op=delete&id={$message->id}' class='getcmd' rel='{$this->display}area'><img src='".Core::getSetting('style')."/icons/del.gif' alt='".ct_delete."' title='".ct_delete."' /></a></td>";
						echo "</tr>";
					}
					echo "</tbody>";
				}
				echo "</table>";
				echo "</div>";

				$select->closeCursor();
				$select = null;
			break;

			case 'create':
				if(!empty($_POST)) {
					$form = array();
					$form['text'] = empty($_REQUEST['text']) ? true : false;
					if(Core::getObject('form')->check($form)) {
						$serverid = (Core::getObject('session')->checkPerm('globalmaintenance')) ? $_REQUEST['serverid'] : Core::getObject('session')->server->id;
						if($this->edit) {
							$result = $db->prepare("
								UPDATE rcp_web_messages
								SET serverid = :serverid, text = :text
								WHERE id = :id
							");
							$result->bindParam('serverid', $serverid);
							$result->bindParam('text', $_REQUEST['text']);
							$result->bindParam('id', $_REQUEST['id']);
							$result->execute();
						} else {
							$result = $db->prepare("
								INSERT INTO rcp_web_messages
								(login,serverid,text,date)
								VALUES
								(:login,:serverid,:text, NOW())
							");
							$result->bindParam('login', Core::getObject('session')->admin->username);
							$result->bindParam('serverid', $serverid);
							$result->bindParam('text', $_REQUEST['text']);
							$result->execute();
						}

						if($result) {
							Core::getObject('messages')->add(pt_saved);
							$this->edit = true;
							$result->closeCursor();
						} else {
							trigger_error(pt_saveerr);
						}
						$result = null;
					}
				} elseif($this->edit) {
					$select = $db->prepare("SELECT id, login, serverid, text FROM rcp_web_messages WHERE id = :id");
					$select->bindParam('id', $_REQUEST['id']);
					$select->execute();
					if($select->columnCount()) {
						$message = $select->fetch(PDO::FETCH_OBJ);
						if(Core::getObject('session')->admin->username == $message->login || Core::getObject('session')->checkPerm('globalmaintenance')) {
							$_REQUEST['id']			= $message->id;
							$_REQUEST['login']		= $message->login;
							$_REQUEST['serverid']	= $message->serverid;
							$_REQUEST['text']		= $message->text;
						} else {
							trigger_error(pt_editerr1);
							return;
						}
						$select->closeCursor();
					}
					$select = null;
				}

				echo "<form action='ajax.php' method='post' name='messages' id='messages' rel='{$this->display}area' class='postcmd'>";
				echo "<div class='f-row' style='height:150px;'>
						<label for='text' style='height:150px;'>".pt_text."</label>
						<div class='f-field' style='height:150px;'><textarea id='text' name='text' cols='15' rows='8' style='height:150px;'>{$_REQUEST['text']}</textarea></div>
					</div>";
				if(Core::getObject('session')->checkPerm('globalmaintenance')) {
					echo "<div class='f-row'>
							<label for='text'>".pt_server."</label>
							<div class='f-field'><select name='serverid'>";
								if(Core::getObject('session')->servers) {
									foreach(Core::getObject('session')->servers->server as $node)
									{
										echo "<option value='". (string) $node->id ."'"; if((string) $node->id == $_REQUEST['serverid']) { echo " selected='selected'"; } echo "> ". (string) $node->name ." (". (string) $node->connection->host ." - ". (string) $node->connection->port .")</option>";
									}
								}
					echo "		<option value='0'>".pt_allservers."</option>
							</select></div>
						</div>";
				}
				echo "<input type='hidden' name='plugin' value='{$this->id}' />";
				echo "<input type='hidden' name='op' value='create' />";
				echo "<input type='hidden' name='edit' value='{$this->edit}' />";
				echo "<input type='hidden' name='id' value='{$_REQUEST['id']}' />";
				echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
				echo "</form>";
			break;

			case 'delete':
				$select = $db->prepare("SELECT id, login FROM rcp_web_messages WHERE id = :id ");
				$select->bindParam('id', $_REQUEST['id']);
				$select->execute();
				if($select->columnCount()) {
					$message = $select->fetch(PDO::FETCH_OBJ);
					if(Core::getObject('session')->admin->username == $message->login || Core::getObject('session')->checkPerm('globalmaintenance')) {
						$delete = $db->prepare("DELETE from rcp_web_messages WHERE id = :id");
						$delete->bindParam('id', $message->id);
						$delete->execute();
						Core::getObject('messages')->add(pt_deleted);
					} else {
						trigger_error(pt_deleteerr1);
					}
					$select->closeCursor();
				} else {
					trigger_error(pt_deleteerr2);
				}
				$select = null;
			break;

			case 'unsend':
				//Update data
				$update = $db->prepare("UPDATE rcp_web_messages SET status = '0' WHERE id = :id");
				$update->bindParam('id', $_REQUEST['id']);
				$update->execute();
				$update->closeCursor();
				$update = null;
				redirect("ajax.php?plugin={$this->id}");
			break;

			case 'send':
				echo "<div class='legend'>Sending...</div>";

				//Get data
				$select = $db->prepare("SELECT id, serverid, text FROM rcp_web_messages WHERE status = '0' && id = :id");
				$select->bindParam('id', $_REQUEST['id']);
				$select->execute();
				if($select->columnCount()) {
					$data = $select->fetch(PDO::FETCH_OBJ);
					foreach(Core::getObject('session')->servers->children() as $node)
					{
						if($data->serverid == 0 || $data->serverid == (string) $node->id) {
							$host   = (string) $node->connection->host;
							$port = (string) $node->connection->port;
							$psw  = (string) $node->connection->password;

							$connection = new IXR_Client_Gbx;
							if($connection->InitWithIp($host, $port)) {
								if($connection->query('Authenticate','SuperAdmin',$psw)) {
									/*
									ChatSendServerMessage
									boolean ChatSendServerMessage(string)
									Send a text message to all clients without the server login. Only available to Admin. 
									*/
									if($connection->query('GetServerName')) {
										$Name = $connection->getResponse();
									}
									$text = sprintf($this->format, $data->text);
									$status = ($connection->query('ChatSendServerMessage', $text)) ? 'ok' : 'error'; 
									echo "<div>Sending to <span class='bold'>{$Name}</span> ... {$status}</div>";
								}
								$connection->Terminate();
							}
						}
					}
					$select->closeCursor();
				}
				$select = null;

				//Update data
				$update = $db->prepare("UPDATE rcp_web_messages SET status = '1' WHERE id = :id");
				$update->bindParam('id', $_REQUEST['id']);
				$update->execute();
				$update->closeCursor();
				$update = null;
			break;
		}
	}

	private function GetServerName($id)
	{
		if($id == 0) return pt_allservers;
		if(Core::getObject('session')->servers) {
			foreach(Core::getObject('session')->servers->server as $node)
			{
				if((string) $node->id == $id) return $node->name;
			}
		}
		return pt_unknownserver;
	}
}
?>