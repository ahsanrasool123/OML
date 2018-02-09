<?php // Estate agent control object
class agent{

	function agent(){
		$this->extra_user_accounts = 3;
		if($_POST['action'] == 'agentsignup'){$this->signup();}
		if($_POST['action'] == 'agentlogin'){$this->login();}
		if($_POST['action'] == 'uploadagentlogo'){$this->uploadLogo();}
		
		if($_POST['action'] == 'updateAgent'){$this->updateProfile();}
	}
	
	//--------------------------------------------------
	
	// Update an agent's profile from their admin screen
	function updateProfile(){
		
		if(!$_SESSION['agent_id']){return;}
		
		while(list($k, $v)=each($_POST)){
			$this->$k = cleanInput($v);
			$$k = esc($this->$k);
			#echo "$k = $v";	
		}
		
		if(!$type){return;}
				
		if(!$firstname){
			addError("Please enter your first name");
		}
		if(!$surname){
			addError("Please enter yourlast name");
		}
		if(!$name){
			addError("Please enter your company name");
		}
		if(!$firstname){
			addError("Please enter your biography");
		}
		
		// Save for proview screen
		if($type == 'preview'){
			$_SESSION['agent_profile_preview'] = array();
			$_SESSION['agent_profile_preview']['name'] = $this->name;
			$_SESSION['agent_profile_preview']['firstname'] = $this->firstname;
			$_SESSION['agent_profile_preview']['surname'] = $this->surname;
			$_SESSION['agent_profile_preview']['biog'] = $this->biog;
			$_SESSION['agent_profile_preview']['tmp_name'] = $_FILES['photo']['tmp_name'];
			$_SESSION['agent_profile_make_preview'] = 1;
			return;
		}
		
		// Upload any photo
		$this->uploadPhoto();
		
		if( count($GLOBALS['a_errors']) ){
			return false;
		}
		
		$sql = "UPDATE `agents` SET `firstname` = '$firstname', `surname` = '$surname', `name` = '$name', `biog` = '$biog', `date_updated` = ".time()." WHERE `agent_id` = ".$_SESSION['agent_id']." LIMIT 1";
		#echo $sql;
		query($sql);
		
		
		
		if( count($GLOBALS['a_errors']) ){
			return false;
		}
		
		// Log it
		$log = new logging;
		$log->logMe('Updated personal profile');
		
		$_SESSION['agentProfileUpdate'] = 'complete';
	}
	
	//--------------------------------------------------
	
	// Retrieve a database record as an object
	function retrieve($agent_id){
		if(!$agent_id && !is_numeric($agent_id)){return false;}
		$sql = "SELECT * FROM `agents` WHERE `agent_id` = $agent_id LIMIT 1";
		#echo $sql;
		$res = query($sql);
		if(!num_rows($res)){return false;}
		$rs = fetch_assoc($res);
		while(list($k, $v)=each($rs)){
			$this->$k = $v;
		}
		
		// Address
		$this->a_address = array();
		if($this->address){array_push($this->a_address, $this->address);}
		if($this->address_1){array_push($this->a_address, $this->address_1);}
		if($this->address_2){array_push($this->a_address, $this->address_2);}
		if($this->address_3){array_push($this->a_address, $this->address_3);}
		if($this->address_4){array_push($this->a_address, $this->address_4);}
		if($this->postcode){array_push($this->a_address, $this->postcode);}
		$this->address = implode(chr(10), $this->a_address);
		
		// Logo
		if($this->logo){
			$this->logo_url = 'images/agentlogos/'.$this->logo.'?c='.time();
		}else{
			$this->logo_url = 'images/nophoto.jpg';
		}
		
		// Photo
		if($this->photo){
			$this->photo_url = 'images/agentphotos/'.$this->photo.'?c='.time();
		}else{
			$this->photo_url = 'images/nophoto.jpg';
		}
		
		// Status
		if($this->online == 0){
			$this->status = 'Pending';
		}else{
			$this->status = 'Active';
		}
		
		// Contact name
		if($this->firstname && $this->surname){
			$this->contact_name = $this->firstname.' '.$this->surname;
		}
		
		// full name and title
		if($this->firstname && $this->surname){
			$this->full_name = $this->firstname.' '.$this->surname.', '.$this->name;
		}
		
		// Get the Sripe token
		$this->stripe_token = result("SELECT `token` FROM `stripe_payments` WHERE `agent_id` = '$this->agent_id'");
		
		// When does the current contract expire?
		if($this->contract_start > 1){
			$this->contract_expires = $this->contract_start + ((3600*24)*365);
		}
		
		// Agent rating
		$this->rating = 4;
	}
	
	//--------------------------------------------------
	
	// Delete an agent and their media
	function delete($agent_id){
		if(!$agent_id && !is_numeric($agent_id)){return false;}
		$this->retrieve($agent_id);
		if($this->logo){
			@unlink($_SERVER['DOCUMENT_ROOT'].'/images/agentlogos/'.$this->logo);
		}
		query("DELETE FROM `agents` WHERE `agent_id` = $agent_id LIMIT 1");
		query("DELETE FROM `agent_relationship` WHERE `agent_id` = $agent_id");
		query("DELETE FROM `agent_postcodes` WHERE `agent_id` = $agent_id");
		query("DELETE FROM `messages` WHERE `from_agent_id` = $agent_id OR `to_agent_id` = $agent_id");
		query("DELETE FROM `agent_ratings` WHERE `agent_id` = $agent_id");
		query("DELETE FROM `stripe_payments` WHERE `agent_id` = $agent_id");
		
		// Log it
		$log = new logging;
		$log->agent_id = $agent_id;
		$log->logMe('Deleted agent '.$agent_id);
	}
	
	//--------------------------------------------------
	
	// New agent signing up
	function signup(){

		while(list($k, $v)=each($_POST)){
			$this->$k = cleanInput($v);
			$$k = esc($this->$k);
		}
		
		// Delete any offline registrations with this email address
		$used_agent_id = result("SELECT `agent_id` FROM `agents` WHERE `email` = '".$email."' AND `online` = 0");
		query("DELETE FROM `agents` WHERE `agent_id` = $used_agent_id OR `parent_id` = $used_agent_id AND `online` = 0");
		
		// Validation
		if(!$firstname){addError("Please enter your first name");}
		if(!$surname){addError("Please enter your last name");}
		if(!$name){addError("Please enter your business name");}
		if(!$tel){addError("Please enter your telephone number");}
		if($tel){
			if(strlen($tel) > 20){
				addError("Please enter a valid telephone number");
			}
		}
		if(!$email){addError("Please enter your email address");}
		if($email){
			
			if(!emailValid($email)){
				addError("Please enter your email address");
			}
		}
		if(!$pw){addError("Please select a password");}
		if($pw && (strlen($pw) > 30 || strlen($pw) < 8)){addError("Your password should be between 8 and 30 characters in length");}
		if($pw && !$pwc){addError("Please confirm your password");}
		if($pw && $pwc){
			if($pw != $pwc){
				addError("The password and password confirmation values are different");
			}
		}
		
		// Check if user already exists as an agent
		if($this->exists($this->email)){addError("Sorry, an account already exists for the email address `".$email."`");}
		
		// Check if user already exists as a customer
		if($this->existsAsCustomer($this->email)){addError("Sorry, a customer account already exists for the email address `".$email."`");}

		// Any errors?
		if(count($GLOBALS['a_errors'])){
			echo implode(chr(10), $GLOBALS['a_errors']);
			return false;
		}
		
		// Hash the password
		$salt = makeSalt();
		$hash = generateHash($salt, $pw);
		
		// SQL
		$sql = "INSERT INTO `agents` (
			`name`,
			`firstname`,
			`surname`,
			`email`, 
			`tel`, 
			`address`,
			`postcode`,
			`salt`, 
			`pw`, 
			`date_created`, 
			`date_updated`,
			`online`
		)VALUES(
			'$name', 
			'$firstname',
			'$surname',
			'$email', 
			'$tel',
			'$address',
			'$postcode',
			'$salt', 
			'$hash', 
			'".time()."', 
			'".time()."',
			0
		)";
		
		#exit($sql);
		query($sql);
		
		$this->agent_id = insert_id();
		$_SESSION['temp_agent_id'] = $this->agent_id;
		
		// Log it
		$log = new logging;
		$log->agent_id = $this->agent_id;
		$log->logMe('Agent signed up');
		
		// Tag this user's parent ID which will be the same as the agent ID
		query("UPDATE `agents` SET `parent_id` = $this->agent_id WHERE `agent_id` = $this->agent_id LIMIT 1");
		
		
		
		//Create the other extra agents on this account
		for($i=1; $i <= $this->extra_user_accounts; $i++){
			$sql = "INSERT INTO `agents` (
				`parent_id`,
				`name`, 
				`tel`, 
				`address`,
				`postcode`,
				`salt`, 
				`pw`, 
				`date_created`, 
				`date_updated`,
				`online`
			)VALUES(
				'$this->agent_id',
				'$name', 
				'$tel',
				'$address',
				'$postcode',
				'$salt', 
				'$hash', 
				'".time()."', 
				'".time()."',
				0
			)";
			query($sql);
			
			$this->agent_id = insert_id();
			
			// Log it
			$log = new logging;
			$log->agent_id =$this->agent_id;
			$log->logMe('Agent created a colleague with agent_id: '.$this->agent_id);
		}
		
		// Send the welcome email
		$e = new email();
		$e->sendAgentWelcomeEmail($this->agent_id, $pw);
		
		// Add a welcome message
		$msg = new messenger;
		$msg->agent_id = $this->agent_id;
		$msg->addWelcomeMessage('agent');
		
		return $this->agent_id;
	}
	
	//--------------------------------------------------
	
	// Does this email exist?
	function exists($email){
		$res = query("SELECT `agent_id` FROM `agents` WHERE `email` = '".esc($email)."'");
		if(!num_rows($res)){return 0;}else{return 1;}
	}
	
	//--------------------------------------------------
	
	// Does this email exist?
	function existsAsCustomer($email){
		$res = query("SELECT `cust_id` FROM `customers` WHERE `email` = '".esc($email)."'");
		if(!num_rows($res)){return 0;}else{return 1;}
	}
	
	
	//--------------------------------------------------
	
	// Admin function only
	function loginAs($agent_id){
		$a = new agent;
		$a->retrieve($agent_id);
		if(!$a->online){
			addError("This account is closed");
			return false;
		}
		
		unset($_SESSION['cust_id']);
		$_SESSION['agent_id'] = $a->agent_id;
		$_SESSION['parent_id'] = $a->parent_id;
		$_SESSION['signed_in'] = 'agent';
		
		
		// Log it
		$log = new logging;
		$log->agent_id = $agent_id;
		$log->logMe('Logged in');
		
		header('Location: http://'.$_SERVER['HTTP_HOST'].'/');
		exit();
	}
	
	//--------------------------------------------------
	
	// Renders an agent's logo in a container
	function showLogo($agent_id = 0){
		// Logged in agent
		if($_SESSION['agent_id']){
			if(!$this->email){
				$this->retrieve($_SESSION['agent_id']);
			}
			
			echo '<table class="agentThumb"><tr><td>';
			if($_SESSION['agent_id']){
				echo '<a href="javascript:dashNav(\'editlogo\');" class="editbutton">EDIT</a>';
			}
			echo '<img src="'.$this->logo_url.'?c='.time().'" alt="'.html($this->name).'"/>';
			echo '</td></tr></table>'.chr(10);
			
			return;
		}
		// Agent listing on website
		if($agent_id && is_numeric($agent_id)){
			$a = new agent;
			$a->retrieve($agent_id);
			echo '<div class="agentThumb">';
			echo '<img src="'.$a->logo_url.'" alt="'.html($a->name).'"/>';
			echo '</div>'.chr(10);
		}
	}
	
	//--------------------------------------------------
	
	// Renders an agent's photo in a container
	function showPhoto($agent_id = 0){
		// Logged in agent
		if($_SESSION['agent_id']){
			if(!$this->agent_id){
				$this->retrieve($_SESSION['agent_id']);
			}
			
			echo '<div class="agentThumb" style="background-image:url('.$this->photo_url.'?c='.time().')">';
			if($_SESSION['agent_id'] == $this->agent_id){
				echo '<a href="javascript:dashNav(\'editphoto\');" class="editbutton">EDIT</a>';
			}
			echo '</div>'.chr(10);
			
			return;
		}
		// Agent listing on website
		if($agent_id && is_numeric($agent_id)){
			$a = new agent;
			$a->retrieve($agent_id);
			echo '<div class="agentThumb" style="background-image:url('.$this->photo_url.'?c='.time().')">';
			echo '</div>'.chr(10);
		}
	}
	
	//--------------------------------------------------
	
	// Renders an agent's photo in a container
	function showMiniPhoto($agent_id = 0){
		// Logged in agent
		if($_SESSION['agent_id']){
			if(!$this->email){
				$this->retrieve($_SESSION['agent_id']);
			}
			
			echo '<div class="agentMiniThumb" style="background-image:url('.$this->photo_url.'?c='.time().')">';
			if($_SESSION['agent_id'] == $agent_id){
				echo '<a href="javascript:dashNav(\'editphoto\');" class="editbutton">EDIT</a>';
			}
			echo '</div>'.chr(10);
			
			return;
		}
		// Agent listing on website
		if($agent_id && is_numeric($agent_id)){
			$a = new agent;
			$a->retrieve($agent_id);
			echo '<div class="agentMiniThumb" style="background-image:url('.$this->photo_url.'?c='.time().')">';
			echo '</div>'.chr(10);
		}
	}
	
	//--------------------------------------------------
	
	// Renders an agent's corporate profile in full
	function showProfile($agent_id = 0){
	
		// Agent listing on website (not the logged in agent)
		if($agent_id && is_numeric($agent_id)){
			$a = new agent;
			$a->retrieve($agent_id);
		}
		
		// Logged in agent
		if($_SESSION['agent_id'] || $agent_id == 0){
			$a = new agent;
			$a->retrieve($_SESSION['agent_id']);
		}
		
		// Display the profile
		echo '<div class="agentBiog">';
		
		echo '<div class="table">';
		echo '<div class="row">';
		
		echo '<div class="cell" style="width:180px">';
		
		$a->showLogo();
		
		echo '</div>';
		echo '<div class="cell">';
		echo '<h3>'.html($a->name).'</h3>'.chr(10);
		echo '<p>'.nl2br(html($a->biog)).'</p>';
		echo '<p><strong>Address</strong><br/>'.nl2br(html($a->address)).'</p>';
		echo '<p><strong>Telephone:</strong> '.html($this->tel).'</p>';
		echo '<p><strong>Email:</strong> <a href="mailto:'.$a->email.'">'.html($a->email).'</a></p>';
		
		// Billing cycle
		if($_SESSION['agent_id']){
			echo '<p><strong>Billing period:</strong> '.ucfirst($a->billing_period).'</p>';
		}
		
		echo '</div></div></div>';
		
		echo '</div>'.chr(10);
		return;
	}
	
	
	//--------------------------------------------------
	
	// Renders an agent's personal profile in full
	function showPersonalProfile($agent_id=0){
	
		$a = new agent;
		$a->retrieve($agent_id);
		
		// Display the profile
		echo '<div class="agentBiog">';
		
		// Open table
		echo '<div class="table">';
		echo '<div class="row">';
		echo '<div class="cell" style="width:180px">';
		
		$a->showPhoto($agent_id);
		
		echo '</div>'.chr(10);
		echo '<div class="cell">'.chr(10);
		
		if(!$a->email){// Not yet activated
			echo '<h3>This profile has not yet been activated</h3>';
			
			// Activate button
			echo '<div onclick="showActivForm(\''.$a->agent_id.'\')" class="button activateButton" id="actb_'.$a->agent_id.'">Activate now</div>';
			
			// activeate form for extra agents
			echo '<div id="act_'.$a->agent_id.'" class="agentActivBox">';
			echo '<form name="activeateb" method="post">';
			echo '<div id="errorboxact_'.$a->agent_id.'" class="errorbox"></div>';
			echo '<p>Please enter your coleague\'s email address and select a password. The login details will be sent to them via email.</p>';
			
			// Firstname
			formField('firstname_'.$a->agent_id, $_POST['firstname'], 'First name');
			
			// Surname
			formField('surname_'.$a->agent_id, $_POST['surname'], 'Last name');
			
			// Email
			formField('email_'.$a->agent_id, $_POST['email'], 'Email address');
			
			// Password
			passwordField('pw_'.$a->agent_id, $_POST['pw'], 'Choose a password');
			
			// Password confirmation
			passwordField('pwc_'.$a->agent_id, $_POST['pwc'], 'Confirm your password');
			
			echo '<div id="okboxact_'.$a->agent_id.'" class="okbox"><p>The profile has been activated and the user notified by email.</p></div>';
			echo '<input type="button" name="activateme" value="Activate account" onclick="activateAgent('.$a->agent_id.')"/>';
			echo '<input type="hidden" name="agent_id_'.$a->agent_id.'" id="agent_id_'.$a->agent_id.'" value="'.$a->agent_id.'">';
			
			echo '</form>';
			echo '</div>';
			
		}else{
			echo '<h3>'.html($a->contact_name).'</h3>';
			echo '<p><strong>Biography:</strong><br/>'.nl2br(html($a->biog)).'</p>';
			echo '<p><strong>Email:</strong><br/>'.html($a->email).'</p>';
			echo '<p><strong>Telephone:</strong><br/>'.html($a->tel).'</p>';
			
			// Billing cycle
			if($_SESSION['agent_id']){
				echo '<p><strong>Billing period:</strong> '.ucfirst($a->billing_period).'</p>';
			}
			
			echo '<p><strong>Your pitch message content:</strong><br/>'.nl2br(html($a->pitch)).'</p>';
		}
		
		// Close table
		echo '</div>'.chr(10);
		echo '</div>'.chr(10);
		echo '</div>'.chr(10);

		// Close div		
		echo '</div>'.chr(10);
		return;
	}
	
	
	//--------------------------------------------------
	
	// Allow the agent to add a photo to their profile
	function uploadPhoto(){
		
		// Have we a file at all?
		if(!$_FILES['photo']['name']){
			#addError("Please select a file");
			return;
		}
		
		// Any file errors?
		if($_FILES['photo']['error']){
			addError("Sorry your file has an error #".$_FILES['photo']['error']." and cannot be used");
			return;
		}
		
		// Check the file type
		$ext = strtolower(substr($_FILES['photo']['name'], strrpos($_FILES['photo']['name'], '.')+1, strlen($_FILES['photo']['name'])));
		$a_allowed = array('jpg', 'jpeg', 'gif', 'png');
		if(!in_array($ext, $a_allowed)){
			addError("Sorry, your file is not one of our supported image formats. Please use JPEG, GIF or PNG files.");
			return;
		}
		
		// Check the file size
		$mb_limit = 30;
		$size_limit = $mb_limit*(1000*1000);
		if( $_FILES['photo']['size'] > $size_limit ){
			addError("Sorry your file size exceeds the ".$mb_limit."Mb allowed for photos. Please reduce it's size and try again");
			return;
		}
		
		$src = $_FILES['photo']['tmp_name'];
		$dest = $_SERVER['DOCUMENT_ROOT'].'/images/agentphotos/orig/'.$_FILES['photo']['name'];
		
		// Upload it
		if(!@copy($src, $dest)){
			addError("Sorry your photo could not be uploaded at this time. Please check server permissions for `".$dest."`");
			return;
		}
		
		// Make the web usable version
		$new_filename = $_SESSION['agent_id'].'.jpg';
		$thumb = $_SERVER['DOCUMENT_ROOT'].'/images/agentphotos/'.$new_filename;
		if(!createThumb($dest, $thumb, 500)){
			addError("The thumbnail file could not be created");
			unlink($dest);
			return;
		}
		
		// Log the name in the database against all agents on this parent_id
		$sql = "UPDATE `agents` SET `photo` = '".$new_filename."', `date_updated` = ".time()." WHERE `agent_id` = '".esc($_SESSION['agent_id'])."' LIMIT 1";
		query($sql);
		
		// Delete the original photo
		unlink($dest);
		
		// Log it
		$log = new logging;
		$log->logMe('Updated photo');
		
	}
	
	//--------------------------------------------------
	
	// Allow the agent to add a logo to their profile
	function uploadLogo(){
		
		// Have we a file at all?
		if(!$_FILES['logo']['name']){
			addError("Please select a file");
			return;
		}
		
		// Any file errors?
		if($_FILES['logo']['error']){
			addError("Sorry your file has an error #".$_FILES['logo']['error']." and cannot be used");
			return;
		}
		
		// Check the file type
		$ext = strtolower(substr($_FILES['logo']['name'], strrpos($_FILES['logo']['name'], '.')+1, strlen($_FILES['logo']['name'])));
		$a_allowed = array('jpg', 'jpeg', 'gif', 'png');
		if(!in_array($ext, $a_allowed)){
			addError("Sorry, your file is not one of our supported image formats. Please use JPEG, GIF or PNG files.");
			return;
		}
		
		// Check the file size
		$mb_limit = 30;
		$size_limit = $mb_limit*(1000*1000);
		if( $_FILES['logo']['size'] > $size_limit ){
			addError("Sorry your file size exceeds the ".$mb_limit."Mb allowed for logos. Please reduce it's size and try again");
			return;
		}
		
		$src = $_FILES['logo']['tmp_name'];
		$dest = $_SERVER['DOCUMENT_ROOT'].'/images/agentlogos/orig/'.$_FILES['logo']['name'];
		
		// Upload it
		if(!@copy($src, $dest)){
			addError("Sorry your logo could not be uploaded at this time. Please check server permissions for `".$dest."`");
			return;
		}
		
		// Make the web usable version
		$new_filename = $_SESSION['agent_id'].'.jpg';
		$thumb = $_SERVER['DOCUMENT_ROOT'].'/images/agentlogos/'.$new_filename;
		if(!createThumb($dest, $thumb, 500)){
			addError("The thumbnail file could not be created");
			unlink($dest);
			return;
		}
		
		// Log the name in the database against all agents on this parent_id
		$sql = "UPDATE `agents` SET `logo` = '".$new_filename."', `date_updated` = ".time()." WHERE `agent_id` = '".esc($_SESSION['agent_id'])."' OR `parent_id` = '".esc($_SESSION['agent_id'])."'";
		query($sql);
		
		// Log it
		$log = new logging;
		$log->logMe('Updated logo');
		
		// Delete the original logo
		unlink($dest);
		return true;
	}
	
	//--------------------------------------------------
	
	// Return the number of matching agents for a customer
	function customersAgentCount($custtype){
		$this->return_agent_count = 1;
		return $this->agentSelector($custtype, 'new');
	}
	
	//--------------------------------------------------
	
	// Lists the customers's selected agents in the dashboard screen
	function agentSelector($custtype='seller', $type = 'new'){
		
		if(!$_SESSION['cust_id']){return false;}

		// Get the IDs of all selected agents for this customer
		if($type == 'new'){
			$sql = "SELECT `agent_id` FROM `agents` WHERE `online` = 1 AND `published` = 1 AND `agent_id` NOT IN (SELECT DISTINCT(`agent_id`) FROM `agent_relationship` WHERE `cust_id` = ".$_SESSION['cust_id']." AND `role` = '".$_SESSION['current_dash']."')";
		}else if($type == 'added'){
			$sql = "SELECT `agent_id` FROM `agents` WHERE `online` = 1 AND `published` = 1 AND `agent_id` IN (SELECT DISTINCT(`agent_id`) FROM `agent_relationship` WHERE `cust_id` = ".$_SESSION['cust_id']." AND `status` = 'added' AND `role` = '".$_SESSION['current_dash']."')";
		}else if($type == 'blocked'){
			$sql = "SELECT `agent_id` FROM `agents` WHERE `online` = 1 AND `published` = 1 AND `agent_id` IN (SELECT DISTINCT(`agent_id`) FROM `agent_relationship` WHERE `cust_id` = ".$_SESSION['cust_id']." AND `status` = 'blocked' AND `role` = '".$_SESSION['current_dash']."')";
		}
		#echo $sql.'<hr/>';
		$res = query($sql);
		
		$a_selected_agents = array();
		if(num_rows($res)){
			while($rs = fetch_assoc($res)){
				array_push($a_selected_agents, $rs['agent_id']);
			}
		}
		
		// Find out which postcodes the customer has selected
		$a_codes = array();
		if($type == 'buyer'){
			$res = query("SELECT `code` FROM `buyer_postcodes` WHERE `cust_id` = ".$_SESSION['cust_id']);
			while($rs = fetch_assoc($res)){
				$a_codes[$rs['code']] = $rs['code'];
			}
		}else{
			$res = query("SELECT DISTINCT(`code`) FROM `property` WHERE `cust_id` = ".$_SESSION['cust_id']);
			while($rs = fetch_assoc($res)){
				$a_codes[$rs['code']] = $rs['code'];
			}
		}
		#print_r($a_codes);
	
		// Find agents that match the customer's codes
		$a_agent_id_matching_codes = array();
		if(count($a_codes)){
			$sql2 = "SELECT DISTINCT(`agent_id`) FROM `agent_postcodes` WHERE `code` IN ('".implode("','", $a_codes)."')";
			$res = query($sql2);
			while($rs = fetch_assoc($res)){
				array_push($a_agent_id_matching_codes, $rs['agent_id']);
			}
		}
		if(!count($a_agent_id_matching_codes)){// Fall back if there were no postcode matching agents
		 	$a_agent_id_matching_codes = array(0);
		}
		#print_r($a_agent_id_matching_codes);
	
		// Build clause
		if(count($a_agent_id_matching_codes)){
			$clause = " AND `agent_id` IN(".implode(',', $a_agent_id_matching_codes).")";
		}
		
		// Build the final SQL query
		if($type == 'new'){
			if(count($a_selected_agents)){
				$clause2 = " NOT IN(".implode(", ", $a_selected_agents).")";
			}
			$sql = "SELECT * FROM `agents` WHERE `email` != '' AND `online` = 1 AND `agent_id` $clause2 ORDER BY `name`";
		}else if($type == 'added'){// Added agents
			if(count($a_selected_agents)){
				$clause2 =  " AND `agent_id` IN(".implode(", ", $a_selected_agents).")";
				$sql = "SELECT * FROM `agents` WHERE `email` != '' AND `online` = 1 $clause $clause2 ORDER BY `name`";
			}
		}else if($type == 'blocked'){// Blocked agents
			if(count($a_selected_agents)){
				$clause2 =  " AND `agent_id` IN(".implode(", ", $a_selected_agents).")";
				$sql = "SELECT * FROM `agents` WHERE `email` != '' AND `online` = 1 $clause $clause2 ORDER BY `name`";
			}
		}

		
		// List all added agents
		if($type == 'added'){// Added
			$sql = "SELECT `agent_id` FROM `agent_relationship` WHERE `status` = '$type' AND `cust_id` = '".$_SESSION['cust_id']."'  AND `role` = '".esc($_SESSION['current_dash'])."'";
		}else if($type == 'blocked'){ // Blocked
			$sql = "SELECT `agent_id` FROM `agent_relationship` WHERE `status` = '$type' AND `cust_id` = '".$_SESSION['cust_id']."' AND `role` = '".esc($_SESSION['current_dash'])."'";
		}else{// New and unmolested
			#if(count($a_selected_agents)){
				$sql = "SELECT `agent_id` FROM `agents` WHERE `email` != '' AND `online` = 1 AND `published` = 1 AND `agent_id` IN (".implode(', ', $a_agent_id_matching_codes).") AND `agent_id` NOT IN (SELECT DISTINCT(`agent_id`) FROM `agent_relationship` WHERE `cust_id` = ".$_SESSION['cust_id'].")";
			#}else{
				#$sql = "SELECT `agent_id` FROM `agents` WHERE `email` != '' AND `online` = 1 AND `published` = 1 AND `agent_id` IN (".implode(', ', $a_agent_id_matching_codes).")";
			#}
		}

		#echo $sql;
		$res = query($sql);
		
		// If we're only returning the number of matching agents do this:
		if($this->return_agent_count){
			return num_rows($res);
		}
		
		// No matching agents
		if(!num_rows($res)){
			if($type == 'new'){
				echo '<div class="centerMe" style="min-height:100px">No new matching agents</div>';
			}else if($type == 'added'){
				echo '<div class="centerMe" style="min-height:100px">You have not added any agents</div>';
			}else{
				echo '<div class="centerMe" style="min-height:100px">You have not blocked any agents</div>';
			}
			return;
		}
		
		echo '<div id="agentSelector">'.chr(10);

		// Matches
		while($rs = fetch_assoc($res)){// loop
			if($_SESSION['current_dash'] == 'buyer'){
				$this->agentResultRowBuyer($rs['agent_id']);
			}else{
				$this->agentResultRowSeller($rs['agent_id']);
			}
		}
		
		echo '</div>'.chr(10).chr(10);
	}
	

	//--------------------------------------------------
	
	// Displays a matching agent as a <li> row for sellers
	function agentResultRowSeller($agent_id){
			$a = new agent;
			$a->retrieve($agent_id);
			
			if(!$a->online){return false;}
			
			$role = esc($_SESSION['current_dash']);
			
			$blocked = result("SELECT COUNT(`id`) FROM `agent_relationship` WHERE `cust_id` = '".esc($_SESSION['cust_id'])."' AND `agent_id` = '".esc($agent_id)."' AND `status` = 'blocked' AND `role` = '$role'");
			$added = result("SELECT COUNT(`id`) FROM `agent_relationship` WHERE `cust_id` = '".esc($_SESSION['cust_id'])."' AND `agent_id` = '".esc($agent_id)."' AND `status` = 'added' AND `role` = '$role'");
			
			
			if($blocked){$legend1 = 'Unblock';}else{$legend1 = 'Block';}
			if($added){$legend2 = 'Remove';}else{$legend2 = 'Add';}
	
			// Row 3
			echo '<div class="agentsRow3" id="agent_'.$a->agent_id.'">
			
				<div class="agentsDetail" onclick="loadAgentProf('.$a->agent_id.')">
                    <div class="agentThumb" style="background-image:url('.$a->photo_url.')"></div>
                    <div class="vCenter agentName" style="padding:8px">'.html($a->contact_name).'<br/>'.html($a->name).'</div>
                </div>'.chr(10);
            
			    
            echo '<div class="agentButtons" style="display:flex">';
			if(!$added){
            	echo '  <div class="centerMe" onclick="addAgent('.$a->agent_id.')"><img src="../images/button-allow-contact.png" alt="Allow contact" title="Allow contact"/></div>'.chr(10);
            }
			if(!$blocked){
				echo '  <div class="centerMe" onclick="blockAgent(\''.$a->agent_id.'\');" id="blockButton_'.$a->agent_id.'"><img src="../images/button-block.png" alt="'.$legend1.'" title="Block"/></div>'.chr(10);
            }
			echo '</div>'.chr(10);
				
			echo '</div>'.chr(10).chr(10);

	
	}
	
	//--------------------------------------------------
	
	// Displays a matching agent as a <li> row for buyers
	function agentResultRowBuyer($agent_id){
		
			$a = new agent;
			$a->retrieve($agent_id);
			
			if(!$a->online){return false;}
			
			$role = esc($_SESSION['current_dash']);
			
			$blocked = result("SELECT COUNT(`id`) FROM `agent_relationship` WHERE `cust_id` = '".esc($_SESSION['cust_id'])."' AND `agent_id` = '".esc($agent_id)."' AND `status` = 'blocked' AND `role` = '$role'");
			$added = result("SELECT COUNT(`id`) FROM `agent_relationship` WHERE `cust_id` = '".esc($_SESSION['cust_id'])."' AND `agent_id` = '".esc($agent_id)."' AND `status` = 'added' AND `role` = '$role'");
			
			
			if($blocked){$legend1 = 'Unblock';}else{$legend1 = 'Block';}
			if($added){$legend2 = 'Remove';}else{$legend2 = 'Add';}
	
			// Row 3
			echo '<div class="agentsRow3" id="agent_'.$a->agent_id.'">';
			
			
				// Column 1
				echo '<div class="agentsDetail col1" onclick="loadAgentProf('.$a->agent_id.')">';
					echo '<div class="agentThumb" style="background-image:url('.$a->photo_url.')"></div>';
                	echo '<div class="vCenter agentName" style="padding:8px">'.html($a->contact_name).'<br/>'.html($a->name).'</div>';
                echo '</div>'.chr(10);
            
			   
			// Column 2
            echo '<div class="agentButtons centerMe col2">';

				// Allow contact
				echo '<div onclick="addAgent('.$a->agent_id.')"><img src="../images/b-contact.gif" alt="Allow contact" title="Allow contact from this agent"/></div>'.chr(10);
				
				// Mute
				echo '<div onclick="blockAgent('.$a->agent_id.')"><img src="../images/b-mute.gif" alt="Mute agent" title="Mute agent"/></div>'.chr(10);

				// Block
				echo '<div onclick="blockAgent('.$a->agent_id.')"><img src="../images/b-block.gif" alt="Block agent" title="Block agent"/></div>'.chr(10);
			
			echo '</div>'.chr(10);
			
			
			
			// Col 3	
			echo '<div class="centerMe col3">';
				echo '<img src="../images/b-proposals.gif" alt="'.$legend1.'" title="View proposals from this agent" onclick="viewProposals(\''.$a->agent_id.'\');" id="blockButton_'.$a->agent_id.'"/>';
			echo '</div>'.chr(10);
            
			
			
			
			// Close agentsRow3
			echo '</div>'.chr(10).chr(10);

	
	}
	
	//--------------------------------------------------
	

	// Generate a list of the customers that have added this agent as a fave
	function listMyCustomers($type='seller'){
	
		if(!$_SESSION['agent_id']){return false;}
	
		$res = query("SELECT * FROM `customers` WHERE `online` = 1 AND `cust_id` IN(SELECT DISTINCT(`cust_id`) FROM `agent_relationship` WHERE `agent_id` = ".$_SESSION['agent_id'].") ORDER BY `surname`");
		

		if(!num_rows($res)){// None
			echo '<p>No '.$type.'s have selected you at this time</p>';
			return;
		}
		
		while($rs = fetch_assoc($res)){ // loop
			$c = new customer;
			$c->retrieve($rs['cust_id']);
			if($type == 'seller' && $c->seller){
				echo '<div class="customerRow vCenter" onclick="newMessageWin('.$c->cust_id.')" title="Click here to send '.html($c->firstname).' a message">'.chr(10);
				echo '<div>'.html($c->name).'</div>'.chr(10);
				echo '</div>'.chr(10).chr(10);
			}
			if($type == 'buyer' && $c->buyer){
				echo '<div class="customerRow vCenter buyerIcon" onclick="newMessageWin('.$c->cust_id.')" title="Click here to send '.html($c->firstname).' a message">'.chr(10);
				echo '<div>'.html($c->name).'</div>'.chr(10);
				echo '</div>'.chr(10).chr(10);
			}
		}

	}
	
	//--------------------------------------------------
	
	// Report on the status of selected postcodes in the agent's account
	function postcodeReport($agent_id){
		echo '<div>Pending: '.$this->postcodesPending($agent_id).'</div>';
		echo '<div>Active: '.$this->postcodesLive($agent_id).'</div>';
		echo '<div>Expired: '.$this->postcodesExpired($agent_id).'</div>';
	}
	
	//--------------------------------------------------
	
	// Returns count of postcodes selected but not yet active
	function postcodesPending($agent_id){
		return result("SELECT COUNT(`id`) FROM `agent_postcodes` WHERE `agent_id` = $agent_id AND (`date_from` = 0 OR `date_to` = 0)");
	}
	
	//--------------------------------------------------
	
	// Returns count of live paid for postcodes
	function postcodesLive($agent_id){
		return result("SELECT COUNT(`id`) FROM `agent_postcodes` WHERE `agent_id` = $agent_id AND (`date_from` <> 0 AND `date_to` != 0) AND (`date_from` <= ".time()." AND `date_to` > ".time().")");
	}
	
	//--------------------------------------------------
	
	// Returns count of postcodes that have expired
	function postcodesExpired($agent_id){
		return result("SELECT COUNT(`id`) FROM `agent_postcodes` WHERE `agent_id` = $agent_id AND (`date_to` <> 0 AND `date_to` < ".time().") ");
	}
	
	//--------------------------------------------------

	// Admin feature - make a postcode active for an agent for one month
	function makePostcodeActive($id){
		if(!is_numeric($id)){return false;}
		$then = time()+(86400*30);
		$sql = "UPDATE `agent_postcodes` SET `date_from` = ".time().", `date_to` = ".$then." WHERE `id` = $id";
		query($sql);
		header('Location: agent_postcodes.php?agent_id='.$_GET['agent_id'].'&page='.$_GET['page']);
	}
	
	//--------------------------------------------------

	// Admin feature - make a postcode expire now
	function makePostcodeExpired($id){
		if(!is_numeric($id)){return false;}
		$then = time()+(86400*30);
		$sql = "UPDATE `agent_postcodes` SET `date_to` = ".time()." WHERE `id` = $id";
		query($sql);
		header('Location: agent_postcodes.php?agent_id='.$_GET['agent_id'].'&page='.$_GET['page']);
	}
	
	//--------------------------------------------------
	
	// Returns the status of an agent's selected postcode
	function postcodeStatus($rs){
		if(!$rs['date_from']){return 'Pending';}
		if($rs['date_to'] < time()){return 'Expired';}
		if($rs['date_from'] < time() && $rs['date_to'] > time()){return 'Active';}
		return 'Changed';
	}
	
	//--------------------------------------------------
	
	// Display the colleagues in the agent dashboard
	function showColleagues(){
		$res = query("SELECT `agent_id` FROM `agents` WHERE `parent_id` = '".esc($_SESSION['parent_id'])."' AND `agent_id` != ".$_SESSION['agent_id']."");
		while($rs = fetch_assoc($res)){
			echo '<div class="colleageBox">';
			$this->showPersonalProfile($rs['agent_id']);
			echo '</div>';
		}
		return;
	}
	
	//--------------------------------------------------
	
	// Activaes an agent account (used by the main admin agent)
	function activateAgent(){
		while(list($k, $v) = each($_POST)){
			$this->$k = cleanInput($v);
			$$k = esc($v);
		}
		
		if(!$agent_id || !is_numeric($agent_id)){addError("Please select an agent first");}
		if(userExists($this->email)){
			addError("An account already exists for this email address");
		}
		if(count($GLOBALS['a_errors'])){
			echo '<div>'.implode('</div>', $GLOBALS['a_errors']).'</div>';
			exit();
		}
		
		if(!$firstname){addError("Please enter your first name");}
		if(!$surname){addError("Please enter your last name");}
		
		if(!$email){addError("Please enter an email address");}
		if(!emailValid($this->email)){addError("Please enter a valid email address");}
		if(!$pw){addError("Please enter a password");}
		if($pw){
			if(strlen($pw)<8 || strlen($pw)>30){
				addError("The password needs to be between 8 and 30 characters");
			}
		}
		
		$parent_id = result("SELECT `parent_id` FROM `agents` WHERE `agent_id` = $agent_id");
		if($parent_id != $_SESSION['agent_id']){
			addError("You do not have permissions to change details on this account");
		}
		
		// Any errors?
		if(count($GLOBALS['a_errors'])){
			echo '<div>'.implode('</div>', $GLOBALS['a_errors']).'</div>';
			exit();
		}
		
		// Generate the hash
		$salt = result("SELECT `salt` FROM `agents` WHERE `agent_id` = $agent_id");
		$hash = generateHash($salt, $this->pw);
		
		$sql = "UPDATE `agents` SET `firstname` = '$firstname', `surname` = '$surname', `email` = '$email', `pw` = '$hash', `online` = 1, `date_updated` = ".time()." WHERE `agent_id` = $agent_id LIMIT 1";
		#echo $sql;
		query($sql);

		// Send the new user a welcome email with login details
		$email = new email;
		$email->activatedAgentLoginEmail($this->firstname, $this->email, $this->pw);
		
		// Log it
		$log = new logging;
		$log->agent_id = $agent_id;
		$log->logMe('Activated agent account '.$agent_id);
		
		return true;
	}
	
	//--------------------------------------------------
	
	// Toggles an agent online/offline
	function toggleOnline($agent_id){
		if(!$agent_id || !is_numeric($agent_id)){return false;}
		
		$online = result(query("SELECT `online` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'"), 0);
		
		if($online){
			query("UPDATE `agents` SET `online` = 0, `auto_renew` = 0 WHERE `agent_id` = '".esc($agent_id)."' LIMIT 1");
			
			// Log it
			$log = new logging;
			$log->agent_id = $agent_id;
			$log->logMe('Toggled agent account offline');
			
		}else{
			query("UPDATE `agents` SET `online` = 1, `auto_renew` = 1 WHERE `agent_id` = '".esc($agent_id)."' LIMIT 1");
			
			// Log it
			$log = new logging;
			$log->agent_id = $agent_id;
			$log->logMe('Toggled agent account online');
		}
	}
	
	//--------------------------------------------------
	
	// Toggles an agent published status
	function togglePublished($agent_id){
		if(!$agent_id || !is_numeric($agent_id)){return false;}
		
		$pub = result(query("SELECT `published` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'"), 0);
		
		if($pub){
			query("UPDATE `agents` SET `published` = 0 WHERE `agent_id` = '".esc($agent_id)."' LIMIT 1");
			
			// Log it
			$log = new logging;
			$log->agent_id = $agent_id;
			$log->logMe('Set agents account to NOT published');
			
		}else{
			query("UPDATE `agents` SET `published` = 1 WHERE `agent_id` = '".esc($agent_id)."' LIMIT 1");
			
			// Log it
			$log = new logging;
			$log->agent_id = $agent_id;
			$log->logMe('Set agent account to published');
		}
	}
	
	//--------------------------------------------------
	
	// Show a list of buyers in the agent's areas
	function listBuyers($filter=''){

		// Filters from the dashboard via AJAX
		$bedrooms = $_POST['type'];
		$value = $_POST['value'];
		
		/*
		if($_POST['bedrooms'] && $_POST['bedrooms'] != 'Please select...'){
			$filterquery = " AND `bedrooms` = '".esc(cleanInput($_POST['bedrooms']))."'";
		}
		if($_POST['price_range'] && $_POST['price_range'] != 'Please select...'){
			$filterquery .= " AND `price_range` = '".esc(cleanInput($_POST['price_range']))."'";
		}
		*/
			
		$subquery = "SELECT DISTINCT(`code`) FROM `agent_postcodes` WHERE `agent_id` = '".esc($_SESSION['agent_id'])."'";
		$sql = "SELECT DISTINCT(`cust_id`) FROM `seller_postcodes` WHERE `code` IN($subquery)";
		$sql .= " AND `cust_id` IN(SELECT `cust_id` FROM `agent_relationship` WHERE `agent_id` = ".$_SESSION['agent_id']." AND `status` = 'added' AND `role` = 'buyer')";		
		
		// Temp sql
		$sql = "SELECT `cust_id` FROM `agent_relationship` WHERE `agent_id` = ".$_SESSION['agent_id']." AND `status` = 'added' AND `role` = 'buyer'";

		//echo $sql;
		$res = query($sql);
		if(!num_rows($res)){
			echo '<div class="centerMe" style="height:80px">You don\'t have any buyers yet.</div>';
			return;
		}
		
		$a_matching_customers = array();
		while($rs = fetch_assoc($res)){
			array_push($a_matching_customers, $rs['cust_id']);
		}
		
		$sql = "SELECT DISTINCT(`cust_id`) FROM `agent_relationship` WHERE `agent_id` = '".$_SESSION['agent_id']."' AND `status` = 'added' AND `role` = 'buyer' AND `cust_id` IN(".implode(', ', $a_matching_customers).")";
		$res = query($sql);
		
		if(!num_rows($res)){
			echo '<div class="noMessages">You don\'t have any buyers yet</div>';
			return;
		}
		
		// List em
		echo '<ul>'.chr(10);
		while($rs = fetch_assoc($res)){
			$c = new customer;
			$c->showMiniProfile($rs['cust_id'], 'buyer', $filter);
		}
		echo '</ul>';
	}
	
	//--------------------------------------------------
	
	// Show a list of buyers in the agent's areas
	function listSellers($filter=''){
	
		// Filters from the dashboard via AJAX
		$bedrooms = $_POST['type'];
		$value = $_POST['value'];
		
		/*
		if($_POST['bedrooms'] && $_POST['bedrooms'] != 'Please select...'){
			$filterquery = " AND `bedrooms` = '".esc(cleanInput($_POST['bedrooms']))."'";
		}
		if($_POST['price_range'] && $_POST['price_range'] != 'Please select...'){
			$filterquery .= " AND `price_range` = '".esc(cleanInput($_POST['price_range']))."'";
		}
		*/
		
		$subquery = "SELECT DISTINCT(`code`) FROM `agent_postcodes` WHERE `agent_id` = '".esc($_SESSION['agent_id'])."'";
		$sql = "SELECT DISTINCT(`cust_id`) FROM `seller_postcodes` WHERE `code` IN($subquery)";
		$sql .= " AND `cust_id` IN(SELECT `cust_id` FROM `customers` WHERE `seller` = 1 AND `online` = 1 $filterquery)";		
		
		// Temp sql
		$sql = "SELECT `cust_id` FROM `agent_relationship` WHERE `agent_id` = ".$_SESSION['agent_id']." AND `status` = 'added' AND `role` = 'seller'";

		$res = query($sql);
		if(!num_rows($res)){
			echo '<div class="centerMe" style="height:80px">You don\'t have any sellers yet</div>';
			return;
		}
		
		$a_matching_customers = array();
		while($rs = fetch_assoc($res)){
			array_push($a_matching_customers, $rs['cust_id']);
		}
		
		$sql = "SELECT DISTINCT(`cust_id`) FROM `agent_relationship` WHERE `agent_id` = '".$_SESSION['agent_id']."' AND `status` = 'added' AND `role` = 'seller' AND `cust_id` IN(".implode(', ', $a_matching_customers).")";
		$res = query($sql);
		
		if(!num_rows($res)){
			echo '<div class="noMessages">You don\'t have any sellers yet</div>';
			return;
		}
		
		// List em
		echo '<ul>'.chr(10);
		while($rs = fetch_assoc($res)){
			$c = new customer;
			$c->showMiniProfile($rs['cust_id'], 'seller', $filter);
		}
		echo '</ul>';
	}
	
	//--------------------------------------------------
	
	// Toggles the auto-renew flag on an agent's postcode
	function toggleCodeAutoRenewal($id){
		if(!is_numeric($id)){return;}
		
		$code = result("SELECT `code` FROM `agent_postcodes` WHERE `id` = $id");
		
		$auto_renew = result("SELECT `auto_renew` FROM `agent_postcodes` WHERE `id` = $id LIMIT 1");
		if($auto_renew){
			query("UPDATE `agent_postcodes` SET `auto_renew` = 0 WHERE `id` = $id LIMIT 1");
			
			// Log it
			$log = new logging;
			$log->logMe('Toggled code autorenew to `off` for code '.$code);
			
		}else{
			query("UPDATE `agent_postcodes` SET `auto_renew` = 1 WHERE `id` = $id LIMIT 1");
			
			// Log it
			$log = new logging;
			$log->logMe('Toggled code autorenew to `on` for code '.$code);
		}
	
		if($_SERVER['SCRIPT_NAME'] == '/cms/agent_postcodes.php'){
			header('Location: http://'.$_SERVER['HTTP_HOST'].'/cms/agent_postcodes.php?agent_id='.$_GET['agent_id']);
			exit();
		}
	}
	
	//--------------------------------------------------

	// Alert agents of new buyers on their patch
	function alertAgentsNewBuyers(){
	
		$start = strtotime("Last month");
		
		$res = query("SELECT `agent_id` FROM `agents` WHERE `online` = 1 AND `stripe_cust_id` != ''");
		if(!num_rows($res)){return false;}
		
		while($rs = fetch_assoc($res)){
			
			// Get the agent's codes
			$a_agent_codes = array();
			$res2 = query("SELECT `code` FROM `agent_postcodes` WHERE `agent_id` = ".$rs['agent_id']." AND `date_to` > ".time());
			while($rs2 = fetch_assoc($res2)){
				array_push($a_agent_codes, $rs2['code']);
			}
			
			// Get customers that match the agent's code
			$a_cust_ids = array();
			$res3 = query("SELECT DISTINCT(`cust_id`) FROM `buyer_postcodes` WHERE `date_created` > $start AND `code` IN ('".implode("','", $a_agent_codes)."')");
			while($rs3 = fetch_assoc($res3)){
				array_push($a_cust_ids, $rs3['cust_id']);
			}
			
			// Send emails to agents
			if(count($a_cust_ids)){
				$email = new email;
				$email->sendBuyerUpdateToAgent($rs['agent_id'], $a_cust_ids);
			}
			
		}
		
	}
	
	//--------------------------------------------------

	// Alert agents of new buyers on their patch
	function alertAgentsNewSellers(){
	
		$start = strtotime("Last month");
		
		$res = query("SELECT `agent_id` FROM `agents` WHERE `online` = 1 AND `stripe_cust_id` != ''");
		if(!num_rows($res)){return false;}
		
		while($rs = fetch_assoc($res)){
			
			// Get the agent's codes
			$a_agent_codes = array();
			$res2 = query("SELECT `code` FROM `agent_postcodes` WHERE `agent_id` = ".$rs['agent_id']." AND `date_to` > ".time());
			while($rs2 = fetch_assoc($res2)){
				array_push($a_agent_codes, $rs2['code']);
			}
			
			// Get customers that match the agent's code
			$a_cust_ids = array();
			$res3 = query("SELECT DISTINCT(`cust_id`) FROM `seller_postcodes` WHERE `date_created` > $start AND `code` IN ('".implode("','", $a_agent_codes)."')");
			while($rs3 = fetch_assoc($res3)){
				array_push($a_cust_ids, $rs3['cust_id']);
			}
			
			// Send emails to agents
			if(count($a_cust_ids)){
				$email = new email;
				$email->sendSellerUpdateToAgent($rs['agent_id'], $a_cust_ids);
			}
			
		}
		
	}
	
	//--------------------------------------------------
	
	// Handle a request to cancel an agent's code from the subscription settings page
	function cancelCodeRequest(){
		$code = cleanInput($_POST['code']);
		if(!$code){return false;}
		
		$date_to = result("SELECT `date_to` FROM `agent_postcodes` WHERE `agent_id` = ".$_SESSION['agent_id']." AND `code` = '".esc($code)."' LIMIT 1");
		if(!$date_to){echo 'This code is not currently active on your account';return;}
		
		
		
		$die_date = strtotime(date("M-d-Y", $date_to)." + 2 months");
		//echo datetime($date_to).' - '.datetime($die_date);
		$sql = "UPDATE `agent_postcodes` SET `die_date` = $die_date WHERE `agent_id` = ".$_SESSION['agent_id']." AND `code` = '".esc($code)."'";
		//echo $sql;
		query($sql);
		
		$e = new email;
		$e->cancelAgentCodeRequest($_SESSION['agent_id'], $code);
		
		// Log it
		$log = new logging;
		$log->logMe('Agent cancelled the code '.$code);
		
		echo 'The code `'.$code.'` has been cancelled';
	}
	
	//--------------------------------------------------
	
	// Debug function from CMS - clears a properties die date
	function clearPropDieDate($id){
		if(!is_numeric($id)){return;}
		$sql = "UPDATE `agent_postcodes` SET `die_date` = 0 WHERE `id` = $id LIMIT 1";
		query($sql);
		header('Location: agent_postcodes.php?agent_id='.$_GET['agent_id']);
		exit();
	}
	
}// Ends class
?>