
; /* Start:"a:4:{s:4:"full";s:89:"/bitrix/components/bitrix/disk.aggregator/templates/.default/script.min.js?16992579742851";s:6:"source";s:70:"/bitrix/components/bitrix/disk.aggregator/templates/.default/script.js";s:3:"min";s:74:"/bitrix/components/bitrix/disk.aggregator/templates/.default/script.min.js";s:3:"map";s:74:"/bitrix/components/bitrix/disk.aggregator/templates/.default/script.map.js";}"*/
BX.namespace("BX.Disk");BX.Disk.AggregatorClass=function(){var e=function(e){this.ajaxUrl="/bitrix/components/bitrix/disk.aggregator/ajax.php"};e.prototype.showHiddenContent=function(e){e.style.display=e.style.display=="none"?"block":"none"};e.prototype.hide=function(e){if(!e.getAttribute("displayOld")){e.setAttribute("displayOld",e.style.display)}e.style.display="none"};e.prototype.showNetworkDriveConnect=function(e){e=e||{};var t=e.link,i=this.showHiddenContent,s=this.hide;i(BX("bx-disk-network-drive-full"));BX.Disk.modalWindow({modalId:"bx-disk-show-network-drive-connect",title:BX.message("DISK_AGGREGATOR_TITLE_NETWORK_DRIVE"),contentClassName:"tac",contentStyle:{},events:{onAfterPopupShow:function(){var e=BX("disk-get-network-drive-link");BX.focus(e);e.setSelectionRange(0,e.value.length)},onPopupClose:function(){s(BX("bx-disk-network-drive"));s(BX("bx-disk-network-drive-full"));document.body.appendChild(BX("bx-disk-network-drive-full"));this.destroy()}},content:[BX.create("label",{text:BX.message("DISK_AGGREGATOR_TITLE_NETWORK_DRIVE_DESCR_MODAL")+" :",props:{className:"bx-disk-popup-label","for":"disk-get-network-drive-link"}}),BX.create("input",{style:{marginTop:"10px"},props:{id:"disk-get-network-drive-link",className:"bx-disk-popup-input",type:"text",value:t}}),BX("bx-disk-network-drive-full")],buttons:[new BX.PopupWindowButton({text:BX.message("DISK_AGGREGATOR_BTN_CLOSE"),events:{click:function(){BX.PopupWindowManager.getCurrentPopup().close()}}})]});if(BX("bx-disk-network-drive-secure-label")){s(BX.findChildByClassName(BX("bx-disk-show-network-drive-connect"),"bx-disk-popup-label"));s(BX.findChildByClassName(BX("bx-disk-show-network-drive-connect"),"bx-disk-popup-input"))}};e.prototype.getListStorage=function(e,t){var i=null,s=null;if(BX("bx-disk-da-site-id")){i=BX("bx-disk-da-site-id").value}if(BX("bx-disk-da-site-dir")){s=BX("bx-disk-da-site-dir").value}var o=this.showHiddenContent,n="bx-disk-"+t+"-div";if(!BX(n).getElementsByTagName("ul").length){BX.ajax({method:"POST",dataType:"json",url:BX.Disk.addToLinkParam(this.ajaxUrl,"action",e),data:{siteId:i,siteDir:s,proxyType:t,sessid:BX.bitrix_sessid()},onsuccess:function(e){if(e.status=="success"){var t="";t+='<h2 class="bx-disk-aggregator-storage-title">'+BX.util.htmlspecialchars(e.title)+"</h2>";t+="<ul>";for(var i in e.listStorage){t+='<li class="bx-disk-aggregator-list">';t+='<img style="vertical-align:middle" src="'+e.listStorage[i]["ICON"]+'" class="bx-disk-aggregator-icon" />';t+='<a class="bx-disk-aggregator-a-link" href="'+e.listStorage[i]["URL"]+'">'+BX.util.htmlspecialchars(e.listStorage[i]["TITLE"])+"</a>";t+="</li>"}t+="</ul>";BX(n).innerHTML=t;o(BX(n))}else{e.errors=e.errors||[{}];BX.Disk.showModalWithStatusAction({status:"error",message:e.errors.pop().message})}}})}else{o(BX(n))}};return e}();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:92:"/bitrix/components/bitrix/disk.help.network.drive/templates/.default/script.js?1699257973302";s:6:"source";s:78:"/bitrix/components/bitrix/disk.help.network.drive/templates/.default/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
BX.namespace("BX.Disk");
BX.Disk.HelpNetworkDriveClass = (function ()
{
	var HelpNetworkDriveClass = function (parameters){};
	HelpNetworkDriveClass.prototype.showContent = function (el)
	{
		el.style.display = (el.style.display == 'none') ? 'block' : 'none';
	};

	return HelpNetworkDriveClass;
})();

/* End */
;; /* /bitrix/components/bitrix/disk.aggregator/templates/.default/script.min.js?16992579742851*/
; /* /bitrix/components/bitrix/disk.help.network.drive/templates/.default/script.js?1699257973302*/

//# sourceMappingURL=page_88e3ca57d9405013fb942c9752ca28a2.map.js