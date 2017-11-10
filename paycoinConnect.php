<?php
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])!='xmlhttprequest') {sleep(2);exit;} // ajax request
include('../../config.php'); // $sdata;
include('lang/lang.php');
// ***** ACTION : URL *******************
if (isset($_POST['action']))
	{
	switch($_POST['action'])
		{
		// ******************************************************
		case 'mail':
		$o = '<div>';
		$o .= '<p>'.T_("Your email is required to get the download link").'</p>';
		$o .= '<div>'.T_("Email").' : <input type="text" id="paycoinMail" name="mail" value="" /></div>';
		$o .= '<div style="text-align:right;margin-top:10px;"><input type="button" value="'.T_("Send").'" onclick="jQuery(\'#paycoinMailError\').empty();paycoinPrepare(document.getElementById(\'paycoinMail\').value)" /></div>';
		$o .= '<div id="paycoinMailError" style="color:red"></div>';
		$o .= '</div>';
		echo $o;
		break;
		// ******************************************************
		case 'prepare':
		$reset = paycoin_del_tmp($sdata);
		$amo = 0;
		$a = json_decode(strip_tags(stripslashes($_POST['cart'])),true);
		if(isset($a['prod'])) foreach($a['prod'] as $r) $amo += (floatval((isset($r['p'])?$r['p']:'0')) * (isset($r['q'])?$r['q']:'1'));
		if(isset($a['ship']) && $a['ship']) $amo += floatval($a['ship']); // Shipping Cost
		$q = file_get_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/paycoin.json');
		$b = json_decode($q,true);
		$secret = (!empty($b['secret'])?$b['secret']:0);
		$key = (!empty($b['key'])?$b['key']:0);
		$round = (!empty($b['round'])?$b['round']:6);
		$c = array();
		$c['address'] = blockonomics_get_new_adress($key,$secret,$reset);
		$c['rate'] = blockonomics_get_rate('EUR');
		$c['price'] = number_format($amo/$c['rate']+pow(.1,$round+1),$round,'.',''); // 1 satoshi = 0.00000001 BTC
		$c['lang'] = array(
			'tit'=>T_('Bitcoin Payment'),
			'inst'=>T_('Send the exact amount at the following address'),
			'addr'=>T_('Address'),
			'amo'=>T_('Amount'),
			'info1'=>T_('Copy the address or use the QRcode.'),
			'info2'=>T_('If you send another bitcoin amount, payment system will ignore you !'),
			'info3'=>T_('The check of the transaction can take a few minutes. Keep this window open.')
			);
		$c['t'] = time();
		$out = json_encode($c);
		$cart = $a;
		$cart['btcadr'] = $c['address'];
		$cart['status'] = 0;
		$cart['rate'] = $c['rate'];
		$cart['price'] = $c['price'];
		if(!empty($_POST['mail'])) $cart['mail'] = strip_tags($_POST['mail']);
		if(!empty($_POST['name'])) $cart['name'] = strip_tags($_POST['name']);
		if(!empty($_POST['adre'])) $cart['adre'] = strip_tags($_POST['adre']);
		if(isset($a['digital']) && !empty($_POST['ran'])) $cart['digital'] = $a['digital'].'|'.strip_tags($_POST['ran']);
		if(file_put_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/tmp/cart'.$c['address'].'.json', json_encode($cart))) echo $out;
		break;
		// ******************************************************
		case 'check':
		$adr = strip_tags($_POST['adr']);
		$o = 'no';
		if(file_exists(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/tmp/cart'.$adr.'.json'))
			{
			$q = file_get_contents(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/tmp/cart'.$adr.'.json'); $a = json_decode($q,true);
			if(!empty($a['status']) && $a['status']==2)
				{
				$o = 'ok';
				unlink(dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/tmp/cart'.$adr.'.json');
				if(!empty($a['Ubusy']))
					{
					$busy = $a['Ubusy'];
					$q = file_get_contents(dirname(__FILE__).'/../../data/'.$busy.'/site.json'); $b = json_decode($q,true);
					if(!empty($b['url']))
						{
						$o .= $b['url'].'?paycoin=ok';
						if(!empty($a['digital'])) $o .= '&digit='.$a['digital'];
						}
					}
				}
			}
		echo $o;
		break;
		// ******************************************************
		}
	}
function blockonomics_get_new_adress($key,$secret,$reset=0)
	{
	// return '189CEMECgP36iXpCKQoBbRQn3dTCUPi5dm';
	$opt = array('http'=>array(
		'header'=>'Authorization: Bearer '.$key,
		'method'=>'POST',
		'content'=>''
		));
	$context = stream_context_create($opt);
	if($secret) $q = @file_get_contents('https://www.blockonomics.co/api/new_address?match_callback='.$secret.($reset?'&reset=1':''), false, $context);
	else $q = @file_get_contents('https://www.blockonomics.co/api/new_address'.($reset?'?reset=1':''), false, $context);
	if($q)
		{
		$out = json_decode($q);
		return $out->address;
		}
	}
function blockonomics_get_rate($currency) // Exchange rate - EUR, GBP, USD ...
	{
	// return '6420.61';
	$opt = array('http'=>array('method' =>'GET'));
	$context = stream_context_create($opt);
	$q = @file_get_contents('https://www.blockonomics.co/api/price?currency='.$currency, false, $context);
	if($q)
		{
		$out = json_decode($q);
		return number_format($out->price,8,'.','');
		}
	}
function paycoin_del_tmp($sdata,$delay=172800) // 48h
	{
	$d = dirname(__FILE__).'/../../data/_sdata-'.$sdata.'/_paycoin/tmp/';
	$h = @opendir($d);
	$reset = 1;
	$c = 0;
	if($h)
		{
		while(($f=readdir($h))!==false)
			{
			if($f=='.' || $f=='..') continue;
			if(is_file($d.$f) && substr($f,0,4)=='cart')
				{
				++$c;
				if(filemtime($d.$f)>time()-7200) $reset = 0; // < 2h
				if(filemtime($d.$f)<time()-$delay) @unlink($d.$f);
				}
			}
		if($c>47) $reset = 1;
		@closedir($h);
		}
	return $reset;
	}
?>
