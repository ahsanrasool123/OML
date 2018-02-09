<?
// This is the dynamic page stub for virtual pages
include('config.php');

$uri = $_SERVER['REQUEST_URI'];

if(!$_GET['slug']){
	header("HTTP/1.1 303 See Other");
	header("Location: http://$_SERVER[HTTP_HOST]/");
	exit();
}

// Page content
if($_GET['slug']){
	$page->retrieve($_GET['slug']);
	
}

// Form actions
if($_POST['action'] == 'updateSeller'){
	$cust->updateSellerProfile();
}
if($_POST['action'] == 'updateBuyer'){
	$cust->updateBuyerProfile();
}

include('inc.header.php');

if($page->boxed){
	echo '<div class="whitebox">';
}

// Generate the helper text popup
if($page->helper_text){
	echo '<div class="question whiteboxquestion" rel="'.html($page->helper_text).'"></div>';
}


#$page->h1();

// Page content
$page->content();


// Includes agents

if($uri == '/agent/register/'){include('inc/inc.signup_agent.php');}

if(is_numeric($_SESSION['agent_id'])){// Logged in agents only
	if($uri == '/agent/dashboard/'){include('inc/inc.dashboard_agent.php');}

	if($uri == '/agent/dashboard/old/'){include('inc/inc.dashboard_agent_old.php');}
}



// Logout
if($uri == '/logout/'){
	include('inc/inc.logout.php');
}

// Include from CMS
if($page->include_file){
	include_once('inc/'.$page->include_file);
}

// Includes buyers
if(!$_SESSION['signed_in']){
	if($uri == '/buyer/register/'){include('inc/inc.signup_buyer.php');}
}

// Includes sellers
if(!$_SESSION['signed_in']){
	if($uri == '/seller/register/'){include('inc/inc.signup_seller.php');}
}
if($_SESSION['signed_in']){
	if($uri == '/seller/property/'){include('inc/inc.add_property.php');}
}


if(is_numeric($_SESSION['cust_id'])){// Logged in customers only
	if($uri == '/buyer/dashboard/'){include('inc/inc.dashboard_buyer.php');}
	if($uri == '/seller/dashboard/'){include('inc/inc.dashboard_seller.php');}
	if($uri == '/buyer/dashboard/old/'){include('inc/inc.dashboard_buyer_old.php');}
	if($uri == '/seller/dashboard/old/'){include('inc/inc.dashboard_seller_old.php');}
}

if($uri == '/forgotten-password/'){include('inc/inc.forgotten-password.php');}



// Close white box
if($page->boxed){
	echo '</div>';
}


include('inc.footer.php');
?>