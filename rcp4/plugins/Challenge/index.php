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
class Challenge extends rcp_plugin
{
	public  $display		= 'box';
	public  $title			= 'Challenge Info';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservstatus	= array(3,4,5);
	public  $vpermissions	= array('viewmaps');
	private $file;
    
	public function onLoad()
	{
		$this->file = (array_key_exists('file', $_REQUEST)) ? urldecode($_REQUEST['file']) : '';
	}

	public function onOutput()
	{
		if(Core::getObject('gbx')->query('GetChallengeInfo', $this->file)) {
			$ChallengeInfo = Core::getObject('gbx')->getResponse();

			//Get TMX add. data
			$file = Core::getObject('web')->checkURL('http://united.tm-exchange.com/apiget.aspx?action=apitrackinfo&uid='. $ChallengeInfo['UId'], 'file');
			if(!empty($file) && is_array($file) && $file[0]) { 
				if(stristr($file[0], Chr(27))) {
					$tmx = false;
				} else {
					$file = explode(Chr(9), $file[0]);
					$tmx = array(
						'id'		=> $file[0],
						'userid'	=> $file[2],
						'uploaded'	=> $file[4],
						'updated'	=> $file[5],
						'type'		=> $file[10],
						'routes'	=> $file[11],
						'lenght'	=> $file[12],
						'difficult'	=> $file[13],
						'records'	=> $file[14],
						'imgurl'	=> 'http://united.tm-exchange.com/get.aspx?action=trackscreen&id='. $file[0]
					);
				}
			} else {
				trigger_error(pt_tmxerr);
			}

			$ChallengeInfo = array(
				'Name'			=> Core::getObject('tparse')->toHTML($ChallengeInfo['Name']),
				'UId'			=> $ChallengeInfo['UId'], 
				'FileName'		=> str_replace('Challenges/Downloaded/', '',$ChallengeInfo['FileName']),
				'Author'		=> Core::getObject('tparse')->toHTML($ChallengeInfo['Author']),
				'Environment'	=> $ChallengeInfo['Environnement'],
				'Mood'			=> $ChallengeInfo['Mood'],
				'BronzeTime'	=> Core::getObject('tparse')->toRaceTime($ChallengeInfo['BronzeTime']),
				'SilverTime'	=> Core::getObject('tparse')->toRaceTime($ChallengeInfo['SilverTime']),
				'GoldTime'		=> Core::getObject('tparse')->toRaceTime($ChallengeInfo['GoldTime']),
				'AuthorTime'	=> Core::getObject('tparse')->toRaceTime($ChallengeInfo['AuthorTime']),
				'CopperPrice'	=> number_format($ChallengeInfo['CopperPrice'], 0, ',', '.'),
				'LapRace'		=> (empty($ChallengeInfo['LapRace'])) ? 1 : $ChallengeInfo['LapRace'],
				'tmx'			=> $tmx
			);
		}
		?>		
		<fieldset>
			<div class='legend'><?php echo pt_gcd; ?></div>
			<div class='f-row'>
				<label><?php echo pt_name; ?></label>
				<div class='f-field'><?php echo $ChallengeInfo['Name']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_author; ?></label>
				<div class='f-field'><?php echo $ChallengeInfo['Author']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_env; ?></label>
				<div class='f-field'><?php echo $ChallengeInfo['Environment']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_mood; ?></label>
				<div class='f-field'><?php echo $ChallengeInfo['Mood']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_atime; ?></label>
				<div class='f-field'><?php echo $ChallengeInfo['AuthorTime']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_gtime; ?></label>
				<div class='f-field'><?php echo $ChallengeInfo['GoldTime']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_stime; ?></label>
				<div class='f-field'><?php echo $ChallengeInfo['SilverTime']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_btime; ?></label>
				<div class='f-field'><?php echo $ChallengeInfo['BronzeTime']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_coppers; ?></label>
				<div class='f-field'><?php echo $ChallengeInfo['CopperPrice']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_laps; ?></label>
				<div class='f-field'><?php echo $ChallengeInfo['LapRace']; ?></div>
			</div>
		</fieldset>

		<?php
		if($ChallengeInfo['tmx']['id']) {
			?>
			<fieldset>
				<div class='legend'><?php echo pt_tmx; ?> (<a href='http://united.tm-exchange.com/main.aspx?action=trackshow&id=<?php echo $ChallengeInfo['tmx']['id']; ?>#auto' target='_blank'>united.tm-exchange.com</a>)</div>
				<div class='f-row'>
					<label><?php echo pt_type; ?></label>
					<div class='f-field'><?php echo $ChallengeInfo['tmx']['type']; ?></div>
				</div>
				<div class='f-row'>
					<label><?php echo pt_routes; ?></label>
					<div class='f-field'><?php echo $ChallengeInfo['tmx']['routes']; ?></div>
				</div>
				<div class='f-row'>
					<label><?php echo pt_length; ?></label>
					<div class='f-field'><?php echo $ChallengeInfo['tmx']['lenght']; ?></div>
				</div>
				<div class='f-row'>
					<label><?php echo pt_diff; ?></label>
					<div class='f-field'><?php echo $ChallengeInfo['tmx']['difficult']; ?></div>
				</div>
				<div class='f-row'>
					<label><?php echo pt_records; ?></label>
					<div class='f-field'><?php echo $ChallengeInfo['tmx']['records']; ?></div>
				</div>
			</fieldset>
			<?php
		}
	}
}
?>