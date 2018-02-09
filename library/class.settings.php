<?php // Generic settings
class settings{
	
	function settings(){
		if($_POST['action'] == 'updateNotificationSettings'){
			$this->updateNotificationSettings();
		}
	}
	
	// ---------------------------------------------
	
	// User updating their notification settings
	function updateNotificationSettings(){
		
		while(list($k, $v)=each($_POST)){
			$$k = esc(cleanInput($v));
		}
		
		// Agents
		if($_SESSION['agent_id']){
			
			if($notification_message){
				$sql = "UPDATE `agents` SET `notification_message` = '".$notification_message."' WHERE `agent_id` = ".$_SESSION['agent_id']." LIMIT 1";
				query($sql);
			}
			if($notification_buyer){
				$sql = "UPDATE `agents` SET `notification_buyer` = '".$notification_buyer."' WHERE `agent_id` = ".$_SESSION['agent_id']." LIMIT 1";
				query($sql);
			}
			
			if($notification_seller){
				$sql = "UPDATE `agents` SET `notification_seller` = '".$notification_seller."' WHERE `agent_id` = ".$_SESSION['agent_id']." LIMIT 1";
				query($sql);
			}
			
		}
		
		// Customers
		if($_SESSION['cust_id']){
			
			if($notification_message){
				$sql = "UPDATE `customers` SET `notification_message` = '".$notification_message."' WHERE `cust_id` = ".$_SESSION['cust_id']." LIMIT 1";
				query($sql);
			}
			
			if($notification_agent){
				$sql = "UPDATE `customers` SET `notification_agent` = '".$notification_agent."' WHERE `cust_id` = ".$_SESSION['cust_id']." LIMIT 1";
				query($sql);
			}
			
			if($notification_pitch){
				$sql = "UPDATE `customers` SET `notification_pitch` = '".$notification_pitch."' WHERE `cust_id` = ".$_SESSION['cust_id']." LIMIT 1";
				query($sql);
			}
			
			if($notification_proposal){
				$sql = "UPDATE `customers` SET `notification_proposal` = '".$notification_proposal."' WHERE `cust_id` = ".$_SESSION['cust_id']." LIMIT 1";
				query($sql);
			}
			
		}
		$this->settingsUpdated=1;
	}
	
	// ---------------------------------------------
}
?>