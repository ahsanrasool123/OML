<?php
$a_pies_js = array();

function notificationSelection($name, $selected){
	
	echo '<div class="flex notificationSelector">';

	if($selected == 'immediate'){$chk = 'checked=checked';}else{$chk = '';}
	echo '<div><input type="radio" name="'.$name.'" value="immediate" '.$chk.'></div>';
	echo '<div>Immediately</div>';
	
	if($selected == 'daily'){$chk = 'checked=checked';}else{$chk = '';}
	echo '<div><input type="radio" name="'.$name.'" value="daily" '.$chk.'></div>';
	echo '<div>Once a day</div>';
	
	if($selected == 'weekly'){$chk = 'checked=checked';}else{$chk = '';}
	echo '<div><input type="radio" name="'.$name.'" value="weekly" '.$chk.'></div>';
	echo '<div>Weekly</div>';
	
	if($selected == 'never'){$chk = 'checked=checked';}else{$chk = '';}
	echo '<div><input type="radio" name="'.$name.'" value="never" '.$chk.'></div>';
	echo '<div>Never</div>';
	
	echo '</div>';
}

//----------------------------------------------

// Renders a tool tip question mark on the page with option for added class
function question($rel, $class='', $css=''){
	if($class){$class = ' '.$class;}
	if($css){$css = ' css="'.$css.'"';}

	if( is_numeric($rel) ){ // Pull it from the database
		$help = new helper;
		$help->retrieve($rel);
		$rel = '';
		if($help->title){$rel .= '<p><strong>'.html($help->title).'</strong></p>';}else{$rel .= '<p><strong>Help and Information</strong></p>';}
		$rel .= '<p>'.nl2br(html($help->text)).'</p>';
	}
	
	$rel .= '<p>All this help and information can be found in the Off Market Lounge and accessed at any time from the Lounge button at the top right-hand side of the page.</p>';
	
	echo '<div class="question'.$class.'" rel="'.html($rel).'"'.$css.'></div>';
}

//----------------------------------------------

// Generate the agents star count
function stars($c=0){
	echo '<ul class="starlist">'.chr(10);
	for($i=1;$i<6;$i++){
		if($i<=$c){
    		echo '<li></li>'.chr(10);
		}else{
			echo '<li style="opacity:0.2"></li>'.chr(10);
		}
	}
    echo '</ul>';
}

//----------------------------------------------

// Generate the agents star count
function rateMe($name){
	if($_POST[$name]){
		echo '<script>
		$(document).ready(function(){
			rating(\''.$name.'\', \''.$_POST[$name].'\')
		})
		</script>';	
	}
	echo '<ul class="ratelist" id="ratelist_'.$name.'" style="display:inline-block">'.chr(10);
	for($i=1;$i<6;$i++){
    	echo '<li onclick="rating(\''.$name.'\', '.$i.')"></li>'.chr(10);
	}
    echo '</ul>';
	echo '<input type="hidden" name="'.$name.'" id="'.$name.'" value="'.$_POST[$name].'">';
}

//----------------------------------------------

function closeAccount(){
	
	// Agents
	if($_SESSION['agent_id']){
		$a = new agent;
		$a->retrieve($_SESSION['agent_id']);
		if($a->contract_expires > time()){
			echo 'Your current contract expires on '.shortdate($a->contract_expires).'. You cannot cancel your account before this date';
			return;
		}
		$sql = "UPDATE `agents` SET `auto_renew` = 0, `online` = 0 WHERE `agent_id` = '".esc($_SESSION['agent_id'])."' LIMIT 1";
		query($sql);
		
		// Log it
		$log = new logging;
		$log->logMe('Account closed (set to offline)');
	}
	
	// Customers
	if($_SESSION['cust_id']){
		$sql = "UPDATE `customers` SET `online` = 0 WHERE `cust_id` = '".esc($_SESSION['cust_id'])."'";
		query($sql);
		
		// Log it
		$log = new logging;
		$log->logMe('Account closed (set to offline)');
	}
	
	// Log them out
	$a_exclusions = array('access', 'admin', 'cms_user_id', 'cms_username', 'cms_security_level', 'cms_authorised');
	while(list($k, $v) = each($_SESSION)){
		if(!in_array($k, $a_exclusions)){
			unset($_SESSION[$k]);
		}
	}
	
	
}

//----------------------------------------------

// Check if an email address exists as a customer OR agent
function userExists($email=''){
	$exists1 = result("SELECT COUNT(`agent_id`) FROM `agents` WHERE `email` = '".esc($email)."'");
	$exists2 = result("SELECT COUNT(`cust_id`) FROM `customers` WHERE `email` = '".esc($email)."'");
	$tot = $exists1+$exists2;
	if(!$tot){$tot=0;}
	return $tot;
}


//----------------------------------------------

// Check if an email address exists as a customer OR agent
function userExistsAgent($email=''){
	$exists1 = result("SELECT COUNT(`agent_id`) FROM `agents` WHERE `email` = '".esc($email)."' AND `online` = 1");
	$exists2 = result("SELECT COUNT(`cust_id`) FROM `customers` WHERE `email` = '".esc($email)."'");
	$tot = $exists1+$exists2;
	if(!$tot){$tot=0;}
	return $tot;
}


//----------------------------------------------

function updatePassword(){
	$pw = cleanInput($_POST['pw']);
	$pwc = cleanInput($_POST['pwc']);
	$aid = cleanInput($_POST['aid']);
	$cid = cleanInput($_POST['cid']);
	if(!$pw){addError("Please choose a password");}
	if(!$pwc){addError("Please confirm your password");}
	if(count($GLOBALS['a_errors'])){return false;}
	if($pw != $pwc){
		addError("Your password and password confirmation do not match");
		return false;
	}
	if($pw && (strlen($pw) > 30 || strlen($pw) < 8)){addError("Your password should be between 8 and 30 characters in length"); return;}
	
	if($aid){
		$a = explode('*', $_GET['aid']);
		if(count($a) != 2){getLost();}
		$agent_id = $a[0];
		$salt = $a[1];
		$exists = result("SELECT `agent_id` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."' AND `salt` = '".esc($salt)."'");
		if(!$exists){addError("Sorry we couldn't find your agent account");}
		$sql = "UPDATE `agents` SET `pw` = '".esc(generateHash($salt, $pw))."' WHERE `agent_id` = $agent_id LIMIT 1";
		query($sql);
		
		// Log it
		$log = new logging;
		$log->agent_id = $agent_id;
		$log->logMe('Password was reset');
		
		header('Location: /password-reset-confirm/');
		exit();
		return true;
	}
	
	if($cid){
		$a = explode('*', $_GET['cid']);
		if(count($a) != 2){getLost();}
		$cust_id = $a[0];
		$salt = $a[1];
		$exists = result("SELECT `cust_id` FROM `customers` WHERE `cust_id` = '".esc($cust_id)."' AND `salt` = '".esc($salt)."'");
		if(!$exists){addError("Sorry we couldn't find your customer account");}
		$sql = "UPDATE `customers` SET `pw` = '".esc(generateHash($salt, $pw))."' WHERE `cust_id` = $cust_id LIMIT 1";
		query($sql);
		
		// Log it
		$log = new logging;
		$log->cust_id = $cust_id;
		$log->logMe('Password was reset');
		
		header('Location: /password-reset-confirm/');
		exit();
		return true;
	}
	
	addError("The password could not be updated at this time");
	return;
}

//----------------------------------------------

// Convert a float to pounds sterling
function currency($price, $currency='&pound;'){
	$price = round($price, 2);
	$price = $currency.number_format($price, 0, '.', ',');
	return $price;
}

//----------------------------------------------

// URL failure or break
function getLost($in){
	include_once('inc.header.php');
	echo '<div class="pad center">';
	echo '<h1>The link failed</h1>';
	echo '<p>Please check the link did not become broken.</p>';
	echo '</div>';
	include_once('inc.footer.php');
	exit();
}

//----------------------------------------------

// Log in
function login(){
	
	$email = cleanInput($_POST['email']);
	$pw = cleanInput($_POST['pw']);
	
	// check the agents table
	$salt = result("SELECT `salt` FROM `agents` WHERE `email` = '".esc($email)."'");
	$hash = generateHash($salt, $pw);
	$agent_id = result("SELECT `agent_id` FROM `agents` WHERE `email` = '".esc($email)."' AND `pw` = '".esc($hash)."'");
	$parent_id = result("SELECT `parent_id` FROM `agents` WHERE `email` = '".esc($email)."' AND `pw` = '".esc($hash)."'");
	$online = result("SELECT `online` FROM `agents` WHERE `email` = '".esc($email)."' AND `pw` = '".esc($hash)."'");
	
	if($agent_id){
		if(!$online){
			addError("Sorry, your agent account has been closed. To reopen it please contact customer services.");
			return false;
		}
		$_SESSION['agent_id'] = $agent_id;
		$_SESSION['parent_id'] = $parent_id;
		$_SESSION['signed_in'] = 'agent';
		
		// Log it
		$log = new logging;
		$log->logMe('Agent logged in');
		
		query("UPDATE `agents` SET `last_login` = ".time()." WHERE `agent_id` = $agent_id");
		
		header('Location: http://'.$_SERVER['HTTP_HOST'].'/agent/dashboard/');
		exit();
	}
	
	// check the customers table next
	$salt = result("SELECT `salt` FROM `customers` WHERE `email` = '".esc($email)."'");
	$hash = generateHash($salt, $pw);
	$cust_id = result("SELECT `cust_id` FROM `customers` WHERE `email` = '".esc($email)."' AND `pw` = '".esc($hash)."'");
	$online = result("SELECT `online` FROM `customers` WHERE `email` = '".esc($email)."' AND `pw` = '".esc($hash)."'");
	$seller = result("SELECT `seller` FROM `customers` WHERE `email` = '".esc($email)."' AND `pw` = '".esc($hash)."'");
	$buyer = result("SELECT `buyer` FROM `customers` WHERE `email` = '".esc($email)."' AND `pw` = '".esc($hash)."'");
	
	if($cust_id){
		if(!$online){
			addError("Sorry, your account has been closed. To reopen it please contact customer services.");
			return false;
		}
		$_SESSION['cust_id'] = $cust_id;
		$_SESSION['buyer'] = $buyer;
		$_SESSION['seller'] = $seller;
		$_SESSION['signed_in'] = 'customer';
		
		// Which dashboard do they see?
		if($seller){
			$_SESSION['current_dash'] = 'seller';
		}else if($buyer){
			$_SESSION['current_dash'] = 'buyer';
		}
		
		query("UPDATE `customers` SET `last_login` = ".time()." WHERE `cust_id` = $cust_id LIMIT 1");
		
		// Log it
		$log = new logging;
		$log->logMe('Customer logged in');
		
		if($buyer && $seller){
			header('Location: http://'.$_SERVER['HTTP_HOST'].'/choose-profile/');
		}else if($seller){
			header('Location: http://'.$_SERVER['HTTP_HOST'].'/seller/dashboard/');
		}else{
			header('Location: http://'.$_SERVER['HTTP_HOST'].'/buyer/dashboard/');
		}
		exit();
	}
	addError("Sorry, we couldn't find an account for `".html($email)."`");
	return false;
	
}

//----------------------------------------------

function addrFormat($in){
	return str_replace(',', '<br/>', $in);
}

//----------------------------------------------

// Logs a user out of the whole system
function logout(){
	$a_exclusions = array('access', 'access2', 'admin', 'cms_user_id', 'cms_username', 'cms_security_level', 'cms_authorised');
	
	// Log it
	$log = new logging;
	$log->logMe('Logged out');
	
	while(list($k, $v) = each($_SESSION)){
		if(!in_array($k, $a_exclusions)){
			unset($_SESSION[$k]);
		}
	}
	header('Location: /logout/');
	exit();
}

//----------------------------------------------





//----------------------------------------------

// returns the status of a postcode on a client account
function codeStatus($date_from, $date_to){
	
	// Customer postcodes are always active
	if($_SESSION['cust_id']){return 'Active';}
	
	// Agent postcodes have varying statuses
	if($date_from == 0){
		return 'Pending payment';
	}
	if(time() >= $date_from && time() <= $date_to){
		return 'Active';
	}
	if(time() > $date_to){
		return 'Expired';
	}
	return 'Unknown';
}

//----------------------------------------------

// Generate the progress bar on the signup screens
function progressBar($stage=2, $stages=4){
	$stage_w = round(320/($stages-1))-5;
	$left = ($stage_w * $stage)-$stage_w;
	$left = $left-5;
	echo '<div id="progressBar">'.chr(10);
	echo '<p>Stage '.$stage.' of '.$stages.'</p>';
	echo '<ul>';
	for($i=1; $i<=$stages; $i++){
		#if($i>1){$link = 'javascript:window.history.back()';}else{$link = 'javascript:void(0)';}
		$l = ($i-1)*$stage_w.'px';
		echo '<li style="left:'.$l.'"><a href="">'.$i.'</a></li>';
	}
	echo '</ul>';
	echo '<div id="progressIndicator" style="left:'.$left.'px"></div>'.chr(10);
	echo '</div>'.chr(10);
}

//----------------------------------------------

// Send variables as $_POST - supply a name/value paired array
function postData($fields, $url){
	if(!is_array($fields)){echo 'postData() expects an array'; return false;}
	foreach($fields as $key=>$value) { $fields_string .= $key.'='.urlencode($value).'&'; }
	rtrim($fields_string, '&');
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_POST, count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

//----------------------------------------------

// Resize an image to thumbnail size..
function createThumb($image, $destfilename, $longest_side, $squared=0){

	global $a_errors;
	
	if(!file_exists($image)){
		addError("The source image does not exist in createThumb()");
		return FALSE;
	}
		
	if( strrpos($image, '/') ){
		$filename = substr($image, strrpos($image, '/')+1, strlen($image) );
	}else{
		$filename = $image;
	}
	
	$ext = strtolower(substr($image, strrpos($image, '.')+1, strlen($image)));

	if($ext == 'jpg'){
		if(!$source =  imagecreatefromjpeg($image)){
			addError("Error: imagecreatefromjpeg() could not open the file: $path");
		}
	}elseif($ext == 'png'){
		if(!$source =  imagecreatefrompng($image)){
			addError("Error: imagecreatefrompng() could not open the file: $path");
		}
	}elseif($ext == 'gif'){
		if(!$source =  imagecreatefromgif($image)){
			addError("Error: imagecreatefromgif() could not open the file: $path");
		}
	}
		
	if(!$source){
		addError("Sorry the image format isn't recognised, please use JPEG, PNG or GIF format images");
	}
		
	if(count($a_errors)){
		return false;
	}

	$width = round(imagesx($source));
	$height = round(imagesy($source));

	if($width>=$height){
		$scale=$longest_side/$width;
		$dest_w=$longest_side;
		$dest_h=round($height*$scale);
	}else{
		$scale=$longest_side/$height;
		$dest_h=$longest_side;
		$dest_w=round($width*$scale);
	}
	$scale = round($scale*100);
	$scale = $scale/100;

	if($squared){
		$dest_w = $dest_h = $longest_side;
	}
	
	$dest =  imagecreatetruecolor($dest_w, $dest_h);
	$black = imagecolorallocate($dest, 255, 255, 255);

	imagefill ( $dest, 0, 0, $black);
	
	imagecopyresampled($dest, $source, 0, 0, 0, 0, $dest_w, $dest_h, $width, $height);

	if( !imagejpeg($dest, $destfilename, 100) ){
		echo "Error: createThumb() could not write to: $destfilename<br>";
	}
	return true;
}

//----------------------------------------------

function showErrors(){
	if( count($GLOBALS['a_errors']) ){
		foreach($GLOBALS['a_errors'] as $error){
			echo '<div>'.html($error).'</div>';
		}
	}
}

//----------------------------------------------

function menuAgent(){
	if($_SESSION['agent_id']){
		$sql = "SELECT `slug`, `title`, `menu_title` FROM `pages` WHERE `menu_agent` = 1 AND (`logged_in` = 'Yes' OR `logged_in` = '') ORDER BY `order`";
	}else{
		$sql = "SELECT `slug`, `title`, `menu_title` FROM `pages` WHERE `menu_agent` = 1 AND (`logged_in` = 'No' OR `logged_in` = '') ORDER BY `order`";
	}
	$res = query($sql);
	if(!num_rows($res)){return;}
	echo '<ul class="menu" id="menuAgent">'.chr(10);
	while( $rs = fetch_assoc($res) ){
		$legend = $rs['title'];
		if($rs['menu_title']){$legend = $rs['menu_title'];}
		echo '<li><a href="http://'.$_SERVER['HTTP_HOST'].'/'.$rs['slug'].'">'.html($legend).'</a></li>'.chr(10);
	}
	echo '</ul>'.chr(10);
}

//----------------------------------------------

function menuBuyer(){
	
	if($_SESSION['cust_id']){
		$sql = "SELECT `slug`, `title`, `menu_title` FROM `pages` WHERE `menu_buyer` = 1 AND (`logged_in` = 'Yes' oR `logged_in` = '') ORDER BY `order`";
	}else{
		$sql = "SELECT `slug`, `title`, `menu_title` FROM `pages` WHERE `menu_buyer` = 1 AND (`logged_in` = 'No' OR `logged_in` = '') ORDER BY `order`";
	}
	
	$res = query($sql);
	if(!num_rows($res)){return;}
	echo '<ul class="menu" id="menuBuyer">'.chr(10);
	while( $rs = fetch_assoc($res) ){
		$legend = $rs['title'];
		if($rs['menu_title']){$legend = $rs['menu_title'];}
		echo '<li><a href="http://'.$_SERVER['HTTP_HOST'].'/'.$rs['slug'].'">'.html($legend).'</a></li>'.chr(10);
	}
	echo '</ul>'.chr(10);
}

//----------------------------------------------

// Used in the seller signup page
function nextButton($id, $legend='NEXT'){
	echo '<div class="backNextBox">';
	echo '<div><input type="button" value="BACK" onclick="jump(\''.($id-2).'\')"></div>';
	echo '<div><input type="button" value="'.$legend.'" onclick="next(\''.$id.'\')" class="greenbutton nextbutton"></div>';
	echo '</div>';
}

//----------------------------------------------


function menuSeller(){
	if($_SESSION['cust_id']){
		$sql = "SELECT `slug`, `title`, `menu_title` FROM `pages` WHERE `menu_seller` = 1 AND (`logged_in` = 'Yes' OR `logged_in` = '') ORDER BY `order`";
	}else{
		$sql = "SELECT `slug`, `title`, `menu_title` FROM `pages` WHERE `menu_seller` = 1 AND (`logged_in` = 'No' OR `logged_in` = '') ORDER BY `order`";
	}
	$res = query($sql);
	if(!num_rows($res)){return;}
	echo '<ul class="menu" id="menuSeller">'.chr(10);
	while( $rs = fetch_assoc($res) ){
		$legend = $rs['title'];
		if($rs['menu_title']){$legend = $rs['menu_title'];}
		echo '<li><a href="http://'.$_SERVER['HTTP_HOST'].'/'.$rs['slug'].'">'.html($legend).'</a></li>'.chr(10);
	}
	echo '</ul>'.chr(10);
}

//----------------------------------------------

// Cleans the input from form input vars such as GET and POST
function cleanInput($in){
	$op = trim($in);
	$op = stripslashes($op);
	$op = str_replace(':', ';', $op);
	$op = str_replace('Â', '', $op);
	return($op);
}

//----------------------------------------------

function radioButtons($array, $name, $selected, $label=''){
	if(!count($array)){return;}
	echo '<div class="table" style="width:100%">';
	
	while(list($k, $v) = each($array) ){
		echo '<div class="row">';
		if($v == $selected && $selected){$sel = ' checked';}else{$sel = '';}
		
		echo '<label style="padding:0px">';
		echo '<div class="cell" style="width:20px"><input name="'.$name.'" class="'.$name.'" id="'.$name.'" type="radio" value="'.$v.'" '.$sel.'/></div>';
		echo '<div class="cell">'.html($v).'</div>';
		echo '</label>';
		echo '</div>';
	}
	
	echo '</div>';
}

//----------------------------------------------

function radioButtonsHoriz($array, $name, $selected, $label=''){
	if(!count($array)){return;}
	echo '<div>';
	
	while(list($k, $v) = each($array) ){
		if($v == $selected && $selected){$sel = ' checked';}else{$sel = '';}
		echo '<label style="display:inline-block"><input name="'.$name.'" class="'.$name.'" id="'.$name.'" type="radio" value="'.$v.'" '.$sel.'/>&nbsp;'.html($v).'</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	
	echo '</div>';
}

//----------------------------------------------

// Display an array of checkboxes
function checkboxes($a, $name, $a_selected, $legend=''){
	$i=1;
	
	if($legend){
		echo '<label>'.html($legend).'</label>';
	}
	
	while(list($k, $v)=each($a)){
		$fieldname = $name.'_'.$i;

		if(@in_array($v, $a_selected)){$sel = ' checked="checked"';}else{$sel = false;}
		
		if(!$sel){
			if($_POST[$fieldname] == $v){$sel = ' checked="checked"';}else{$sel = false;}
		}
		
		echo '<label>';
		echo '<input type="checkbox" name="'.$fieldname.'" id="'.$fieldname.'" value="'.$v.'" '.$sel.'/>';
		echo '&nbsp;'.html($v);
		echo '</label>'.chr(10).chr(10);
		$i++;
	}
}



//----------------------------------------------

// Render a form input field with type detection
function formField($name, $value= '', $label='', $css=''){
	
	$type = 'text';
	if($name == 'email'){
		$type = 'email';	
	}
	if($name == 'tel'){
		$type = 'tel';	
	}
	
	echo '<div class="table formfield">';
	echo '<div class="row">';
	echo '<div class="cell" style="width:200px; text-align:left">'.html($label).'</div>';

	echo '<div class="cell" style="text-align:left">';
	echo '<input type="'.$type.'" name="'.$name.'" id="'.$name.'" value="'.$value.'"';
	if($css){echo 'style="'.$css.'"';}
	echo '/></div>';
	echo '</div>';
	echo '</div>';
}

//----------------------------------------------

function textarea($name, $value= '', $label='', $rows=6, $css=''){
	echo '<div class="table" id="table_'.$name.'">';
	echo '<div class="row">';
	
	echo '<div class="cell" style="min-width:100px">';
	echo '<label>'.html($label).'</label>';
	echo '</div>';
	
	echo '<div class="cell">';
	echo '<textarea rows="'.$rows.'" name="'.$name.'" id="'.$name.'" style="'.$css.'">'.$value.'</textarea>';
	echo '</div>';
	
	echo '</div>';
	echo '</div>';
}

//----------------------------------------------

function passwordField($name, $value= '', $label='', $css='width:120px'){
	echo '<div class="table">';
	echo '<div class="row">';
	
	echo '<div class="cell" style="width:200px; text-align:left">'.html($label).'</div>';
	echo '<div class="cell" style="text-align:left"><input type="password" name="'.$name.'" id="'.$name.'" value="'.$value.'"';
	if($css){echo 'style="'.$css.'"';}
	echo '/></div>';
	
	echo '</div>';
	echo '</div>';
}

//----------------------------------------------

// Chart declaration
function pie($sent=0, $read=0, $clicked=0, $optouts, $dim=200){
	global $a_pies_js;
	$rand = rand(1,99999);
	echo chr(10).chr(10).'<div id="canvas-holder-'.$rand.'">
	<canvas id="chart-area-'.$rand.'" width="'.$dim.'" height="'.$dim.'"/>
</div>
	<script>
		var pieData'.$rand.' = [
				{
					value: '.$sent.',
					color:"#F7464A",
					highlight: "#FF5A5E",
					label: "Recipients"
				},
				{
					value: '.$read.',
					color: "#65e090",
					highlight: "#8df4b1",
					label: "Opens"
				},
				{
					value: '.$clicked.',
					color: "#FDB45C",
					highlight: "#FFC870",
					label: "Clicks"
				},
				{
					value: '.$optouts.',
					color: "#00abb6",
					highlight: "#49e4ee",
					label: "Opt-outs"
				}

			];
			';
				
				// Store start commands in array for startPies() function
				array_push($a_pies_js, 'var ctx'.$rand.' = document.getElementById("chart-area-'.$rand.'").getContext("2d");
				window.myPie'.$rand.' = new Chart(ctx'.$rand.').PolarArea(pieData'.$rand.', {
					scaleShowLabels: true,
					showTooltips: true
				});');
	echo '
	</script>'.chr(10).chr(10);
	
	echo '<span class="small grey">Recipients: '.$sent.'<br/>Read: '.$read.'<br/>Clicks: '.$clicked.'<br/>Optouts: '.$optouts.'</span>';
}

//----------------------------------------------

function listChart($members, $optouts, $dead, $dim=200){
	global $a_pies_js;
	$rand = rand(1,99999);
	echo chr(10).chr(10).'<div id="canvas-holder-'.$rand.'">
	<canvas id="chart-area-'.$rand.'" width="'.$dim.'" height="'.$dim.'"/>
</div>
	<script>
		var pieData'.$rand.' = [
				{
					value: '.$members.',
					color:"#5a00ff",
					highlight: "#ad81ff",
					label: "Live members"
				},
				{
					value: '.$optouts.',
					color: "#ff8400",
					highlight: "#ffb05c",
					label: "Opt outs"
				},
				{
					value: '.$dead.',
					color: "#6a6a6a",
					highlight: "#bcbcbc",
					label: "Dead"
				}

			];
			';
				
				// Store start commands in array for startPies() function
				array_push($a_pies_js, 'var ctx'.$rand.' = document.getElementById("chart-area-'.$rand.'").getContext("2d");
				window.myPie'.$rand.' = new Chart(ctx'.$rand.').PolarArea(pieData'.$rand.', {
					scaleShowLabels: true,
					showTooltips: true
				});');
	echo '
	</script>'.chr(10).chr(10);
	
	echo '<span class="small grey">Live members: '.$members.'<br/>Dead: '.$dead.'<br/>Optouts: '.$optouts.'</span>';
}

//----------------------------------------------

// Call this after chart declarations to start the charts
function startPies(){
	global $a_pies_js;
	if(!is_array($a_pies_js)){
		echo '<p>There are no charts to display on this page</p>';
		return;
	}
	echo '<script>
			window.onload = function(){
			';
			foreach($a_pies_js as $js){
				echo chr(10).$js.chr(10);
			}
				
	echo '};
	</script>';
	
}

//----------------------------------------------

// Send a basic HTML email
function mail_html($email_to, $from_email, $from_name, $replyto, $subject, $message){
    $header = "From: ".$from_name." <".$from_email.">\r\n";
    $header .= "Reply-To: ".$replyto."\r\n";
    $header .= "MIME-Version: 1.0\r\n";
	$header .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	// Additional headers
	$header .= 'To: '.$email_to.' <'.$email_to.'>' . "\r\n";
	$header .= 'From: '.$from_name.' <'.$from_email.'>' . "\r\n";

    if (@mail($email_to, $subject, $message, $header)) {
		return true;
    } else {
		return false;
    }
}

//----------------------------------------------

// Checks to see if this is an injection attack..
function isAttack($val){
	if(eregi("to:", $val)){return TRUE;}
	if(eregi("from:", $val)){return TRUE;}
	if(eregi("cc:", $val)){return TRUE;}
	if(eregi("bcc:", $val)){return TRUE;}
	if(eregi('Content-Type:', $val)){return TRUE;}
	if(eregi('Subject:', $val)){return TRUE;}
	if(eregi('MIME-Version:', $val)){return TRUE;}
	if(eregi('Content-Transfer-Encoding:', $val)){return TRUE;}
	return FALSE;
}

//----------------------------------------------

// Database query..
function query($sql){
	global $a_alerts, $mysqli;
	if(!$sql){return;}
	$res = $mysqli->query($sql);
	if($mysqli->error){
		print($sql."<hr/>\r\n".$mysqli->error);
	}
	return $res;
}

//----------------------------------------------

// Database result
function result($sql='', $row=0){
	global $mysqli;
	if(!$sql){return;}
	if(is_string($sql)){
		$res = query($sql);
	}else{
		$res = $sql;
	}
	if(!$res || !num_rows($res)){return 0;}
	$res->data_seek($row);
	$row = $res->fetch_row();
	$res->close();
	if(count($row) == 1){
		return $row[0];
	}
	return $row;
}

//----------------------------------------------

function num_rows($res){
	global $mysqli;
	if(!@mysqli_num_rows($res)){return 0;}
	return @mysqli_num_rows($res);
}

//----------------------------------------------

function fetch_assoc($res=0){
	global $mysqli;
	return @mysqli_fetch_assoc($res);
}

//----------------------------------------------

function fetch_array($res){
	global $mysqli;
	return mysqli_fetch_array($res);
}

//----------------------------------------------

function affected_rows($res=object){
	global $mysqli;
	return $mysqli->affected_rows;
}

//----------------------------------------------

function insert_id($res=object){
	global $mysqli;
	return $mysqli->insert_id;
}

//----------------------------------------------

function fetch_row($res){
	global $mysqli;
	return mysqli_fetch_row($res);
}

//----------------------------------------------

function list_tables($res){
	global $mysqli;
	$tableList = array();
	while($cRow = mysqli_fetch_array($res)){
		$tableList[] = $cRow[0];
	}
	return $tableList;
}

//----------------------------------------------

function list_fields($db_name, $table){
	global $mysqli;
	$a_fields = array();
	$res = query("SHOW COLUMNS FROM $table FROM $db_name");
	while($rs = fetch_assoc($res)){
		array_push($a_fields, $rs['Field']); 
	}
 	return $a_fields;
	
}

//----------------------------------------------

function free_result($res){
	global $mysqli;
	return mysqli_free_result($res);
}

//----------------------------------------------

function num_fields($res){
	global $mysqli;
	return $mysqli->field_count($res);
}

//----------------------------------------------

function field_name($fields, $i){
	global $mysqli;
	$finfo = $fields->fetch_field_direct(1);
	return $finfo->name;
}

//----------------------------------------------

function esc($in){
	global $mysqli;
	$in = stripslashes($in);
	return mysqli_real_escape_string ($mysqli, $in);
}

//----------------------------------------------

// Write time from unix input
function timestamp($time){
	if($time){
		return date("H:i", $time);
	}else{
		return '-';
	}
}

//----------------------------------------------

function html($in){
	$in = str_replace('‘', "'", $in);
	$in = str_replace('’', "'", $in);
	$in = str_replace('–', '-', $in);
	$in = str_replace('’', '-', $in);
	$in = str_replace('£', '&pound;', $in);
	$in = str_replace('Â', '', $in);
	
	
	
	$in = mb_convert_encoding($in, 'HTML-ENTITIES', 'UTF-8');
	
	
	return $in;	
}

//----------------------------------------------

/// Replaces the links in the email with the relay page
function replaceLinks($html, $email_id=0, $despatch_id=0){
	global $g_listserver_url;
	global $track_individual_contacts, $contact_id;
	
	if(!$html){return FALSE;}

	$a = explode('href="', $html);
	
	// No links
	if(count($a)<2){
		return $html;
	}
	
	$b = array();
	
	for($i=0; $i<count($a); $i++){
		$cut = substr($a[$i], 0, strpos($a[$i], '"'));
		if(eregi('http://', $cut)){
			($b[$cut] = 1);
		}
	}
	$a = false;
	
	while(list($k, $v) = each($b)){
		$new = $g_listserver_url.'link.php?d='.$despatch_id.'&e='.$email_id;
		
		// If we're tracking individual contact IDs
		if($track_individual_contacts){
			$new .= '&cid='.$contact_id;
		}
		$new .= '&u='.urlencode($k);
		$html = str_replace('href="'.$k.'"', 'href="'.$new.'"', $html);
		$new = $k = false;
	}
	return $html;
}

//----------------------------------------------

function dropdown($array, $name, $selected = FALSE, $label='', $usekeys=false){
	if(!is_array($array)){return '<span style="color:red">No array supplied in dropdown()</span>';}
	if(!$name){return '<span style="color:red">No name given in dropdown()</span>';}
	
	echo '<div class="formfield">';
	
	if($label){
		echo '<label>'.html($label).'</label>';
	}
	
	echo "<select name=\"$name\" id=\"".$name."\">\n";
	
	// Simple array
	if(!$usekeys){
		foreach($array as $item){
			if($item == $selected){$sel = ' selected';}else{$sel = '';}
			echo '<option value="'.html($item).'"$sel>'.html($item).'</option>'."\n";
		}
	}
	
	// Use the array keys as the values of the list
	if($usekeys){
		while(list($k, $item) = each($array)){
			if($item == $k){$sel = ' selected';}else{$sel = '';}
			echo "<option value=\"$k\"$sel>".html($item).'</option>'."\n";
		}
	}
	
	echo "</select>\n";
	
	if($label){
		echo '</div>';
	}
}


//----------------------------------------------

// Select list
function formSelect($array, $name, $selected = FALSE, $label=''){
	if(!is_array($array)){return '<span style="color:red">No array supplied in dropdown()</span>';}
	if(!$name){return '<span style="color:red">No name given in dropdown()</span>';}
	
	if($label){echo '<label>'.html($label).'<br/>';}
	
	echo "<select name=\"$name\" id=\"$name\">\n";

	while(list($k, $v) = each($array)){
		if($k == $selected){$sel = ' selected';}else{$sel = '';}
		echo "<option value=\"$k\"$sel>".html($v).'</option>'."\n";
	}

	echo "</select>\n";
	
	if($label){'</label>';}
	echo '<br/>';
}

//----------------------------------------------

// Select list
function formSelectVals($array, $name, $selected = FALSE, $label=''){

	if($label){echo '<label style="text-align:left; display:inline-block; min-width:190px">'.html($label).'&nbsp;</label>';}
	
	echo "<select name=\"$name\" id=\"$name\">\n";

	while(list($k, $v) = each($array)){
		if($v == $selected){$sel = ' selected';}else{$sel = '';}
		echo "<option value=\"$v\"$sel>".html($v).'</option>'."\n";
	}

	echo "</select>\n";
	
	echo '<br/>';
}
//----------------------------------------------

function dropdownKeys($array, $name, $selected = FALSE, $js='', $flip=false){
	if(!is_array($array)){return '<span style="color:red">No array supplied in dropdown()</span>';}
	if(!$name){return '<span style="color:red">No name given in dropdown()</span>';}
	
	$keys = array_keys($array);
	
	echo "<select name=\"$name\" $js>\n";

	if(!$flip){
	
		while(list($k, $v)=each($array)){
			if($k == $selected){$sel = ' selected';}else{$sel = '';}
			echo "<option value=\"$k\"$sel>".htmlentities($v).'</option>'."\n";
		}
	
	}else{
	
		while(list($k, $v)=each($array)){
			if($item == $selected){$sel = ' selected';}else{$sel = '';}
			echo "<option value=\"$v\"$sel>".htmlentities($k).'</option>'."\n";
		}

	}
	
	echo "</select>\n";
}

//----------------------------------------------

// Generates a random password string
function generatepassword($name_length=8, $name_base=1) {
	$base_a1 = "aeiouy"; 	
	$base_a2 = "bcdfghjklmnpqrstvwxz"; 
	$base_0 = "0123456789"; 
	$retval = ''; 
	mt_srand((double)microtime()*1000000); 
	switch ($name_base) { 
		case 1:{ 
			$sw=false; 
			for ($p=0;$p<$name_length;$p++){ 
				if ($sw) 
					$retval .= substr($base_a1,mt_rand(0,strlen($base_a1)-1),1); 
				else 
					$retval .= substr($base_a2,mt_rand(0,strlen($base_a2)-1),1); 
					$sw=!$sw; 
				} 
				break;} 
	case 2:{ 
		for ($p=0;$p<$name_length;$p++) 
		$retval .= substr($base_0,mt_rand(0,strlen($base_0)-1),1); 
		break;
		} 
	} 
	return $retval; 
}

//----------------------------------------------

// Generates a random salt string for hashing passwords
function makeSalt(){
	$pw = generatepassword();
	$salt = substr(md5($pw), 1, 8);
	return $salt;
}

//----------------------------------------------

// Generates a hash from a salt string and the user's password
function generateHash($salt, $pw){
	return(md5($salt.$pw));
}

//----------------------------------------------

// Generate numbered page links
function pagelinks($page, $pages){

	if($pages<2){return FALSE;}
	$a = array();
	
	// Get querystring values to pass back via the links
	while(list($k, $v)=each($_GET)){
		if($k!='action' && $k!='page'){
			$query_string .= "$k=$v&";
		}
	}	
	
	// Make links
	if($page != 1){//Previous
		array_push($a, '<a href="?page='.($page-1).'&'.$query_string.'">&lt; prev </a>');
	}
	
	if($pages<9){// Page numbers
		for($i=1; $i<=$pages;$i++){
			if($page == $i){
				array_push($a, '<a href="#" style="font-weight:bold">'.$i.'</a>');
			}else{
				array_push($a, '<a href="?page='.$i.'&'.$query_string.'">'.$i.'</a>');
			}
		}
	}else{
		$to = ($page+6);
		if($to > $pages){
			$to = $pages;
		}
		if($page <= 9){$to = 9;}
		$from = $to-10;
		if($from < 1){$from = 1;}
		for($i=$from; $i <= $to;$i++){
			if($page == $i){
				array_push($a, '<a href="#" style="font-weight:bold">'.$i.'</a>');
			}else{
				array_push($a, '<a href="?page='.$i.'&'.$query_string.'">'.$i.'</a>');
			}
		}
		if($i<$pages){
			//array_push($a, ' --> ');
			array_push($a, '<a href="?page='.$pages.'&'.$query_string.'">'.$pages.'</a>');
		}
	}
	
	// Next
	if($page != $pages){
		array_push($a, '<a href="?page='.($page+1).'&'.$query_string.'"> next &gt;</a>');
	}
	echo '<div class="pagelinks"><div align="center" class="small" style="margin-bottom:10px">Showing page '.$page.' of '.$pages.'</div>'.implode('', $a).'</div>';
	
}

//----------------------------------------------

// Returns Yes or No depending on a binary flag setting
function flag($in){
	if($in){
		return 'Yes';
	}else{
		return 'No';
	}
}

//----------------------------------------------

// Cleans and validates an email variable
function clean_email($val){
	$val = str_replace("\r", ' ', $val);
	$val = str_replace("\n", ' ', $val);
	$val = trim($val);
	$pos = strpos($val, ' ');
	if($pos){
		$val = substr($val, 0, $pos);
	}
	$pattern = '^([a-zA-Z0-9_\-])+(\.([a-zA-Z0-9_\-])+)*@((\[(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5])))\.(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5])))\.(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5])))\.(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5]))\]))|((([a-zA-Z0-9])+(([\-])+([a-zA-Z0-9])+)*\.)+([a-zA-Z])+(([\-])+([a-zA-Z0-9])+)*))$';

	if( eregi($pattern, $val) ){
		return esc($val);
	}else{
		return FALSE;
	}
}

//----------------------------------------------

// Cleans data sent from a form to be used by the mail() function. Prevents injection attacks..
function clean_mail($val){
	$val = eregi_replace("to:", ' ', $val);
	$val = eregi_replace("from:", ' ', $val);
	$val = eregi_replace("cc:", ' ', $val);
	$val = eregi_replace("bcc:", ' ', $val);
	$val = eregi_replace('Content-Type:', '', $val);
	$val = eregi_replace('Subject:', ' ', $val);
	$val = eregi_replace('MIME-Version:', ' ', $val);
	$val = eregi_replace('Content-Transfer-Encoding:', ' ', $val);
	return $val;
}

//----------------------------------------------

// Clean variables of SQL..
function clean_sql($val){
	$val=trim($val);
	return esc($val);
}

//----------------------------------------------

// Javascript alert
function alert($msg){
	echo "<script>alert('".addslashes($msg)."')</script>";
}

//----------------------------------------------

// Date functions
function longdate($in){
	return date("l F j Y", $in);
}

//----------------------------------------------

function shortdate($in){
	if($in){
		return date("d.m.y", $in);
	}else{
		return FALSE;
	}
}

//----------------------------------------------

function datetime($unix){
	if($unix){
		return date("d.m.y - H:i", $unix);
	}else{
		return FALSE;
	}
}

//----------------------------------------------

function datetimeSec($unix){
	if($unix){
		return date("d.m.y - H:i:s", $unix);
	}else{
		return FALSE;
	}
}

//----------------------------------------------

function emailValid($email){
	if(filter_var($email, FILTER_VALIDATE_EMAIL) ){
    	return true;
	}
	return false;
}

//----------------------------------------------

// Adds an error to the errors array..
function addError($error){
	if(!is_array($GLOBALS['a_errors'])){
		$GLOBALS['a_errors'] = array();
	}
	if($error){
		array_push($GLOBALS['a_errors'], html($error));
	}
}

//----------------------------------------------

// Show errors as bulleted list
function displayErrors(){
	if(count($GLOBALS['a_errors'])){
		//echo '<p class="red">The form could not be processed:</p>';
		echo '<ul class="errorlist">';
		foreach($GLOBALS['a_errors'] as $error){
			echo '<li>'.htmlentities($error).'</li>';
		}
		echo '</ul>';
	}
}

//----------------------------------------------

// Generates a CSV file from a database query
function csvFromQuery($sql, $attachment_filename='data.csv', $first_row_names=true){
	
	$res = query($sql);
	
	// Write file headers..
	header("Content-type: application/csv");
	header("Content-Disposition: attachment; filename=".$attachment_filename);

	// Loop thru data..
	$i=0;
	while( $rs = fetch_assoc($res) ){


		// Print the column headings as first line of file..
		if(!$i){
			$a_keys = array_keys($rs);
			if($first_row_names){	
				print( implode(', ', $a_keys) );
				print "\r\n";
			}
		}
		
		// Escape the data
		$a_temp=array();
		foreach($a_keys as $key){
			if($rs[$key]){
				$value = esc($rs[$key]);
				$value = str_replace(',', '', $value);
				array_push($a_temp, $value );
			}else{
				array_push($a_temp, ' ');
			}
		}
		
		// Print the data in comma separated lines..
		print(implode(',', $a_temp) );
		print("\r\n");
		$i++;
	}
	exit();
}

//----------------------------------------------

function listMe($array){
	while(list($k, $v) = each($array)){
		if(is_array($v)){
			echo "$k = ";
			print_r($v);
			echo "<br/>";
		}else{
			echo "$k = $v <br/>";
		}
	}
}

//----------------------------------------------

?>