//
// CMSUno
// Plugin paycoin
//
function f_save_paycoin(){
	jQuery(document).ready(function(){
		var key=document.getElementById("paycoinKey").value;
		jQuery.post('uno/plugins/paycoin/paycoin.php',{'action':'save','unox':Unox,'key':key},function(r){
			f_alert(r);
		});
	});
}
function f_load_paycoin(){
	jQuery(document).ready(function(){
		jQuery.ajax({type:'POST',url:'uno/plugins/paycoin/paycoin.php',data:{'action':'load','unox':Unox},dataType:'json',async:true,success:function(r){
			if(r.key!=undefined)document.getElementById('paycoinKey').value=r.key;
			if(r.secret!=undefined)document.getElementById('paycoinSec').innerHTML+=r.secret;
		}});
	});
}
function f_treated_paycoin(f,g,h){
	jQuery.post('uno/plugins/paycoin/paycoin.php',{'action':'treated','unox':Unox,'id':g},function(r){f_alert(r);});
	f.parentNode.className="PaycoinTreatedYes";
	f.innerHTML=h;f.className="";f.onclick="";
}
function f_archivOrderPaycoin(f,g){
	if(confirm(g)){jQuery.post('uno/plugins/paycoin/paycoin.php',{'action':'archiv','unox':Unox,'id':f},function(r){
		f_alert(r);
		if(r.substr(0,1)!='!')f_paycoinVente();
	});}
}
function f_paycoinRestaurOrder(f){jQuery.post('uno/plugins/paycoin/paycoin.php',{'action':'restaur','unox':Unox,'f':f},function(r){f_alert(r);f_paycoinArchiv();});}
function f_paycoinViewA(f){
	jQuery('#paycoinArchData').empty();
	jQuery.post('uno/plugins/paycoin/paycoin.php',{'action':'viewA','unox':Unox,'arch':f},function(r){jQuery('#paycoinArchData').append(r);jQuery('#paycoinArchData').show();});
}
function f_paycoinArchiv(){
	jQuery('#paycoinArchiv').empty();
	document.getElementById('paycoinArchiv').style.display="block";
	document.getElementById('paycoinConfig').style.display="none";
	document.getElementById('paycoinVente').style.display="none";
	document.getElementById('paycoinDetail').style.display="none";
	document.getElementById('paycoinA').className="bouton fr current";
	document.getElementById('paycoinC').className="bouton fr";
	document.getElementById('paycoinV').className="bouton fr";
	document.getElementById('paycoinD').style.display="none";
	jQuery.post('uno/plugins/paycoin/paycoin.php',{'action':'viewArchiv','unox':Unox},function(r){jQuery('#paycoinArchiv').append(r);jQuery('#paycoinArchData').hide();});
}
function f_paycoinConfig(){
	document.getElementById('paycoinArchiv').style.display="none";
	document.getElementById('paycoinConfig').style.display="block";
	document.getElementById('paycoinVente').style.display="none";
	document.getElementById('paycoinDetail').style.display="none";
	document.getElementById('paycoinA').className="bouton fr";
	document.getElementById('paycoinC').className="bouton fr current";
	document.getElementById('paycoinV').className="bouton fr";
	document.getElementById('paycoinD').style.display="none";
}
function f_paycoinVente(){
	document.getElementById('paycoinArchiv').style.display="none";
	document.getElementById('paycoinConfig').style.display="none";
	jQuery('#paycoinVente').empty();document.getElementById('paycoinVente').style.display="block";
	document.getElementById('paycoinDetail').style.display="none";
	document.getElementById('paycoinA').className="bouton fr";
	document.getElementById('paycoinC').className="bouton fr";
	document.getElementById('paycoinV').className="bouton fr current";
	document.getElementById('paycoinD').style.display="none";
	jQuery.post('uno/plugins/paycoin/paycoin.php',{'action':'vente','unox':Unox,'udep':Udep},function(r){jQuery('#paycoinVente').append(r);});
}
function f_paycoinDetail(f){
	jQuery('#paycoinDetail').empty();
	document.getElementById('paycoinArchiv').style.display="none";
	document.getElementById('paycoinConfig').style.display="none";
	document.getElementById('paycoinVente').style.display="none";
	document.getElementById('paycoinDetail').style.display="block";
	document.getElementById('paycoinA').className="bouton fr";
	document.getElementById('paycoinC').className="bouton fr";
	document.getElementById('paycoinV').className="bouton fr";
	document.getElementById('paycoinD').style.display="block";
	jQuery.post('uno/plugins/paycoin/paycoin.php',{'action':'detail','unox':Unox,'id':f},function(r){
		if(r.substr(0,1)!='!')jQuery('#paycoinDetail').append(r);
		else f_alert(r);
	});
}
//
f_load_paycoin();f_paycoinVente();
