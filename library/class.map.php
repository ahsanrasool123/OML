<?php // Map functions class
class map{
	
	function map(){
		$this->google_api_server_key = $GLOBALS['google_api_server_key'];
		$this->units = 'imperial';
		$this->transport_mode = 'driving';
		#$this->storeCoordinates('15 Parkview Road, Croydon, CR07DF', 7);
		#$this->calculateDistance('Natural History Museum, London', 'Cork, Ireland');
		$this->initializePostcodeCoordinates();
	}
	
	
	// ----------------------------------------------------------------------------
	
	
	// Returns the address and coordinates as an associative array after querying the Google maps API
	function getCoordinatesArray($address_part){
		$address_part = urlencode($address_part);
		$url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address_part.'&key='.$this->google_api_server_key;
		$json = file_get_contents($url);
		$array = json_decode($json, true);
		return $array['results'][0];
	}
	
	
	// ----------------------------------------------------------------------------
	
	
	// Return the longitude and latitude of an address
	function getCoordinates($address_part){
		$array = $this->getCoordinatesArray($address_part);
		return $array;
	}
	
	
	// ----------------------------------------------------------------------------
	
	
	// Permanently store the coordinates and address breakdown in a property record
	function storeCoordinates($address_part, $prop_id=0){
		if(!$prop_id || !is_numeric($prop_id)){return false;}
		$array = $this->getCoordinatesArray($address_part);
		#print_r($array['address_components'][7]['long_name']);
		$sql = "UPDATE `property` SET 
			`lng` = '".$array['geometry']['location']['lng']."', 
			`lat` = '".$array['geometry']['location']['lat']."',
			`formatted_address` = '".esc($array['formatted_address'])."', 
			`maps_address_1` = '".esc($array['address_components'][0]['long_name'])."', 
			`maps_address_2` = '".esc($array['address_components'][1]['long_name'])."', 
			`maps_address_3` = '".esc($array['address_components'][2]['long_name'])."', 
			`maps_address_4` = '".esc($array['address_components'][3]['long_name'])."', 
			`maps_address_5` = '".esc($array['address_components'][4]['long_name'])."', 
			`maps_address_6` = '".esc($array['address_components'][5]['long_name'])."', 
			`maps_address_7` = '".esc($array['address_components'][6]['long_name'])."', 
			`google_data` = '".serialize($array)."'
			 WHERE `prop_id` = $prop_id LIMIT 1";
		#echo $sql;
		query($sql);
		
		// Get the first part of the postcode from Google
		$sql = "SELECT `postcode` FROM `property` WHERE `prop_id` = $prop_id";
		#exit($sql);
		$postcode = result($sql);
		if($postcode){
			$this->storeCodePart($postcode, $prop_id);
		}
	}
	
	
	// ----------------------------------------------------------------------------
	
	
	// Determines and stores the postcode area
	function storeCodePart($postcode='', $prop_id=0){
		if(!$prop_id || !is_numeric($prop_id)){return false;}
		if(!$postcode){return false;}
		$array = $this->getCoordinatesArray($postcode);
		$postcode = $array['address_components'][0]['long_name'];
		if(strpos($postcode, ' ')>0){
			$postcode = trim(substr($postcode, 0, strpos($postcode, ' ')));
		}
		$sql = "UPDATE `property` SET `code` = '".esc($postcode)."' WHERE `prop_id` = $prop_id LIMIT 1";
		#exit($sql);
		query($sql);
	}
	
	// ----------------------------------------------------------------------------
	
	
	// Determines and stores the postcode area
	function storeCodePartAgent($postcode='', $agent_id){
		if(!$postcode){return false;}
		$array = $this->getCoordinatesArray($postcode);
		#print_r($array);
		$lng = $array['geometry']['location']['lng'];
		$lat = $array['geometry']['location']['lat'];
		$sql = "UPDATE `agents` SET `lat` = '".esc($lat)."', `lng` = '".esc($lng)."' WHERE `agent_id` = $agent_id LIMIT 1";
		#echo $sql;
		query($sql);
	}
	
	// ----------------------------------------------------------------------------
	
	
	// Calculate the journey distance between two locations
	function calculateDistance($start, $end){
		$start = urlencode($start);
		$end = urlencode($end);
		$url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$start.'&destinations='.$end.'&mode='.$this->transport_mode.'&language=gb-GB&units='.$this->units.'&key='.$this->google_api_server_key;
		$json = file_get_contents($url);
		return($json);
	}
	
	
	// ----------------------------------------------------------------------------
	
	// Render a google map on the epage
	function showMap($address, $w=500, $h=400){
		$url = 'https://www.google.com/maps/embed/v1/place?key='.$GLOBALS['google_api_browser_key'].'&q='.urlencode($address).'&zoom=12';
		echo '<iframe width="'.$w.'" height="'.$h.'" frameborder="0" style="border:0" ';
		echo 'src="'.$url.'" allowfullscreen>';
		echo '</iframe>';
	}
	
	// ----------------------------------------------------------------------------
	
	// Render a google map showing search results
	function searchMap($address, $w=500, $h=400){
		$url = 'https://www.google.com/maps/embed/v1/search?key='.$GLOBALS['google_api_browser_key'].'&q='.urlencode($address).'&zoom=12';
		echo '<iframe width="'.$w.'" height="'.$h.'" frameborder="0" style="border:0" ';
		echo 'src="'.$url.'" allowfullscreen>';
		echo '</iframe>';
	}
	
	// ----------------------------------------------------------------------------
	
	
	// Controls for users to select the postcodes they want
	function postcodeSelector($custtype='seller'){
		
		if($_SESSION['agent_id'] && is_numeric($_SESSION['agent_id'])){
			$type = 'agent';
		}else if($_SESSION['cust_id'] && is_numeric($_SESSION['cust_id'])){
			$type = 'customer';
		}else{
			$is_signup = true;
		}	
		
		// get the agents codes
		$a_codes = array();
		if($is_signup){
			$a_codes = $_SESSION['a_customer_postcodes'];
		}else{
			// Existing users
			if($type == 'agent'){
				$res = query("SELECT `code` FROM `agent_postcodes` WHERE `agent_id` = ".$_SESSION['agent_id']);
			}else{
				$res = query("SELECT `code` FROM `".$custtype."_postcodes` WHERE `cust_id` = ".$_SESSION['cust_id']);
			}
			
			while($rs = fetch_assoc($res)){
				array_push($a_codes, $rs['code']);
			}
		
		}
		
		echo '<div id="postcodePanel">'.chr(10).chr(10);
		
		// Filter controls
		echo '<div id="codeFilterBox">'.chr(10);
		echo '<input type="text" name="codeFilter" id="codeFilter" value="Filter postcodes..." onchange="filterCodes()"/>';
		echo '<input type="button" value=""/>';
		echo '</div>';

		
		// Get all postcodes available
		echo '<h3>Postcodes</h3>';
		
		if(count($a_codes)>0){
			$sql = "SELECT * FROM `postcodes` WHERE `code` NOT IN('".implode('\', \'', $a_codes)."') ORDER BY `area`";
		}else{
			$sql = "SELECT * FROM `postcodes` ORDER BY `area`";
		}
		
		$res = query($sql);
		
		if(num_rows($res)){
			echo '<ul class="codeSelector" id="availablecodes">';
			$this->availableCodeSelectorList();
			echo '</ul>';
			
		}else{
			echo '<p>No matching postcodes</p>';
		}
		


		// Already selected postcodes for this user
		/*
		echo '<h3>Saved codes</h3>'.chr(10);
		
		if($is_signup){
			$sql = "SELECT * FROM `postcodes` WHERE `code` IN ('".implode("', '", $_SESSION['a_customer_postcodes'])."') ORDER BY `area`";
		}else{
			
			if($type == 'agent'){
				$sql = "SELECT * FROM `agent_postcodes` WHERE `agent_id` = ".$_SESSION['agent_id']." ORDER BY `code`";
			}else{
				$sql = "SELECT * FROM `postcodes` WHERE `code` IN (SELECT `code` FROM `".$custtype."_postcodes` WHERE `cust_id` = ".$_SESSION['cust_id'].") ORDER BY `area`";
			}
			
		}
		$res = query($sql);
		
		echo '<ul class="codeSelector" id="mycodes">';
		$this->myCodeSelectorList($res);
		echo '</ul>';
		*/
		
		echo '</div>'.chr(10);
		
	}
	
	// ----------------------------------------------------------------------------
	
	// Generic function to render a map in an iframe with multiple marker pins
	function mapMarkers($a_locs = 0, $getString=''){
		if(!is_array($a_locs)){
			#$a_locs = array('name' => 'London', 'address' => 'London W1', 'lat'=>'', 'lng' => '');
			echo 'mapMarkers() requires a data array to generate a map';
			return false;
		}
		#print_r($a_locs);
		$_SESSION['map_data'] = serialize($a_locs);
		echo '<iframe src="/library/maps/index.php'.$getString.'" class="mapframe" id="mapframe"></iframe>';
		return true;
	}
	
	// ----------------------------------------------------------------------------
	
	// Renders a map showing all the customer's approved agents
	function customerAgents($cust_id = 0, $current_dash=false){
	
		if(!$cust_id){
			$cust_id = $_SESSION['cust_id'];
		}
		if(!$cust_id){return false;}
		
		if(!$current_dash){
			$current_dash = $_SESSION['current_dash'];
		}
		
		//Make sure agentshave lat and lang values
		$res = query("SELECT * FROM `agents` WHERE `lat` = '' AND `postcode` != ''");
		while( $rs = fetch_assoc($res) ){
			$this->storeCodePartAgent($rs['postcode'], $rs['agent_id']);
		}
		
		// Get the customer's added agents
		$sql = "SELECT `agent_id`, `firstname`, `surname`, `name`, `postcode`, `lat`, `lng`  FROM `agents` WHERE `online` = 1 AND `published` = 1 AND `agent_id` IN (SELECT DISTINCT(`agent_id`) FROM `agent_relationship` WHERE `cust_id` = ".$cust_id." AND `status` = 'added' AND `role` = '".$current_dash."')";
		$res = query($sql);
		if(!num_rows($res)){echo 'You have no agents yet';return;}
		
		
		
		$a_locs = array();
		$i=0;
		while($rs = fetch_assoc($res)){
			$a = new agent;
			$a->retrieve($rs['agent_id']);
			$a_locs[$i]['name'] = $rs['firstname'].' '.$rs['surname'].', '.$rs['name'];
			$a_locs[$i]['lat'] = $rs['lat'];
			$a_locs[$i]['lng'] = $rs['lng'];
			$i++;
		}
		//print_r($a_locs);
		$this->mapMarkers($a_locs, '?nomarker=1');
	}
	
	// ----------------------------------------------------------------------------
	
	
	// Finds lng and lat of stored postcodes
	function initializePostcodeCoordinates(){
		$res = query("SELECT * FROM `postcodes` WHERE `lng` = '' OR `lat` = ''");
		if(!num_rows($res)){return;}
		while($rs = fetch_assoc($res)){
			$a = $this->getCoordinatesArray($rs['area'].', '.$rs['code']);
			$lng = $a['geometry']['location']['lng'];
			$lat = $a['geometry']['location']['lat'];
			$sql = "UPDATE `postcodes` SET `lng` = '".esc($lng)."', `lat` = '".esc($lat)."' WHERE `code` = '".esc($rs['code'])."'";
			query($sql);
		}
	}
	
	// ----------------------------------------------------------------------------
	
	
	// Render a google map showing all the customer's postcodes as markers
	function showCustomerPostcodes($type='seller'){
		
		if($_SESSION['cust_id']){
			$sql = "SELECT * FROM `postcodes` WHERE `code` IN (SELECT `code` FROM `".$type."_postcodes` WHERE `cust_id` = ".$_SESSION['cust_id'].")";
		}else{// New cust signing up
			$sql = "SELECT * FROM `postcodes` WHERE `code` IN ('".implode("', '", $_SESSION['a_customer_postcodes'])."')";
		}
		#echo $sql;
		$res = query($sql);
		if(!num_rows($res)){return;}
		
		$a_locs = array();
		$i=0;
		while($rs = fetch_assoc($res)){
			$a_locs[$i]['name'] = $rs['area'];
			$a_locs[$i]['address'] = $rs['code'];
			$a_locs[$i]['lng'] = $rs['lng'];
			$a_locs[$i]['lat'] = $rs['lat'];
			$i++;
		}
		$this->mapMarkers($a_locs);
	}
	
	// ----------------------------------------------------------------------------
	
	// Render a map of the agent's postcodes
	function showAgentPostcodes(){
		$time = time();
		$res = query("SELECT * FROM `postcodes` WHERE `code` IN (SELECT `code` FROM `agent_postcodes` WHERE `agent_id` = ".$_SESSION['agent_id']." AND (`date_from` < $time AND `date_to` > $time))");
		$a_locs = array();
		$i=0;
		while($rs = fetch_assoc($res)){
			$a_locs[$i]['name'] = $rs['area'];
			$a_locs[$i]['address'] = $rs['code'];
			$a_locs[$i]['lng'] = $rs['lng'];
			$a_locs[$i]['lat'] = $rs['lat'];
			$i++;
		}
		$this->mapMarkers($a_locs);
	}
	
	// ----------------------------------------------------------------------------
	
	// Render a google map showing all the customer's postcodes as markers
	function showTempPostcodes($type='seller'){

		if(count($_SESSION['a_customer_postcodes'])){
			$sql = "SELECT * FROM `postcodes` WHERE `code` IN ('".implode("', '", $_SESSION['a_customer_postcodes'])."')";
		}else{
			$sql = "SELECT * FROM `postcodes` WHERE `code` = 'W1'";
		}
		
		$res = query($sql);
		if(!num_rows($res)){
			return false;
		}
		
		$a_locs = array();
		$i=0;
		while($rs = fetch_assoc($res)){
			$a_locs[$i]['name'] = $rs['area'];
			$a_locs[$i]['address'] = $rs['code'];
			$a_locs[$i]['lng'] = $rs['lng'];
			$a_locs[$i]['lat'] = $rs['lat'];
			$i++;
		}
		
		if(!count($a_locs)){
			$a_locs[$i]['name'] = 'London';
			$a_locs[$i]['address'] = 'W1';
			#$a_locs[$i]['lng'] = $rs['lng'];
			#$a_locs[$i]['lat'] = $rs['lat'];
		}
		
		$this->mapMarkers($a_locs);
	}
	
	// ----------------------------------------------------------------------------
	
	// Retrieve a postcode from the DB
	function retrievePostcode($id=0){
		if( !$id || !is_numeric($id) ){return false;}
		$res = query("SELECT * FROM `postcodes` WHERE `id` = $id LIMIT 1");
		if(!num_rows($res)){return false;}
		$rs = fetch_assoc($res);
		while(list($k, $v)=each($rs)){
			$this->$k = $v;
		}
	}
	
	// ----------------------------------------------------------------------------
	
	// Add or update a postcode in the DB
	function updatePostcode(){
		while(list($k, $v)=each($_POST)){
			$this->$k = cleanInput($v);
			$$k = esc($this->$k);
		}
		
		if(!is_numeric($id)){
			if(!$code){addError("Please enter a code");}
		}
		if(!$area){addError("Please enter an area");}
		
		if(count($GLOBALS['a_errors'])){return false;}
		
		if(is_numeric($id) && $id){
			$sql = "UPDATE `postcodes` SET `area` = '$area', `keywords` = '$keywords' WHERE `id` = $id LIMIT 1";
		}else{
			$sql = "INSERT INTO `postcodes` (`code`, `area`, `keywords`)VALUES('$code', '$area', '$keywords')";
			$insert=1;
		}

		query($sql);
		
		header('Location: postcode_list.php');
		exit();
	}
	
	// ----------------------------------------------------------------------------
	
	// Permanently delete a postcode from the DB
	function deletePostcode($id){
		if(!$_SESSION['cms_user_id']){exit('Please log in as an administrator');}
		if(!is_numeric($id)){return false;}
		$code = result("SELECT `code` FROM `postcodes` WHERE `id` = $id");
		query("DELETE FROM `agent_postcodes` WHERE `code` = '".esc($code)."'");
		query("DELETE FROM `seller_postcodes` WHERE `code` = '".esc($code)."'");
		query("DELETE FROM `buyer_postcodes` WHERE `code` = '".esc($code)."'");
		query("DELETE FROM `postcodes` WHERE `id` = $id LIMIT 1");
		header('Location: postcode_list.php?terms='.$_GET['terms']);
		exit();
	}
	
	
	// ----------------------------------------------------------------------------
	
	// Checks the user has input a valid postcode
	function postcodeExists($postcode){
		if(!$postcode){return false;}
		$array = $this->getCoordinatesArray($postcode);
		if(is_array($array['address_components'])){
			return 1;
		}else{
			return 0;
		}
		
	}
	
	// ----------------------------------------------------------------------------
	


	// List of selected postcodes for the selector
	function myCodeSelectorList($res){
		while($rs = fetch_assoc($res)){
			echo '<li id="pc_'.$rs['code'].'">';
	
			$status = codeStatus($rs['date_from'], $rs['date_to']);
			
			echo '<div class="code'.$status.'">'.html($rs['area'].' &nbsp;&nbsp;['.$rs['code'].']');
			if($_SESSION['agent_id']){
				echo '<br/>Status:'.$status;
			}
			echo '</div>';
			if($status != 'Active' || is_numeric($_SESSION['cust_id'])){
				echo '<div onclick="removeCode(\''.$rs['code'].'\')" class="remove"><img src="images/remove.gif" alt="Add"/></div>';
			}
			echo '</li>';
		}
	}
	
	// ----------------------------------------------------------------------------
	
	// List of available postcodes for the selector
	function availableCodeSelectorList($custtype='buyer', $clause=''){
		
		if(!$_SESSION['cust_id'] && !$_SESSION['agent_id']){
			$is_signup = true;
		}
		
		if($is_signup){
			$a_codes = $_SESSION['a_customer_postcodes'];
		}else{
			
			if($_SESSION['agent_id']){
				$sql = "SELECT code FROM `agent_postcodes` WHERE `agent_id` = ".$_SESSION['agent_id']." ORDER BY `code`";
			}else{
				$sql = "SELECT code FROM `postcodes` WHERE `code` IN (SELECT `code` FROM `".$custtype."_postcodes` WHERE `cust_id` = ".$_SESSION['cust_id'].") ORDER BY `area`";
			}
			$a_codes = array();
			$res2 = query($sql);
			while($rs = fetch_assoc($res2)){
				array_push($a_codes, $rs['code']);
			}
		}

		
		$res = query("SELECT * FROM `postcodes` WHERE 1 $clause ORDER BY `code`");
		while($rs = fetch_assoc($res)){
			$rs['area'] = str_replace('London - ', '', $rs['area']);
			
			if( in_array($rs['code'], $a_codes) ){
				$class = 'codeSelected';
			}else{
				$class = '';
			}
			
			echo '<li id="pc_'.$rs['code'].'" class="'.$class.'">';
			echo '<div>['.$rs['code'].'] &nbsp;&nbsp;'.html($rs['area'].'').'</div>';
			if($class){
				echo '<div class="add" onclick="removeCode(\''.$rs['code'].'\');"><img src="images/remove.gif" alt="Remove"/></div>';
			}else{
				echo '<div class="add" onclick="addCode(\''.$rs['code'].'\');"><img src="images/add.gif" alt="Add"/></div>';
			}
			echo '</li>';
		}
	}
	
	// ----------------------------------------------------------------------------
	
}// ends class
?>