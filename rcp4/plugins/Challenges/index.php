<?php
/**
* remoteCP 4
* ütf-8 release
* Sort method by lukefwkr
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
class Challenges extends rcp_plugin
{
	public  $id	     		= 'Challenges';
	public  $display		= 'main';
	public  $title			= 'Challenges';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $usejs			= false;
	public  $usecss			= false;
	public  $nsqlcon		= true;
	public  $nservstatus	= array(3,4,5);
	public  $vpermissions	= array('viewmaps');
	public  $apermissions	= array(
		'RemoveChallengeList'		=> 'editmaps',
		'ChooseNextChallengeList'	=> 'editmaps',
		'ShuffleChallengeList'		=> 'editmaps',
		'SortChallengeList'			=> 'editmaps'
	);
	private $list			= array();
	private $sql;

	public function onLoad()
	{
		$this->sql = Core::getObject('settings')->database;
	}
    
	public function onExec()
	{
		$this->readlist();
	}

	public function onOutput()
	{
		if(Core::getObject('gbx')->query('GetCurrentGameInfo', 1))
			$CurrentGameInfo = Core::getObject('gbx')->getResponse();

		if(Core::getObject('gbx')->query('GetCurrentChallengeInfo'))
			$CurrentChallengeInfo = Core::getObject('gbx')->getResponse();

		$i = 0;
		while(true)
		{
			Core::getObject('gbx')->suppressNextError();
			if(!Core::getObject('gbx')->query('GetChallengeList', 50, $i)) break;
			$i = $i + 50;
			$Challenges = Core::getObject('gbx')->getResponse();
			if(empty($Challenges)) break;
			foreach($Challenges AS $value)
			{
				$ChallengeList[] = array(
					'FileName'		=> urlencode($value['FileName']),
					'Name'			=> Core::getObject('tparse')->toHTML($value['Name']),
					'Author'		=> $value['Author'],
					'Environment'	=> $value['Environnement'],
					'GoldTime'		=> Core::getObject('tparse')->toRaceTime($value['GoldTime']),
					'CopperPrice'	=> number_format($value['CopperPrice'], 0, ',', '.'),
					'UId'			=> $value['UId']
				);
			}
		}

		if(Core::getObject('session')->checkPerm('editmaps'))
			echo "<form action='ajax.php' method='post' name='challengelist' id='challengelist' class='postcmdc' rel='{$this->display}area'>";

		echo "<div class='bg-t'>";
		echo "<table>";
		echo "<colgroup>";
		if(Core::getObject('session')->checkPerm('editmaps')) {
			echo "<col width='5%' />";
			echo "<col width='5%' />";
		}
		if(Core::getObject('db')->checkConnection()) {
			echo "<col width='25%' />";
			echo "<col width='15%' />";
			echo "<col width='10%' />";
		} else {
			echo "<col width='30%' />";
			echo "<col width='20%' />";
			echo "<col width='15%' />";
		}
		echo "<col width='15%' />";
		echo "<col width='15%' />";
		if(Core::getObject('db')->checkConnection()) echo "<col width='15%' />";
		echo "</colgroup>";
		echo "<thead>";
		echo "<tr>";
		if(Core::getObject('session')->checkPerm('editmaps')) {
			echo "<th><input type='checkbox' name='challengelist[0]' value='all' class='checkall checkbox' /></th>";
			echo "<th></th>";
		}
		echo "	<th class='il'>".pt_name." (". count($ChallengeList) .")</th>";
		echo "  <th class='il'>".pt_author."</th>";
		echo "	<th>".pt_env."</th>";
		echo "	<th class='ir'>".pt_gtime."</th>";
		echo "	<th class='ir'>".pt_coppers."</th>";
		if(Core::getObject('db')->checkConnection()) echo "  <th class='ir'>".pt_votings."</th>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		if(!empty($ChallengeList)) {
			$i = 0;
			$tablecolor = 'l';
			foreach($ChallengeList AS $value)
			{
				++$i;
				$tablecolor = ($tablecolor == 'l') ? 'm' : 'l';
				echo "<tr"; if($value['UId'] == $CurrentChallengeInfo['UId']) { echo " class='bg-h'>"; } else { echo " class='bg-{$tablecolor}'>"; }
				if(Core::getObject('session')->checkPerm('editmaps')) {
					echo "<td class='ic'>";
					if($value['UId'] != $CurrentChallengeInfo['UId'])
						echo "<input type='checkbox' name='challengelist[]' value='{$value['FileName']}' class='selrow checkbox' />";
					echo "</td>";
					echo "<td class='ic'>";
					echo "<input type='text' name='sorting[]' size='3' value='{$i}' maxlength='4' style='width:30px;' class='ir' />";
					echo "<input type='hidden' name='sortdata[]' value='{$value['FileName']}' />";
					echo "</td>";
				}
				echo "<td><a href='ajax.php?plugin=Challenge&file={$value['FileName']}' rel='400;0' class='modal'>{$value['Name']}</a></td>";
				echo "<td>{$value['Author']}</td>";
				echo "<td class='ic'>{$value['Environment']}</td>";
				echo "<td class='ir'>{$value['GoldTime']}</td>";
				echo "<td class='ir'>{$value['CopperPrice']}</td>";
				if(Core::getObject('db')->checkConnection()) {
					$select = Core::getObject('db')->getConnection()->prepare("
						SELECT 	(
									SELECT SUM({$this->sql->votes->Score})
									FROM {$this->sql->votes['table']}
									WHERE {$this->sql->votes->ChallengeId} = a.{$this->sql->challenges->Id}
								) AS Score
						FROM {$this->sql->challenges['table']} AS a
						WHERE a.{$this->sql->challenges->Uid} = :uid");
					$select->bindParam('uid', $value['UId']);
					$select->execute();
					$row = $select->fetch(PDO::FETCH_OBJ);
					$select->closeCursor();
					$select = null;
					echo "<td class='ir'>". ($row->Score+0) ."</td>";
				}
				echo "</tr>";
			}
		} else {
			echo "<tr class='bg-l'>";
			echo "	<td colspan='"; if(Core::getObject('session')->checkPerm('editmaps')) { echo "6"; } else { echo "5"; } echo "'>".pt_nochallenges."</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
		echo "</div>";

		if(Core::getObject('session')->checkPerm('editmaps')) {
			echo "<fieldset>";
			echo "<div class='legend'>".pt_options."</div>";
			echo "  <div class='f-row'>
					<label for='action'></label>
					<div class='f-field'>
				  		<select name='action'>
							<option value='ChooseNextChallengeList'>".pt_choose."</option>
							<option value='RemoveChallengeList'>".pt_del."</option>
							<option value='ShuffleChallengeList'>".pt_shuffle."</option>
							<option value='SortChallengeList'>".pt_sort."</option>
						</select>
					</div>
				</div>";
			echo "	  <input type='hidden' name='plugin' value='{$this->id}' />
				  <button type='submit' class='wide' title='".ct_submit."'>".ct_submit."</button>";
			echo "</fieldset>";
			echo "</form>";
		}
	}

	private function readlist()
	{
		if(array_key_exists('challengelist', $_POST))
			$players = array_values($_POST['challengelist']);

		if (!empty($players)) {
			foreach($players as $value)
			{
				$this->list[] = urldecode($value);
			}
		}
	}

	public function RemoveChallengeList()
	{
		if(empty($this->list)) return;
		Core::getObject('actions')->add('RemoveChallengeList', $this->list);
	}

	public function ChooseNextChallengeList()
	{
		if(empty($this->list)) return;
		Core::getObject('actions')->add('ChooseNextChallengeList', $this->list);
	}

	public function ShuffleChallengeList()
	{
		if(empty($this->list)) return;
		shuffle($this->list);
		Core::getObject('actions')->add('ChooseNextChallengeList', $this->list);
	}

	public function SortChallengeList()
	{
		if(array_key_exists('sorting', $_POST))
			$sorting = array_values($_POST['sorting']);

		if(array_key_exists('sortdata', $_POST))
			$mydata = array_values($_POST['sortdata']);

		foreach($mydata as $value)
		{
			$sortdata[] = urldecode($value);
		}
		array_multisort($sorting, $sortdata);
		Core::getObject('actions')->add('ChooseNextChallengeList', $sortdata);
	}
}
?>