<?php // Customer control object
class customer{

	function customer(){
		if($_POST['action'] == 'sellersignup'){$this->sellerSignup();}
		if($_POST['action'] == 'buyersignup'){$this->buyerSignup();}
		if($_POST['action'] == 'customerlogin'){$this->login();}
	}
	
	//--------------------------------------------------

	function retrieve($cust_id){
		if(!$cust_id && !is_numeric($cust_id)){return false;}
		$res = query("SELECT * FROM `customers` WHERE `cust_id` = $cust_id LIMIT 1");
		if(!num_rows($res)){return false;}
		$rs = fetch_assoc($res);
		while(list($k, $v)=each($rs)){
			$this->$k = $v;
		}
		$this->name = $this->firstname.' '.$this->surname;
		$this->agents = unserialize($this->agents);
		
		// Status
		if($this->buyer && $this->seller){
			$this->status = 'Buyer and Seller';
		}else if($this->buyer){
			$this->status = 'Buyer';
		}else if($this->seller){
			$this->status = 'Seller';
		}
		
		// Types of property a buyer wants
		if($this->types){
			$this->a_types = unserialize($this->types);
		}else{
			$this->a_types = array();
		}
		
		// Must haves for property a buyer wants
		if($this->must_have){
			$this->a_must_have = unserialize($this->must_have);
		}else{
			$this->a_must_have = array();
		}
		
		// Customers don't have photos so we use the default
		$this->photo_url = 'images/nophoto.jpg';
		
		// Buyer locations sought
		if($this->buyer){
			$a_codes_sought = array();
			$res = query("SELECT `code` FROM `buyer_postcodes` WHERE `cust_id` = $cust_id ORDER BY `code`");
			while($rs = fetch_assoc($res)){
				array_push($a_codes_sought, $rs['code']);
			}
			$this->codes_sought = implode(', ',$a_codes_sought);
		}
	}
	
	//--------------------------------------------------
	
	// A customer requesting a delete of their account
	function customerDelete(){
		$GLOBALS['a_errors'] = array();
		if(!$_SESSION['cust_id']){return;}
		
		reset($_POST);
		
		$a_agent_rating = $a_agent_comment = array();
		
		while(list($k, $v)=each($_POST)){
			$this->$k = cleanInput($v);
			$$k = esc($this->$k);
			
			// Agent ratings
			if(substr($k, 0, 14) == 'rate_agent_id_'){
				$index = str_replace('rate_agent_id_', '', $k);
				$a_agent_rating[$index] = $v;
			}
			
			// Agent comments
			if(substr($k, 0, 17) == 'comment_agent_id_'){
				$index = str_replace('comment_agent_id_', '', $k);
				if($v && $v != 'Comments'){
					$a_agent_comment[$index] = $v;
				}
			}
			
		}
		
		if(!$experience){addError("Please rate your experience");}
		if(!$looking_for){addError("Please indicate whether you found what you were looking for");}
		if(!$recommend){addError("Please indicate if you would recommend us");}
		
		if(count($GLOBALS['a_errors'])){return;}
		
		$c = new customer;
		$c->retrieve($_SESSION['cust_id']);
		
		if($comments == 'Comments...'){$comments = 'No comment supplied';}
		
		$sql = "INSERT INTO `customer_feedback` (
			`overall_rating`, 
			`found_what_looking_for`, 
			`recommend`, 
			`comments`, 
			`unix`,
			`name`,
			`email`,
			`tel`
		)VALUES(
			'$experience', 
			'$looking_for', 
			'$recommend', 
			'$comments', 
			'".time()."',
			'".esc($c->name)."',
			'".esc($c->email)."',
			'".esc($c->tel)."'
		)";
		#echo $sql.'<hr/>';
		query($sql);
		
		// Store agent comments and ratings
		while(list($agent_id, $v)=each($a_agent_rating)){
			$sql = "INSERT INTO `agent_ratings` (`agent_id`, `rating`, `comment`, `unix`, `name`, `email`, `tel`)VALUES('".esc($agent_id)."', '".esc($v)."', '".esc($a_agent_comment[$agent_id])."', ".time().", '".esc($c->name)."', '".esc($c->email)."', '".esc($c->tel)."')";
			#echo $sql.'<hr/>';
			query($sql);
		}
		
		// Delete their account
		$this->delete($_SESSION['cust_id']);
		
		session_destroy();
		
		header('Location: /account-closed/');
		exit();
	}
	
	//--------------------------------------------------
	
	// Permanently delete a customer and all their media and messages (use with caution)
	function delete($cust_id){
		if(!$cust_id && !is_numeric($cust_id)){return false;}
		query("DELETE FROM `seller_postcodes` WHERE `cust_id` = $cust_id");
		query("DELETE FROM `buyer_postcodes` WHERE `cust_id` = $cust_id");
		query("DELETE FROM `property` WHERE `cust_id` = $cust_id");
		query("DELETE FROM `agent_relationship` WHERE `cust_id` = $cust_id");
		query("DELETE FROM `messages` WHERE `from_cust_id` = $cust_id OR `to_cust_id` = $cust_id");
		query("DELETE FROM `customers` WHERE `cust_id` = $cust_id LIMIT 1");
		query("DELETE FROM `log` WHERE `cust_id` = $cust_id LIMIT 1");
		
		// Log it
		$log = new logging;
		$log->cust_id = $cust_id;
		$log->logMe('Customer deleted '.$cust_id);
	}
	
	//--------------------------------------------------
	
	// New seller signing up
	function sellerSignup(){
		
		while(list($k, $v)=each($_POST)){
			$this->$k = cleanInput($v);
			$$k = esc($this->$k);
		}
		
		// Validation
		if(!$firstname){addError("Please enter your first name");}
		if(!$surname){addError("Please enter your last name");}
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
		
		// Check buyer/seller flags have good data
		$seller = 1;
		if($_SESSION['buyer_and_seller']){// both
			$buyer = 1;
		}
		
		// Check if user already exists as a buyer or seller
		$exists_id = $this->exists($this->email);
		
		// OK they exists already, we need to establish if they are registering as a new user type (buyer/seller)
		if($exists_id){
			$this->getTypes($exists_id);
			if($this->buyer && $buyer==1){addError("The email address `".$this->email."` is already registered as a buyer");}
			if($this->seller && $seller==1){addError("The email address `".$this->email."` is already registered as a seller");}
			return;
			if(!count($GLOBALS['a_errors'])){$update_to_both_types = 1;}
		}
		
		// Check to see if they are already registered as an agent
		$exists_agent_id = $this->existsAsAgent($this->email);
		if($exists_agent_id){
			addError("The email address `".$this->email."` is already registered as an agent");
			return;
		}
		
		// Any errors?
		if(count($GLOBALS['a_errors'])){return false;}
		
		// Hash the password
		$salt = makeSalt();
		$hash = generateHash($salt, $pw);
		
		// SQL
		if($update_to_both_types){
			$sql= "UPDATE `customers` SET 
				`firstname` = '$firstname', 
				`surname` = '$surname', 
				`email` = '$email',
				`tel` = '$tel', 
				`salt` = '$salt',
				`pw` = '$hash',
				`date_updated` = '".time()."',
				`buyer` = 1,
				`seller` = 1
				 WHERE `cust_id` = $exists_id LIMIT 1";
		}else{
			$sql = "INSERT INTO `customers` (
				`firstname`, 
				`surname`, 
				`email`, 
				`tel`, 
				`salt`, 
				`pw`, 
				`date_created`, 
				`date_updated`,
				`buyer`,
				`seller`
			)VALUES(
				'$firstname', 
				'$surname', 
				'$email', 
				'$tel',
				'$salt', 
				'$hash', 
				'".time()."', 
				'".time()."',
				'$buyer',
				'$seller'
			)";
			$insert = true;
		}
		
		query($sql);
		if($insert){$this->cust_id = insert_id();}else{$this->cust_id = $exists_id;}
		
		
		// Set the property details
		if($postcode){
			$sql = "INSERT INTO `property` (
				`cust_id`, 
				`postcode`, 
				`type`, 
				`bedrooms`, 
				`bathrooms`, 
				`floors`, 
				`sq_ft`, 
				`situation`, 
				`desc`, 
				`tenure`, 
				`value`,
				`date_created`,
				`date_updated`,
				`outside_space`,
				`parking`,
				`real_estate`,
				`age_of_property`,
				`period`
			)VALUES(
				'".$this->cust_id."',
				'$postcode',
				'$type',
				'$bedrooms',
				'$bathrooms',
				'$floors',
				'$sq_ft',
				'$situation',
				'$desc',
				'$tenure',
				'$value',
				'".time()."',
				'".time()."',
				'$outside_space',
				'$parking',
				'$real_estate',
				'$age_of_property',
				'$period'
			)";
			query($sql);
			$this->prop_id = insert_id();
			
			// Log it
			$log = new logging;
			$log->prop_id = $this->prop_id;
			$log->logMe('New property added');
			
			// Query Google maps API for further information
			$map = new map;
			$map->storeCoordinates($postcode, $this->prop_id);
		
			// Notify agents about this new property
			if($insert){
				$email = new email;
				$email->sendAgentNewPropertyMessage($this->prop_id);
			}
		}
		
		// Store the customer's selected postcodes
		$this->storeSignupPostcodes($this->cust_id, 'seller');

		// Add a welcome message
		$msg = new messenger;
		$msg->cust_id = $this->cust_id;
		$msg->firstname = $this->firstname;
		$msg->addWelcomeMessage('seller');
		
		// Log it
		$log = new logging;
		$log->logMe('New customer signed up');
		
		// Send the welcome email
		$e = new email;
		$e->sendSellerWelcomeEmail($this->cust_id, $pw);

		
		// People signing up as both types need to complete the buyer form too
		if($_SESSION['buyer_and_seller']){
			$_SESSION['temp_cust_id'] = $this->cust_id;
			header("Location: ".$GLOBALS['system_url']."/buyer/register/");
			exit();
		}else{
			
			// Set session
			$_SESSION['cust_id'] = $this->cust_id;
			$_SESSION['signed_in'] = 'customer';
			$_SESSION['seller'] = 1;
		
		}
		
		// Redirect user
		$url = 'http://'.$_SERVER['HTTP_HOST'].'/seller/signup-confirmation/';
		
		header("Location: ".$url);
		exit();
	}
	
	//--------------------------------------------------
	
	// New buyers signing up
	function buyerSignup(){
		
		$a_types = array();
		$a_must_have = array();
		while(list($k, $v)=each($_POST)){
			$this->$k = cleanInput($v);
			$$k = esc($this->$k);
			if(substr($k, 0, 5) == 'type_'){
				array_push($a_types, $v);
			}
			if(substr($k, 0, 10) == 'must_have_'){
				array_push($a_must_have, $v);
			}
		}
		
		// Validation
		if(!$firstname){addError("Please enter your first name");}
		if(!$surname){addError("Please enter your last name");}
		if(!$email){addError("Please enter your email address");}
		
		// Check email
		if(!$_SESSION['temp_cust_id']){
			if($email){
				if(!emailValid($email)){
					addError("Please enter your email address");
				}
			}
		}
		
		// Check password
		if(!$_SESSION['temp_cust_id']){
			if(!$pw){addError("Please select a password");}
			if($pw && (strlen($pw) > 30 || strlen($pw) < 8)){addError("Your password should be between 8 and 30 characters in length");}
			if($pw && !$pwc){addError("Please confirm your password");}
			if($pw && $pwc){
				if($pw != $pwc){
					addError("The password and password confirmation values are different");
				}
			}
		}
		
		if(!$additional_information){addError("Please enter some additional information about your requirements");}
		
		// Check buyer/seller flags have good data
		if(!is_numeric($buyer) || !is_numeric($seller)){addError("Bad input");}
		if($buyer){$buyer=1;}else{$buyer=0;}
		if($seller){$seller=1;}else{$seller=0;}
		
		// Check if user already exists as a buyer or seller
		if(!$_SESSION['temp_cust_id']){
			$exists_id = $this->exists($this->email);
		}
		
		// OK they exists already, we need to establish if they are registering as a new user type (buyer/seller)
		if($exists_id){
			$this->getTypes($exists_id);
			if($this->buyer && $buyer==1){addError("The email address `".$this->email."` is already registered as a buyer");}
			if($this->seller && $seller==1){addError("The email address `".$this->email."` is already registered as a seller");}
			return;
			if(!count($GLOBALS['a_errors'])){$update_to_both_types = 1;}
		}
		
		// Check to see if they are already registered as an agent
		$exists_agent_id = $this->existsAsAgent($this->email);
		if($exists_agent_id){
			addError("The email address `".$this->email."` is already registered as an agent");
			return;
		}
		
		// Any errors?
		if(count($GLOBALS['a_errors'])){return false;}
		
		// Hash the password
		$salt = makeSalt();
		$hash = generateHash($salt, $pw);
		
		// SQL
		if($_SESSION['temp_cust_id']){// It's a seller also registering as a buyer
			$sql = "UPDATE `customers` SET 	
				`type` = '".esc(serialize($a_types))."',
				`additonal_information` = '$additional_information',
				`price_range_min` = '$price_range_min',
				`price_range_max` = '$price_range_max',
				`looking_to_move` = '$looking_to_move',
				`outside_space` = '$outside_space',
				`parking` = '$parking',
				`real_estate` = '$real_estate',
				`property_age` = '$property_age',
				`modern_period` = '$modern_period'
			 WHERE `cust_id` = ".$_SESSION['temp_cut_id']." LIMIT 1";
		}else{
		
			$sql = "INSERT INTO `customers` (
				`firstname`, 
				`surname`, 
				`email`, 
				`tel`, 
				`salt`, 
				`pw`, 
				`date_created`, 
				`date_updated`,
				`buyer`,
				`seller`,
				`types`,
				`must_have`,
				`additional_information`,
				`price_range_min`,
				`price_range_max`,
				`looking_to_move`,
				`outside_space`,
				`parking`,
				`real_estate`,
				`property_age`,
				`modern_period`
			)VALUES(
				'$firstname', 
				'$surname', 
				'$email', 
				'$tel',
				'$salt', 
				'$hash', 
				'".time()."', 
				'".time()."',
				'$buyer',
				'$seller',
				'".esc(serialize($a_types))."',
				'".esc(serialize($a_must_have))."',
				'$additional_information',
				'$price_range_min',
				'$price_range_max',
				'$looking_to_move',
				'$outside_space',
				'$parking',
				'$real_estate',
				'$property_age',
				'$modern_period'
			)";
			$insert = true;
		
		}
		#exit($sql);
		query($sql);
		if($insert){
			$this->cust_id = insert_id();
		}else{
			$this->cust_id = $_SESSION['temp_cust_id'];
		}
		
		// Store the customer's selected postcodes
		$this->storeSignupPostcodes($this->cust_id, 'buyer');
		
		// Set session
		$_SESSION['cust_id'] = $this->cust_id;
		$_SESSION['signed_in'] = 'customer';
		$_SESSION['buyer'] = 1;
		if($_SESSION['temp_cust_id']){
			$_SESSION['seller'] = 1;
		}
		unset($_SESSION['temp_cust_id']);
		
		// Add a welcome message
		$msg = new messenger;
		$msg->cust_id = $this->cust_id;
		$msg->firstname = $this->firstname;
		$msg->addWelcomeMessage('buyer');

		
		// Send the welcome email
		$e = new email;
		$e->sendBuyerWelcomeEmail($this->cust_id, $pw);
		
		// Log it
		$log = new logging;
		$log->logMe('New buyer signed up');
		
		// Redirect user
		$url = 'http://'.$_SERVER['HTTP_HOST'].'/buyer/signup-confirmation/';

		header("Location: ".$url);
		exit();
	}
	
	//--------------------------------------------------
	
	// Stores the customer's codes once they've signed up
	function storeSignupPostcodes($cust_id, $type='buyer'){
	
		if(!count($_SESSION['a_customer_postcodes'])){
			return;
		}
		
		$a_areas = array();
		$res = query("SELECT `code`, `area` FROM `postcodes` WHERE `code` IN('".implode("', '", $_SESSION['a_customer_postcodes'])."')");
		while($rs = fetch_assoc($res)){
			$a_areas[ $rs['code'] ] = $rs['area'];
		}
		
		foreach($_SESSION['a_customer_postcodes'] as $code){
			$sql = "INSERT INTO `".$type."_postcodes` (`cust_id`, `code`, `area`, `date_created`)VALUES('".esc($cust_id)."', '".esc($code)."', '".esc($a_areas[$code])."', ".time().")";
			query($sql);
		}
	}
	
	//--------------------------------------------------
	
	// Check if an email address exists and return the cust_id if it does
	function exists($email){
		$res = query("SELECT `cust_id` FROM `customers` WHERE `email` = '".esc($email)."'");
		if(!num_rows($res)){return 0;}
		$rs = fetch_assoc($res);
		return $rs['cust_id'];
	}
	
	//--------------------------------------------------
	
	// Check if an email address exists and return the cust_id if it does
	function existsAsAgent($email){
		$res = query("SELECT `agent_id` FROM `agents` WHERE `email` = '".esc($email)."'");
		if(!num_rows($res)){return 0;}
		$rs = fetch_assoc($res);
		return $rs['agent_id'];
	}
	
	//--------------------------------------------------

	// Rertrieves the user types from the DB
	function getTypes($cust_id){
		$res = query("SELECT `buyer`, `seller` FROM `customers` WHERE `cust_id` = $cust_id LIMIT 1");
		$rs = fetch_assoc($res);
		while(list($k, $v)=each($rs)){
			$this->$k = $v;
		}
	}
	
	//--------------------------------------------------
	
	function signupConfMessage($cust_id=0){
		if(!$cust_id){return false;}
		$this->retrieve($cust_id);
		#var_dump($this);
		echo '<div class="pad" style="text-align:center">';
		echo "<p>Thank you for registering";
		if($this->buyer && $this->seller){
			echo ". Your account has been upgraded to that you are now a buyer and seller.";
			$dashboard = '/switch-profile/';
		}else if($this->buyer){
			echo " as a buyer.";
			$dashboard = '/buyer/dashboard/';
		}else{
			echo " as a seller.";
			$dashboard = '/seller/dashboard/';
		}
		echo "</p>";
		echo '<p>Your account has now been set up. Please proceed to your dashboard to tell us more about your desired property and locations.</p>';
		echo '<a href="'.$dashboard.'" class="button">Take me to my dashboard</a>';
		echo '</div>';
	}
	
	//--------------------------------------------------
	
	// Admin logs in as a customer
	function loginAs($cust_id){
		$c = new customer;
		$c->retrieve($cust_id);
		if(!$c->online){
			addError("This account is closed");
			return false;
		}
		unset($_SESSION['agent_id']);
		unset($_SESSION['parent_id']);
		$_SESSION['cust_id'] = $c->cust_id;
		$_SESSION['signed_in'] = 'customer';
		$_SESSION['buyer'] = $c->buyer;
		$_SESSION['seller'] = $c->seller;
		
		// Which dashboard do they see?
		if($c->seller){
			$_SESSION['current_dash'] = 'seller';
		}else if($c->buyer){
			$_SESSION['current_dash'] = 'buyer';
		}
		
		header('Location: http://'.$_SERVER['HTTP_HOST'].'/');
		exit();
	}
	
	//--------------------------------------------------
	
	// Show a list of the customer's properties on the dash each with a mini map
	function listMyProperty(){
		
		if(!$_SESSION['cust_id']){return;}
		$res = query("SELECT `prop_id`, `formatted_address`, `lng` FROM `property` WHERE `online` = 1 AND `cust_id` = '".esc($_SESSION['cust_id'])."' ORDER BY `date_created`");
		if(!num_rows($res)){
			echo '<p>You have not added a property yet.</p>';
			return;
		}
		
		while($rs = fetch_assoc($res)){
				
				echo '<div class="table" id="prop_'.$rs['prop_id'].'">'; // Open table
				echo '<div class="row">';
				echo '<div class="cell" style="width:200px">';
				
				
				if($rs['lng']){	
					$map = new map;
					$map->showMap($rs['formatted_address'], 200, 160);
				
				}
				echo '</div><div class="cell">';
				
				echo '<p>'.addrFormat(html($rs['formatted_address'])).'</p>';	
				
				echo '<div class="button" onclick="removeProperty('.$rs['prop_id'].')">Remove property</div>';
				echo '<hr/>';
				echo '</div></div></div>'; // Close table
				
				
			
		}
		
	}
	
	//--------------------------------------------------
	
	// Show the customer's profile in the dashboard
	function showProfile($cust_id=0){
		if(!$cust_id){$cust_id = $_SESSION['cust_id'];}
		$c = new customer;
		$c->retrieve($cust_id);
		echo '<div class="custBiog">';
		echo '<h3>Name: '.html($c->name).'</h3>';
		if($_SESSION['cust_id'] == $cust_id){
			echo '<p><strong>Email:</strong>'.html($c->email).'</p>';
			echo '<p><strong>Telephone:</strong>'.html($c->tel).'</p>';
		}

		
		if($c->buyer){
			echo '<h3>I\'m looking for</h3>'.chr(10);
			
			echo '<div class="table">';
			
			echo '<div class="row">';
			echo '<div class="cell" style="width:160px"><strong>My requirements: </strong></div><div class="cell">'.nl2br(html($c->requirements)).'</div>';
			echo '</div>';
			
			echo '<div class="row">';
			echo '<div class="cell"><strong>Price range: </strong></div><div class="cell">'.html($c->price_range).'</div>';
			echo '</div>';
			
			echo '<div class="row">';
			echo '<div class="cell"><strong>Number of Bedrooms: </strong></div><div class="cell">'.html($c->bedrooms).'</div>';
			echo '</div>';
			
			echo '</div>';
		}
		
		// Buttons
		if($_SESSION['agent_id']){
			if($c->seller){
				echo '<div class="button" onclick="messageWin('.$c->cust_id.')">Contact seller</div>';
			}else if($c->buyer){
				echo '<div class="button" onclick="messageWin('.$c->cust_id.')">Contact seller</div>';
			}
		}

		
		
		echo '</div>'.chr(10);
		return;
	}
	
	//---------------------------------------------------------
	
	// Show the customer's profile in the dashboard
	function showMiniProfile($cust_id, $type='buyer', $filter=''){

		$c = new customer;
		$c->retrieve($cust_id);

		// Filtered by the search box on the agents vendors panels
		if($filter){
			if(strpos($c->name, $filter)>-1){
				$ok = 1;
			}
			if(!$ok){
				return;
			}
		}
		
		$date_read = result("SELECT `date_read` FROM `agent_relationship` WHERE `agent_id` = ".$_SESSION['agent_id']." AND `cust_id` = $cust_id");
		$starred = result("SELECT `starred` FROM `agent_relationship` WHERE `agent_id` = ".$_SESSION['agent_id']." AND `cust_id` = $cust_id");
		
		if($type=='buyer'){
			
			// Buyer
			echo '<div class="vendItem" onclick="loadBuyerProf('.$cust_id.')">
        		<div>
        	    	<p>'.html($c->name).', '.currency($c->price_range_min).'-'.currency($c->price_range_max).'<br/>Looking to move: '.html($c->looking_to_move).'</p>
        	    </div>';
        	
			if(!$date_read){
				echo '<div class="newMsg newMsg'.$_SESSION['current_dash'].'">NEW</div>';  
			} 
			if($starred){
				echo '<div class="starred"></div>';  
			} 
			
        	echo '</div>';
			
		}else{
			
			$prop_id = result("SELECT `prop_id` FROM `property` WHERE `cust_id` = $c->cust_id LIMIT 1");
			$p = new property;
			$p->retrieve($prop_id);
			
			// Seller
			echo '<div class="vendItem" onclick="loadSellerProf('.$cust_id.')">
        		<div>
        	    	<p>'.html($c->name).', '.$p->bedrooms.' bedroom '.html($p->type).', '.html($p->code).'<br/>Value: '.$p->value.'</p>';
        	    echo '</div>';
        	    
				if(!$date_read){
					echo '<div class="newMsg newMsg'.$_SESSION['current_dash'].'">NEW</div>';  
				}
				if($starred){
					echo '<div class="starred"></div>';  
				} 
				
        	echo '</div>';
		}
		
		return;
	}
	
	//--------------------------------------------------
	
	// Buyer updating their profile via AJAX/Dashboard
	function updateBuyerProfile(){
		
		if(!$_SESSION['cust_id']){addError("Please log in first");return;}
		
		if(!$_SESSION['buyer']){$new = 1;}
		
		$a_types = array();
		$a_must_have = array();
		while(list($k, $v)=each($_POST)){
			$this->$k = cleanInput($v);
			$$k = esc($this->$k);
			if(substr($k, 0, 5) == 'type_'){
				array_push($a_types, $v);
			}
			if(substr($k, 0, 10) == 'must_have_'){
				array_push($a_must_have, $v);
			}
		}
		
		#print_r($a_types);
		
		if(!$firstname){addError("Please enter your first name");}
		if(!$surname){addError("Please enter your last name");}

		$price_range_min = cleanInput($_POST['price_range_min']);
		$price_range_max = cleanInput($_POST['price_range_max']);
		
		if($price_range_min > 0 && $price_range_max > 0){
			if($price_range_max <= $price_range_min){
				addError("Your maximum price needs to be higher than your minimum price");
			}
		}
		
		$cust_id = $_SESSION['cust_id'];

		$c = new customer;
		$c->retrieve($_SESSION['cust_id']);
		
		if($email != $c->email){
			if(userExists($email)){
				addError("There is already an account with this email address");
			}
		}
		
		if(count($GLOBALS['a_errors'])){
			return false;
		}
		
		// Log seller adding a buyer profile
		if($new){
			$log = new logging;
			$log->logMe('Buyer added a seller profile');
			$m = new messenger;
			$m->addWelcomeMessage('seller');
		}
		
		// SQL
		$sql = "UPDATE `customers` SET 
		`buyer` = 1,
		`firstname` = '".esc($firstname)."',
		`surname` = '".esc($surname)."',
		`email` = '".esc($email)."',
		`date_updated` = ".time().",
		`additional_information` = '".esc($additional_information)."',
		`price_range_min` = '".esc($price_range_min)."',
		`price_range_max` = '".esc($price_range_max)."',
		`looking_to_move` = '".esc($looking_to_move)."',
		`types` = '".serialize($a_types)."'
		WHERE `cust_id` = $cust_id LIMIT 1";
		
		#echo '<hr/>'.$sql;
		query($sql);
		
		$_SESSION['buyer'] = 1;

		// Log it
		$log = new logging;
		$log->logMe('Updated buyer profile');
		
		$this->buyerUpdated = 1;
		
		return true;
	}
	
	
	//--------------------------------------------------
	
	
	// Seller updating their profile via AJAX/Dashboard
	function updateSellerProfile(){
		
		if(!$_SESSION['cust_id']){addError("Please log in first");return;}
		
		$a_types = array();
		$a_must_have = array();
		while(list($k, $v)=each($_POST)){
			$this->$k = cleanInput($v);
			$$k = esc($this->$k);
			if(substr($k, 0, 5) == 'type_'){
				array_push($a_types, $v);
			}
			if(substr($k, 0, 10) == 'must_have_'){
				array_push($a_must_have, $v);
			}
		}
	
		$cust_id = $_SESSION['cust_id'];

		$c = new customer;
		$c->retrieve($_SESSION['cust_id']);
		
		if($email != $c->email){
			if(userExists($email)){
				addError("There is already an account with this email address");
			}
		}
		
		if(count($GLOBALS['a_errors'])){
			return false;
		}
		
		$sql = "UPDATE `customers` SET 
		`seller` = 1, 
		`firstname` = '".esc($firstname)."',
		`surname` = '".esc($surname)."',
		`date_updated` = ".time().",
		`email` = '".esc($email)."'
		WHERE `cust_id` = $cust_id LIMIT 1";
		
		if($prop_id){
		
		  $sql2 = "UPDATE `property` SET 
		  `code` = '".esc($code)."',
		  `postcode` = '".esc($postcode)."',
		  `type` = '".esc($type)."',
		  `bedrooms` = '".esc($bedrooms)."',
		  `bathrooms` = '".esc($bathrooms)."',
		  `floors` = '".esc($floors)."',
		  `sq_ft` = '".esc($sq_ft)."',
		  `tenure` = '".esc($tenure)."',
		  `value` = '".esc($value)."',
		  `desc` = '".esc($desc)."',
		  `situation` = '".esc($situation)."',
		  `outside_space` = '".esc($outside_space)."',
		  `parking` = '".esc($parking)."',
		  `real_estate` = '".esc($real_estate)."'
		  WHERE `cust_id` = $cust_id AND `prop_id` = $prop_id LIMIT 1";
		  
		}else{ // Buyer adding seller profile
			
			$sql2 = "INSERT INTO `property` (`code`, `postcode`, `type`, `bedrooms`, `bathrooms`, `floors`, `sq_ft`, `tenure`, `value`, `desc`, `situation`, `outside_space`, `parking`, `real_estate`, `cust_id`) VALUES ('".esc($code)."', '".esc($postcode)."', '".esc($type)."', '".esc($bedrooms)."', '".esc($bathrooms)."', '".esc($floors)."', '".esc($sq_ft)."', '".esc($tenure)."', '".esc($value)."', '".esc($desc)."', '".esc($situation)."', '".esc($outside_space)."', '".esc($parking)."', '".esc($real_estate)."', ".$_SESSION['cust_id'].")";
			$_SESSION['seller'] = 1;
			$insert = 1;
		}
		
		#echo '<hr/>'.$sql;
		#echo '<hr/>'.$sql2;
		
		query($sql);
		query($sql2);
		
		if($insert){
			$this->prop_id = insert_id();
		}
		
		
		// Query Google maps API for further information
		$map = new map;
		$map->storeCoordinates($postcode, $this->prop_id);
		
		if(!$prop_id){ // Buyer adding seller profile
		
			// Log it
			$log = new logging;
			$log->prop_id = $this->prop_id;
			$log->logMe('Buyer registered as seller and new property added');
			
			
			
			// Welcome message
			$m = new messenger;
			$m->addWelcomeMessage('seller');
			
			// Notify agents about this new property
			if($insert){
				$email = new email;
				$email->sendAgentNewPropertyMessage($this->prop_id);
			}
		
		}else{ // Seller updating profile only
			// Log it
			$log = new logging;
			$log->logMe('Updated seller profile');
		
		}
		
		$this->sellerUpdated = 1;
		
		#return true;
	}
	
	//--------------------------------------------------
	
	// Return the number of live properties on this user's account
	function propertyCount($cust_id){
		return(result("SELECT COUNT(`prop_id`) FROM `property` WHERE `cust_id` = '".esc($cust_id)."' AND `online` = 1"));
	}
	
	//--------------------------------------------------
	
	function listBuyerCodes($cust_id){
		if(!is_numeric($cust_id)){return;}
		$a_codes = array();
		$res = query("SELECT `code` FROM `buyer_postcodes` WHERE `cust_id` = $cust_id ORDER BY `code`");
		while($rs = fetch_assoc($res)){
			array_push($a_codes, $rs['code']);
		}
		echo implode(', ', $a_codes);
	}
	
	//--------------------------------------------------
	
	function listSellerCodes($cust_id){
		if(!is_numeric($cust_id)){return;}
		$a_codes = array();
		$res = query("SELECT DISTINCT(`code`) FROM `property` WHERE `cust_id` = $cust_id ORDER BY `code`");
		while($rs = fetch_assoc($res)){
			array_push($a_codes, $rs['code']);
		}
		echo implode(', ', $a_codes);
	}
	
}// Ends class
?>