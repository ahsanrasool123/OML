<?php // Off Market 2016
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
session_start();

if($_SESSION['agent_id']){$_SESSION['current_dash'] = 'agent';}

// Redirect - loading agent profile
if($_GET['action'] == 'loadAgentProfile'){
	$_SESSION['load_agent_id'] = $_GET['agent_id'];
	header('Location: agent-profile/');
	exit();
}
// Redirect - loading buyer profile
if($_GET['action'] == 'loadBuyerProfile'){
	$_SESSION['load_cust_id'] = $_GET['cust_id'];
	header('Location: buyer-profile/');
	exit();
}
// Redirect - loading seller profile
if($_GET['action'] == 'loadSellerProfile'){
	$_SESSION['load_cust_id'] = $_GET['cust_id'];
	header('Location: seller-profile/');
	exit();
}
// Redirect - loading dashboad message in main message screen
if($_GET['action'] == 'openMsg'){
	$_SESSION['load_msg_id'] = $_GET['msg_id'];
	header('Location: '.$_SESSION['current_dash'].'/messages/');
	exit();
}
// Redirect - customer viewing agent proposals
if($_GET['action'] == 'viewProposals'){
	$_SESSION['load_proposals_agent_id'] = $_GET['agent_id'];
	header('Location: '.$_SESSION['current_dash'].'/messages/');
	exit();
}




// Include Stripe
require_once($_SERVER['DOCUMENT_ROOT'].'/stripe-php-3.9.0/init.php');

// Redirect to correct host
if($_SERVER['HTTP_HOST'] != 'offmarketuk.com'){
	header('Location: http://offmarketuk.com');
	exit();
}

// Offline DEV mode
if($_SERVER['REMOTE_ADDR'] != '82.37.145.171'){
	if($_GET['unlock'] == 'offmark'){
		$_SESSION['access2'] = 1;
		header('Location: http://'.$_SERVER['HTTP_HOST']);
		exit();	
	}
	if(!$_SESSION['access2']){
		if(substr($_SERVER['REQUEST_URI'], 0, 5) != '/cms/'){
			if(substr($_SERVER['REQUEST_URI'], 0, 5) != '/inc/'){
				if(substr($_SERVER['REQUEST_URI'], 0, 6) != '/cron/'){
					readfile('inc/holding.html');
					exit();
				}
			}
		}
	}
}

// MySQL DB
$db_host = '127.0.0.1';
$db_user = 'offmarke_user'; 
$db_auth = 'fhc37^325s5%dBeF2n';
$db_name = 'offmarke_data';

// System varsiables
$GLOBALS['a_errors'] = array();
$GLOBALS['system_name'] = 'Off Market London';
$GLOBALS['system_url'] = 'http://offmarketuk.com';
$GLOBALS['noreply_email'] = 'noreply@offmarketglobal.com';
$GLOBALS['admin_email'] = 'test@meerkats.co.uk';
$GLOBALS['logo'] = 'http://'.$_SERVER['HTTP_HOST'].'/images/logo-top.png';
$GLOBALS['currency'] = 'gbp';
$GLOBALS['price_per_code'] = 250;
$GLOBALS['price_per_year'] = 3000;
$GLOBALS['trial_period_duration'] = 1; // In months

$GLOBALS['url_facebook'] = 'http://www.facebook.com';
$GLOBALS['url_twitter'] = 'http://www.twitter.com';
$GLOBALS['url_linkedin'] = 'http://www.linkedin.com';
$GLOBALS['url_instagram'] = 'http://www.instagram.com';

// Create an array session for customer signups
if(!is_array($_SESSION['a_customer_postcodes'])){
	$_SESSION['a_customer_postcodes'] = array();
}

$a_outside_space = array('Please select...', 'Shared', 'Private', 'Garden', 'Balcony');
$a_parking = array('Please select...', 'On street', 'Off street', 'Garage', 'Drive', 'Car port');
$a_real_estate = array('Please select...', 'Chain free', 'Chain');
$a_property_age = array('Please select...', 'New build', 'Old build');
$a_modern_period = array('Please select...', 'Modern', 'Period');

$a_property_types = array(
	'detached' => 'Detached house',
	'semi' => 'Semi-detached house',
	'terraced' => 'Terraced house',
	'flat' => 'Flat/Apartment',
	'cottage' => 'Cottage',
	'bungalow' => 'Bungalow',
	'penthouse' => 'Penthouse',
	'commercial' => 'Commercial',
	'other' => 'Other'
);

$a_bedrooms = array(0=>'Please select...', 1, 2, 3, 4, '5+');
$a_bathrooms = array(0=>'Please select...', 1, 2, 3, 4, '5+');
$a_floors = array(0=>'Please select...', 1, 2, 3, 4, '5+');

$a_sq_foot = array('Please select...', 'Dont know', '0-500 sq ft', '500-1000 sq ft', '1000-1500 sq ft', '1500-2000 sq ft', '2000-2500 sq ft', '2500-3000 sq ft', '3000+ sq ft');

$a_situation = array('I\'m unsure of the value of my property and would like to test the market', 'I will sell depending on the valuation I receive', 'I have found a property and need to sell', 'I am looking to sell my home discreetley');

$a_tenure = array('Please select...', 'Freehold', 'Leasehold', 'Share of freehold', 'Shared ownership/', 'Dont know');

$a_prop_values = array('Please select...', '0-100k', '100k-149k', '150k-199k', '200-249k', '250k-299k', '300k-349k', '350k-399k', '400k-449k', '450k-499k', '500k-549k', '550k-599k', '600k-649k', '650k-699k', '700k-799k', '800-899k', '900k-1M', '1M-1.5M', '1.5M-2M', '2M+', 'I don\'t know');

$a_looking_to_move = array('Please select...', 'Within 3 months', '3-6 months', 'Within a year', 'Will move when I find the right property');

$a_must_haves = array('Outside space', 'Parking', 'No chain');

$a_price_min = array(0 => 'No min',
50000 => '50,000',
60000 => '60,000',
70000 => '70,000',
90000 => '80,000',
90000 => '90,000',
100000 => '100,000',
110000 => '110,000',
120000 => '120,000',
130000 => '130,000',
140000 => '140,000',
150000 => '150,000',
160000 => '160,000',
170000 => '170,000',
180000 => '180,000',
190000 => '190,000',
200000 => '200,000',
210000 => '210,000',
220000 => '220,000',
230000 => '230,000',
240000 => '240,000',
250000 => '250,000',
260000 => '260,000',
270000 => '270,000',
280000 => '280,000',
290000 => '290,000',
300000 => '300,000',
325000 => '325,000',
350000 => '350,000',
375000 => '375,000',
400000 => '400,000',
425000 => '425,000',
450000 => '450,000',
475000 => '475,000',
500000 => '500,000',
550000 => '550,000',
600000 => '600,000',
650000 => '650,000',
700000 => '700,000',
800000 => '800,000',
900000 => '900,000',
1000000 => '1,000,000',
1250000 => '1,250,000',
1500000 => '1,500,000',
1750000 => '1,750,000',
2000000 => '2,000,000',
2500000 => '2,500,000',
3000000 => '3,000,000',
4000000 => '4,000,000',
5000000 => '5,000,000',
6000000 => '6,000,000',
7500000 => '7,500,000',
10000000 => '10,000,000',
15000000 => '15,000,000',
20000000 => '20,000,000',
'No min' => 'No min');

$a_price_max = array(0 => 'No max',
50000 => '50,000',
60000 => '60,000',
70000 => '70,000',
90000 => '80,000',
90000 => '90,000',
100000 => '100,000',
110000 => '110,000',
120000 => '120,000',
130000 => '130,000',
140000 => '140,000',
150000 => '150,000',
160000 => '160,000',
170000 => '170,000',
180000 => '180,000',
190000 => '190,000',
200000 => '200,000',
210000 => '210,000',
220000 => '220,000',
230000 => '230,000',
240000 => '240,000',
250000 => '250,000',
260000 => '260,000',
270000 => '270,000',
280000 => '280,000',
290000 => '290,000',
300000 => '300,000',
325000 => '325,000',
350000 => '350,000',
375000 => '375,000',
400000 => '400,000',
425000 => '425,000',
450000 => '450,000',
475000 => '475,000',
500000 => '500,000',
550000 => '550,000',
600000 => '600,000',
650000 => '650,000',
700000 => '700,000',
800000 => '800,000',
900000 => '900,000',
1000000 => '1,000,000',
1250000 => '1,250,000',
1500000 => '1,500,000',
1750000 => '1,750,000',
2000000 => '2,000,000',
2500000 => '2,500,000',
3000000 => '3,000,000',
4000000 => '4,000,000',
5000000 => '5,000,000',
6000000 => '6,000,000',
7500000 => '7,500,000',
10000000 => '10,000,000',
15000000 => '15,000,000',
20000000 => '20,000,000',
'No max' => 'No max');

$GLOBALS['a_price_range'] = array(
	0 => 'Please select...',
	1 => '100k - 200k',
	2 => '200k - 300k',
	3 => '300k - 400k',
	4 => '500k - 600k',
	5 => '600k - 1M',
	6 => '1M-2M',
	7 => '2M+'
);




// Google Maps API
// This needs domain verification via a TXT record in the server DNS config
#$GLOBALS['google_api_browser_key'] = 'AIzaSyC3bMn-HPtpKFIhxrjejWpoFyEZB0UDEpw'; // Meerkats API key (only for dev work)
#$GLOBALS['google_api_server_key'] = 'AIzaSyBNcdYf0SA-tQoSn-LomtrKRxsqdF4i8pw'; 

$GLOBALS['google_api_browser_key'] = 'AIzaSyAt6F2VI6K_yAqgJHVBCnP92gOpz4_yiXU'; // OMG API key
$GLOBALS['google_api_server_key'] = 'AIzaSyC4UqqFMaUpaxX_IVnNGKnKzU1Y9HnK--Y'; 
$GLOBALS['google_analytics'] = "<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
  ga('create', 'UA-71678319-1', 'auto');
  ga('send', 'pageview');
</script>";


// Stripe payments
// test keys
$GLOBALS['secret_key'] = 'sk_test_xshjJphYtAMnLCM1ijqvGJmZ';
$GLOBALS['publishable_key'] = 'pk_test_y3oGKGl5sRDOymhJy61rnVp7';

// Live keys
#$GLOBALS['secret_key'] = 'sk_live_qL2OTsDaiXBYCVdCp0es2iKL';
#$GLOBALS['publishable_key'] = 'pk_live_XunncotXkbfKWeNEhtmHZbgw';


// Which dashboard is current?
if(is_numeric($_SESSION['cust_id'])){
	if($_SERVER['REQUEST_URI'] == '/buyer/dashboard/'){$_SESSION['current_dash'] = 'buyer';}
	if($_SERVER['REQUEST_URI'] == '/seller/dashboard/'){$_SESSION['current_dash'] = 'seller';}
	if($_SERVER['REQUEST_URI'] == '/buyer/dashboard/old/'){$_SESSION['current_dash'] = 'buyer';}
	if($_SERVER['REQUEST_URI'] == '/seller/dashboard/old/'){$_SESSION['current_dash'] = 'seller';}
}


// Include file libraries
include_once('library/inc.connection.php');
include_once('library/inc.functions.php');
include_once('library/class.map.php');
include_once('library/class.customer.php');
include_once('library/class.agent2.php');
include_once('library/class.property.php');
include_once('library/class.htmlpage.php');
include_once('library/class.messenger.php');
include_once('library/class.email.php');
include_once('library/class.payment.php');
include_once('library/class.logging.php');
include_once('library/class.settings.php');
include_once('library/class.helper.php');


// Users cannot be logged in as both user types
if($_SESSION['agent_id'] && $_SESSION['cust_id']){
	logout();
}

// Log out
if($_GET['logout']){logout();}

// Log in
if($_POST['action'] == 'login'){login();}


// Objects
$map 	= new map;
$agent 	= new agent;
$cust 	= new customer;
$prop 	= new property;
$page 	= new htmlpage;
$email 	= new email;
$pay 	= new payment;
$log 	= new logging;
$settings = new settings;


// Customer deleting profile
if($_POST['action'] == 'customerDelete'){
	$cust2 = new customer;
	$cust2->customerDelete();
}

// Redirect logged in agents from the index page
if($_SESSION['agent_id'] && $_SERVER['REQUEST_URI'] == '/'){
	header('Location: /agent/dashboard/');
	exit();
}
// Redirect logged in customers from the index page
if($_SESSION['cust_id'] && $_SERVER['REQUEST_URI'] == '/'){
	$temp_c = new customer;
	$temp_c->retrieve($_SESSION['cust_id']);
	if($temp_c->seller && $temp_c->buyer){$url = '/switch-profile/';}else if($temp_c->seller){$url = '/seller/dashboard/';}else{$url = '/buyer/dashboard/';}
	header('Location: '.$url);
	exit();
}
?>