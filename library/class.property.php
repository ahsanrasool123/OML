<?php // Property object
class property{

	function property(){
		if($_POST['action'] == 'editproperty'){$this->update();}
	}
	
	//---------------------------------------------------
	
	function retrieve($prop_id){
		if(!$prop_id && !is_numeric($prop_id)){return false;}
		$res = query("SELECT * FROM `property` WHERE `prop_id` = $prop_id LIMIT 1");
		if(!num_rows($res)){return false;}
		$rs = fetch_assoc($res);
		while(list($k, $v)=each($rs)){
			$this->$k = $v;
		}
		// Seller's name
		$this->seller_name = result("SELECT CONCAT(`firstname`, ' ', `surname`) FROM `customers` WHERE `cust_id` = $this->cust_id");
		// Description
		if(!$this->desc){
			$this->desc = $rs['bedrooms'].' bedroom '.ucfirst($rs['type']).' in '.$this->code;
		}

	}
	
	//---------------------------------------------------
	
	// Permanently delete a property
	function delete($prop_id){
		if(!$prop_id && !is_numeric($prop_id)){return false;}
		query("DELETE FROM `property` WHERE `prop_id` = $prop_id LIMIT 1");
	}
	
	//---------------------------------------------------
	
	// Remove a property by marking it offline
	function remove($prop_id){
		$cust_id = $_SESSION['cust_id'];
		if(!$cust_id || !is_numeric($cust_id)){return false;}
		$sql = "UPDATE `property` SET `online` = 0 WHERE `prop_id` = '".esc($prop_id)."' AND `cust_id` = '".esc($cust_id)."' LIMIT 1";
		query($sql);
		return true;
	}
	
	//---------------------------------------------------
	
	// Update or add a property
	function update(){
		
		while(list($k, $v)=each($_POST)){
			if($v == 'Please select...'){$v = '';}
			$this->$k = cleanInput($v);
			$this->$k = str_replace(',', '', $this->$k);
			$$k = esc($this->$k);
		}
		
		// Validation
		if(!$type){addError("Please tell us what type your property is");}
		if(!$postcode){addError("Please enter the postcode of your property");}
		if(!$bedrooms){addError("Please select the number of bedrooms");}
		if(!$bathrooms){addError("Please select the number of bathrooms");}
		if(!$floors){addError("Please tell us how many floors there are");}
		if(!$sq_ft){addError("Please tell the size of the property in square feet");}

		// Any errors?
		if(count($GLOBALS['a_errors'])){return false;}
		
		// Remove spaces in postcodes
		$postcode = str_replace(' ', '', $postcode);
		
		// SQL
		if($prop_id && is_numeric($prop_id)){
			$sql= "UPDATE `property` SET 
				`postcode` = '$postcode', 
				`date_updated` = '".time()."',
				`address_1` = '$address_1',
				`address_2` = '$address_2',
				`address_3` = '$address_3',
				`address_4` = '$address_4',
				`type` = '$type',
				`bedrooms` = '$bedrooms',
				`bathrooms` = '$bathrooms',
				`floors` = '$floors',
				`sq_ft` = '$sq_ft'
				 WHERE `prop_id` = ".esc($prop_id)." LIMIT 1";
		}else{
			$sql = "INSERT INTO `property` (
				`postcode`, 
				`cust_id`,
				`address_1`,
				`address_2`,
				`address_3`,
				`address_4`,
				`type`,
				`bedrooms`,
				`bathrooms`,
				`floors`,
				`sq_ft`,
				`date_created`
			)VALUES(
				'$postcode', 
				'".esc($_SESSION['cust_id'])."',
				'$address_1',
				'$address_2',
				'$address_3',
				'$address_4',
				'$type',
				'$bedrooms',
				'$bathrooms',
				'$floors',
				'$sq_ft',
				'".time()."'
			)";
			$insert = true;
		}
		
		query($sql);
		#exit($sql);
		if($insert){$this->prop_id = insert_id();}
		
		$_SESSION['prop_id'] = $this->prop_id;
		
		// Build the address string for google maps API
		$a_address_parts = array();
		$a_fields = array('address_1', 'address_2', 'address_3', 'address_4', 'postcode');
		foreach($a_fields as $field){
			if($this->$field){
				array_push($a_address_parts, $this->$field);
			}
		}
		$address_part = implode(', ', $a_address_parts);

		// Query Google maps API for further information
		$map = new map;
		$map->storeCoordinates($address_part, $this->prop_id);
		
		// Notify agents about this new property
		if($insert){
			$email = new email;
			$email->sendAgentNewPropertyMessage($this->prop_id);
		}
		
		header('Location: http://'.$_SERVER['HTTP_HOST'].'/customer/dashboard/');
		exit();
	}
	
	//---------------------------------------------------
	
	// List properties available to this agent
	function listAgentProperties($agent_id=0){
		if(!$agent_id){$agent_id = $_SESSION['agent_id'];}
		if(!$agent_id){return false;}
		
		// Filters from the dashboard via AJAX
		if($_POST['bedrooms'] && $_POST['bedrooms'] != 'Please select...'){
			$filterquery = " AND `bedrooms` = '".esc(cleanInput($_POST['bedrooms']))."'";
		}
		if($_POST['type'] && $_POST['type'] != 'Please select...'){
			$filterquery .= " AND `type` = '".esc(cleanInput($_POST['type']))."'";
		}
		if($_POST['value'] && $_POST['value'] != 'Please select...'){
			$filterquery .= " AND `value` = '".esc(cleanInput($_POST['value']))."'";
		}
		
		$now = time();
		$subquery = "SELECT `code` FROM `agent_postcodes` WHERE `agent_id` = $agent_id AND (`date_from` <= $now AND `date_to` >= $now)";
		$sql = "SELECT `prop_id` FROM `property` WHERE `code` IN ($subquery) $filterquery";
		#echo $sql;
		$res = query($sql);
		
		echo '<ul class="agentPropList">';
		
		while($rs = fetch_assoc($res)){// loop
			$p = new property;
			$p->retrieve($rs['prop_id']);
			
			// See what the relationship is between buyer and seller
			unset($ourstatus);
			$blocked = result("SELECT COUNT(id) FROM `agent_relationship` WHERE `agent_id` = $agent_id AND `cust_id` = '".$p->cust_id."' AND `status` = 'blocked'");
			$added = result("SELECT COUNT(`id`) FROM `agent_relationship` WHERE `agent_id` = $agent_id AND `cust_id` = '".$p->cust_id."' AND `status` = 'added'");
			$pitched = result("SELECT COUNT(`msg_id`) FROM `messages` WHERE `from_agent_id` = '".$_SESSION['agent_id']."' AND `prop_id` = '".$p->prop_id."'");
			
			if(!$blocked){// Only show if not blocked
			
				$c = new customer;
				$c->retrieve($p->cust_id);
				
				echo '<li id="prop_'.$p->prop_id.'">';
				
				// Type and location and seller name
				echo '<h3>'.$p->desc.' owned by '.html($c->name).'</h3>';
				
				// Details
				echo '<div class="propertyListDetails">';
				echo '<strong>Tenure:</strong> '.$p->tenure.'<br/>';
				echo '<strong>Bathroooms:</strong> '.$p->bathrooms.'<br/>';
				echo '<strong>Floors:</strong> '.$p->floors.'<br/>';
				echo '<strong>Square feet:</strong> '.$p->sq_ft.'<br/>';
				echo '<strong>Seller\'s situation:</strong> '.$p->situation.'<br/>';
				echo '</div>';
				
				
				// Buttons
				if($added){
					echo '<div class="button" onclick="messageWin('.$p->cust_id.')">Contact seller</div>';
					#echo '<div class="button" onclick="openThread('.$p->cust_id.')">View conversation</div>';
				}else{
					if($pitched){
						echo '<div class="button">Pitch sent</div>';
					}else{
						echo '<div class="button" onclick="sendPitch('.$p->prop_id.')" id="pitchBut_'.$p->prop_id.'">Send my preset pitch to this seller</div>';
					}
				}
				
				echo '</li>';
				$i++;
			}
			
		}
		
		echo '</ul>';
		
		if(!$i){// No properties
			echo '<p>There are no available properties in your areas.</p>';
			return;
		}
	}
	
	//---------------------------------------------------
	
	// Send a picch message to a customer
	function sendPitch($prop_id){
		$this->retrieve($prop_id);
		if(!$this->prop_id){return;}
		if(!$_SESSION['agent_id']){return;}
		
		$c = new customer;
		$c->retrieve($this->cust_id);
		
		$a = new agent;
		$a->retrieve($_SESSION['agent_id']);
		
		$subject = 'Please consider me as your agent';
		
		$msg = "Dear ".$c->firstname.chr(10).chr(10);
		$msg .= "Regarding your property: ".$this->formatted_address.chr(10).chr(10);
		$msg .= $a->pitch.chr(10).chr(10);
		$msg .= "Kind regards".chr(10);
		$msg .= $a->contact_name.chr(10);
		$msg .= $a->name.chr(10);
		
		$sql = "INSERT INTO `messages` (
			`to_cust_id`, `from_agent_id`, `subject`, `message`, `date_sent`, `prop_id`
		)VALUES(
			'".$c->cust_id."', '".$a->agent_id."', '".esc($subject)."', '".esc($msg)."', ".time().", '".esc($prop_id)."'
		)";
		
		query($sql);
		
		// Send the seller an email notification
		$email = new email;
		$email->sendSellerPitchNotification($c->cust_id, $a->agent_id, $prop_id);
		
		// Log it
		$log = new logging;
		$log->cust_id = $c->cust_id;
		$log->prop_id = $prop_id;
		$log->logMe('Pitch message sent to '.$c->name);
		
	}
	
	
	
	//---------------------------------------------------
	
}// Ends class
?>