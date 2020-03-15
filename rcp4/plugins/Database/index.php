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
class Database extends rcp_plugin
{
	public  $display		= 'main';
	public  $title			= 'Database';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nsqlcon		= true;
	public  $nservcon		= false;
	public  $vpermissions	= array('database');
	public  $apermissions	= array(
		'dbdelete'	=> 'database',
		'dbedit'	=> 'database'
	);
	private $pagination		= false;
	private $search			= false;
	private $orderby		= false;
	private $opsel			= false;
	private $obsel			= false;
	private $otsel			= false;
	private $sbsel			= false;
	private $op				= false;
	private $op2			= false;
	private $op3			= false;
	private $sql			= false;

	public function onLoad()
	{
		//Get db connection
		if(!Core::getObject('db')->checkConnection()) return;
		$db = Core::getObject('db')->getConnection();

		$this->sql				= Core::getObject('settings')->database;
		$op						= array('challenges', 'players', 'records', 'dbedit');
		$ordertype				= array('asc', 'desc');
		$_REQUEST['op']			= in_array($_REQUEST['op'], $op) ? $_REQUEST['op'] : 'challenges';
		if($_REQUEST['op'] == 'challenges') {
			$orderby			= array((string) $this->sql->challenges->Name, (string) $this->sql->challenges->Author, (string) $this->sql->challenges->Environment);
			$searchby			= array((string) $this->sql->challenges->Name, (string) $this->sql->challenges->Author, (string) $this->sql->challenges->Environment);
		} else {
			$orderby			= array((string) $this->sql->players->Login, (string) $this->sql->players->NickName, (string) $this->sql->players->Wins, (string) $this->sql->players->TimePlayed);
			$searchby			= array((string) $this->sql->players->Login, (string) $this->sql->players->NickName, (string) $this->sql->players->Wins, (string) $this->sql->players->TimePlayed);
		}
		$_REQUEST['orderby']	= in_array($_REQUEST['orderby'], $orderby)		? $_REQUEST['orderby']	 : $orderby[0];
		$_REQUEST['ordertype']	= in_array($_REQUEST['ordertype'], $ordertype)	? $_REQUEST['ordertype'] : $ordertype[0];
		$_REQUEST['searchby']	= in_array($_REQUEST['searchby'], $searchby)	? $_REQUEST['searchby']  : $searchby[0];

		$key = array_search($_REQUEST['op'], $op);
		$this->op = $op[$key];
		$this->op2= $_REQUEST['op2'];
		$this->op3= $_REQUEST['op3'];
		$this->opsel[$this->op] = " selected='selected'";
		$key = array_search($_REQUEST['orderby'], $orderby);
		$key = $orderby[$key];
		$this->obsel[$key] = " selected='selected'";
		$key = array_search($_REQUEST['ordertype'], $ordertype);
		$key = $ordertype[$key];
		$this->otsel[$key] = " selected='selected'";
		$key = array_search($_REQUEST['searchby'], $searchby);
		$key = $searchby[$key];
		$this->sbsel[$key] = " selected='selected'";
		$this->orderby = "ORDER BY {$_REQUEST['orderby']} {$_REQUEST['ordertype']}";

		$this->search = "WHERE 1";
		if(!empty($_REQUEST['searchvalue'])) {
			$_REQUEST['searchvalue'] = str_replace('*', '%', $_REQUEST['searchvalue']);
			$_REQUEST['searchvalue'] = str_replace('?', '_', $_REQUEST['searchvalue']);
			$pos = strpos ($_REQUEST['searchvalue'], '%');
			if ($pos === false) {
				$pos = strpos ($_REQUEST['searchvalue'], '_');
				if($pos === false) {
					$_REQUEST['searchvalue'] = '%'. $_REQUEST['searchvalue'] .'%';
				}
			}
			$this->search = "WHERE {$_REQUEST['searchby']} LIKE '{$_REQUEST['searchvalue']}'";
		}
	}

	public function onOutput()
	{
		if($_REQUEST['action'])
			return true;

		//Get db connection
		$db = Core::getObject('db')->getConnection();

		if(!$this->op2) {
		echo "<form action='ajax.php' method='post' id='Databaseselect' name='Databaseselect' class='postcmd' rel='{$this->display}area'>";
		echo "<input type='hidden' name='plugin' value='{$this->id}' />";
		echo "<input type='hidden' name='op' value='{$this->op}' />";
		echo "<fieldset><div class='legend'>".pt_options."</div>";
		echo "<div class='f-row'>";
		echo "	<label for='op'>".pt_display."</label>";
		echo "	<div class='f-field'><select name='op'><option value='challenges'{$this->opsel['challenges']}>".pt_challenges."</option><option value='players'{$this->opsel['players']}>".pt_players."</option></select> <button type='submit' title='".ct_go."'><img src='".Core::getSetting('style')."/icons/select.gif' alt='".ct_submit."' title='".ct_submit."' /></button></div>";
		echo "</div>";
		echo "<div class='f-row'>";
		echo "	<label for='orderby'>".pt_orderby."</label>";
		echo "	<div class='f-field'><select name='orderby'>";
		switch($this->op) {
			default:
				echo "<option value='{$this->sql->challenges->Name}'{$this->obsel['Name']}>".pt_obname."</option><option value='{$this->sql->challenges->Author}'{$this->obsel['Author']}>".pt_obauthor."</option><option value='{$this->sql->challenges->Environment}'{$this->obsel['Environment']}>".pt_obenvironment."</option>";
			break;

			case 'players':
				echo "<option value='{$this->sql->players->Login}'{$this->obsel['Login']}>".pt_oblogin."</option><option value='{$this->sql->players->NickName}'{$this->obsel['NickName']}>".pt_obnickname."</option><option value='{$this->sql->players->Wins}'{$this->obsel['Wins']}>".pt_obwins."</option><option value='{$this->sql->players->TimePlayed}'{$this->obsel['TimePlayed']}>".pt_obtimeplayed."</option>";
			break;
		}
		echo "</select> <button type='submit' title='".ct_submit."'><img src='".Core::getSetting('style')."/icons/select.gif' alt='".ct_submit."' title='".ct_submit."' /></button></div>";
		echo "</div>";
		echo "<div class='f-row'>";
		echo "	<label></label>";
		echo "	<div class='f-field'><select name='ordertype'><option value='asc'{$this->otsel['asc']}>".pt_otasc."</option><option value='desc'{$this->otsel['desc']}>".pt_otdesc."</option></select> <button type='submit' title='".ct_submit."'><img src='".Core::getSetting('style')."/icons/select.gif' alt='".ct_submit."' title='".ct_submit."' /></button></div>";
		echo "</div>";
		echo "</fieldset>";
		echo "<fieldset><div class='legend'>".pt_search."</div>";
		echo "<div class='f-row'>";
		echo "	<label for='searchvalue'>".pt_searchvalue."</label>";
		echo "	<div class='f-field'><input type='text' name='searchvalue' value='{$_REQUEST['searchvalue']}' /> <button type='submit' title='".ct_go."'><img src='".Core::getSetting('style')."/icons/select.gif' alt='".ct_submit."' title='".ct_submit."' /></button></div>";
		echo "</div>";
		echo "<div class='f-row'>";
		echo "	<label for='searchby'>".pt_searchby."</label>";
		echo "	<div class='f-field'><select name='searchby'>";
		switch($this->op) {
			default:
				echo "<option value='{$this->sql->challenges->Name}'{$this->sbsel['Name']}>".pt_obname."</option><option value='{$this->sql->challenges->Author}'{$this->sbsel['Author']}>".pt_obauthor."</option><option value='{$this->sql->challenges->Environment}'{$this->sbsel['Environment']}>".pt_obenvironment."</option>";
			break;

			case 'players':
				echo "<option value='{$this->sql->players->Login}'{$this->sbsel['Login']}>".pt_oblogin."</option><option value='{$this->sql->players->NickName}'{$this->sbsel['NickName']}>".pt_obnickname."</option><option value='{$this->sql->players->Wins}'{$this->sbsel['Wins']}>".pt_obwins."</option><option value='{$this->sql->players->TimePlayed}'{$this->obsel['TimePlayed']}>".pt_obtimeplayed."</option>";
			break;
		}
		echo "</select> <button type='submit' title='".ct_submit."'><img src='".Core::getSetting('style')."/icons/select.gif' alt='".ct_submit."' title='".ct_submit."' /></button></div>";
		echo "</div>";
		echo "</fieldset>";
		echo "</form>";
		}

		switch($this->op) {
			default:
				$select	= $db->query("SELECT COUNT({$this->sql->challenges->Id}) AS Count FROM {$this->sql->challenges['table']}");
				$count	= $select->fetchColumn();
				$limit	= $this->pagination($count, 30, $_REQUEST['start']);
				$select->closeCursor();
				$select = null;

				$select = $db->query("
					SELECT a.*, (SELECT MIN(b.{$this->sql->records->Score}) FROM {$this->sql->records['table']} AS b WHERE a.{$this->sql->challenges->Id} = b.{$this->sql->records->ChallengeId}) AS record
					FROM {$this->sql->challenges['table']} AS a
					{$this->search}
					{$this->orderby}
					LIMIT $limit[0], $limit[1]
				");

				echo "<div class='bg-t'>";
				echo "<table>";
				echo "<colgroup>";
				if(Core::getObject('session')->checkPerm('database')) {
					echo "	<col width='15%' />";
					echo "	<col width='35%' />";
				} else {
					echo "	<col width='50%' />";
				}
				echo "	<col width='15%' />";
				echo "	<col width='10%' />";
				echo "	<col width='25%' />";
				echo "</colgroup>";
				echo "<thead>";
				echo "	<tr>";
				if(Core::getObject('session')->checkPerm('database'))
					echo "<th>".pt_options2."</th>";
				echo "		<th class='il'>".pt_name."</th>";
				echo "		<th class='il'>".pt_author."</th>";
				echo "		<th>".pt_environment."</th>";
				echo "		<th>".pt_trecord."</th>";
				echo "	</tr>";
				echo "</thead>";
				echo "<tbody>";

				while($row = $select->fetch(PDO::FETCH_OBJ))
				{
					echo "<tr>";
					if(Core::getObject('session')->checkPerm('database'))
						echo "<td class='ic'><a href='ajax.php?plugin=Database&op=records&id=".$row->{$this->sql->challenges->Id}."&op2=hide' rel='750;0' class='modal' title='".pt_records."'><img src='".Core::getSetting('style')."/icons/records.gif' alt='".pt_records."' title='".pt_records."' /></a> <a href='ajax.php?plugin={$this->id}&op=dbedit&id=".$row->{$this->sql->challenges->Id}."&op2=challenges&op3=edit' rel='dbeditC".$row->{$this->sql->challenges->Id}."' class='getcmd'><img src='".Core::getSetting('style')."/icons/edit.gif' alt='".ct_edit."' title='".ct_edit."' /></a> <a href='ajax.php?plugin={$this->id}&op=dbdelete&op2=challenges&id=".$row->{$this->sql->challenges->Id}."&action=dbdelete' rel='dbdelete".$row->{$this->sql->challenges->Id}."' class='getcmdc'><img src='".Core::getSetting('style')."/icons/del.gif' alt='".ct_delete."' title='".ct_delete."' /></a></td>";
					echo "<td><a href='ajax.php?plugin=Database&op=records&id=".$row->{$this->sql->challenges->Id}."&op2=hide' title='".pt_records."' rel='750;0' class='modal'>". Core::getObject('tparse')->toHTML($row->{$this->sql->challenges->Name}) ."</a></td>";
					echo "<td>".$row->{$this->sql->challenges->Author}."</td>";
					echo "<td class='ic'>".$row->{$this->sql->challenges->Environment}."</td>";
					echo "<td class='ic'>".Core::getObject('tparse')->toRaceTime($row->record)."</td>";
					echo "</tr>";
					echo "<tr><td colspan='"; if(Core::getObject('session')->checkPerm('database')) { echo "5"; } else { echo "4"; } echo "'><span id='dbdelete".$row->{$this->sql->challenges->Id}."'></span><span id='dbeditC".$row->{$this->sql->challenges->Id}."'></span></td></tr>";
				}

				echo "</tbody>";
				echo "</table>";
				echo "</div>";
				$this->ppagination('challenges', $this->display.'area', "&orderby={$_REQUEST['orderby']}&ordertype={$_REQUEST['ordertype']}&searchby={$_REQUEST['searchby']}&searchvalue={$_REQUEST['searchvalue']}");
				$select->closeCursor();
				$select = null;
			break;

			case 'players':
				$select	= $db->query("SELECT COUNT({$this->sql->players->Id}) AS Count FROM {$this->sql->players['table']}");
				$count	= $select->fetchColumn();
				$limit	= $this->pagination($count, 30, $_REQUEST['start']);
				$select->closeCursor();
				$select = null;

				$select = $db->query("
					SELECT a.*, (SELECT COUNT(b.{$this->sql->records->Id}) FROM {$this->sql->records['table']} as b WHERE b.{$this->sql->records->PlayerId} = a.{$this->sql->players->Id}) AS records
					FROM {$this->sql->players['table']} AS a
					{$this->search}
					{$this->orderby}
					LIMIT $limit[0], $limit[1]
				");

				echo "<div class='bg-t'>";
				echo "<table>";
				echo "<colgroup>";
				if(Core::getObject('session')->checkPerm('database')) {
					echo "	<col width='10%' />";
					echo "	<col width='25%' />";
				} else {
					echo "	<col width='35%' />";
				}
				echo "	<col width='25%' />";
				echo "	<col width='10%' />";
				echo "	<col width='15%' />";
				echo "	<col width='5%' />";
				echo "	<col width='10%' />";
				echo "</colgroup>";
				echo "<thead>";
				echo "	<tr>";
				if(Core::getObject('session')->checkPerm('database'))
					echo "<th>".pt_options2."</th>";
				echo "		<th class='il'>".pt_login."</th>";
				echo "		<th class='il'>".pt_nickname."</th>";
				echo "		<th>".pt_records."</th>";
				echo "		<th>".pt_update."</th>";
				echo "		<th>".pt_wins."</th>";
				echo "		<th>".pt_timeplayed."</th>";
				echo "	</tr>";
				echo "</thead>";
				echo "<tbody>";

				while($row = $select->fetch(PDO::FETCH_OBJ))
				{
					echo "<tr>";
					if(Core::getObject('session')->checkPerm('database'))
						echo "<td class='ic'><a href='ajax.php?plugin={$this->id}&op=dbedit&id=".$row->{$this->sql->players->Id}."&op2=players&op3=edit' rel='dbeditP".$row->{$this->sql->players->Id}."' class='getcmd'><img src='".Core::getSetting('style')."/icons/edit.gif' alt='".ct_edit."' title='".ct_edit."' /></a> <a href='ajax.php?plugin={$this->id}&op=dbdelete&op2=players&id=".$row->{$this->sql->players->Id}."&action=dbdelete' rel='dbdelete".$row->{$this->sql->players->Id}."' class='getcmdc'><img src='".Core::getSetting('style')."/icons/del.gif' alt='".ct_delete."' title='".ct_delete."' /></a></td>";
					echo "<td>".$row->{$this->sql->players->Login}."</td>";
					echo "<td>". Core::getObject('tparse')->toHTML($row->{$this->sql->players->NickName}) ."</td>";
					echo "<td class='ic'>".$row->records."</td>";
					echo "<td class='ic'>".$row->{$this->sql->players->UpdatedAt}."</td>";
					echo "<td class='ic'>".$row->{$this->sql->players->Wins}."</td>";
					echo "<td class='ic'>".$row->{$this->sql->players->TimePlayed}."</td>";
					echo "</tr>";
					echo "<tr><td colspan='"; if(Core::getObject('session')->checkPerm('database')) { echo "7"; } else { echo "6"; } echo "'><span id='dbdelete".$row->{$this->sql->players->Id}."'></span><span id='dbeditP".$row->{$this->sql->players->Id}."'></span></td></tr>";
				}

				echo "</tbody>";
				echo "</table>";
				echo "</div>";
				$this->ppagination('players', $this->display.'area', "&orderby={$_REQUEST['orderby']}&ordertype={$_REQUEST['ordertype']}&searchby={$_REQUEST['searchby']}&searchvalue={$_REQUEST['searchvalue']}");
				$select->closeCursor();
				$select = null;
			break;

			case 'records':
				$id = $_REQUEST['id'];
				$select	= $db->prepare("SELECT COUNT({$this->sql->records->Id}) AS Count FROM {$this->sql->records['table']} WHERE {$this->sql->records->ChallengeId} = :id");
				$select->bindParam('id', $id);
				$select->execute();
				$count	= $select->fetchColumn();
				$limit	= $this->pagination($count, 15, $_REQUEST['start']);
				$select->closeCursor();
				$select = null;

				$select = $db->prepare("
					SELECT a.*, b.{$this->sql->players->Login}, b.{$this->sql->players->NickName}, c.{$this->sql->challenges->Name} AS Challenge
					FROM {$this->sql->records['table']} AS a
					LEFT JOIN {$this->sql->players['table']} AS b
						ON a.{$this->sql->records->PlayerId} = b.{$this->sql->players->Id}
					LEFT JOIN {$this->sql->challenges['table']} AS c
						ON a.{$this->sql->records->ChallengeId} = c.{$this->sql->challenges->Id}
					{$this->search} && {$this->sql->records->ChallengeId} = :id
					GROUP BY a.{$this->sql->records->Id}
					ORDER BY {$this->sql->records->Score} asc
					LIMIT $limit[0], $limit[1]
				");
				$select->bindParam('id', $id);
				$select->execute();

				echo "<div class='bg-t'>";
				echo "<table>";
				echo "<colgroup>";
				if(Core::getObject('session')->checkPerm('database')) {
					echo "	<col width='10%' />";
					echo "	<col width='30%' />";
				} else {
					echo "	<col width='40%' />";
				}
				echo "	<col width='30%' />";
				echo "	<col width='15%' />";
				echo "	<col width='15%' />";
				echo "</colgroup>";
				echo "<thead>";
				echo "	<tr>";
				if(Core::getObject('session')->checkPerm('database'))
					echo "<th>".pt_options2."</th>";
				echo "		<th class='il'>".pt_challenge."</th>";
				echo "		<th class='il'>".pt_nickname."</th>";
				echo "		<th>".pt_score."</th>";
				echo "		<th>".pt_date."</th>";
				echo "	</tr>";
				echo "</thead>";
				echo "<tbody>";

				while($row = $select->fetch(PDO::FETCH_OBJ))
				{
					echo "<tr>";
					if(Core::getObject('session')->checkPerm('database'))
						echo "<td class='ic'><a href='ajax.php?plugin={$this->id}&op=dbedit&id=".$row->{$this->sql->records->Id}."&op2=records&op3=edit' rel='dbeditR".$row->{$this->sql->records->Id}."' class='getcmd'><img src='".Core::getSetting('style')."/icons/edit.gif' alt='".ct_edit."' title='".ct_edit."' /></a> <a href='ajax.php?plugin={$this->id}&op=dbdelete&op2=records&id=".$row->{$this->sql->records->Id}."&action=dbdelete' rel='dbdelete".$row->{$this->sql->records->Id}."' class='getcmdc'><img src='".Core::getSetting('style')."/icons/del.gif' alt='".ct_delete."' title='".ct_delete."' /></a></td>";
					echo "<td>". Core::getObject('tparse')->toHTML($row->Challenge) ."</td>";
					echo "<td>". Core::getObject('tparse')->toHTML($row->{$this->sql->players->NickName}) ."</td>";
					echo "<td class='ic'>". Core::getObject('tparse')->toRaceTime($row->{$this->sql->records->Score}) ."</td>";
					echo "<td class='ic'>".$row->{$this->sql->records->Date}."</td>";
					echo "</tr>";
					echo "<tr><td colspan='"; if(Core::getObject('session')->checkPerm('database')) { echo "5"; } else { echo "4"; } echo "'><span id='dbdelete".$row->{$this->sql->records->Id}."'></span><span id='dbeditR".$row->{$this->sql->records->Id}."'></span></td></tr>";
				}

				echo "</tbody>";
				echo "</table>";
				echo "</div>";
				$this->ppagination('records', 'sbox-content', "&id={$id}&op2=hide");
				$select->closeCursor();
				$select = null;
			break;

			case 'dbedit':
				$id = $_REQUEST['id'];
				switch($this->op2) {
					default:
						if($this->op3 == 'edit') {
							$select = $db->prepare("SELECT * FROM {$this->sql->challenges['table']} WHERE {$this->sql->challenges->Id} = :id");
							$select->bindParam('id', $id);
							$select->execute();
							$row = $select->fetch(PDO::FETCH_OBJ);
							$_REQUEST['dbe_Name']			= specialchars($row->{$this->sql->challenges->Name});
							$_REQUEST['dbe_Author']			= specialchars($row->{$this->sql->challenges->Author});
							$_REQUEST['dbe_Environment']	= specialchars($row->{$this->sql->challenges->Environment});
							$select->closeCursor();
							$select = null;
						}

						echo "<form action='ajax.php' method='post' id='fdbeditC{$id}' name='fdbeditC{$id}' class='postcmd' rel='dbeditC{$id}'>";
						echo "<fieldset>";
						echo "<div class='f-row'>";
						echo "	<label for='dbe_Name'>".pt_name."</label>";
						echo "	<div class='f-field'><input type='text' name='dbe_Name' value='{$_REQUEST['dbe_Name']}' /></div>";
						echo "</div>";
						echo "<div class='f-row'>";
						echo "	<label for='dbe_Author'>".pt_author."</label>";
						echo "	<div class='f-field'><input type='text' name='dbe_Author' value='{$_REQUEST['dbe_Author']}' /></div>";
						echo "</div>";
						echo "<div class='f-row'>";
						echo "	<label for='dbe_Environment'>".pt_environment."</label>";
						echo "	<div class='f-field'><input type='text' name='dbe_Environment' value='{$_REQUEST['dbe_Environment']}' /></div>";
						echo "</div>";
						echo "<button type='submit' class='wide'>".ct_submit."</button>";
						echo "</fieldset>";
						echo "<input type='hidden' name='plugin' value='{$this->id}' />";
						echo "<input type='hidden' name='op' value='dbedit' />";
						echo "<input type='hidden' name='op2' value='{$this->op2}' />";
						echo "<input type='hidden' name='action' value='dbedit' />";
						echo "<input type='hidden' name='id' value='{$id}' />";
						echo "</form>";
					break;

					case 'players':
						if($this->op3 == 'edit') {
							$select = $db->prepare("SELECT * FROM {$this->sql->players['table']} WHERE {$this->sql->players->Id} = :id");
							$select->bindParam('id', $id);
							$select->execute();
							$row = $select->fetch(PDO::FETCH_OBJ);
							$_REQUEST['dbe_Login']		= specialchars($row->{$this->sql->players->Login});
							$_REQUEST['dbe_NickName']	= specialchars($row->{$this->sql->players->NickName});
							$_REQUEST['dbe_UpdatedAt']	= specialchars($row->{$this->sql->players->UpdatedAt});
							$_REQUEST['dbe_Wins']		= specialchars($row->{$this->sql->players->Wins});
							$_REQUEST['dbe_TimePlayed']	= specialchars($row->{$this->sql->players->TimePlayed});
							$select->closeCursor();
							$select = null;
						}

						echo "<form action='ajax.php' method='post' id='fdbeditP{$id}' name='fdbeditP{$id}' class='postcmd' rel='dbeditP{$id}'>";
						echo "<fieldset>";
						echo "<div class='f-row'>";
						echo "	<label for='dbe_Login'>".pt_login."</label>";
						echo "	<div class='f-field'><input type='text' name='dbe_Login' value='{$_REQUEST['dbe_Login']}' /></div>";
						echo "</div>";
						echo "<div class='f-row'>";
						echo "	<label for='dbe_NickName'>".pt_nickname."</label>";
						echo "	<div class='f-field'><input type='text' name='dbe_NickName' value='{$_REQUEST['dbe_NickName']}' /></div>";
						echo "</div>";
						echo "<div class='f-row'>";
						echo "	<label for='dbe_UpdatedAt'>".pt_update."</label>";
						echo "	<div class='f-field'><input type='text' name='dbe_UpdatedAt' value='{$_REQUEST['dbe_UpdatedAt']}' /></div>";
						echo "</div>";
						echo "<div class='f-row'>";
						echo "	<label for='dbe_wins'>".pt_wins."</label>";
						echo "	<div class='f-field'><input type='text' name='dbe_Wins' value='{$_REQUEST['dbe_Wins']}' /></div>";
						echo "</div>";
						echo "<div class='f-row'>";
						echo "	<label for='dbe_TimePlayed'>".pt_timeplayed."</label>";
						echo "	<div class='f-field'><input type='text' name='dbe_TimePlayed' value='{$_REQUEST['dbe_TimePlayed']}' /></div>";
						echo "</div>";
						echo "<button type='submit' class='wide'>".ct_submit."</button>";
						echo "</fieldset>";
						echo "<input type='hidden' name='plugin' value='{$this->id}' />";
						echo "<input type='hidden' name='op' value='dbedit' />";
						echo "<input type='hidden' name='op2' value='{$this->op2}' />";
						echo "<input type='hidden' name='action' value='dbedit' />";
						echo "<input type='hidden' name='id' value='{$id}' />";
						echo "</form>";
					break;

					case 'records':
						if($this->op3 == 'edit') {
							$select = $db->prepare("SELECT * FROM {$this->sql->records['table']} WHERE {$this->sql->records->Id} = :id");
							$select->bindParam('id', $id);
							$select->execute();
							$row = $select->fetch(PDO::FETCH_OBJ);
							$_REQUEST['dbe_Score']	= specialchars($row->{$this->sql->records->Score});
							$_REQUEST['dbe_Date']	= specialchars($row->{$this->sql->records->Date});
							$select->closeCursor();
							$select = null;
						}

						echo "<form action='ajax.php' method='post' id='fdbeditR{$id}' name='fdbeditR{$id}' class='postcmd' rel='dbeditR{$id}'>";
						echo "<fieldset>";
						echo "<div class='f-row'>";
						echo "	<label for='dbe_Score'>".pt_score."</label>";
						echo "	<div class='f-field'><input type='text' name='dbe_Score' value='{$_REQUEST['dbe_Score']}' /></div>";
						echo "</div>";
						echo "<div class='f-row'>";
						echo "	<label for='dbe_Date'>".pt_date."</label>";
						echo "	<div class='f-field'><input type='text' name='dbe_Date' value='{$_REQUEST['dbe_Date']}' /></div>";
						echo "<button type='submit' class='wide'>".ct_submit."</button>";
						echo "</fieldset>";
						echo "<input type='hidden' name='plugin' value='{$this->id}' />";
						echo "<input type='hidden' name='op' value='dbedit' />";
						echo "<input type='hidden' name='op2' value='{$this->op2}' />";
						echo "<input type='hidden' name='action' value='dbedit' />";
						echo "<input type='hidden' name='id' value='{$id}' />";
						echo "</form>";
					break;
				}
			break;

			case 'dbdelete':
				//do not remove
			break;
		}
	}

	private function pagination($count = 0, $limit = 10, $start = 1, $last = false)
	{
		if(empty($start))
			$start = 1;

		$pages = ceil($count / $limit);
		$start = ($last) ? $pages - 1 : $start;
		$prev  = $start - 1;
		$next  = $start + 1;
 
		$this->pagination = array(
			'limit'	=> $limit,
			'prev'	=> $prev,
			'next'	=> $next,
			'pages'	=> $pages,
			'count'	=> $count,
			'start'	=> $start
		);

		$start = (($start * $limit) - $limit);
		return array($start, $limit);
	}

	private function ppagination($op, $display, $querystring = '')
	{
		echo "<div>";
		echo "<div class='pagination'>";
		echo "	<div><span>{$this->pagination['count']} elements &bull;</span></div>";
		if($this->pagination['start'] >= 2)
			echo "<div><a href='ajax.php?plugin={$this->id}&op={$op}&start={$this->pagination['prev']}{$querystring}' rel='{$display}' class='getcmd'>&#171;</a></div>";

		if($this->pagination['pages'] <= 10) {
			for ($i = 1; $i <= $this->pagination['pages']; ++$i)
			{
				echo "<div"; if($this->pagination['start'] == $i) { echo " class='sel'"; } echo "><a href='ajax.php?plugin={$this->id}&op={$op}&start={$i}{$querystring}' rel='{$display}' class='getcmd'>{$i}</a></div>";
			}
		} else {
			for ($i = 1; $i <= $this->pagination['pages']; ++$i)
			{
		 		if($i <= 3 || $i > ($this->pagination['pages']-4))
					echo "<div"; if($this->pagination['start'] == $i) { echo " class='sel'"; } echo "><a href='ajax.php?plugin={$this->id}&op={$op}&start={$i}{$querystring}' rel='{$display}' class='getcmd'>{$i}</a></div>";
			}
		}

		if($this->pagination['pages'] > $this->pagination['start'])
			echo "<div><a href='ajax.php?plugin={$this->id}&op={$op}&start={$this->pagination['next']}{$querystring}' rel='{$display}' class='getcmd'>&#187;</a></div>";
		echo "</div>";
		echo "<br style='clear:both;' />";
		echo "</div>";
	}

	public function dbdelete()
	{
		//Get db connection
		$db = Core::getObject('db')->getConnection();

		$id = $_REQUEST['id'];
		switch($this->op2) {
			default:
				$select = $db->prepare("SELECT {$this->sql->challenges->Name} FROM {$this->sql->challenges['table']} WHERE {$this->sql->challenges->Id} = :id");
				$select->bindParam('id', $id);
				$select->execute();
				$row = $select->fetch(PDO::FETCH_OBJ);
				$select->closeCursor();
				$select = null;
				$name = Core::getObject('tparse')->toHTML($row->{$this->sql->challenges->Name});

				$delete = $db->prepare("DELETE FROM {$this->sql->challenges['table']} WHERE {$this->sql->challenges->Id} = :id");
				$delete->bindParam('id', $id);
				$delete->execute();
				if($delete) {
					$delete->closeCursor();
					$delete = null;
					$delete = $db->prepare("DELETE FROM {$this->sql->records['table']} WHERE {$this->sql->records->ChallengeId} = :id");
					$delete->bindParam('id', $id);
					$delete->execute();
					if($delete)
						trigger_error(sprintf(pt_challengedelok, $id, $name));
					else
						trigger_error(sprintf(pt_challengedelerr, $id, $name));
					$delete->closeCursor();
					$delete = null;
				} else {
					trigger_error(sprintf(pt_challengedelerr, $id, $name));
				}
			break;

			case 'players':
				$select = $db->prepare("SELECT {$this->sql->players->NickName} FROM {$this->sql->players['table']} WHERE {$this->sql->players->Id} = :id");
				$select->bindParam('id', $id);
				$select->execute();
				$row = $select->fetch(PDO::FETCH_OBJ);
				$select->closeCursor();
				$select = null;
				$name = Core::getObject('tparse')->toHTML($row->{$this->sql->players->NickName});

				$delete = $db->prepare("DELETE FROM {$this->sql->players['table']} WHERE {$this->sql->players->Id} = :id");
				$delete->bindParam('id', $id);
				$delete->execute();
				if($delete) {
					$delete->closeCursor();
					$delete = null;
					$delete = $db->prepare("DELETE FROM {$this->sql->records['table']} WHERE {$this->sql->records->PlayerId} = :id");
					$delete->bindParam('id', $id);
					$delete->execute();
					if($delete)
						trigger_error(sprintf(pt_playerdelok, $id, $name));
					else
						trigger_error(sprintf(pt_playerdelerr, $id, $name));
					$delete->closeCursor();
					$delete = null;
				} else {
					trigger_error(sprintf(pt_playerdelerr, $id, $name));
				}
			break;

			case 'records':
				$delete = $db->prepare("DELETE FROM {$this->sql->records['table']} WHERE {$this->sql->records->Id} = :id");
				$delete->bindParam('id', $id);
				$delete->execute();
				if($delete)
					trigger_error(sprintf(pt_recorddelok, $id, $name));
				else
					trigger_error(sprintf(pt_recorddelerr, $id, $name));
				$delete->closeCursor();
				$delete = null;
			break;
		}
	}

	public function dbedit()
	{
		//Get db connection
		$db = Core::getObject('db')->getConnection();

		$id = $_REQUEST['id'];
		switch($this->op2) {
			default:
				if(!empty($_REQUEST['dbe_Name']) && !empty($_REQUEST['dbe_Author']) && !empty($_REQUEST['dbe_Environment'])) {
					$update = $db->prepare("UPDATE {$this->sql->challenges['table']} SET {$this->sql->challenges->Name} = :name, {$this->sql->challenges->Author} = :author, {$this->sql->challenges->Environment} = :envi WHERE {$this->sql->challenges->Id} = :id");
					$update->bindValue('name', specialchars($_REQUEST['dbe_Name'],true));
					$update->bindValue('author', specialchars($_REQUEST['dbe_Author'],true));
					$update->bindValue('envi', specialchars($_REQUEST['dbe_Environment'],true));
					$update->bindParam('id', $id);
					$update->execute();
					if($update)
						trigger_error(sprintf(pt_challengesaveok, $id, $_REQUEST['dbe_Name']));
					else
						trigger_error(sprintf(pt_challengesaveerr, $id, $_REQUEST['dbe_Name']));
					$update->closeCursor();
					$update = null;
				}
			break;

			case 'players':
				if(!empty($_REQUEST['dbe_Login']) && !empty($_REQUEST['dbe_NickName']) && !empty($_REQUEST['dbe_UpdatedAt']) && !empty($_REQUEST['dbe_TimePlayed'])) {
					$update = $db->prepare("UPDATE {$this->sql->players['table']} SET {$this->sql->players->Login} = :login,  {$this->sql->players->NickName} = :nickname, {$this->sql->players->UpdatedAt} = :updatedat, {$this->sql->players->Wins} = :wins, {$this->sql->players->TimePlayed} = :timeplayed WHERE {$this->sql->players->Id} = :id");
					$update->bindValue('login', specialchars($_REQUEST['dbe_Login'],true));
					$update->bindValue('nickname', specialchars($_REQUEST['dbe_NickName'],true));
					$update->bindValue('updatedat', specialchars($_REQUEST['dbe_UpdatedAt'],true));
					$update->bindValue('wins', specialchars($_REQUEST['dbe_Wins'],true));
					$update->bindValue('timeplayed', specialchars($_REQUEST['dbe_TimePlayed'],true));
					$update->bindParam('id', $id);
					$update->execute();
					if($update)
						trigger_error(sprintf(pt_playersaveok, $id, $_REQUEST['dbe_Login']));
					else
						trigger_error(sprintf(pt_playersaveerr, $id, $_REQUEST['dbe_Login']));
					$update->closeCursor();
					$update = null;
				}
			break;

			case 'records':
				if(!empty($_REQUEST['dbe_Date'])) {
					$update = $db->prepare("UPDATE {$this->sql->records['table']} SET {$this->sql->records->Score} = :score, {$this->sql->records->Date} = :date WHERE {$this->sql->records->Id} = :id");
					$update->bindValue('score', specialchars($_REQUEST['dbe_Score'],true));
					$update->bindValue('date', specialchars($_REQUEST['dbe_Date'],true));
					$update->bindParam('id', $id);
					$update->execute();
					if($update)
						trigger_error(sprintf(pt_recordsaveok, $id));
					else
						trigger_error(sprintf(pt_recordsaveerr, $id));
					$update->closeCursor();
					$update = null;
				}
			break;
		}
	}
}
?>