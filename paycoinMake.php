<?php
if (!isset($_SESSION['cmsuno'])) exit();
?>
<?php
if(file_exists('data/_sdata-'.$sdata.'/paycoin.json'))
	{
	$q1 = file_get_contents('data/_sdata-'.$sdata.'/paycoin.json');
	$a1 = json_decode($q1,true);
	if(1)
		{
		// JSON : {"prod":{"0":{"n":"clef de 12","p":8.5,"i":"","q":1},"1":{"n":"tournevis","p":1.5,"i":"","q":2},"2":{"n":"papier craft","p":0.21,"i":"","q":30}},"digital":"Ubusy|readme","ship":"4","name":"Sting","adre":"rue du lac 33234 PLOUG"}
		// n=nom, p=prix, i=ID, q=quantite
		// OK : ?paycoin=ok&digit=mapage|monplugin|123456789123
		$Ufoot .= '<script type="text/javascript" src="uno/plugins/paycoin/paycoinInc.js"></script>'."\r\n";
		$Ufoot .= '<script type="text/javascript" src="uno/plugins/paycoin/qrcode.min.js"></script>'."\r\n";
		$Uonload .= "if('ok'==unoGvu('paycoin')){unoPop('".T_('Thank you for your payment')."',15000);document.cookie='cart=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';}";
		$unoPop=1; // include unoPop.js in output
		}
	}
?>
