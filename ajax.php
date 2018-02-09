<?PHP //AJAX processor 
include('config.php');


//------------------------------------------------

// Validate part one of the agent signup form
if($_POST['action'] == 'agentRegPart1'){
	$email = cleanInput($_POST['email']);
	$pw = cleanInput($_POST['pw']);
	$pwc = cleanInput($_POST['pwc']);
	
	if(!$email || !emailValid($email)){
		addError("Please enter a valid email address");
		showErrors();
		exit();
	}
	
	$exists = result("SELECT COUNT(`agent_id`) FROM `agents` WHERE `email` = '".esc($email)."'", 0);
	
	if($exists){
		addError("An account with this email address already exists");
		showErrors();
		exit();
	}

	if(!$pw){
		addError("Please choose a password");
	}else{
		
		if(strlen($pw) < 8 || strlen($pw) > 30){
			addError("Your password needs to be between 8 and 30 characters in length");
		}
		
		if(!$pwc){
			addError("Please confirm your password");
		}else{
			if($pw != $pwc){
				addError("Your password and password confirmation do not match");
			}
		}
	}
	
	showErrors();
	
	exit();
}

//------------------------------------------------

// Validate part one of the agent signup form
if($_POST['action'] == 'agentRegPart2'){
	$name = cleanInput($_POST['name']);
	$tel = cleanInput($_POST['tel']);
	$address_1 = cleanInput($_POST['address_1']);
	$address_2 = cleanInput($_POST['address_2']);
	$address_3 = cleanInput($_POST['address_3']);
	$address_4 = cleanInput($_POST['address_4']);
	$postcode = cleanInput($_POST['postcode']);
	
	if(!$name){addError("Please enter your business name");}
	if(!$address_1){addError("Please complete the address 1 field");}
	if(!$postcode){addError("Please enter your postcode");}
	if(!$tel){addError("Please enter your telephone number");}

	showErrors();
	
	exit();
}

//------------------------------------------------

// Add a postcode to a user account - works for agents AND customers
if($_POST['action'] == 'addPostcode'){

	$code = cleanInput($_POST['code']);
	$agent_id = $_SESSION['agent_id'];
	$cust_id = $_SESSION['cust_id'];
	
	if($_SESSION['agent_id'] && is_numeric($_SESSION['agent_id'])){
		$type = 'agent';
	}else if($_SESSION['cust_id'] && is_numeric($_SESSION['cust_id'])){
		$type = 'customer';
	}else{
		$is_signup = true;
	}

	if(!$name){addError("Please select a code first");}
	
	$area = stripslashes(result("SELECT `area` FROM `postcodes` WHERE `code` = '".esc($code)."'", 0));
	
	// User signing up so they don't have an ID yet
	if($is_signup){
		
		array_push($_SESSION['a_customer_postcodes'], $code);
		
	}else{// Existing customer
		
		if($type == 'agent'){
			query("DELETE FROM `agent_postcodes` WHERE `code` = '".esc($code)."' AND `agent_id` = $agent_id");
			query("INSERT INTO `agent_postcodes` (`code`, `area`, `agent_id`, `date_created`)VALUES('".esc($code)."', '".esc($area)."', $agent_id, ".time().")");
		}else{
			query("DELETE FROM `seller_postcodes` WHERE `code` = '".esc($code)."' AND `cust_id` = $cust_id");
			query("INSERT INTO `seller_postcodes` (`code`, `area`, `cust_id`, `date_created`)VALUES('".esc($code)."', '".esc($area)."', $cust_id, ".time().")");
		}
	
	}
	
	exit();
}

//------------------------------------------------

// Removes a postcode from an agent's portfolio
if($_POST['action'] == 'removePostcode'){

	if($_SESSION['agent_id'] && is_numeric($_SESSION['agent_id'])){
		$type = 'agent';
	}else if($_SESSION['cust_id'] && is_numeric($_SESSION['cust_id'])){
		$type = 'customer';
	}else{
		$is_signup = true;
	}

	$code = cleanInput($_POST['code']);
	$agent_id = $_SESSION['agent_id'];
	if(!$name){addError("Please select a code first");}
	
	if($is_signup){
	
		if( ($key = array_search($code, $_SESSION['a_customer_postcodes']) ) !== false) {
    		unset($_SESSION['a_customer_postcodes'][$key]);
		}
	
	}else{
	
		if($type == 'agent'){
			query("DELETE FROM `agent_postcodes` WHERE `code` = '".esc($code)."' AND `agent_id` = $agent_id");
		}else{
			query("DELETE FROM `seller_postcodes` WHERE `code` = '".esc($code)."' AND `cust_id` = ".$_SESSION['cust_id']);
		}
	
	}
	exit();
}

//------------------------------------------------

// Loads agent's codes into the second selector after they've added one using addPostcode
if($_GET['action'] == 'loadSelectedCodes'){

	if($_SESSION['agent_id'] && is_numeric($_SESSION['agent_id'])){
		$type = 'agent';
	}else if($_SESSION['cust_id'] && is_numeric($_SESSION['cust_id'])){
		$type = 'customer';
	}else{
		$is_signup = true;
	}

	$agent_id = $_SESSION['agent_id'];
	$cust_id = $_SESSION['cust_id'];
	
	if($is_signup){
		$res = query("SELECT * FROM `postcodes` WHERE `code` IN ('".implode("', '", $_SESSION['a_customer_postcodes'])."') ORDER BY `area`");
	}else{
	
		if($type == 'agent'){
			$res = query("SELECT * FROM `agent_postcodes` WHERE `agent_id` = ".$_SESSION['agent_id']." ORDER BY `area`");
		}else{
			$res = query("SELECT * FROM `postcodes` WHERE `code` IN (SELECT `code` FROM `seller_postcodes` WHERE `cust_id` = ".$_SESSION['cust_id'].") ORDER BY `area`");
		}
	
	}

	// output a <li> list of postcodes
	$map = new map;
	$map->myCodeSelectorList($res);

	exit();
}

//------------------------------------------------

// Reloads the postcodes available list
if($_GET['action'] == 'loadAvailableCodes'){

	$agent_id = $_SESSION['agent_id'];
	$cust_id = $_SESSION['cust_id'];
	$filter = cleanInput($_GET['filter']);
	$reset = cleanInput($_GET['reset']);
	
	// Save the filter
	if($filter){
		$_SESSION['saved_filter'] = $filter;
	}
	
	if($reset){
		unset($_SESSION['saved_filter']);
	}
	
	if($agent_id && is_numeric($agent_id)){
		$type = 'agent';
	}else if($cust_id && is_numeric($cust_id)){
		$type = 'customer';
	}else{
		$is_signup = true;
	}
	
	//Filters
	if($filter){$clause = " AND CONCAT (`code`, `area`, `keywords`) LIKE '%".esc($filter)."%' ";	}
	
	if($_SESSION['saved_filter']){
		$clause = " AND CONCAT (`code`, `area`, `keywords`) LIKE '%".esc($_SESSION['saved_filter'])."%' ";
	}
	
	if($is_signup){
		#$sql = "SELECT * FROM `postcodes` WHERE `code` NOT IN ('".implode("', '", $_SESSION['a_customer_postcodes'])."') $clause ORDER BY `area`";
		$sql = "SELECT * FROM `postcodes` WHERE 1 $clause ORDER BY `area`";
	}else{
		
		if($type == 'agent'){
			$sql = "SELECT * FROM `postcodes` WHERE `code` NOT IN (SELECT `code` FROM `agent_postcodes` WHERE `agent_id` = ".$_SESSION['agent_id'].") $clause ORDER BY `area`";
		}else{
			$sql = "SELECT * FROM `postcodes` WHERE `code` NOT IN (SELECT `code` FROM `seller_postcodes` WHERE `cust_id` = ".$_SESSION['cust_id'].") $clause ORDER BY `area`";
		}
		
	}

	// Show matches
	//echo $sql;
	$res = query($sql);
	if( num_rows($res) ){
			// Output a <li> of matching postcodes
			$map->availableCodeSelectorList($type, $clause);
			if($clause){
				echo '<div onclick="clearFilter()" class="button">Clear search</div>';
			}
	}else{
		echo '<li>No matching postcodes</li>';
	}
	exit();
}

//------------------------------------------------

// Customer adding an agent to their account (stored in a serialized array on the `customers` table)
if($_POST['action'] == 'addAgentToCust'){
	$agent_id = cleanInput($_POST['agent_id']);
	$cust_id = $_SESSION['cust_id'];
	
	if($agent_id && is_numeric($agent_id)){
		$type = 'agent';
	}else if($cust_id && is_numeric($cust_id)){
		$type = 'customer';
	}else{
		echo 'Please log in to see your postcodes';
		exit();
	}
	
	if(!$cust_id || !is_numeric($cust_id)){exit("Please log in");}
	if(!$agent_id || !is_numeric($agent_id)){exit("Please select an agent");}
	$c = new customer;
	$c->retrieve($cust_id);
	
	// Remove any current status
	query("DELETE FROM `agent_relationship` WHERE `cust_id` = '$cust_id' AND `agent_id` = '".esc($agent_id)."' AND `role` =  '".esc($_SESSION['current_dash'])."'");

	// .. and enter the selections into the selected agents table
	query("INSERT INTO `agent_relationship` (`cust_id`, `agent_id`, `status`, `role`)VALUE('$cust_id', '".esc($agent_id)."', 'added', '".esc($_SESSION['current_dash'])."')");
	
	
	exit();
}

//------------------------------------------------

// Customer blocking an agent
if($_GET['action'] == 'blockAgent'){
	$agent_id = $_GET['agent_id'];
	if(!is_numeric($agent_id)){exit();}
	
	#$blocked = result("SELECT COUNT(`id`) FROM `agent_relationship` WHERE `cust_id` = '".esc($_SESSION['cust_id'])."' AND `agent_id` = '".esc($agent_id)."' AND `status` = 'blocked'");
	
	query("DELETE FROM `agent_relationship` WHERE `cust_id` = '".esc($_SESSION['cust_id'])."' AND `agent_id` = '".esc($agent_id)."' AND `role` = '".esc($_SESSION['current_dash'])."'");
	
	if(!$blocked){
		$sql = "INSERT INTO `agent_relationship` (`cust_id`, `agent_id`, `status`, `role`)VALUES('".esc($_SESSION['cust_id'])."', '".esc($agent_id)."', 'blocked', '".esc($_SESSION['current_dash'])."')";
		query($sql);
	}
	
	exit();
}

//------------------------------------------------

// Load agents into list in customer's dashboard
if($_GET['action'] == 'loadAgents'){
	$agent = new agent;
	$agent->agentSelector();
	exit();
}

//------------------------------------------------

// Reload the markers map on the dashbnoards
if($_GET['action'] == 'reloadMap'){
	#print_r($_SESSION);
	#exit();
	$cust_id = $_SESSION['cust_id'];
	$agent_id = $_SESSION['agent_id'];
	
	$m = new map;
	
	if($agent_id && is_numeric($agent_id)){
		$m->showAgentPostcodes();
	}
	if($cust_id && is_numeric($cust_id)){
		$m->showCustomerPostcodes();
	}
	
	if(count($_SESSION['a_customer_postcodes'])){
		$m->showTempPostcodes();
	}
	
	exit();
}


//------------------------------------------------

// Toggles the user between premium and normal status, used during signup.
if($_POST['action'] == 'togglePremium'){
	$premium = $_POST['premium'];
	if(!is_numeric($premium)){exit('Invalid data');}
	if($premium > 1 || $premium < 0){exit('Value set too high');}
	if(!$_SESSION['agent_id']){exit('You must be logged in to change this setting');}
	$sql = "UPDATE `agents` SET `premium` = '$premium' WHERE `agent_id` = '".esc($_SESSION['agent_id'])."' LIMIT 1";
	query($sql);
	exit();
}

//------------------------------------------------

// Open the message send window
if($_GET['action'] == 'messageWin'){
	$id = $_GET['id'];
	if(!$id || !is_numeric($id)){exit('#false:'.$id);}
	$m = new messenger();
	$m->messageWin($id);
	exit();
}


//------------------------------------------------

// Send a message
if($_GET['action'] == 'deleteMessage'){
	$m = new messenger;
	$m->delete($_GET['msg_id']);
	exit();
}

//------------------------------------------------


if($_POST['action'] == 'updatePersonalProfile'){
	$firstname = cleanInput($_POST['firstname']);
	$surname = cleanInput($_POST['surname']);
	$email = cleanInput($_POST['email']);
	$tel = cleanInput($_POST['tel']);
	$biog = cleanInput($_POST['biog']);
	$pitch = cleanInput($_POST['pitch']);
	$pw = cleanInput($_POST['pw']);
	$pwc = cleanInput($_POST['pwc']);
	$agent_id = cleanInput($_POST['agent_id']);
	$billing_period = cleanInput($_POST['billing_period']);
	
	if(!$agent_id || !is_numeric($agent_id)){
		$agent_id = $_SESSION['agent_id'];
	}
	
	if(!$agent_id){addError("Please log in first");}
	
	if(!emailValid($email)){addError("Please enter a valid email address");}
	if($pw){
		if(strlen($pw)<8 || strlen($pw)>30){
			addError("Your password must be between 8 and 30 characters");
		}
		if($pw != $pwc){addError("Your password confirmation does not match your password");}
	}
	
	$a = new agent;
	$a->retrieve($agent_id);
	
	// Check the email address hasn't been used before
	if($email != $a->email){
		if(userExists($email)){
			addError("There is already an account with this email address**");
		}
	}
	
	if(!$pitch){addError("Please enter some text for your pitch email to sellers.");}
	
	if(count($GLOBALS['a_errors'])){
		echo '<div>'.implode('</div>', $GLOBALS['a_errors']).'</div>';
		exit();
	}
	
	$sql = "UPDATE `agents` SET `firstname` = '".esc($firstname)."',
	`surname` = '".esc($surname)."',
	`email` = '".esc($email)."',
	`tel` = '".esc($tel)."',
	`pitch` = '".esc($pitch)."',
	`billing_period` = '".esc($billing_period)."',
	`biog` = '".esc($biog)."' WHERE `agent_id` = $agent_id LIMIT 1";
	
	query($sql);
	
	// Update password?
	if($pw){
		$hash = generateHash($a->salt, $pw);
		$sql= "UPDATE `agents` SET `pw` = '".esc($hash)."' WHERE `agent_id` = $agent_id LIMIT 1";
		query($sql);
	}
	
	exit();
}

//------------------------------------------------


// Agent updating their corporate profile via the dashboard
if($_POST['action'] == 'updateCorporateProfile'){
	$name = cleanInput($_POST['name']);
	$address_1 = cleanInput($_POST['address_1']);
	$address_2 = cleanInput($_POST['address_2']);
	$address_3 = cleanInput($_POST['address_3']);
	$address_4 = cleanInput($_POST['address_4']);
	$postcode = cleanInput($_POST['postcode']);
	$agent_id = cleanInput($_POST['agent_id']);
	
	if(!$agent_id || !is_numeric($agent_id)){
		$agent_id = $_SESSION['agent_id'];
	}
	
	if(!$agent_id){addError("Please log in first");}
	
	if($pw){
		if(strlen($pw)<8 || strlen($pw)>30){
			addError("Your password must be between 8 and 30 characters");
		}
		if($pw != $pwc){addError("Your password confirmation does not match your password");}
	}
	
	$a = new agent;
	$a->retrieve($agent_id);
	
	if(count($GLOBALS['a_errors'])){
		echo '<div>'.implode('</div>', $GLOBALS['a_errors']).'</div>';
		exit();
	}
	
	$sql = "UPDATE `agents` SET `name` = '".esc($name)."',
	`address_1` = '".esc($address_1)."',
	`address_2` = '".esc($address_2)."',
	`address_3` = '".esc($address_3)."',
	`address_4` = '".esc($address_4)."',
	`postcode` = '".esc($postcode)."' WHERE `agent_id` = $agent_id LIMIT 1";
	
	query($sql);
	exit();
}

//------------------------------------------------


// Seller updating their corporate profile via the dashboard
if($_POST['action'] == 'updateCustomerProfile'){
	$cust = new customer;
	$cust->updateProfile();
	exit();
}

//------------------------------------------------


// Agent updating their corporate profile via the dashboard
if($_POST['action'] == 'updateBuyerProfile'){
	$cust = new customer;
	$cust->updateBuyerProfile();
	exit();
}

//------------------------------------------------

// Master agent is activating a colleague from the dashboard
if($_POST['action'] == 'activateAgent'){
	$agent = new agent;
	$agent->activateAgent();
	exit();
}

//------------------------------------------------

// Reading a message from the dashboard
if($_GET['action'] == 'openMessage'){
	$msg = new messenger;
	$msg->show($_GET['msg_id']);
	exit();
}

//------------------------------------------------

// Reading a message from the dashboard
if($_GET['action'] == 'replyMessage'){
	$msg = new messenger;
	$msg->replyWindow($_GET['msg_id']);
	exit();
}

//------------------------------------------------

// Sending a new message
if($_GET['action'] == 'newMessage'){
	$msg = new messenger;
	$msg->newMessage($_GET['id']);
	exit();
}

//------------------------------------------------

// Seller removing a property from their portfolio
if($_GET['action'] == 'removeProperty'){
	$prop_id = $_GET['prop_id'];
	if(!is_numeric($prop_id)){exit();}
	$prop = new property;
	$prop->remove($prop_id);
	exit();
}

//------------------------------------------------

// Load a box of messages
if($_GET['action'] == 'loadMessages'){
	$msg = new messenger;
	if($_GET['type'] == 'unread'){
		$msg->listUnreadMessages(0);
	}
	if($_GET['type'] == 'inbox'){
		$msg->listReceivedMessages(0);
	}
	if($_GET['type'] == 'outbox'){
		$msg->listSentMessages(0);
	}
	if($_GET['type'] == 'deleted'){
		$msg->listDeletedMessages(0);
	}
	if($_GET['type'] == 'pitches'){
		$msg->listPitches(0);
	}
	exit();
}

//------------------------------------------------

if($_GET['action'] == 'newMessageCount'){
	$msg = new messenger;
	exit($msg->unreadMessageCount());
}

//------------------------------------------------

// Agent sending a pitch by clicking the pitch button in their dashboard
if($_GET['action'] == 'sendPitch'){
	$prop->sendPitch($_GET['prop_id']);
	exit();
}

//------------------------------------------------

// Agent sending a pitch by clicking the pitch button in their dashboard
if($_GET['action'] == 'reloadCart'){
	$pay = new payment;
	$pay->payForCodesButton();
	exit();
}

//------------------------------------------------

// User setting the billing period from the cart button in the postcodes screen
if($_GET['action'] == 'setBillingPeriod'){
	
	$val = $_GET['val'];
	$_SESSION['temp_billing_period'] = $val;
	
	if(!$_SESSION['agent_id']){exit();}
	
	if($val == 'monthly'){
		query("UPDATE `agents` SET `billing_period` = 'monthly' WHERE `agent_id` = ".$_SESSION['agent_id']." LIMIT 1");
	}else{
		query("UPDATE `agents` SET `billing_period` = 'yearly' WHERE `agent_id` = ".$_SESSION['agent_id']." LIMIT 1");
	}
	
	exit();
}

//------------------------------------------------

// Someone is closing their account
if($_POST['action'] == 'deleteMyAccount'){
	closeAccount();
	exit();
}

//------------------------------------------------

//Check if an email address exists
if($_POST['action'] == 'emailExists'){
	$email = trim(cleanInput($_POST['email']));
	echo userExists($email);
	exit();
}

//------------------------------------------------

//Check if an agent email address exists and is online
if($_POST['action'] == 'emailExistsAgent'){
	$email = trim(cleanInput($_POST['email']));
	echo userExistsAgent($email);
	exit();
}

//------------------------------------------------

//Check if a postcode is real and returns data from Google maps API
if($_POST['action'] == 'returnCodeFromGoogle'){
	$postcode = trim(cleanInput($_POST['postcode']));
	$map = new map;
	echo $map->postcodeExists($postcode);
	exit();
}

//------------------------------------------------

// An agent filtering available properties from the dashboard filters
if($_POST['action'] == 'filterAgentProperties'){
	$prop->listAgentProperties();
	exit();
}

//------------------------------------------------

// An agent filtering their buyers from the dashboard filters
if($_POST['action'] == 'filterAgentBuyers'){
	$a = new agent;
	$a->listBuyers();
	exit();
}

//------------------------------------------------

if($_GET['action'] == 'setBuyerSeller'){
	$_SESSION['buyer_and_seller'] = 1;
}

//------------------------------------------------

// Return the number of selected postcodes in the signup process
if($_POST['action'] == 'codesSelected'){
	echo count($_SESSION['a_customer_postcodes']);
	exit();
}

//------------------------------------------------

if($_GET['action'] == 'saveEmail'){
	$_SESSION['temp_email'] = $_GET['email'];
	exit();
}

//------------------------------------------------

// New agent signing up
if($_POST['action'] == 'agentSignup'){
	$agent = new agent;
	$agent->signup();
	exit();
}

//------------------------------------------------

// Clear the saved search in the postcode selector
if($_GET['action'] == 'clearfilter'){
	unset($_SESSION['saved_filter']);
	exit();
}

//------------------------------------------------

// Load a specific box of messages
if($_GET['action'] == 'loadMailPanel'){
	$msg = new messenger;
	if($_GET['type']){
		$msg->listFolder($_GET['type']);
	}
	exit();
}

//------------------------------------------------

// Search messages from the dashboard
if($_GET['action'] == 'searchMessages'){
	$msg = new messenger;
	$terms = cleanInput($_GET['terms']);
	$msg->searchReceivedMessages($terms);
	exit();
}

//------------------------------------------------

// Reload panels in the customer dashboards
if($_GET['action'] == 'loadAgentsNew'){
	$agent = new agent;
	$agent->agentSelector('buyer', 'new');
	exit();
}
if($_GET['action'] == 'loadAgentsApproved'){
	$agent = new agent;
	$agent->agentSelector('buyer', 'added');
	exit();
}
if($_GET['action'] == 'loadAgentsBlocked'){
	$agent = new agent;
	$agent->agentSelector('buyer', 'blocked');
	exit();
}

//------------------------------------------------

// Load all messages into main message screen
if($_POST['action'] == 'loadAllMessages'){
	$msg = new messenger;
	$msg->loadAllMessages();
	exit();
}

//------------------------------------------------

// Load a message into the main mess\ge window
if($_POST['action'] == 'loadMessage'){
	if(!$_SESSION['agent_id'] && !$_SESSION['cust_id']){
		exit('You must be logged in first');
	}
	$msg = new messenger();
	$msg->loadMessage($_POST['msg_id']);
	$_SESSION['msg_id'] = $_POST['msg_id'];
	exit();	
}


//------------------------------------------------

// Delete the current message
if($_POST['action'] == 'deleteMessage'){
	$msg = new messenger();
	
	if($_SESSION['msg_id']){
		if($msg->deleteMessage($_SESSION['msg_id'])){
			exit('<div class="pad centerMe">Message deleted</div>');
		}else{
			exit('<div class="pad centerMe">Message delete failed</div>');
		}
	}else{
		exit('<div class="pad centerMe">Please select a message first</div>');
	}
	exit();
}

//------------------------------------------------

// Delete the current message
if($_POST['action'] == 'restoreMessage'){
	$msg = new messenger();
	
	$deleted = result("SELECT `deleted` FROM `messages` WHERE `msg_id` = '".$_SESSION['msg_id']."'");
	
	if(!$deleted){return;}
	
	if($_SESSION['msg_id']){
		if($msg->restoreMessage($_SESSION['msg_id'])){
			exit('<div class="pad centerMe">Message restored to inbox</div>');
		}else{
			exit('<div class="pad centerMe">Message restore failed</div>');
		}
	}else{
		exit('<div class="pad centerMe">Please select a message first</div>');
	}
	exit();
}

//------------------------------------------------


// Star the current message
if($_POST['action'] == 'starMessage'){
	$msg = new messenger();
	
	if($_SESSION['msg_id']){
		$msg->starMessage($_SESSION['msg_id']);
	}else{
		exit('<div class="pad">Please select a message first</div>');
	}
	exit();
}

//------------------------------------------------

// Reply to current message by loading message window
if($_POST['action'] == 'replyMessage'){
	$msg = new messenger();
	
	if($_SESSION['msg_id']){
		$msg->messageForm($_SESSION['msg_id']);
	}else{
		exit('<div class="pad" style="text-align:center">Please select a message first</div>');
	}
	
	exit();
}

//------------------------------------------------

// Reply to current message by loading message window
if($_POST['action'] == 'loadDraft'){
	$msg = new messenger();
	
	if($_POST['msg_id']){
		$msg->messageForm($_POST['msg_id']);
	}else{
		exit('<div class="pad" style="text-align:center">Please select a draft message first</div>');
	}
	
	exit();
}

//------------------------------------------------

// Send a new message to a specific customer
if($_POST['action'] == 'newMessageWin'){
	$msg = new messenger();
	$msg->to_cust_id = $_POST['cust_id'];
	$msg->messageForm('0');
	exit();
}

//------------------------------------------------

if($_GET['action'] == 'clearMsgCookie'){
	unset($_SESSION['msg_id']);
	exit();
}

//------------------------------------------------

// Send a message
if($_POST['action'] == 'sendMessage'){
	$msg = new messenger();
	$msg->send();
	exit();
}

//------------------------------------------------

// Send a message
if($_POST['action'] == 'saveMessageDraft'){
	$msg = new messenger();
	$msg->send('draft');
	exit();
}

//------------------------------------------------

// List buyers/sellers in message screen
if($_GET['action'] == 'listCustomers'){
	$agent = new agent;
	$agent->listMyCustomers($_GET['type']);
	exit();
}

//------------------------------------------------

// Search the contact history
if($_GET['action'] == 'searchHistory'){
	$msg = new messenger;
	if(is_numeric($_GET['agent_id'])){
		$msg->customerContactHistory($_GET['agent_id'], $_GET['terms']);
	}else if(is_numeric($_GET['cust_id'])){
		$msg->agentContactHistory($_GET['cust_id'], $_GET['terms']);
	}
	exit();
}

//------------------------------------------------

// Reload the content of the agents screen in the customer dash
if($_GET['action'] == 'reloadAgents'){
		echo '<div id="agentsNew" class="agentTypePanel">';
        $agent->agentSelector('buyer', 'new');
		echo '</div>';
        
        echo '<div id="agentsApproved" class="agentTypePanel" style="display:none">';
        $agent->agentSelector('buyer', 'added');
		echo '</div>';
        
        echo '<div id="agentsBlocked" class="agentTypePanel" style="display:none">';
        $agent->agentSelector('buyer', 'blocked');
		echo '</div>';
}

//------------------------------------------------

// Loads the preview window in the agent profile editor
if($_GET['action'] == 'loadAgentPreview'){
	include('inc/inc.agent-profile.php');
	exit();
}

//------------------------------------------------

// Loads the agent's default pitch email into the message window
if($_POST['action'] == 'loadProposalTemplate'){
	if(!$_SESSION['agent_id']){exit('Sorry the template could not be found');}
	$a = new agent;
	$a->retrieve($_SESSION['agent_id']);
	$c = new customer;
	$c->retrieve($_POST['cust_id']);
	
	echo 'Dear '.$c->firstname.chr(10).chr(10);
	
	if($_POST['custtype'] == 'seller'){
		echo 'I would really like to talk to you about selling your property.'.chr(10).chr(10);
	}else{
		echo 'I would really like to talk to you about your property requirements.'.chr(10).chr(10);
	}
	
	echo $a->biog.chr(10).chr(10);
	
	echo 'Kind regards'.chr(10);
		echo $a->full_name;
	
	exit();
}

//------------------------------------------------

// Set an agent's profile to published
if($_GET['action'] == 'publishAgentProfile'){
	if(!$_SESSION['agent_id']){exit();}
	query("UPDATE `agents` SET `published` = 1 WHERE `agent_id` = ".$_SESSION['agent_id']." LIMIT 1");
	exit();
}

//------------------------------------------------

// Agent cancelling one of their codes
if($_POST['action'] == 'cancelCodeRequest'){
	$agent = new agent;
	$agent->cancelCodeRequest();
	exit();
}

//------------------------------------------------

// Agent filtering their vendors from the dashboard
if($_GET['action'] == 'loadVendors'){
	$terms = cleanInput($_GET['terms']);
	$type = cleanInput($_GET['type']);
	$agent = new agent;

	if($type == 'sellers'){
		echo '<div class="centerMe" style="height:40px">Matching sellers</div>';
		$agent->listSellers($terms);
	}else if($type == 'buyers'){
		echo '<div class="centerMe" style="height:40px">Matching buyers</div>';
		$agent->listBuyers($terms);
	}
	exit();
}

//------------------------------------------------

exit('Function not found');
?>