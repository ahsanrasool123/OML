<?php // Class to deal with the sending of emails
class email{

	function email(){
		
		// Reset password
		if($_POST['action'] == 'forgottenpassword'){
			$this->passwordResetEmail($_POST['email']);
		}
		
		if($_POST['action'] == 'contactForm'){
			$this->contactFormSubmit();
		}
		#$this->send('graham@meerkats.co.uk', 'Test email from, OMG', 'Just a test', $GLOBALS['noreply_email']);
		#echo mail('graham@meerkats.co.uk', 'Test mail() email from, OMG', 'Just a test', $GLOBALS['noreply_email']);
	}
	
	//-------------------------------------------------
	
	// Basic HTML email sending (all functions in the class use this)
	function send($to, $subject, $message, $from){
		#if(!emailValid($to)){addError("Please use a valid `to` address");}
		#if(!emailValid($from)){addError("Please use a valid `from` address");}
		#if(count($GLOBALS['a_errors'])){return false;}
		$a = file('http://'.$_SERVER['HTTP_HOST'].'/inc/email.template.php');
		$template = implode('', $a);
		$html = str_replace('%message%', $message, $template);
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
		$headers .= 'From: '.$GLOBALS['system_name'].'<'.$from.'>' . "\r\n";
		mail($to, $subject, $html, $headers);
		
		//Log it
		$log = new logging;
		$log->logEmail($to, $subject, $html, $headers);
	}

	//-------------------------------------------------
	
	// Welcome email for buyers signing up
	function sendBuyerWelcomeEmail($cust_id, $pw=''){
		$c = new customer;
		$c->retrieve($cust_id);
		$to = $c->email;
		
		
		
		if(!$pw){
			$pw = '<em>As chosen when you signed up</em>';	
		}
		
		$subject = 'Welcome to '.$GLOBALS['system_name'];
		$message = '<p>Dear '.html($c->firstname).'</p>';
		$message .= '<p>Thank you for registering your property requirements with '.$GLOBALS['system_name'].'. Now you can sit back, relax and wait for the agents to come running!</p>';
		
		$message .= '<p>Your login details are as follows:</p>';
		$message .= '<p>Email address: '.$c->email.'<br/>Password: '.$pw.'</p>';
		
		$message .= '<p>Want to check in to see what’s new? <a href="'.$GLOBALS['system_url'].'/sign-in/">Click here</a> to log in.</p>';
		$message .= '<p>We look forward to seeing you back on the site very soon.</p>';
		$message .= '<p>The '.$GLOBALS['system_name'].' Team</p>';
		
		$from = $GLOBALS['noreply_email'];
		
		$this->send($to, $subject, $message, $from);
		
		//Log it
		$log = new logging;
		$log_cust_id = $cust_id;
		$log_email = $to;
		$log->logMe("Buyer welcome email sent to $to");
	}
	
	//-------------------------------------------------
	
	// Welcome email for sellers signing up
	function sendSellerWelcomeEmail($cust_id, $pw=''){
		$c = new customer;
		$c->retrieve($cust_id);
		$to = $c->email;
		
		if(!$pw){
			$pw = '<em>As chosen when you signed up</em>';	
		}
		
		$subject = 'Welcome to '.$GLOBALS['system_name'];
		$message = '<p>Dear '.html($c->firstname).'</p>';
		$message .= '<p>Thank you for registering your property with '.html($GLOBALS['system_name']).'. Now you can sit back, relax and wait for the agents to come running!</p>';
		$message .= '<p>Your login details are as follows:</p>';
		$message .= '<p>Email address: '.$c->email.'<br/>Password: '.$pw.'</p>';
		
		$message .= '<p>Want to check in to see what’s new? <a href="'.$GLOBALS['system_url'].'/sign-in/">Click here</a> to log in.</p>';
		
		$message .= '<p>We look forward to seeing you back on the site very soon.</p>';
		
		$message .= '<p>The '.$GLOBALS['system_name'].' Team</p>';
		
		$from = $GLOBALS['noreply_email'];
		$this->send($to, $subject, $message, $from);
		
		//Log it
		$log = new logging;
		$log_cust_id = $cust_id;
		$log_email = $to;
		$log->logMe("Seller welcome email sent to $to");
	}
	
	//-------------------------------------------------
	
	// Welcome email for agents signing up
	function sendAgentWelcomeEmail($agent_id, $pw=''){
		$a = new agent;
		$a->retrieve($agent_id);
		
		if(!$pw){
			$pw = '<em>As chosen when you signed up</em>';	
		}
		
		$a_codes = array();
		$res = query("SELECT * FROM `agent_postcodes` WHERE `agent_id` = $a->agent_id AND `date_to` > ".time()." ORDER BY `code`");
		while($rs = fetch_assoc($res)){
			array_push($a_codes, $rs['code']);
		}
		$codes = implode(', ',$a_codes);
		
		$to = $a->email;
		$subject = 'Welcome to Off Market Global';
		$message = '<p>Dear '.html($a->firstname).'</p>';
		$message .= '<p>Thanks for registering as an Off Market Agent with '.$GLOBALS['system_name'].'.</p>';
		
		$message .= '<p>Your login details are as follows:</p>';
		$message .= '<p>Email address: '.$a->email.'<br/>Password: '.$pw.'</p>';
		
		$message .= '<p>You are subscribed to the following postcode/s:</p>';
		
		$message .= '<p>'.$codes.'</p>';
		
		$message .= '<p>Want to take a look around and see who’s looking to buy or sell? <a href="'.$GLOBALS['system_url'].'/sign-in/">Click here</a> to log in and start matching people with their perfect properties and buyers!</p>';
		
		$message .= '<p>We look forward to seeing you back on the site very soon.</p>';
		
		$message .= '<p>The Off Market London team</p>';
		
		$from = $GLOBALS['noreply_email'];
		$this->send($to, $subject, $message, $from);
		
		//Log it
		$log = new logging;
		$log_agent_id = $agent_id;
		$log_email = $to;
		$log->logMe("Agent welcome email sent to $to");
	}
	
	//-------------------------------------------------
	
	// Notify the recipient of a new message
	function sendNewMessageNotification($msg_id){
		
		$msg = new messenger;
		$msg->retrieve($msg_id);
		
		//if(!$msg->msg_id){return false;}
		
		$subject = 'You have a new message at '.$GLOBALS['system_name'];
		
		if($msg->to_cust_id){
			$contact = new customer;
			$contact->retrieve($msg->to_cust_id);
			$to = $contact->email;
		}
		if($msg->to_agent_id){
			$contact = new agent;
			$contact->retrieve($msg->to_agent_id);
			$to = $contact->email;
		}
		
		$message .= '<p>Hi '.$msg->to_firstname.'</p>';
		$message .= '<p>You have received a new message from '.$msg->from_name.'.</p>';
		$message .= '<p>Don’t miss out! To read it, <a href="'.$GLOBALS['system_url'].'/sign-in/">log in to your Off Market London account here</a>.</p>';
		$message .= '<p>Remember, you can control the amount of notifications you receive by going to ‘Notification Settings’ in your dashboard.</p>';
		$message .= '<p>The '.$GLOBALS['system_name'].' Team</p>';

		
		$from = $GLOBALS['noreply_email'];
		$this->send($to, $subject, $message, $GLOBALS['noreply_email']);
		
		//Log it
		$log = new logging;
		$log->agent_id = $to_agent_id;
		$log->cust_id = $to_cust_id;
		$log->email = $to;
		$log->logMe("New message notification sent");

		return true;
	}

	//-------------------------------------------------
	
	// Send the user a password reset email
	function passwordResetEmail($email=''){
		if(!$email){addError("Please enter your registered email address"); return false;}
		$email = cleanInput($email);
		if(!$email || $email == 'Email address'){addError("Please enter your registered email address"); return;}
		
		if(!emailValid($email)){addError("Please use a valid `to` address"); return;}
		$agent_id = result("SELECT `agent_id` FROM `agents` WHERE `email` = '".esc($email)."'");
		if(!$agent_id){
			$cust_id = result("SELECT `cust_id` FROM `customers` WHERE `email` = '".esc($email)."'");
		}
		if($agent_id){
			$a = new agent;
			$a->retrieve($agent_id);
			$name = $a->contact_name;
			$link = $GLOBALS['system_url'].'/reset.php?aid='.$a->agent_id.'*'.$a->salt;
		}else if($cust_id){
			$c = new customer;
			$c->retrieve($cust_id);
			$name = $c->name;
			$link = $GLOBALS['system_url'].'/reset.php?cid='.$c->cust_id.'*'.$c->salt;
		}else{
			addError("Sorry we couldn't find that email address.");
			return false;
		}

		$subject = 'Password reset for '.$GLOBALS['system_name'];
		
		$message = "<p>Hi $name</p>";
		$message .= '<p>We received your request to reset your password.</p>';
		$message .= '<p>Please <a href="'.$link.'" target="_blank">click here</a> to change your password to something more memorable.</p>';
		$message .= '<p>The '.$GLOBALS['system_name'].' Team</p>';
		
		$this->send($email, $subject, $message, $GLOBALS['noreply_email']);
		$this->passwordResetSent = true;
		
		//Log it
		$log = new logging;
		$log->agent_id = $a->agent_id;
		$log->cust_id = $c->cust_id;
		$log->email = $email;
		$log->logMe("Password reset email sent");
		
		return true;
	}
	
	//-------------------------------------------------
	
	// Print the message to the browser and mark it as read
	function activatedAgentLoginEmail($firstname, $email, $pw){
		$subject = 'Welcome to your new agent account at '.$GLOBALS['system_name'];
		
		$msg = "<p>Dear $firstname</p>";
		$msg .= "<p>Your account with ".html($GLOBALS['system_name'])." has now been set up for you. These are your login details:</p>";
		$msg .= "<p><a href=\"".$GLOBALS['system_url']."/login/\">".$GLOBALS['system_url']."/login/</a></p>";
		$msg .= "<p>Email address: $email</p>";
		$msg .= "<p>Password: $pw</p>";
		$msg .= "<p>Kind regards<br/>".$GLOBALS['system_name']."<br/>".$GLOBALS['system_url']."</p>";
		
		$this->send($email, $subject, $msg, $GLOBALS['noreply_email']);
		
		//Log it
		$log = new logging;
		$log->agent_id = $agent_id;
		$log->cust_id = $cust_id;
		$log->email = $email;
		$log->logMe("Agent activation email sent");
		
	}
	
	//-------------------------------------------------
	
	// Notifies agents of a new property in their area
	function sendAgentNewPropertyMessage($prop_id){

		$prop = new property;
		$prop->retrieve($prop_id);
		if(!$prop->code){return;}
		
		// Find agents that can get this message
		$a_agent_ids = array();
		$now = time();
		$res = query("SELECT `agent_id` FROM `agent_postcodes` WHERE `code` = '".esc($prop->code)."' AND `date_from` < $now AND `date_to` >= $now");
		while($rs = fetch_assoc($res)){
			$blocked = result("SELECT COUNT(id) FROM `agent_relationship` WHERE `agent_id` = ".$rs['agent_id']." AND `cust_id` = '".$prop->cust_id."' AND `status` = 'blocked' AND `role` = 'seller'");
			if(!$blocked){
				array_push($a_agent_ids, $rs['agent_id']);
			}
		}
		
		$subject = 'New property in your area';
		
		foreach($a_agent_ids as $agent_id){// loop
			unset($a);
			$a = new agent;
			$a->retrieve($agent_id);
			$msg = "<p>Dear ".html($a->firstname)."</p>";
			$msg .= "<p>A new property has become available in your area. To view this property and contact the seller please log into your ".$GLOBALS['system_name']." dashboard to contact this seller.</p>";
			
			$msg .= "<p>";
			$msg .= $prop->bedrooms." bedroom ".$prop->type." in ".$prop->code."<br/>";
			$msg .= 'Bathrooms: '.$prop->bathrooms."<br/>";
			$msg .= 'Floors: '.$prop->floors."<br/>";
			$msg .= 'Square feet: '.$prop->sq_ft."<br/>";
			$msg .= 'Tenure: '.$prop->tenure."<br/>";
			$msg .= 'Approximate value: '.$prop->value."<br/>";
			$msg .= 'Seller\'s situation: '.$prop->situation."<br/>";
			$msg .= "</p>";
			
			if($prop->desc){
				$msg .= "<p><strong>Seller's description:</strong> <br/>".html($prop->desc)."</p>";
			}
			
			
			$msg .= "<p><a href=\"".$GLOBALS['system_url']."/login/\">".$GLOBALS['system_url']."/login/</a></p>";
			$msg .= "<p>Kind regards<br/>".$GLOBALS['system_name']."<br/>".$GLOBALS['system_url']."</p>";
			$msg .= $a->email;
			#echo $msg.'<hr/>';
			$this->send($a->email, $subject, $msg, $GLOBALS['noreply_email']);
			
			//Log it
			$log = new logging;
			$log->agent_id = $agent_id;
			$log->email = $a->email;
			$log->logMe("New property alert sent");
		}
		
	}
	
	//-------------------------------------------------
	
	// Print the message to the browser and mark it as read
	function recurrringBillingNotification($firstname, $email, $amount, $codes, $billing_period){
		$subject = 'Postcode renewal notification from '.$GLOBALS['system_name'];
		$msg = "<p>Dear $firstname</p>";
		$msg .= "<p>Your selected postcodes have been renewed and a payment of ".currency($amount)." taken from your card.</p>";
		if($billing_period == 'monthly'){
			$msg .= "<p>The following codes have now been renewed for one month: ".html($codes).".</p>";
		}else{
			$msg .= "<p>The following codes have now been renewed for one year: ".html($codes).".</p>";
		}
		$msg .= "<p>Kind regards<br/>".$GLOBALS['system_name']."<br/>".$GLOBALS['system_url']."</p>";
		
		$this->send($email, $subject, $msg, $GLOBALS['noreply_email']);
		
		//Log it
		$log = new logging;
		$log->agent_id = $agent_id;
		$log->cust_id = $cust_id;
		$log->email = $email;
		$log->logMe("Recurring billing notification sent to $email");
		
	}
	
	//-------------------------------------------------
	
	// New agents signing up get this billing notification
	function newAgentBillingNotification($firstname, $email, $amount, $codes, $agent_id){
		$subject = 'Welcome to '.$GLOBALS['system_name'];
		$msg = "<p>Dear $firstname</p>";
		
		$msg .= "<p>Welcome to ".html($GLOBALS['system_name']).".</p>";
		
		$msg .= "<p>Your selected postcodes have been added to your account and a payment of ".currency($amount)." taken from your card.</p>";
		
		$billing_period = result("SELECT `billing_period` FROM `agents` WHERE `agent_id` = $agent_id");
		
		if($billing_period == 'monthly'){
			$msg .= "<p>The following postcodes have now been added to your account ".html(implode(', ', $codes))." and will be renewed automatically on a monthly basis.</p>";
		}else{
			$msg .= "<p>The following postcodes have now been added to your account for one year: ".html(implode(', ', $codes))." and will be renewed automatically on a yearly basis.</p>";
		}
		$msg .= "<p>The ".$GLOBALS['system_name']." team</p>";
		
		$this->send($email, $subject, $msg, $GLOBALS['noreply_email']);
		
		//Log it
		$log = new logging;
		$log->agent_id = $agent_id;
		$log->cust_id = $cust_id;
		$log->email = $email;
		$log->logMe("Recurring billing notification sent to $email");
		
	}
	
	//-------------------------------------------------
	
	// Send agents a list of new buyers in their area
	function sendBuyerUpdateToAgent($agent_id, $a_cust_ids){
		if(!count($a_cust_ids)){return false;}
		$res = query("SELECT * FROM `customers` WHERE `online` = 1 AND `buyer` = 1 AND `cust_id` IN (".implode($a_cust_ids).") ORDER BY `date_created` LIMIT 100");
		
		if(!num_rows($res)){return;}
		
		$a = new agent;
		$a->retrieve($agent_id);
		
		$msg = "<p>Hi ".$a->firstname."</p>";
		$msg .= '<p>Good news! '.count($a_cust_ids).' new buyers have registered in your area this week. <a href="'.$GLOBALS['system_url'].'/sign-in/">Click this link</a> to view their property requirements and start matching them with their dream property.</p>';
		
		$msg .= '<p>Remember, you can control the amount of notifications you receive by going to ‘Notification Settings’ in your dashboard.</p>';
		
		$msg .= '<p><a href="'.$GLOBALS['system_url'].'/sign-in/">Click here</a> to log in to your '.html($GLOBALS['system_name']).' account and view your new connections.</p>';
		
		$msg .= '<p>The '.html($GLOBALS['system_name']).' team</p>';

		$subject = 'New buyers in your area';
		$this->send($a->email, $subject, $msg, $GLOBALS['noreply_email']);
		
		//Log it
		$log = new logging;
		$log->agent_id = $agent_id;
		$log->cust_id = $cust_id;
		$log->email = $a->email;
		$log->logMe("New buyers notification sent to agent");
	}
	
	//-------------------------------------------------
	
	// Send agents a list of new buyers in their area
	function sendSellerUpdateToAgent($agent_id, $a_cust_ids){
		if(!count($a_cust_ids)){return false;}
		$res = query("SELECT * FROM `customers` WHERE `online` = 1 AND `seller` = 1 AND `cust_id` IN (".implode($a_cust_ids).") ORDER BY `date_created` LIMIT 100");
		
		if(!num_rows($res)){return;}
		
		$a = new agent;
		$a->retrieve($agent_id);
		
		$msg = "<p>Hi ".$a->firstname."</p>";
		$msg .= '<p>Good news! '.count($a_cust_ids).' new vendors have registered in your area this week. <a href="'.$GLOBALS['system_url'].'/sign-in/">Click this link</a> to view their property details and start matching them with buyers from your little black book.</p>';
		
		$msg .= '<p>Remember, you can control the amount of notifications you receive by going to ‘Notification Settings’ in your dashboard.</p>';
		
		$msg .= '<p><a href="'.$GLOBALS['system_url'].'/sign-in/">Click here</a> to log in to your '.html($GLOBALS['system_name']).' account and view your new connections.</p>';
		
		$msg .= '<p>The '.html($GLOBALS['system_name']).' team</p>';
		
		$subject = 'New vendors in your area';
		$this->send($a->email, $subject, $msg, $GLOBALS['noreply_email']);
		
		//Log it
		$log = new logging;
		$log->agent_id = $agent_id;
		$log->cust_id = $cust_id;
		$log->email = $a->email;
		$log->logMe("New sellers notification sent to agent");
	}
	
	//-------------------------------------------------
	
	// Notify a seller of a new pitch/proposal
	function sendSellerPitchNotification($cust_id, $agent_id, $prop_id){
		
		$a = new agent;
		$a->retrieve($agent_id);
		
		$c = new customer;
		$c->retrieve($cust_id);
		
		$msg = "<p>Hi ".$c->firstname."</p>";
		$msg .= '<p>Great news! You have received a new proposal from '.html($a->contact_name)." of ".html($a->name).'. Please <a href="'.$GLOBALS['system_url'].'/sign-in/">sign in to your Off Market London account to check your messages - they may have an interested buyer who’s waiting to view your property.</p>';

		$msg .= "<p>Remember, you can control the amount of notifications you receive by going to ‘Notification Settings’ in your dashboard.</p>";
		$msg .= "<p>The ".html($GLOBALS['system_name'])." Team</p>";
		
		$subject = 'New proposal notification';
		
		$this->send($c->email, $subject, $msg, $GLOBALS['noreply_email']);
		
		//Log it
		$log = new logging;
		$log->prop_id = $prop_id;
		$log->agent_id = $agent_id;
		$log->cust_id = $cust_id;
		$log->email = $a->email;
		$log->logMe("Pitch notification email sent");
	}
	
	//-------------------------------------------------
	
	// Result of the contact form being submitted
	function contactFormSubmit(){
		while( list($k, $v) = each($_POST) ){
			$v = str_replace(':', ';', $v);
			$this->$k = cleanInput($v);
			$$k = esc($this->$k);
		}
		
		if(!$name){addError("Please enter your name");}
		if(!$email){addError("Please enter your email address");}
		if($email){
			if(!emailValid($this->email)){
				addError("Please enter a valid email address");
			}
		}
		if(!$message){addError("Please enter your message");}
		
		if(count($GLOBALS['a_errors'])){return false;}
		
		$msg = "<p>A message sent from the contact form at ".html($GLOBALS['system_url'])."</p>";
		$msg .= "<p>Name: ".html($this->name)."</p>";
		$msg .= "<p>Email: ".html($this->email)."</p>";
		$msg .= "<hr/>";
		$msg .= "<p>Message:<br/>".nl2br(html($this->message))."</p>";
		
		// Send to admin
		$this->send($GLOBALS['admin_email'], 'A message from '.$this->name, $msg, $GLOBALS['noreply_email']);
		
		// Send confirmation to the sender
		$this->contactFormSubmitConfirmationToSender($this->email);
		
		// Save on the user's sent items
		$sql = "INSERT INTO `messages` (
			`from_cust_id`, 
			`from_agent_id`, 
			`user_type`, 
			`subject`, 
			`date_sent`,
			`message`,
			`status`
		)VALUES(
			'".$_SESSION['cust_id']."', 
			'".$_SESSION['agent_id']."', 
			'".$_SESSION['current_dash']."', 
			'To customer support', 
			".time().",
			'".esc($this->message)."',
			'sent'
		)";
		#echo $sql;
		query($sql);
		
		header('Location: /contact-confirmation/');
		exit();	
	}
	
	//-------------------------------------------------
	
	function contactFormSubmitConfirmationToSender($sender_email){
		$msg = "<p>Hi ".html($c->firstname)."</p>";
		$msg .= "<p>Thank you for getting in touch with Off Market London. One of our customer service representatives will respond to your message within 24 hours.</p><p>The ".$GLOBALS['system_name']." team</p>";
		$this->send($sender_email, 'Thanks for your message', $msg, $GLOBALS['noreply_email']);
		
		//Log it
		$log = new logging;
		$log->agent_id = $agent_id;
		$log->cust_id = $cust_id;
		$log->email = $a->email;
		$log->logMe("Pitch notification email sent");
	}
	
	//-------------------------------------------------
	
	function cancelAgentCodeRequest($agent_id, $code){
		$a = new agent;
		$a->retrieve($agent_id);
		if(!$a->agent_id){return false;}
		
		$msg = "<p>Hi ".html($a->firstname)."</p>";
		$msg .= "<p>We have received and actioned your request to cancel  your subscription to the postcode ".$code.". Following the agreed two-month notice period, your billing will be suspended. If you do not have any active postcodes, your account will be deleted.</p>
<p>If you cancelled your subscription to this postcode in  error, please <a href=\"mailto:".$GLOBALS['admin_email']."\">let us know</a>.</p>
<p>The ".$GLOBALS['system_name']." team</p>";
		
		$this->send($a->email, 'Cancellation request for '.$code, $msg, $GLOBALS['noreply_email']);
	}
	
	//-------------------------------------------------
	
}// ends class
?>