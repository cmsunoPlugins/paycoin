<?php
session_start(); 
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])!='xmlhttprequest') {sleep(2);exit;} // ajax request
if(!isset($_POST['unox']) || $_POST['unox']!=$_SESSION['unox']) {sleep(2);exit;} // appel depuis uno.php
?>
<?php
include('../../config.php');
if (!is_dir('../../data/_sdata-'.$sdata.'/_paycoin/')) mkdir('../../data/_sdata-'.$sdata.'/_paycoin/',0711);
if (!is_dir('../../data/_sdata-'.$sdata.'/_paycoin/tmp/')) mkdir('../../data/_sdata-'.$sdata.'/_paycoin/tmp/');
include('lang/lang.php');
// ********************* actions *************************************************************************
if (isset($_POST['action']))
	{
	switch ($_POST['action'])
		{
		// ********************************************************************************************
		case 'plugin': ?>
		<link rel="stylesheet" type="text/css" media="screen" href="uno/plugins/paycoin/paycoin.css" />
		<div class="blocForm">
			<div id="paycoinA" class="bouton fr" onClick="f_paycoinArchiv();" title="<?php echo T_("Archives");?>"><?php echo T_("Archives");?></div>
			<div id="paycoinC" class="bouton fr" onClick="f_paycoinConfig();" title="<?php echo T_("Configure Paycoin plugin");?>"><?php echo T_("Config");?></div>
			<div id="paycoinV" class="bouton fr current" onClick="f_paycoinVente();" title="<?php echo T_("Sales list");?>"><?php echo T_("Sales");?></div>
			<div id="paycoinD" class="bouton fr current" title="<?php echo T_("Payment Details");?>" style="display:none;"><?php echo T_("Payment Details");?></div>
			<h2><?php echo T_("Paycoin");?></h2>
			<div id="paycoinConfig" style="display:none;">
				<img style="float:right;margin:10px;" src="uno/plugins/paycoin/img/paycoinLogo.png" />
				<p><?php echo T_("Accept easily Bitcoin payments in any wallet with the blockonomics gateway.");?></p>
				<p><?php echo T_("Create your free account on");?>&nbsp;<a href='https://www.blockonomics.co/'>Blockonomics</a>.</p>
				<p><?php echo T_("JQuery is required. See settings tab.");?></p>
				<h3><?php echo T_("Default Settings");?> :</h3>
				<table class="hForm">
					<tr>
						<td><label><?php echo T_("Activate");?></label></td>
						<td><input type="checkbox" class="input" name="activ" id="activ" /></td>
						<td><em><?php echo T_("Enable the payment with Bitcoin");?></em></td>
					</tr>
					<tr>
						<td><label><?php echo T_("API key");?></label></td>
						<td><input type="text" class="input" name="paycoinKey" id="paycoinKey" style="width:300px;" /></td>
						<td><em><?php echo T_("Blockonomics API key generated from Wallet Watcher > Settings.");?></em></td>
					</tr>
					<tr>
						<td><label><?php echo T_("Callback URL");?></label></td>
						<td id="paycoinSec" style="vertical-align:middle;padding:0 10px;"><?php echo substr($_SERVER['HTTP_REFERER'],0,-4).'/plugins/paycoin/callback.php?secret='; ?></td>
						<td><em><?php echo T_("Copy this url and set it in your Blockonomics account > Merchants"); ?></em></td>
					</tr>
				</table>
				<br />
				<div id="btSavePaycoin" class="bouton fr" onClick="f_save_paycoin();" title="<?php echo T_("Save settings");?>"><?php echo T_("Save");?></div>
				<div class="clear"></div>
			</div>
			<div id="paycoinDetail" style="display:none;"></div>
			<div id="paycoinArchiv" style="display:none;"></div>
			<div id="paycoinVente"></div>
		</div>
		<?php break;
		// ********************************************************************************************
		case 'load':
		if(file_exists('../../data/_sdata-'.$sdata.'/paycoin.json'))
			{
			$q = file_get_contents('../../data/_sdata-'.$sdata.'/paycoin.json');
			echo stripslashes($q);
			}
		else echo '[]';
		break;
		// ********************************************************************************************
		case 'save':
		$q = file_get_contents('../../data/busy.json'); $a = json_decode($q,true); $home = $a['nom'];
		$a = Array();
		if(file_exists('../../data/_sdata-'.$sdata.'/paycoin.json'))
			{
			$q = file_get_contents('../../data/_sdata-'.$sdata.'/paycoin.json');
			if($q) $a = json_decode($q,true);
			}
		$a['act'] = $_POST['act'];
		if(file_exists('../../data/payment.json'))
			{
			$q = file_get_contents('../../data/payment.json'); $b = json_decode($q,true);
			if(empty($b['method'])) $b['method'] = array();
			$b['method']['paycoin'] = $_POST['act'];
			file_put_contents('../../data/payment.json',json_encode($b));
			}
		$a['key'] = $_POST['key'];
		$a['url'] = substr($_SERVER['HTTP_REFERER'],0,-4).'/plugins/paycoin/callback.php';
		$a['par'] = '../../../data/_sdata-'.$sdata.'/_paycoin';
		$a['round'] = 6; // BTC float round
		if(empty($a['secret'])) $a['secret'] = sha1(openssl_random_pseudo_bytes(20));
		$out = json_encode($a);
		if(file_put_contents('../../data/_sdata-'.$sdata.'/paycoin.json', $out)) echo T_('Setup OK');
		else echo '!'.T_('Impossible setup');
		break;
		// ********************************************************************************************
		case 'vente':
		echo '<h3>'.T_("List of the Paycoin payments").' :</h3>';
		echo '<style>
				#paycoinVente table tr{border-bottom:1px solid #888;}
				#paycoinVente table th{text-align:center;padding:5px 2px;font-weight:700;}
				#paycoinVente table td{text-align:left;padding:2px 6px;vertical-align:middle;color:#0b4a6a;}
				#paycoinVente table tr.PaycoinTreatedYes td{color:green;}
				#paycoinVente table td.yesno{text-decoration:underline;cursor:pointer;}
				#paycoinVente .paycoinArchiv{width:16px;height:16px;margin:0 auto;background-position:-112px -96px;cursor:pointer;background-image:url("'.$_POST['udep'].'includes/img/ui-icons_444444_256x240.png")}
			</style>';
		$tab = array(); $d = '../../data/_sdata-'.$sdata.'/_paycoin/';
		if($dh=opendir($d))
			{
			while(($file = readdir($dh))!==false) { if($file!='.' && $file!='..') $tab[] = $d.$file; }
			closedir($dh);
			}
		if(count($tab))
			{
			echo '<br /><table>';
			echo '<tr><th>'.T_("Date").'</th><th>'.T_("Type").'</th><th>'.T_("Name").'</th><th>'.T_("Address").'</th><th>'.T_("Article").'</th><th>'.T_("Price").'</th><th>'.T_("Treated").'</th><th>'.T_("Del").'</th><th>'.T_("Archive").'</th></tr>';
			$b = array();
			foreach($tab as $r)
				{
				$q = @file_get_contents($r);
				$a = json_decode($q,true);
				$b[] = $a;
				}
			function sortTime($u1,$u2) {return (isset($u2['time'])?$u2['time']:0) - (isset($u1['time'])?$u1['time']:0);}
			usort($b, 'sortTime');
			foreach($b as $r)
				{
				if($r)
					{
					$item = '';
					$st = (isset($r['status'])?$r['status']:0);
					if($st==2) $typ = T_("Paid");
					else $typ = T_("Uncertain");
					if(!empty($r['digital'])) $typ .= '<br />(Digital)';
					echo '<tr'.($r['treated']?' class="PaycoinTreatedYes"':'').'>';
					echo '<td>'.(isset($r['time'])?date("dMy H:i", $r['time']):'').'<br /><span style="font-size:.8em;text-decoration:underline;cursor:pointer;" onClick="f_paycoinDetail(\''.$r['txid'].'\')">'.$r['txid'].'</span></td>';
					echo '<td style="text-align:center">'.$typ.'</td>';
					echo '<td>'.(!empty($r['name'])?$r['name'].'<br />':'').$r['mail'].'</td>';
					echo '<td style="text-align:center">'.'/'.'</td>'; // Added later
					echo '<td>'.json_encode($r['prod']).'</td>';
					echo '<td>'.$r['price'].' BTC</td>';
					echo '<td style="text-align:center;" '.(!$r['treated']?'onClick="f_treated_paycoin(this,\''.$r['txid'].'\',\''.T_("Yes").'\')"':'').($r['treated']?'>'.T_("Yes"):' class="yesno">'.T_("No")).'</td>';
					echo '<td></td>';
					echo '<td><div class="paycoinArchiv" onClick="f_archivOrderPaycoin(\''.$r['txid'].'\',\''.T_("Are you sure ?").'\')"></div></td>';
					echo '</tr>';
					}
				}
			echo '</table>';
			}
		break;
		// ********************************************************************************************
		case 'treated':
		if(file_exists('../../data/_sdata-'.$sdata.'/_paycoin/paycoin'.$_POST['id'].'.json'))
			{
			$q = file_get_contents('../../data/_sdata-'.$sdata.'/_paycoin/paycoin'.$_POST['id'].'.json');
			if($q)
				{
				$a = json_decode($q,true);
				$a['treated'] = 1;
				$out = json_encode($a);
				if(file_put_contents('../../data/_sdata-'.$sdata.'/_paycoin/paycoin'.$_POST['id'].'.json', $out)) echo T_('Treated');
				exit;
				}
			}
		echo '!'.T_('Error');
		break;
		// ********************************************************************************************
		case 'restaur':
		$d = $_POST['f'];
		$a = explode('__',$d);
		if(count($a)>2) $d1 = $a[0].'.json';
		else $d1 = $d;
		if(file_exists('../../data/_sdata-'.$sdata.'/_paycoin/archive/'.$d) && rename('../../data/_sdata-'.$sdata.'/_paycoin/archive/'.$d, '../../data/_sdata-'.$sdata.'/_paycoin/'.$d1)) echo T_('Restored');
		else echo '!'.T_('Error');
		break;
		// ********************************************************************************************
		case 'archiv':
		$p = '../../data/_sdata-'.$sdata.'/_paycoin/archive';
		if(!is_dir($p)) mkdir($p,0711);
		$d = 'paycoin'.$_POST['id'].'.json';
		$q = file_get_contents('../../data/_sdata-'.$sdata.'/_paycoin/'.$d);
		if($q) $a = json_decode($q,true);
		else $a = array();
		if(!empty($a['time']) && !empty($a['price']))
			{
			$d1 = substr($d,0,-5).'__'.$a['time'].'__'.$a['price'].'__.json';
			}
		else $d1 = $d;
		if(file_exists('../../data/_sdata-'.$sdata.'/_paycoin/'.$d) && rename('../../data/_sdata-'.$sdata.'/_paycoin/'.$d, $p.'/'.$d1)) echo T_('Archived');
		else echo '!'.T_('Error');
		break;
		// ********************************************************************************************
		case 'viewArchiv':
		$p = '../../data/_sdata-'.$sdata.'/_paycoin/archive';
		if(is_dir($p) && $h=opendir($p))
			{
			$b = array();
			while(($d=readdir($h))!==false)
				{
				$ext=explode('.',$d); $ext=$ext[count($ext)-1];
				if($d!='.' && $d!='..' && $ext=='json')
					{
					if(strpos($d,'__')!==false)
						{
						$a = explode('__',$d);
						if(count($a)>2) $b[] = array('idTransaction'=>$a[0], 'time'=>$a[1], 'amount'=>$a[2], 'file'=>$d);
						}
					else
						{
						$q = file_get_contents($p.'/'.$d);
						if($q) $a = json_decode($q,true);
						else $a = array();
						if(!empty($a['time']) && !empty($a['amount']))
							{
							$d1 = substr($d,0,-5).'__'.$a['time'].'__'.$a['amount'].'__.json';
							rename($p.'/'.$d, $p.'/'.$d1);
							}
						}
					
					}
				}
			closedir($h);
			usort($b, function($f,$g) { return $g['time'] - $f['time'];});
			$o = '<div id="paycoinArchData"></div><div>';
			foreach($b as $r)
				{
				$o .= '<div class="paycoinListArchiv" onClick="f_paycoinViewA(\''.$r['file'].'\');">'.$r['idTransaction'].' - '.date('dMy',$r['time']).' - '.substr($r['amount'],0,-2).'&euro;</div>';
				}
			echo $o.'</div><div style="clear:left;"></div>';
			}
		break;
		// ********************************************************************************************
		case 'viewA':
		if(isset($_POST['arch']) && file_exists('../../data/_sdata-'.$sdata.'/_paycoin/archive/'.$_POST['arch']))
			{
			$q = @file_get_contents('../../data/_sdata-'.$sdata.'/_paycoin/archive/'.$_POST['arch']);
			$a = json_decode($q,true); $o = '<h3>'.T_('Archives').'</h3><table class="paycoinTO">';
			foreach($a as $k=>$v)
				{
				if($k=='time') $v .= ' => '.date("d/m/Y H:i",$v);
				$o .= '<tr><td>'.$k.'</td><td>'.(is_array($v)?json_encode($v):$v).'</td></tr>';
				}
			echo $o.'</table><div class="bouton fr" onClick="f_paycoinRestaurOrder(\''.$_POST['arch'].'\');" title="'.T_("Restore").'">'.T_("Restore").'</div><div style="clear:both;"></div>';
			}
		break;
		// ********************************************************************************************
		case 'detail':
		if(isset($_POST['id']) && file_exists('../../data/_sdata-'.$sdata.'/_paycoin/'.$_POST['id'].'.json'))
			{
			$q = @file_get_contents('../../data/_sdata-'.$sdata.'/_paycoin/'.$_POST['id'].'.json');
			$a = json_decode($q,true); $o = '<h3>'.T_('Payment Details').'</h3><table class="paycoinTO">';
			foreach($a as $k=>$v)
				{
				if($k=='time') $v .= ' => '.date("d/m/Y H:i",$v);
				$o .= '<tr><td>'.$k.'</td><td>'.(is_array($v)?json_encode($v):$v).'</td></tr>';
				}
			$o .= '</table>';
			$o .= '<div class="bouton fr" '.((isset($a['treated']) && $a['treated']==0)?'style="display:none;"':'').' onClick="f_archivOrderPaycoin(\''.$_POST['id'].'\',\''.T_("Are you sure ?").'\')" title="">'.T_("Archive").'</div>';
			$o .= '<div style="clear:both;"></div>';
			echo $o;
			}
		else echo '!'.T_('Error');
		break;
		// ********************************************************************************************
		}
	clearstatcache();
	exit;
	}
?>
