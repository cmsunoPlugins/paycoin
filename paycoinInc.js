var paycoinMyCart,paycoinMail='';
function paycoinCart(f){
	paycoinMyCart=f;
	var p=JSON.parse(f);
	if(typeof p.digital!=="undefined"){
		jQuery.post('uno/plugins/paycoin/paycoinConnect.php',{'action':'mail'},function(r){
			jQuery("#unoPop .unoPopContent").empty().append(r);
		});
	}
	else if(typeof p.mail!=="undefined")paycoinPrepare(p.mail);
}
function paycoinPrepare(m){
	if(m.match(/^[a-z0-9\._-]+@([a-z0-9_-]+\.)+[a-z]{2,6}$/i)==null){
		document.getElementById("paycoinMailError").innerHTML="Wrong Email !";
		return;
	}
	paycoinMail=m;
	var da={'action':'prepare','unox':0,'cur':'EUR','cart':paycoinMyCart,'mail':m,'ran':Math.random().toString().substr(2)}
	jQuery.ajax({type:"POST",url:'uno/plugins/paycoin/paycoinConnect.php',data:da,dataType:'json',async:true,success:function(r){
		var a='<div style="text-align:center;margin-bottom:10px;"><img src="uno/plugins/paycoin/img/paycoinLogo.png" alt="'+r.lang.tit+'" tit="'+r.lang.tit+'" /></div>';
		a+='<div>'+r.lang.inst+'</div>';
		jQuery("#unoPop .unoPopContent").empty().append(a);
		jQuery('<div/>',{id:'qrcode',style:'width:120px;height:120px;margin:15px 0 10px;float:left'}).appendTo("#unoPop .unoPopContent");
		a='<div style="float:right;width:45%;margin-top:30px;"><strong>'+r.lang.amo+'</strong> : '+r.price+' BTC<br /><br /><strong>'+r.lang.addr+'</strong> :</div>';
		a+='<div style="clear:both"><div style="border:1px solid #aaa;font-size:.7em;text-align:center;margin:20px 0 10px;">'+r.address+'</div></div>';
		a+='<div style="color:#666;font-size:.8em;"><p>'+r.lang.info1+'</p><p>'+r.lang.info2+'</p><p>'+r.lang.info3+'</p></div>';
		a+='<div id="paycoinInfo" style="color:red"></div>';
		jQuery("#unoPop .unoPopContent").append(a);
		jQuery("#unoPop .unoPopContent").css({'font-size':'.9em','text-align':'left','line-height':'1.2em'});
		var qr=new QRCode(document.getElementById("qrcode"),{width:120,height:120});
		if(r.address!=0){
			qr.makeCode(r.address);
			window.setTimeout(function(){paycoinCheck(r.address)},10000);
			}
		else document.getElementById("paycoinInfo").innerHTML=r.lang.off;
	}});
}
function paycoinCheck(f){
	jQuery.post('uno/plugins/paycoin/paycoinConnect.php',{'action':'check','adr':f,'mail':paycoinMail},function(r){
		if(r.substr(0,2)=='ok'){
			if(r.length>5)window.location=r.substr(2);
			else location.reload();
		}
		else{
			if(r.substr(0,2)=='nc')document.getElementById("paycoinInfo").innerHTML="Waiting for confirmation...";
			window.setTimeout(function(){paycoinCheck(f)},10000);
		}
	});
}
