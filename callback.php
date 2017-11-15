<?php
/*
BLOCKONOMICS CALLBACK VAR :
    * status is the status of tx. 0-Unconfirmed, 1-Partially Confirmed, 2-Confirmed
    * addr is the receiving address
    * value is the recevied payment amount in satoshis
    * txid is the id of the paying transaction
*/
if(!isset($_REQUEST['status']) || empty($_REQUEST['addr']) || empty($_REQUEST['value']) || empty($_REQUEST['txid'])) return;
include(dirname(__FILE__).'/../../config.php');
if(file_exists(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/ssite.json'))
	{
	$q = file_get_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/ssite.json'); $b = json_decode($q,true);
	$mailAdmin = $b['mel'];
	}
else $mailAdmin = false;
include(dirname(__FILE__).'/../../template/mailTemplate.php');
$bottom = str_replace('[[unsubscribe]]','&nbsp;',$bottom);
$q = file_get_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/paycoin.json');
$a = json_decode($q,true);
if($a && !empty($_REQUEST['txid']) && !empty($_REQUEST['secret']) && $_REQUEST['secret']==$a['secret'])
	{
	// lang
	if(file_exists(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/users.json'))
		{
		$q = file_get_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/users.json');
		$a = json_decode($q,true);
		if(!empty($a['g'])) $lang = $a['g'];
		}
	include(dirname(__FILE__).'/lang/lang.php');
	//
	if(file_exists(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/tmp/cart'.$_REQUEST['addr'].'.json'))
		{
		if(VerifTXID($_REQUEST['txid'],$sdata)) return; // TXID already exists
		$q = file_get_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/tmp/cart'.$_REQUEST['addr'].'.json');
		$c = json_decode($q,true);
		$c['txid'] = $_REQUEST['txid'];
		$c['time'] = time();
		$c['status'] = $_REQUEST['status'];
		$c['gateway'] = 'Blockonomics';
		$c['treated'] = 0;
		file_put_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/paycoin'.$_REQUEST['txid'].'.json',json_encode($c));
		if(isset($c['mail'])) file_put_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/tmp/mail'.base64_encode($c['mail']).'.txt',$_REQUEST['txid']);
		unlink(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/tmp/cart'.$_REQUEST['addr'].'.json');
		}
	if($_REQUEST['status']==2 && VerifTXID($_REQUEST['txid'],$sdata))
		{
		if(empty($c))
			{
			$q = file_get_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/paycoin'.$_REQUEST['txid'].'.json');
			$c = json_decode($q,true);
			}
		if(empty($c['price']) || $c['price']!=$_REQUEST['value']) return;
		if($c['status']==2) return; // already treated
		if(!empty($c['digital']))
			{
			/*
			{"prod":[{"n":"Rencontre Premium","p":35,"i":"","q":1}],
			"digital":"index|rencontreP|29901714743043407",
			"Ubusy":index,
			"id":"5a0321071ab89",
			"rate":"6420.61",
			"price":"0.005451",
			"mail":"jean@free.fr"}
			*/
			$q = file_get_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/markdown.json'); $b1 = json_decode($q,true);
			$d = explode("|", $c['digital']); // 0/ busy ; 1/ shortcode (name) : 2/ key
			$busy = $d[0];
			$q = file_get_contents(dirname(__FILE__).'/../../data/'.$busy.'/site.json'); $b2 = json_decode($q,true);
			// copy & rename file
			$fi = dirname(__FILE__).'/../../../files/';
			if(!is_dir($fi.'upload/')) mkdir($fi.'upload/');
			if(!file_exists($fi.'upload/index.html')) file_put_contents($fi.'upload/index.html', '<html></html>');
			if(file_exists($fi.$d[1].'/'.$b1[$busy]['md'][$d[1]]['k'].$d[1].'.zip')) copy($fi.$d[1].'/'.$b1[$busy]['md'][$d[1]]['k'].$d[1].'.zip',$fi.'upload/'.$d[2].$d[1].'.zip');
			$zip = new ZipArchive;
			if($zip->open($fi.'upload/'.$d[2].$d[1].'.zip')===true)
				{
				$zip->addFromString($d[1].'/key.php', '<?php $key = "'.$d[2].'"; ?>');
				$zip->close();
				}
			if(!is_dir(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_digital/'))
				{
				mkdir(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_digital/');
				file_put_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_digital/index.html', '<html></html>');
				}
			file_put_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_digital/'.$d[2].$d[1].'.json', '{"t":"'.time().'","p":"paycoin","d":"'.$d[1].'","k":"'.$d[2].'"}');
			// link to zip in mail
			$msg = $d[1].'.zip :<br />'."\r\n".'<a href="'.$b2['url'].'/files/upload/'.$d[2].$d[1].'.zip">'.$b2['url'].'/files/upload/'.$d[2].$d[1].'.zip</a>'."\r\n<br /><br />\r\n".T_('Thank you for your trust, see you soon!')."\r\n";
			// MAIL USER LINK TO ZIP
			mailUser($c['mail'], 'Download - '.$d[1], $msg, $bottom, $top);
			}
		else if(!empty($c['mail']) && !empty($c['name']) && !empty($c['adre']))
			{
			/*			
			{"prod":[{"n":"Pomme","p":40,"i":"2qdGPomme","q":2,"t":0}],
			"ship":"0",
			"Utax":"|||",
			"curr":"EUR",
			"name":"jean BON",
			"adre":"60 rue Delon",
			"mail":"jean@free.fr",
			"Ubusy":"index",
			"id":"5a030ff2687cf",
			"rate":"6420.61",
			"price":"0.012460"}
			*/
			$name =  $c['name'];
			$adre = $c['adre'];
			$mail = $c['mail'];
			$busy = $c['Ubusy'];
			$q = file_get_contents(dirname(__FILE__).'/../../data/'.$busy.'/site.json'); $b2 = json_decode($q,true);
			$msgOrder = '<p style="text-align:right;">'.date("d/m/Y H:i").'</p><p>'; $b3 = 0; $p = 0; $n = 1;
			foreach($c['prod'] as $r)
				{
				if(!$b3) $b3=1;
				$msgOrder .= $r['q'].' x '.$r['n'].' ('.$r['p'].') = '.($r['q'] * $r['p']).' EUR<br />';
				$p += ($r['q'] * $r['p']);
				++$n;
				}
			if($mail && $busy)
				{
				$msgOrder .= '</p><p>'.T_('Total').' : <strong>'.$p.' &euro;</strong></p>';
				$msgOrder = str_replace(".",",",$msgOrder);
				$msgOrder .= '<p>'.T_('Paid by Paycoin').'.</p><hr /><p>'.T_('Name').' : '.$name.'<br />'.T_('Address').' : '.$adre.'<br />'.T_('Mail').' : '.$mail.'</p>';
				if($b3)
					{
					// MAIL ADMIN ORDER
					mailAdmin(T_('New order by Paycoin'). ' - '.$_REQUEST['addr'], $msgOrder, $bottom, $top, $b2['url']);
					// MAIL USER ORDER
					$iv = openssl_random_pseudo_bytes(16);
					$r = base64_encode(openssl_encrypt($_REQUEST['addr'].'|'.$mail, 'AES-256-CBC', substr($Ukey,0,32), OPENSSL_RAW_DATA, $iv));
					$info = "<a href='".stripslashes($b2['url']).'/uno/plugins/payment/paymentOrder.php?a=look&b='.urlencode($r).'&i='.base64_encode($iv)."&t=paycoin'>".T_("Follow the evolution of your order")."</a>";
					$msgOrderU = $msgOrder.'<br /><p>'.T_('Thank you for your trust.').'</p><p>'.$info.'</p>';
					mailUser($mail, $b2['tit'].' - '.T_('Order'), $msgOrderU, $bottom, $top, $b2['url'].'/'.$busy.'.html');
					}
				}
			}
		if($mailAdmin)
			{
			$msg = "<table>";
			foreach($c as $k=>$v) if(!empty($v)) $msg .= "<tr><td>".$k." : </td><td>".(is_array($v)?json_encode($v):$v)."</td></tr>\r\n";
			$msg .= "</table>\r\n";
			// MAIL ADMIN PAYMENT
			mailAdmin('Paycoin - '.T_('Payment receipt').' : '.$c['price'].' BTC', $msg, $bottom, $top, $b2['url']);
			}
		$c['time'] = time();
		$c['status'] = 2;
		file_put_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/paycoin'.$_REQUEST['txid'].'.json',json_encode($c));
		}
	}
//
function VerifTXID($txid, $sdata)
	{ // TXID exists ?
	if(file_exists(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/paycoin'.$txid.'.json')) return 1;
	return 0;
	}
//
function mailAdmin($tit, $msg, $bottom, $top, $url)
	{
	global $mailAdmin;
	$body = '<b><a href="'.$url.'/uno.php" style="color:#000000;">'.$tit.'</a></b><br />'."\r\n".$msg."\r\n";
	$msgT = strip_tags($body);
	$msgH = $top . $body . $bottom;
	if(file_exists(dirname(__FILE__).'/../newsletter/PHPMailer/PHPMailerAutoload.php'))
		{
		// PHPMailer
		require_once(dirname(__FILE__).'/../newsletter/PHPMailer/PHPMailerAutoload.php');
		$phm = new PHPMailer();
		$phm->CharSet = 'UTF-8';
		$phm->setFrom($mailAdmin);
		$phm->addReplyTo($mailAdmin);
		$phm->addAddress($mailAdmin);
		$phm->isHTML(true);
		$phm->Subject = stripslashes($tit);
		$phm->Body = stripslashes($msgH);		
		$phm->AltBody = stripslashes($msgT);
		if($phm->send()) return true;
		else return false;
		}
	else
		{
		$rn = "\r\n";
		$boundary = "-----=".md5(rand());
		$header = "From: ".$mailAdmin."<".$mailAdmin.">".$rn."Reply-To:".$mailAdmin."<".$mailAdmin.">MIME-Version: 1.0".$rn."Content-Type: multipart/alternative;".$rn." boundary=\"$boundary\"".$rn;
		$msg = $rn."--".$boundary.$rn."Content-Type: text/plain; charset=\"utf-8\"".$rn."Content-Transfer-Encoding: 8bit".$rn.$rn.$msgT.$rn;
		$msg .= $rn."--".$boundary.$rn."Content-Type: text/html; charset=\"utf-8\"".$rn."Content-Transfer-Encoding: 8bit".$rn.$rn.$msgH.$rn.$rn."--".$boundary."--".$rn.$rn."--".$boundary."--".$rn;
		$subject = mb_encode_mimeheader(stripslashes($tit),"UTF-8");
		if(mail($mailAdmin, $subject, stripslashes($msg), $header)) return true;
		else return false;
		}
	}
//
function mailUser($dest, $tit, $msg, $bottom, $top, $url=false)
	{
	global $mailAdmin;
	if($url) $body = '<b><a href="'.$url.'.html" style="color:#000000;">'.$tit.'</a></b><br />'."\r\n".$msg."\r\n";
	else $body = "<b>".$tit."</b><br />\r\n".$msg."\r\n";
	$msgT = strip_tags($body);
	$msgH = $top . $body . $bottom;
	if(file_exists(dirname(__FILE__).'/../newsletter/PHPMailer/PHPMailerAutoload.php'))
		{
		// PHPMailer
		require_once(dirname(__FILE__).'/../newsletter/PHPMailer/PHPMailerAutoload.php');
		$phm = new PHPMailer();
		$phm->CharSet = 'UTF-8';
		$phm->setFrom($mailAdmin);
		$phm->addReplyTo($mailAdmin);
		$phm->addAddress($dest);
		$phm->isHTML(true);
		$phm->Subject = stripslashes($tit);
		$phm->Body = stripslashes($msgH);		
		$phm->AltBody = stripslashes($msgT);
		if($phm->send()) return true;
		else return false;
		}
	else
		{
		$rn = "\r\n";
		$boundary = "-----=".md5(rand());
		$header = "From: ".$mailAdmin."<".$mailAdmin.">".$rn."Reply-To:".$mailAdmin."<".$mailAdmin.">MIME-Version: 1.0".$rn."Content-Type: multipart/alternative;".$rn." boundary=\"$boundary\"".$rn;
		$msg = $rn."--".$boundary.$rn."Content-Type: text/plain; charset=\"utf-8\"".$rn."Content-Transfer-Encoding: 8bit".$rn.$rn.$msgT.$rn;
		$msg .= $rn."--".$boundary.$rn."Content-Type: text/html; charset=\"utf-8\"".$rn."Content-Transfer-Encoding: 8bit".$rn.$rn.$msgH.$rn.$rn."--".$boundary."--".$rn.$rn."--".$boundary."--".$rn;
		$subject = mb_encode_mimeheader(stripslashes($tit),"UTF-8");
		if(mail($dest, $subject, stripslashes($msg), $header)) return true;
		else return false;
		}
	}
?>
