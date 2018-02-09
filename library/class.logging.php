<?php // Logging and stats class
class logging{

	function logging(){
	
	}
	
	//----------------------------------------------------------
	
	// Add a log entry
	function logMe($action='', $prop_id=0){
	
		if(!cleanInput($action)){return false;}
		
		// Session input
		$cust_id = $_SESSION['cust_id'];
		$agent_id = $_SESSION['agent_id'];
		
		// External input
		if($this->cust_id){$cust_id = $this->cust_id;}
		if($this->agent_id){$agent_id = $this->agent_id;}
		
		$sql = "INSERT INTO `log` (`cust_id`, `agent_id`, `cms_user_id`, `prop_id`, `action`, `unix`, `email`)VALUES('".esc($cust_id)."', '".esc($agent_id)."', '".esc($_SESSION['cms_user_id'])."', '".esc($this->prop_id)."', '".esc($action)."', ".time().", '".esc($this->email)."')";
		#exit($sql);
		query($sql);
		
		unset($this->prop_id);
		unset($this->agent_id);
		unset($this->cust_id);
		unset($this->email);
	}
	
	//----------------------------------------------------------
	
	// Add a log entry for a sent email
	function logEmail($to, $subject, $html, $headers){
		$sql = "INSERT INTO `log_sent_emails` (`to`, `subject`, `message`, `cust_id`, `agent_id`, `unix`)VALUES(
			'".esc($to)."', 
			'".esc($subject)."', 
			'".esc($html)."', 
			'".esc($_SESSION['cust_id'])."', 
			'".esc($_SESSION['agent_id'])."',
			".time()."
		)";
		
		query($sql);
	}
	
	//----------------------------------------------------------
}
?>