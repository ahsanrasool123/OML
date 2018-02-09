<?php //Class that handles all payment and billing matters
class payment{

	function payment(){
		if(!$GLOBALS['secret_key']){exit('Please set the Sripe secret key');};
		if(!$GLOBALS['publishable_key']){exit('Please set the Stripe publishable key');};
		if(!$GLOBALS['price_per_code']){exit('Please enter your price per code');}
		
		// Stripe returns a token
		if($_POST['stripeToken']){
			$this->saveToken();
		}
	}	
	
	//-----------------------------------------------------
	
	// Renders the payment form on the dashboard fpr postcode payments
	function payForCodesButton(){

		$now = time();
		
		if($_SESSION['agent_id']){
			$sql = "SELECT COUNT(`code`) FROM `agent_postcodes` WHERE `agent_id` = '".esc($_SESSION['agent_id'])."' AND `date_to` < $now";
			$codes = result($sql);
		}else{// New signup
			$codes = count($_SESSION['a_customer_postcodes']);
		}
		
		//echo 'Codes: '.$codes;
		
		if(!$codes){return false;}
		
		// New signup billing period
		if(!$_SESSION['agent_id']){
			if($_SESSION['temp_billing_period']){
				$billing_period = $_SESSION['temp_billing_period'];
			}
		}
		
		if($billing_period == 'monthly'){
			$price = $GLOBALS['price_per_code']*$codes;
			$desc = $codes.' postcodes at '.currency($GLOBALS['price_per_code']).' each per month';
		}else{
			$price = $GLOBALS['price_per_year']*$codes;
			$desc = $codes.' postcodes at '.currency($GLOBALS['price_per_year']).' each per year';
		}
		
		if(count($_SESSION['a_customer_postcodes'])){// New signups
			$this->checkoutFormNewSignups($price, $desc);
		}else{// Existing users
			$this->checkoutForm($price, $desc);
		}
	}
	
	//-----------------------------------------------------
	
	// Renders the Stripe checkout form on the dash, for paying for postcodes
	function checkoutForm($price, $desc = ''){
		
		if(!$_SESSION['agent_id'] || !is_numeric($_SESSION['agent_id'])){return false;}
		
		if(!$price){$price = 0;}
		
		$email = result("SELECT `email` FROM `agents` WHERE `agent_id` = '".$_SESSION['agent_id']."'");
		$billing_period = result("SELECT `billing_period` FROM `agents` WHERE `agent_id` = '".$_SESSION['agent_id']."'");
		$stripe_cust_id = result("SELECT `stripe_cust_id` FROM `agents` WHERE `agent_id` = '".$_SESSION['agent_id']."'");
		
		$a_codes = array();
		$res = query("SELECT DISTINCT(`code`) FROM `agent_postcodes` WHERE `agent_id` = '".esc($_SESSION['agent_id'])."' AND `date_to` < ".time()."");
		while( $rs = fetch_assoc($res) ){
			array_push($a_codes, $rs['code']);
		}
		
		echo '<p>'.html($desc).'</p>';
		
		if(!$stripe_cust_id){
			echo '<p>Sign up now for your discount {DISCOUNT INFO GOES HERE}.</p>';
		}
		
		radioButtons(array('monthly','yearly'), 'billing_period_new', $billing_period, 'Billing period');

		echo '<form action="" method="POST">
		<input type="hidden" name="action" value="payForPostcodes">
		<input type="hidden" name="amount" value="'.$price.'">
		<input type="hidden" name="codes" value="'.implode(',', $a_codes).'">
  <script
    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
    data-key="'.$GLOBALS['publishable_key'].'"
    data-amount="'.($price*100).'"
	data-email="'.$email.'"
	data-panel-label="Pay for your postcodes"
	data-currency="'.$GLOBALS['currency'].'"
    data-name="'.$GLOBALS['system_name'].'"
    data-description="'.$desc.' ('.currency($price).')"
    data-image="'.$GLOBALS['logo'].'"
    data-locale="auto">
  </script>
</form>';
	}
	
	
	//-----------------------------------------------------
	
	// Renders the Stripe checkout form on the dash, for paying for postcodes
	function checkoutFormNewSignups($price, $desc = ''){
		
		$agent = new agent;
		$agent->retrieve($_SESSION['temp_agent_id']);
		
		if(!$agent->email){
			echo '<p>Complete your registration details first</p>';
			return;
		}
		
		$agent = new agent;
		$agent->retrieve($_SESSION['temp_agent_id']);
		
		if(!$price){$price = 0;}
		
		$a_codes = $_SESSION['a_customer_postcodes'];
		
		echo '<p>'.html($desc).'</p>';
		
		if(!$_SESSION['temp_billing_period']){$_SESSION['temp_billing_period'] = 'yearly';}
		radioButtons(array('monthly','yearly'), 'billing_period_new', $_SESSION['temp_billing_period'], 'Billing period');
	
		echo '<form action="" method="POST">
		<input type="hidden" name="action" value="payForPostcodes">
		<input type="hidden" name="amount" value="'.$price.'">
		<input type="hidden" name="codes" value="'.implode(',', $a_codes).'">
  <script
    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
    data-key="'.$GLOBALS['publishable_key'].'"
    data-amount="'.($price*100).'"
	data-email="'.$agent->email.'"
	data-panel-label="Pay for your postcodes"
	data-currency="'.$GLOBALS['currency'].'"
    data-name="'.$GLOBALS['system_name'].'"
    data-description="'.$desc.' ('.currency($price).')"
    data-image="'.$GLOBALS['logo'].'"
    data-locale="auto">
  </script>
</form>';
	}
	
	//-----------------------------------------------------
	
	// Saves a stripe token resulting from an agent using the checkout
	function saveToken(){
		$token = $_POST['stripeToken'];
		$email = $_POST['stripeEmail'];
		$amount = $_POST['amount'];
		$codes = $_POST['codes'];
		$token_type = $_POST['stripeTokenType'];

		if($_SESSION['agent_id']){
			$agent_id = $_SESSION['agent_id'];
		}else{
			$agent_id = $_SESSION['temp_agent_id'];
		}
		
		$sql = "INSERT INTO `stripe_payments` (
			`agent_id`, 
			`token`, 
			`token_type`, 
			`email`, 
			`unix`, 
			`ip`, 
			`amount`, 
			`desc`
		)VALUES(
			'".esc($agent_id)."', 
			'".esc($token)."', 
			'".esc($token_type)."', 
			'".esc($email)."', 
			".time().", 
			'".$_SERVER['REMOTE_ADDR']."', 
			'".esc($amount)."', 
			'".esc($codes)."'
		)";
		query($sql);
		
		// Create the customer
		$this->createCustomer();
		
		// Charge the card
		$accepted = $this->chargeCustomerId($agent_id, $amount, $token);
		
		if(!$accepted){
			header('Location: /payment-declined/');
			exit();
		}
		
		// Activate the codes
		if($_POST['action'] == 'payForPostcodes' || $_SESSION['temp_agent_id']){
			$this->activatePostcodes();
		}
		
		// Redirect
		if($_SESSION['temp_agent_id']){// New signups
		
			// Clear the signup session
			unset($_SESSION['a_customer_postcodes']);
			unset($_SESSION['temp_billing_period']);
			unset($_SESSION['temp_agent_id']);
			unset($_SESSION['temp_email']);
		
			header('Location: /agent/signup-confirmation/');
		}else{// Existing agent
			header('Location: /payment-confirmation/');
		}
		
		exit();
	}

	
	//-----------------------------------------------------
	
	// Once paid we can activate their codes for a year
	function activatePostcodes(){
		
		// New user creating codes for the first time
		if($_SESSION['temp_agent_id']){
			$agent_id = $_SESSION['temp_agent_id'];
			
			$billing_period = result("SELECT `billing_period` FROM `agents` WHERE `agent_id` = $agent_id");
			
			// Insert any new codes for new agents
			foreach($_SESSION['a_customer_postcodes'] as $code){
				
				if($billing_period == 'yearly'){
					$date_to = strtotime("+1 year");
				}else{
					$date_to = strtotime("+1 month");
				}
				
				$sql = "INSERT INTO `agent_postcodes` (
					`code`, 
					`agent_id`, 
					`date_created`, 
					`date_from`, 
					`date_to`, 
					`auto_renew`
				)VALUES(
					'$code',
					'$agent_id',
					'".time()."',
					'".time()."',
					'".$date_to."', 
					'1'
				)";
				#mail('test@meerkats.co.uk', 'OML SQL', $sql);
				query($sql);
			}
			return true;
		}
		
		
		if($_SESSION['agent_id']){
			$agent_id = $_SESSION['agent_id'];
		}
		

		// Existing agent renewing postcodes
		$now = time();
		
		$billing_period = result("SELECT `billing_period` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'");
		if($billing_period == 'monthly'){
			$date_to = strtotime("+1 month");
		}else{
			$date_to = strtotime("+1 year");
		}
		$sql = "UPDATE `agent_postcodes` SET `date_from` = $now, `date_to` = '$date_to' WHERE `agent_id` = '".esc($agent_id)."' AND `date_to` < $now AND `auto_renew` = 1";
		query($sql);
	}
	
	//-----------------------------------------------------
	
	// Create a customer account on the Stripe system
	function createCustomer(){
	
		if($_SESSION['agent_id']){
			$agent_id = $_SESSION['agent_id'];
		}else{
			$agent_id = $_SESSION['temp_agent_id'];
		}
	
		// Check if this customer exists
		$stripe_cust_id = result("SELECT `stripe_cust_id` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'");
		if($stripe_cust_id){return false;}
	
		\Stripe\Stripe::setApiKey($GLOBALS['secret_key']);
		$token = $_POST['stripeToken'];
		$email = result("SELECT `email` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'");
		$customer = \Stripe\Customer::create(array(
		  "source" => $token,
		  "email" => $email,
		  "description" => "Agent ID: ".$agent_id)
		);
		query("UPDATE `agents` SET `stripe_cust_id` = '".esc($customer->id)."', `contract_start` = ".time()." WHERE `agent_id` = '".esc($agent_id)."'");
	}
	
	
	//-----------------------------------------------------
	
	// Charge a stored customer a fixed amount (the first time only - new customers)
	function chargeCustomerId($agent_id, $amount, $token){

		\Stripe\Stripe::setApiKey($GLOBALS['secret_key']);
		
		// Get the agent's Stripe ID from the database
		$stripe_cust_id = result("SELECT `stripe_cust_id` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'");
		$email = result("SELECT `email` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'");
		$firstname = result("SELECT `firstname` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'");
		$billing_period = result("SELECT `billing_period` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'");
		
		$desc = 'Your postcodes';
		
		if(!$stripe_cust_id){
			addError("Customer Stripe ID not found");
			return false;
		}
		
		try {
			\Stripe\Charge::create(array(
			  "amount"   => ($amount*100),
			  "currency" => $GLOBALS['currency'],
			  "customer" => $stripe_cust_id // Previously stored, then retrieved
			  ));
			  query("UPDATE `stripe_payments` SET
				`result` = 'accepted', 
				`stripe_cust_id` = '".esc($stripe_cust_id )."', 
				`billing_period` = '".esc($billing_period)."'
			WHERE `token` = '".esc($token)."'");
			
			// Activate the account
			query("UPDATE `agents` SET `online` = 1 WHERE `agent_id` = $agent_id LIMIT 1");
			
			// Set the billing period
			if($_SESSION['temp_billing_period']){
				query("UPDATE `agents` SET `billing_period` = '".$_SESSION['temp_billing_period']."' WHERE `agent_id` = $agent_id LIMIT 1");
			}
			
			// Log the agent into their account
			$_SESSION['agent_id'] = $agent_id;
			$_SESSION['parent_id'] = $agent_id;
			$_SESSION['signed_in'] = 'agent';
			
			// Send an email notification
			$e = new email;
			if($_SESSION['temp_agent_id']){
				$e->newAgentBillingNotification($firstname, $email, $amount, $_SESSION['a_customer_postcodes'], $_SESSION['temp_agent_id']);
			}else{
				$e->recurrringBillingNotification($firstname, $email, $amount, $desc, $billing_period);
			}
			
			return true;
			
		} catch(\Stripe\Error\Card $e) {
			query("UPDATE `stripe_payments` SET
				`result` = 'failed', 
				`stripe_cust_id` = '".esc($stripe_cust_id )."', 
				`billing_period` = '".esc($billing_period)."'
			WHERE `token` = '".esc($token)."'");
			return false;
		}
		
	}
	
	
	//-----------------------------------------------------
	
	// Charge a stored customer a fixed amount for recurring billing
	function chargeCustomerIdRecurring($agent_id, $amount, $desc=''){

		\Stripe\Stripe::setApiKey($GLOBALS['secret_key']);
		
		// Get the agent's Stripe ID from the database
		$stripe_cust_id = result("SELECT `stripe_cust_id` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'");
		$email = result("SELECT `email` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'");
		$billing_period = result("SELECT `billing_period` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."'");
		
		if(!$stripe_cust_id){
			addError("Customer Stripe ID not found");
			return false;
		}
		
		try {
			\Stripe\Charge::create(array(
			  "amount"   => ($amount*100),
			  "currency" => $GLOBALS['currency'],
			  "customer" => $stripe_cust_id // Previously stored, then retrieved
			  ));
			  query("INSERT INTO `stripe_payments` (
			  	`agent_id`,
				`result`, 
				`stripe_cust_id`, 
				`amount`, 
				`email`, 
				`unix`, 
				`desc`,
				`token_type`,
				`billing_period`
			)VALUES(
				'".esc($agent_id)."',
				'accepted', 
				'".esc($stripe_cust_id )."', 
				'".esc($amount)."',
				'".esc($email)."',
				".time().",
				'".esc($desc)."',
				'renewal',
				'".esc($billing_period)."'
			)");
			return true;
		} catch(\Stripe\Error\Card $e) {
			query("INSERT INTO `stripe_payments` (
			  	`agent_id`,
				`result`, 
				`stripe_cust_id`, 
				`amount`, 
				`email`, 
				`unix`, 
				`desc`,
				`token_type`,
				`billing_period`
			)VALUES(
				'".esc($agent_id)."',
				'failed', 
				'".esc($stripe_cust_id )."', 
				'".esc($amount)."',
				'".esc($email)."',
				".time().",
				'".esc($desc)."',
				'renewal',
				'".esc($billing_period)."'
			)");
			return false;
		}

	}
	
	//-----------------------------------------------------
	
	// Takes payment for any expired postcodes where the agent has a STRIPE customer ID (triggered from a CRON job)
	function makeRecurringPayments(){
		
		// First we need to cancel and delete any codes that the agent has requested to cancel. These will have a die date
		query("DELETE FROM `agent_postcodes` WHERE ".time()." > `die_date` AND `die_date` > 100");
		
		// Get unique agent_id where the agent has expired codes
		$res = query("SELECT DISTINCT(`agent_id`) FROM `agent_postcodes` WHERE `auto_renew` = 1 AND `date_to` != 0 AND `date_to` < ".time());
		
		while($rs = fetch_assoc($res)){
		
			$stripe_cust_id = result("SELECT `stripe_cust_id` FROM `agents` WHERE `agent_id` = '".esc($rs['agent_id'])."'");
			$billing_period = result("SELECT `billing_period` FROM `agents` WHERE `agent_id` = '".esc($rs['agent_id'])."'");
			$firstname = result("SELECT `firstname` FROM `agents` WHERE `agent_id` = '".esc($rs['agent_id'])."'");
			$email = result("SELECT `email` FROM `agents` WHERE `agent_id` = '".esc($rs['agent_id'])."'");
			
			// Get the expired codes for this agent
			$a_codes = array();
			$res2 = query("SELECT `code` FROM `agent_postcodes` WHERE `auto_renew` = 1 AND `agent_id` = ".esc($rs['agent_id'])." AND (`date_to` != 0 AND `date_to` < ".time().")");
			$num_codes = num_rows($res2);
			while($rs2 = fetch_assoc($res2)){
				array_push($a_codes, $rs2['code']);
			}	
			
			// What's our billing periood here?
			if($billing_period == 'monthly'){
				$amount = $GLOBALS['price_per_code']*$num_codes;
			}else{
				$amount = $GLOBALS['price_per_year']*$num_codes;
			}
			
			// We need the stripe customer ID
			if($stripe_cust_id){
				echo 'We can renew codes for agent '.$rs['agent_id'].' with account '.$stripe_cust_id.' and '.$num_codes.' expired codes ('.implode(', ', $a_codes).'). The customer is billed '.$billing_period.' and the amount charged is '.currency($amount);
			}
			echo '<hr/>';

			// Charge the agent's card
			$ok = $this->chargeCustomerIdRecurring($rs['agent_id'], $amount, implode(',', $a_codes));
			
			if($ok){ // If accepted we can update the expiry dates on the codes
				if($billing_period == 'monthly'){
					$date_to = strtotime("+1 month");
				}else{
					$date_to = strtotime("+1 year");
				}
				$sql = "UPDATE `agent_postcodes` SET `date_to` = ".$date_to." WHERE `agent_id` = '".esc($rs['agent_id'])."' AND (`date_to` != 0 AND `date_to` < ".time().") AND `auto_renew` = 1";
				query($sql);
				
				// Send an email notification
				$e = new email;
				$e->recurrringBillingNotification($firstname, $email, $amount, implode(', ', $a_codes), $billing_period);
			}
			
		}
		
	}
	
	
	//-----------------------------------------------------
	
	// List all payments made by the agent
	function listPayments(){
		if(!$_SESSION['agent_id']){return false;}
		$res = query("SELECT * FROM `stripe_payments` WHERE `agent_id` = '".esc($_SESSION['agent_id'])."' AND `result` = 'accepted' ORDER BY `unix` DESC");
		echo '<ul>'.chr(10);
		while($rs = fetch_assoc($res)){
			echo '<li>'.datetime($rs['unix']).' '.' '.currency($rs['amount']).' '.$rs['billing_period'].' '.$rs['desc'].'</li>'.chr(10);
		}
		echo '</ul>'.chr(10);
	}
	
	//-----------------------------------------------------
	
	// Delete a payment (for development)
	function delete($id){
		if(!is_numeric($id)){return;}
		query("DELETE FROM `stripe_payments` WHERE `id` = $id LIMIT 1");
		header('Location: payments_list.php?terms='.$_GET['terms']);
		exit();
	}
	
} // ends class
?>