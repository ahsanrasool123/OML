$(document).ready(function(){
	
	$( window ).resize(function() {
  		doResize();
	});
	
	$("#searchterms").click(function() {
		if( $("#searchterms").val() == 'Search'){
			$("#searchterms").val('');
		}
	});
	
	$(".question").click(function() {
		var text = $(this).attr('rel');
		var css = $(this).attr('css');
		if(!$(this).html()){
			$(this).html('<div style="'+css+'" class="helpBox">'+text+'</div>');
			$(this).css('z-index', '10000');
		}else{
			$(this).html('');
			$(this).css('z-index', '10');
		}
	});
	$(".question").mouseleave(function() {
		$(this).html('');
		$(this).css('z-index', '10');
	});
	
	$(".mobRack").click(function() {
		var parent = $(this).parent().attr('id');
		$("#"+parent+" .mobRack").hide();
		$("#"+parent+" ul").slideDown();
	});
	
	$("#unreadTab").click(function() {
		unreadBox();
	});
	$("#inboxTab").click(function() {
		inBox();
	});
	$("#pitchesTab").click(function() {
		pitchesBox();
	});
	$("#outboxTab").click(function() {
		outBox();
	});
	$("#deletedTab").click(function() {
		deletedBox();
	});
	
	$(".premium").click(function() {
		togglePremium();
	});
	
	$("#codeFilter").focus(function() {
		if($("#codeFilter").val() == 'Filter postcodes...'){
			$("#codeFilter").val('');	
		}
	});
	
	
	
});

function nl2br (str, is_xhtml) {   
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';    
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
}

function doResize(){// Resize the home page vid
	var w = $( document ).width();
	var h = $( document ).height();
	$("#debug").html(w+' x '+h);
	return;
}

function addCode(code){
	a_data = new Object();
	a_data['action'] = 'addPostcode';
	a_data['code'] = code;
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(!xml){
				$("#availablecodes").load('ajax.php?action=loadAvailableCodes');
				$("#agentSelector").load('ajax.php?action=loadAgents');
				reloadMap();
				reloadCart();
			}else{
				alert(xml);
			}
		}
    });
}

function removeCode(code){
	a_data = new Object();
	a_data['action'] = 'removePostcode';
	a_data['code'] = code;
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(!xml){
				$("#pc_"+code).fadeOut(50);
				$("#availablecodes").load('ajax.php?action=loadAvailableCodes');
				$("#agentSelector").load('ajax.php?action=loadAgents');
				reloadMap();
				reloadCart();
			}else{
				alert(xml);
			}
		}
    });
}

function unreadBox(){
	tab('unread');
}
function inBox(){	
	tab('inbox');
}
function pitchesBox(){
	tab('pitches');
}
function outBox(){
	tab('outbox');
}
function deletedBox(){
	tab('deleted');
}
function tab(name){
	$(".tab").removeClass("tabOn");
	$("#"+name+"Tab").addClass("tabOn");
	$(".messageWin").hide();
	$("#"+name).show();
}


function filterCodes(){
	$("#availablecodes").load('ajax.php?action=loadAvailableCodes&filter='+$("#codeFilter").val());
}
function clearFilter(){
	$("#availablecodes").load('ajax.php?action=loadAvailableCodes&reset=1');
	$("#codeFilter").val('Filter postcodes...');
}
function reloadMap(){
	$("#markerMap").load('ajax.php?action=reloadMap');
}

function togglePremium(){
	var premium = $( "input:radio[name=premium]:checked" ).val();
	a_data = new Object();
	a_data['action'] = 'togglePremium';
	a_data['premium'] = premium;
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){alert(xml);}
		}
    });
}

function emailExists(email){
	var result = null;
	a_data = new Object();
	a_data['action'] = 'emailExists';
	a_data['email'] = email;
	$.ajax({
		async: false,
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				result = xml;
			}else{
				result = 0;	
			}
		}
    });
	return result;
}

function emailExistsAgent(email){
	var result = null;
	a_data = new Object();
	a_data['action'] = 'emailExistsAgent';
	a_data['email'] = email;
	$.ajax({
		async: false,
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				result = xml;
			}else{
				result = 0;	
			}
		}
    });
	return result;
}

function codesSelected(){
	var result = null;
	a_data = new Object();
	a_data['action'] = 'codesSelected';
	$.ajax({
		async: false,
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				result = xml;
			}else{
				result = 0;	
			}
		}
    });
	return result;
}

function linkto(url){
	document.location.href=url;
}

function clearVal(id, def){
	if($("#"+id).val() == def){
		$("#"+id).val('');
	}
}

function closeAccount(){
	if (confirm("Are you sure you wish to close your account?\n\nOK = Yes - Cancel = No")){ 
		a_data = new Object();
		a_data['action'] = 'deleteMyAccount';
		$.ajax({
			type: "POST",
			url: 'ajax.php',
			dataType: "text",
			data : a_data,
			success : function(xml) {
				if(xml){
					alert(xml);
				}else{
					linkto('/account-closed/');
				}
			}
		});
	}
}
function emailValid(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}
function codeExists(postcode){
	var result = null;
	a_data = new Object();
	a_data['action'] = 'returnCodeFromGoogle';
	a_data['postcode'] = postcode;
	$.ajax({
		async: false,
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				result = xml;
			}else{
				result = 0;	
			}
		}
    });
	return result;
}

function setBuyerSeller(){
	$("#null").load('ajax.php?action=setBuyerSeller');
	linkto('/seller/register/');
}

function saveEmail(email){
	$("#null").load('ajax.php?action=saveEmail&email='+email);
}

function valid(inp){
	if(!inp){return false;}
	if(inp == 'Please select...'){return false;}
	return true;
}
function errorMe(error){
	$("#errorbox").html($("#errorbox").html()+'<br/>'+error);
	$("#errorbox").fadeIn();
	errors++;
}
var highest = 0;
function setDone(id){
	$(".progBar li:nth-child("+(id-1)+") a").addClass('stagedone');	
	highest = (parseInt(id)+1);
}
function jump(id){
	if(!parseInt(id)){return;}
	
	if(id >= highest){
		return;
	}
	
	$(".formsection").hide();
	$("#section_"+id).show();
	$("#errorbox").html('');
	highestcompleted = id;
}

function agentNav(id, index){
	$(".agentTypePanel").hide();
	$("h2").removeClass("tabover");
	$("#b"+index).addClass("tabover");
	$("#"+id).show();
	$(".firstrunbox").fadeOut();
}

function rating(thing, score){
	$("#ratelist_"+thing+" > li").css("opacity", "0.3");
	$("#ratelist_"+thing+" > li:nth-of-type("+score+")").css("opacity", "1");
	$("#"+thing+"").val(score);
}