var currentFolder = '';
$(document).ready(function(){

  updateMessageScreen();
  
  $("#msgSearchForm").submit(function(e){
	  e.preventDefault();
	  searchMsg()
  });
  
  $("#msgTerms").click(function() {
	  $("#msgTerms").val('');
  });
  $("#msgTerms").blur(function() {
	  if(!$("#msgTerms").val()){
		  $("#msgTerms").val('Search...');
	  }
  });
  
  $(".firstrunbox").click(function() {
	  $(this).fadeOut();
  });
  
  $("#agentVendorDash .vendSplitPanel").click(function() {
	  $(".firstrunbox").fadeOut();
  });
  
  $("#cart").click(function() {
	  updateBillingPeriod();
  });
  
  $("select[name=filter_bedrooms], select[name=filter_type], select[name=filter_value]").change(function() {
	  filterProperties()
  });
  $("select[name=filter_buyer_bedrooms], select[name=filter_price]").change(function() {
	  filterBuyers()
  });
  
  $(".dashmenu > li").click(function() {
	  $(".submenu", this).show();
  });
  
  $(".dashmenu > li").mouseleave(function() {
	  $(".submenu", this).hide();
  });
  
  $(".dashmenuMob > ul > li").click(function() {
	  $(".submenumob").show();
  });
  
  $(".dashmenuMob > ul > li").mouseleave(function() {
	  $(".submenumob").hide();
  });
  
  $("#vendorTerms").click(function() {						   
		if($("#vendorTerms").val() == 'Search...'){
			$("#vendorTerms").val('');	
		}
	});
  $("#vendorTerms").blur(function() {						   
		if($("#vendorTerms").val() == ''){
			$("#vendorTerms").val('Search...');	
		}
	});
  
  
});

function filterProperties(){
	a_data = new Object();
	a_data['bedrooms'] = $("select[name=filter_bedrooms]").val();
	a_data['type'] = $("select[name=filter_type]").val();
	a_data['value'] = $("select[name=filter_value]").val();
	a_data['action'] = 'filterAgentProperties';

	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				$("#availableProperties").html(xml);
			}
		}
    });
}

function filterBuyers(){
	a_data = new Object();
	a_data['bedrooms'] = $("select[name=filter_buyer_bedrooms]").val();
	a_data['price_range'] = $("select[name=filter_price]").val();
	a_data['action'] = 'filterAgentBuyers';
	//alert(a_data['bedrooms']+' '+a_data['price_range'])
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				$("#availableBuyers").html(xml);
			}
		}
    });
}

function updateBillingPeriod(){
	var period = $('input[name=billing_period_new]:checked').val();
	if(period){
		$("#null").load('ajax.php?action=setBillingPeriod&val='+period);
		reloadCart();
	}
}

function dashNav(id){
	$(".dashBox").hide();
	closeMessage();
	if(id == 'postcodes'){
		reloadMap();
	}
	$("#"+id).show();
}

function updatePersonalProfile(){
	
	$(".errorbox, .okbox").hide();
	
	a_data = new Object();
	a_data['action'] = 'updatePersonalProfile';
	a_data['firstname'] = $("#firstname").val();
	a_data['surname'] = $("#surname").val();
	a_data['email'] = $("#email").val();
	a_data['tel'] = $("#tel").val();
	a_data['biog'] = $("#biog").val();
	a_data['pitch'] = $("#pitch").val();
	a_data['pw'] = $("#pw").val();
	a_data['pwc'] = $("#pwc").val();
	a_data['billing_period'] = $('input[name=billing_period]:checked').val();
	var error = 0;
	
	$("input, textarea").removeClass('formerror');
	
	if(!a_data['firstname']){$("#firstname").addClass('formerror');error++}
	if(!a_data['surname']){$("#surname").addClass('formerror');error++}
	if(!a_data['tel']){$("#tel").addClass('formerror');error++}
	if(!a_data['email']){$("#email").addClass('formerror');error++}
	if(!a_data['biog']){$("#biog").addClass('formerror');error++}
	if(!a_data['billing_period']){$("#billing_period").addClass('formerror');error++}
	
	if(a_data['pw']){
		if(a_data['pw'] != a_data['pwc']){
			$("#pwc").addClass('formerror');
			error++;
		}
	}
	
	if(error){return;}
	
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				$("#errorboxpersonal").html(xml);
				$("#errorboxpersonal").show();
			}else{
				$("#errorboxpersonal").hide();
				$("#okboxpersonal").fadeIn();
			}
		}
    });
}


function updateCorporateProfile(){
	
	$(".errorbox, .okbox").hide();
	
	a_data = new Object();
	a_data['action'] = 'updateCorporateProfile';
	a_data['name'] = $("#name").val();
	a_data['address_1'] = $("#address_1").val();
	a_data['address_2'] = $("#address_2").val();
	a_data['address_3'] = $("#address_3").val();
	a_data['address_4'] = $("#address_4").val();
	a_data['postcode'] = $("#postcode").val();
	var error = 0;
	
	$("input, textarea").removeClass('formerror');
	
	if(!a_data['name']){$("#name").addClass('formerror');error++}
	if(!a_data['address_1']){$("#address_1").addClass('formerror');error++}
	if(!a_data['postcode']){$("#postcode").addClass('formerror');error++}
	
	if(error){return;}
	
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				$("#errorboxcorp").html(xml);
				$("#errorboxcorp").show();
			}else{
				$("#errorboxcorp").hide();
				$("#okboxcorp").fadeIn();
			}
		}
    });
}


function updateCustomerProfile(){
	
	$(".errorbox, .okbox").hide();
	
	a_data = new Object();
	a_data['action'] = 'updateCustomerProfile';
	a_data['firstname'] = $("#firstname").val();
	a_data['surname'] = $("#surname").val();
	a_data['email'] = $("#email").val();
	a_data['tel'] = $("#tel").val();
	a_data['requirements'] = $('textarea#requirements').val();
	a_data['bedrooms'] = $( "#bedrooms option:selected" ).text();
	a_data['price_range'] = $( "#price_range option:selected" ).text();
	var error = 0;
	$("input, textarea, select").removeClass('formerror');
	
	if(!a_data['firstname']){$("#firstname").addClass('formerror');error++}
	if(!a_data['surname']){$("#surname").addClass('formerror');error++}
	if(!a_data['tel']){$("#tel").addClass('formerror');error++}
	if(!a_data['email']){$("#email").addClass('formerror');error++}
	if(a_data['price_range'] == 'Please select...'){$("#price_range").addClass('formerror');error++}
	
	if(error){return;}
	
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				$("#errorboxcust").html(xml);
				$("#errorboxcust").show();
			}else{
				$("#errorboxcust").hide();
				$("#okboxcust").fadeIn();
			}
		}
    });
}

function updateBuyerProfile(){
	
	$(".errorbox, .okbox").hide();
	a_data = new Object();
	
	// Conv form vals
	$('#updatebuyerform input[type=text], #updatebuyerform textarea').each(function(key, value) {
		a_data[this.name] = this.value;
	});
	$('#updatebuyerform select').each(function(key, value) {
		a_data[this.name] = this.value;
	});
	$('#updatebuyerform input[type=checkbox]').each(function(key, value) {
		if(document.getElementById(this.name).checked){
			a_data[this.name] = this.value;
		}
	});
	
	a_data['action'] = 'updateBuyerProfile';
	
	a_data['price_range_min'] = parseInt(a_data['price_range_min']);
	a_data['price_range_max'] = parseInt(a_data['price_range_max']);
	
	var error = 0;
	$("input, textarea, select").removeClass('formerror');
	
	if(!a_data['firstname']){$("#firstname").addClass('formerror');error++}
	if(!a_data['surname']){$("#surname").addClass('formerror');error++}
	if(!a_data['additional_information']){$("#additional_information").addClass('formerror');error++}
	if(!a_data['email']){$("#email").addClass('formerror');error++}
	if(a_data['looking_to_move'] == 'Please select...'){$("#looking_to_move").addClass('formerror');error++}
	//alert(a_data['additional_information'])
	if(a_data['price_range_min']>0){
		if(a_data['price_range_min'] >= a_data['price_range_max']){
			$("#price_range_min, #price_range_max").addClass('formerror');
			error++
		}
	}
	if(error){return;}
	
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				$("#errorboxcust").html(xml);
				$("#errorboxcust").show();
			}else{
				$("#errorboxcust").hide();
				$("#okboxcust").fadeIn();
			}
		}
    });
}

function showActivForm(id){
	$("#actb_"+id).hide();
	$("#act_"+id).fadeIn();
}

function activateAgent(id){
	if(!id){alert('ID is required');}
	$("input").removeClass('formerror');
	a_data = new Object();
	a_data['action'] = 'activateAgent';
	
	a_data['firstname'] = $("#firstname_"+id).val();
	a_data['surname'] = $("#surname_"+id).val();
	
	a_data['email'] = $("#email_"+id).val();
	a_data['pw'] = $("#pw_"+id).val();
	a_data['pwc'] = $("#pwc_"+id).val();
	a_data['agent_id'] = $("#agent_id_"+id).val();	
	var error = 0;
	if(!a_data['firstname']){$("#firstname_"+id).addClass('formerror');error++}
	if(!a_data['surname']){$("#surname_"+id).addClass('formerror');error++}
	
	if(!a_data['email']){$("#email_"+id).addClass('formerror');error++}
	if(!a_data['pw']){$("#pw_"+id).addClass('formerror');error++}
	if(!a_data['pwc']){$("#pwc_"+id).addClass('formerror');error++}
	if(a_data['pw'] != a_data['pwc']){$("#pw_"+id).addClass('formerror');$("#pwc_"+id).addClass('formerror');error++}
	if(error){return;}
	if(error){return;}
	
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				$("#errorboxact_"+id).html(xml);
				$("#errorboxact_"+id).show();
			}else{
				$("#errorboxact_"+id).hide();
				$("#okboxact_"+id).fadeIn();
			}
		}
    });
}


function removeProperty(prop_id){
	if (
		confirm("Are you sure you wish to permanently delete this property?\n\nOK = Yes - Cancel = No")){ 
		$("#null").load('ajax.php?action=removeProperty&prop_id='+prop_id);
		$("#prop_"+prop_id).fadeOut();
	}
}

function blockAgent(agent_id){
	$("#null").load('ajax.php?action=blockAgent&agent_id='+agent_id);
	if($("#blockButton_"+agent_id).html() == 'Block'){
		$("#blockButton_"+agent_id).html('Unblock');
		$("#contact_"+agent_id).hide();
	}else{
		$("#blockButton_"+agent_id).html('Block');
		$("#contact_"+agent_id).show();
	}
	$("#agentsNew").load('ajax.php?action=loadAgentsNew');
	$("#agentsApproved").load('ajax.php?action=loadAgentsApproved');
	$("#agentsBlocked").load('ajax.php?action=loadAgentsBlocked');
}

function contactAgent(agent_id){
	alert('Contact now: '+agent_id)	
}
function addAgent(agent_id){
	a_data = new Object();
	a_data['action'] = 'addAgentToCust';
	a_data['agent_id'] = agent_id;
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				alert(xml);
			}else{
				if($("#agent_"+agent_id).hasClass( "selectedAgent" )){
					$("#agent_"+agent_id).removeClass('selectedAgent');
					$("#addButton_"+agent_id).html('Add');
					$("#blockButton_"+agent_id).html('Block');
				}else{
					$("#agent_"+agent_id).addClass('selectedAgent');
					$("#addButton_"+agent_id).html('Remove');
					$("#blockButton_"+agent_id).html('Block');
				}
				$("#contact_"+agent_id).show();
				$("#agentsNew").load('ajax.php?action=loadAgentsNew');
				$("#agentsApproved").load('ajax.php?action=loadAgentsApproved');
				$("#agentsBlocked").load('ajax.php?action=loadAgentsBlocked');
			}	
		}
    });
}


function showCustomerProfile(cust_id){
	$("#profile_"+cust_id).fadeIn();
	$("#profB_"+cust_id).hide();
}

function sendMessage(){
	
	a_data = new Object();
	$('#messageForm input[type=text], #messageForm input[type=hidden], #messageForm textarea').each(function(key, value) {
		a_data[this.name] = $.trim(this.value);
	});
	
	if(!a_data['msgContent']){return;}
	if(!a_data['to_cust_id'] && !a_data['to_agent_id']){
		alert('Please select a recipient for your message');return;
	}
	
	a_data['action'] = 'sendMessage';
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				$("#msgBody").html(xml);
			}
		}
    });
	
	$("#messageForm").html('<div class="centerMe" style="height:100px">Thank you. Your message has been sent.</div>');
	
	
	
	// From messages screen
	try {
		loadMailPanel(currentFolder);
	}
	catch(err) {}

}

function saveDraft(){
	a_data = new Object();
	$('#messageForm input[type=text], #messageForm input[type=hidden], #messageForm textarea').each(function(key, value) {
		a_data[this.name] = $.trim(this.value);
	});
	
	if(!a_data['msgContent'] || a_data['msgContent'] == 'Write your message here'){alert('Please enter a message first');return;}
	if(!a_data['to_cust_id'] && !a_data['to_agent_id']){
		alert('Please select a recipient for your message');return;
	}
	
	if(a_data['msgContent'] == 'Write your proposal here...' || a_data['msgContent'] == ''){
		alert("Please enter your message before saving");
		return;
	}
	
	a_data['action'] = 'saveMessageDraft';
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				alert(xml);
			}else{
				$("#messageForm").html('<div class="centerMe" style="height:100px">Your message has been saved to drafts.</div>');		
			}
		}
    });
	
}

function updateMessageScreen(){
	$("#unread").load('ajax.php?action=loadMessages&type=unread');
	$("#inbox").load('ajax.php?action=loadMessages&type=inbox');
	$("#outbox").load('ajax.php?action=loadMessages&type=outbox');
	$("#deleted").load('ajax.php?action=loadMessages&type=deleted');
	$("#pitches").load('ajax.php?action=loadMessages&type=pitches');
	$(".msgCounter").load('ajax.php?action=newMessageCount');
	setTimeout("updateMessageScreen()", 30000);
}

function messageClose(){
	$("#messageWin").hide();
	$("#messageWin").html('');
}

function sendPitch(prop_id){
	$("#null").load('ajax.php?action=sendPitch&prop_id='+prop_id);
	$("#pitchBut_"+prop_id).html('PITCH SENT');
	$("#pitchBut_"+prop_id).fadeOut(2000);
}

function openThread(cust_id){
	alert('Will open the conversation between you and customer ID: '+cust_id+' once this function is active');	
}

function reloadCart(){
	$("#cart").load('ajax.php?action=reloadCart');
}

// Load all msg into dash
function showAllMessages(){
	$("#messageTable").load('ajax.php?action=loadAllMessages&type=inbox');
}

// Search msg from dash
function searchMsg(){
	var terms = $("#msgTerms").val();
	$("#messageTable").load('ajax.php?action=searchMessages&terms='+encodeURIComponent(terms));
	$("#msgTerms").val('');
}

function openMsg(msg_id){
	linkto('/index.php?action=openMsg&msg_id='+msg_id);
}

function newMessageWin(cust_id){
	if(!cust_id){return;}
	a_data = new Object();
	a_data['action'] = 'newMessageWin';
	a_data['cust_id'] = cust_id;	
	
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				$("#msgBody").html(xml);
				resetCursor('msgBody');
				$("#msgContent").val('Type your message here');
				$('#msgBody').focus();
			}
		}
    });
	$("#msgSend").css('opacity', '1');
}

function loadAgentProf(agent_id){
	linkto('/index.php?action=loadAgentProfile&agent_id='+agent_id);
}

function loadBuyerProf(cust_id){
	linkto('/index.php?action=loadBuyerProfile&cust_id='+cust_id);
}
function loadSellerProf(cust_id){
	linkto('/index.php?action=loadSellerProfile&cust_id='+cust_id);
}

// Search contact history
function searchHistory(){
	var terms = $("#terms").val();

	if($("#agent_id").val()){
		if(!terms || terms == 'Search...'){
			$("#contactHistory").load('ajax.php?action=searchHistory&agent_id='+$("#agent_id").val());
			return
		}
		$("#contactHistory").load('ajax.php?action=searchHistory&agent_id='+$("#agent_id").val()+'&terms='+encodeURIComponent(terms));
	}
	
	if($("#cust_id").val()){
		if(!terms || terms == 'Search...'){
			$("#contactHistory").load('ajax.php?action=searchHistory&cust_id='+$("#cust_id").val());
			return
		}
		$("#contactHistory").load('ajax.php?action=searchHistory&cust_id='+$("#cust_id").val()+'&terms='+encodeURIComponent(terms));
	}
	
}

function viewProposals(agent_id){
	linkto('/index.php?action=viewProposals&agent_id='+agent_id);
}

function useProposalTemplate(cust_id, custtype){
	if(!cust_id || !custtype){
		$("#msgContent").val('Write your proposal here...');
		return;
	}
	a_data = new Object();
	a_data['action'] = 'loadProposalTemplate';
	a_data['cust_id'] = cust_id;
	a_data['custtype'] = custtype;
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				$("#msgContent").val(xml);	
			}
		}
    });
	
}

function publishAgentProfile(){
	$("#null").load('ajax.php?action=publishAgentProfile');
	$("#publishBox").html('<div class="greenMessage">Your profile has now been sent to all matching vendors.</div>');
}

function cancelCodeRequest(code){
	a_data = new Object();
	a_data['action'] = 'cancelCodeRequest';
	a_data['code'] = code;
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		dataType: "text",
		data : a_data,
		success : function(xml) {
			if(xml){
				alert(xml);
			}
		}
    });
}

function searchVendors(){
	var terms = encodeURIComponent($("#vendorTerms").val());
	if(terms == 'Search...' || !terms){return}
	$("#vend_sellers").load('ajax.php?action=loadVendors&type=sellers&terms='+terms);
	$("#vend_buyers").load('ajax.php?action=loadVendors&type=buyers&terms='+terms);
	$("#vendorTerms").val('Search...');
}