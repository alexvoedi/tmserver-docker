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
class WebStats extends rcp_plugin
{
	public  $display		= 'box';
	public  $title			= 'WebStats';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservcon		= false;
	public  $nsqlcon		= true;
	private $mode			= false;
	private $modes			= array('votes','players');
	private $limit			= array();
	private $order			= array();
	private $sql;

	public function onLoad()
	{
		$this->sql = Core::getObject('settings')->database;

		if(in_array($_REQUEST['mode'], $this->modes))
			$this->mode = $_REQUEST['mode'];
		else
			$this->mode = 'votes';

		$this->limit[0] = array_key_exists('limit', $_REQUEST) ? $_REQUEST['limit'] : 0;
		$this->limit[1] = array_key_exists('count', $_REQUEST) ? $_REQUEST['count'] : 25;
	}

	public function onOutput()
	{
		//Get db connection
		$db = Core::getObject('db')->getConnection();

		switch($this->mode) {
			case 'votes':
				$this->order[0] = array_key_exists('orderby', $_REQUEST)  ? $_REQUEST['orderby'] : 'Karma';
				$this->order[1] = array_key_exists('orderdir', $_REQUEST) ? $_REQUEST['orderdir'] : 'desc';

				$db = Registry::getObject('db')->getConnection();
				$select = $db->query("
					SELECT	c.{$this->sql->challenges->Id} AS Id,
							c.{$this->sql->challenges->Name} AS Name,
							c.{$this->sql->challenges->Author} AS Author,
							c.{$this->sql->challenges->Environment} AS Environment,
							SUM(v.{$this->sql->votes->Score}) AS Karma,
							(	SELECT COUNT(pv.{$this->sql->votes->Score})
								FROM {$this->sql->votes['table']} AS pv
								WHERE pv.{$this->sql->votes->Score} > 0 && pv.{$this->sql->votes->ChallengeId} = v.{$this->sql->votes->ChallengeId}
							) AS KarmaPositive,
							(	SELECT COUNT(pn.{$this->sql->votes->Score})
								FROM {$this->sql->votes['table']} AS pn
								WHERE pn.{$this->sql->votes->Score} < 0 && pn.{$this->sql->votes->ChallengeId} = v.{$this->sql->votes->ChallengeId}
							) AS KarmaNegative
					FROM {$this->sql->votes['table']} AS v
					LEFT JOIN {$this->sql->challenges['table']} AS c
						ON v.{$this->sql->votes->ChallengeId} = c.{$this->sql->challenges->Id}
					GROUP BY v.{$this->sql->votes->ChallengeId}
					ORDER BY {$this->order[0]} {$this->order[1]}
					LIMIT {$this->limit[0]},{$this->limit[1]}
				");
			break;

			case 'players':
				$this->order[0] = array_key_exists('orderby', $_REQUEST)  ? $_REQUEST['orderby'] : (string) $this->sql->players->NickName;
				$this->order[1] = array_key_exists('orderdir', $_REQUEST) ? $_REQUEST['orderdir'] : 'asc';

				$select = $db->query("
					SELECT	p.{$this->sql->players->Id} AS Id,
							p.{$this->sql->players->Login} AS Login,
							p.{$this->sql->players->NickName} AS NickName,
							p.{$this->sql->players->UpdatedAt} AS UpdatedAt,
							p.{$this->sql->players->Wins} AS Wins,
							p.{$this->sql->players->TimePlayed} AS TimePlayed,
							p.{$this->sql->players->TeamId} AS TeamId,
							t.{$this->sql->teams->Name} AS TeamName,
							(	SELECT COUNT(r.{$this->sql->records->Score})
								FROM {$this->sql->records['table']} AS r
								WHERE r.{$this->sql->records->PlayerId} = p.{$this->sql->players->Id}
							) AS RecordsInDB,
							(	SELECT COUNT(v.{$this->sql->votes->Score})
								FROM {$this->sql->votes['table']} AS v
								WHERE v.{$this->sql->votes->PlayerId} = p.{$this->sql->players->Id}
							) AS VotesInDB,
							(	SELECT COUNT(c.{$this->sql->challenges->Id})
								FROM {$this->sql->challenges['table']} AS c
								WHERE c.{$this->sql->challenges->Author} = p.{$this->sql->players->Login}
							) AS ChallengesInDB,
							(	SELECT SUM(cpt1.{$this->sql->cptransactions->Coppers})
								FROM {$this->sql->cptransactions['table']} AS cpt1
								WHERE cpt1.{$this->sql->cptransactions->Login} = p.{$this->sql->players->Login} && cpt1.{$this->sql->cptransactions->Coppers} > 0
							) AS CoppersSpent,
							ABS((SELECT SUM(cpt1.{$this->sql->cptransactions->Coppers})
								FROM {$this->sql->cptransactions['table']} AS cpt1
								WHERE cpt1.{$this->sql->cptransactions->Login} = p.{$this->sql->players->Login} && cpt1.{$this->sql->cptransactions->Coppers} < 0
							)) AS CoppersReceived
					FROM {$this->sql->players['table']} AS p
					LEFT JOIN {$this->sql->teams['table']} AS t
						ON p.{$this->sql->players->TeamId} = t.{$this->sql->teams->Id}
					ORDER BY {$this->order[0]} {$this->order[1]}
					LIMIT {$this->limit[0]},{$this->limit[1]}
				");
			break;
		}

		header("Content-Type: text/xml");
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";
		echo "<result>\r\n";
		while($content = $select->fetch(PDO::FETCH_OBJ))
		{
			switch($this->mode) {
				case 'votes':
					echo "\t<item>\r\n";
					echo "\t\t<Id>{$content->Id}</Id>\r\n";
					echo "\t\t<Name>". urlencode($content->Name) ."</Name>\r\n";
					echo "\t\t<Author>{$content->Author}</Author>\r\n";
					echo "\t\t<Environment>{$content->Environment}</Environment>\r\n";
					echo "\t\t<Karma>{$content->Karma}</Karma>\r\n";
					echo "\t\t<KarmaPositive>{$content->KarmaPositive}</KarmaPositive>\r\n";
					echo "\t\t<KarmaNegative>{$content->KarmaNegative}</KarmaNegative>\r\n";
					echo "\t</item>\r\n";
				break;

				case 'players':
					echo "\t<item>\r\n";
					echo "\t\t<Id>{$content->Id}</Id>\r\n";
					echo "\t\t<Login>{$content->Login}</Login>\r\n";
					echo "\t\t<NickName>". urlencode($content->NickName) ."</NickName>\r\n";
					echo "\t\t<UpdatedAt>{$content->UpdatedAt}</UpdatedAt>\r\n";
					echo "\t\t<Wins>{$content->Wins}</Wins>\r\n";
					echo "\t\t<TimePlayed>{$content->TimePlayed}</TimePlayed>\r\n";
					echo "\t\t<TeamId>{$content->TeamId}</TeamId>\r\n";
					echo "\t\t<TeamName>". urlencode($content->TeamName) ."</TeamName>\r\n";
					echo "\t\t<RecordsInDB>{$content->RecordsInDB}</RecordsInDB>\r\n";
					echo "\t\t<VotesInDB>{$content->VotesInDB}</VotesInDB>\r\n";
					echo "\t\t<ChallengesInDB>{$content->ChallengesInDB}</ChallengesInDB>\r\n";
					echo "\t\t<CoppersSpent>{$content->CoppersSpent}</CoppersSpent>\r\n";
					echo "\t\t<CoppersReceived>{$content->CoppersReceived}</CoppersReceived>\r\n";
					echo "\t</item>\r\n";
				break;
			}
		}
		echo "</result>\r\n";
	}
}
?>