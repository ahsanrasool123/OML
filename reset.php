<?php // Pasword reset
include_once('config.php');


// Do the updated
if($_POST['action'] == 'updatePassword'){updatePassword();}

// Agent resetting a password
if($_GET['aid']){
	$a = explode('*', $_GET['aid']);
	if(count($a) != 2){getLost();}
	$agent_id = $a[0];
	$salt = $a[1];
	$exists = result("SELECT `agent_id` FROM `agents` WHERE `agent_id` = '".esc($agent_id)."' AND `salt` = '".esc($salt)."'");
	if(!$exists){getLost('No agent');}
}

// Customer resetting a password
if($_GET['cid']){
	$a = explode('*', $_GET['cid']);
	if(count($a) != 2){getLost();}
	$cust_id = $a[0];
	$salt = $a[1];
	$sql = "SELECT `cust_id` FROM `customers` WHERE `cust_id` = '".esc($cust_id)."' AND `salt` = '".esc($salt)."'";
	$exists = result($sql);
	if(!$exists){getLost('No customer');}
}

include_once('inc.header.php');


?>
<div class="pad center">
<form name="passwordReset" id="passwordReset" method="post" enctype="multipart/form-data" action="">
<h1>Password Reset</h1>
<p>Please choose a new password</p>
<?
displayErrors();

// Password
passwordField('pw', $_POST['pw'], 'Password');

// Password confirmation
passwordField('pwc', $_POST['pwc'], 'Confirm your new password');

echo '<input type="hidden" name="action" value="updatePassword"/>';
echo '<input type="hidden" name="aid" value="'.$_GET['aid'].'"/>';
echo '<input type="hidden" name="cid" value="'.$_GET['cid'].'"/>';
echo '<input type="submit" name="submit" value="Update my password"/>';
?>

</form>

</div>

<?php

include_once('inc.footer.php');
?>