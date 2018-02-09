<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?=$GLOBALS['system_name'];?></title>
	<base href="http://<?=$_SERVER['HTTP_HOST'];?>/" />
	<link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
	<script src="js/jquery.js"></script>
	<script src="js/scripts.js?cb=<?=date("dmy");?>"></script>
	<link href="css/fonts.css" rel="stylesheet" type="text/css" />
	<link href="css/styles.css?cb=<?=time();?>" rel="stylesheet" type="text/css" />
    <link href="css/helpers.css" rel="stylesheet" type="text/css" />
    
	<link href="css/dashboard.css?cb=<?=time();?>" rel="stylesheet" type="text/css" />
	<script src="js/dashboard.js?cb=<?=date("dmy");?>"></script>
	
    <link href="css/mobile.css?cb=<?=time();?>" rel="stylesheet" type="text/css" />
</head>

<body>

<div id="messageWin"></div>

<div id="wrap">

<header>
	<a href="/" id="logo"><img src="images/offmarketlondon.png" alt="Off Market London"/></a>
    
<form name="searchform" id="searchform" method="get" action="search.php">
    	<input type="text" id="searchterms" name="terms" value="Search"/>
    </form>
    
<ul id="topnav">
     	<? if(!$_SESSION['signed_in']){ ?>
    	
        <li><a href="/sign-in/" class="button">Log in</a></li>
        <li><a href="/agent/home/" class="button greenbutton">Agents</a></li>
        <? }else{ ?>
		
        <li><a href="/lounge/" class="button">Lounge</a></li>
        
        <? if(!$_SESSION['agent_id'] && 1==3){ ?>
        	<? if($_SESSION['buyer']){?>
        	<li><a href="/buyer/dashboard/" class="button">Buyer</a></li>
            <? } 
			if($_SESSION['seller']){ ?>
        	<li><a href="/seller/dashboard/" class="button">Seller</a></li>
            <? } ?>
        <? } ?>   
		<? } ?>
  </ul>  
</header>







<? 
$customer = new customer;
$customer->retrieve($_SESSION['cust_id']);

// Buyer's dashboard menu
if($_SESSION['cust_id'] && $_SESSION['current_dash'] == 'buyer'){?>
<ul id="dashBuyer" class="dashmenu">
  	<li><?=html($customer->name);?></li>
  <li><a href="/buyer/dashboard/">My Dashboard</a></li>
  <li><a href="/buyer/messages/">Mailbox (<span class="msgCounter">0</span>)</a></li>
  <li><a href="/buyer/details/">View/Edit my Details</a></li>
  <li><a href="/buyer/agents/">My OffMarket Agents</a></li>
  <li><a href="javascript:void(0)">Settings</a>
  	<ul class="submenu">
        <li><a href="/notfication-settings/">Notification settings</a></li>
        <? if($_SESSION['buyer'] && !$_SESSION['seller']){?>
        <li><a href="/register-as-seller/">Register as seller</a></li>
        <? } ?>
        <? if($_SESSION['seller'] && $_SESSION['buyer']){?>
        <li><a href="/switch-profile/">Switch profile</a></li>
        <? } ?>
        <li><a href="/delete-profile/">Delete profile</a></li>
      <li><a href="/contact/">Contact OML</a></li>
    </ul>
  </li>
  <li><a href="/?logout=true">Log Out</a></li>
</ul>
<? } ?>

<? 
// Seller's dashboard menu
if($_SESSION['cust_id'] && $_SESSION['current_dash'] == 'seller'){ ?>
<ul id="dashSeller" class="dashmenu">
  	<li><?=html($customer->name);?></li>
  <li><a href="/seller/dashboard/">My Dashboard</a></li>
  <li><a href="/seller/messages/">Mailbox (<span class="msgCounter">0</span>)</a></li>
  <li><a href="/seller/details/">View/Edit my Details</a></li>
  <li><a href="/seller/agents/">My OffMarket Agents</a></li>
  <li><a href="javascript:void(0)">Settings</a>
    <ul class="submenu">
    	<li><a href="/user-guide/">User guide</a></li>
        <li><a href="/notfication-settings/">Notification settings</a></li>
        <? if($_SESSION['seller'] && !$_SESSION['buyer']){?>
        <li><a href="/register-as-buyer/">Register as buyer</a></li>
        <? } ?>
        <? if($_SESSION['seller'] && $_SESSION['buyer']){?>
        <li><a href="/switch-profile/">Switch profile</a></li>
        <? } ?>
        <li><a href="/delete-profile/">Delete profile</a></li>
        <li><a href="/contact/">Contact OML</a></li>
    </ul>
    </li>
  <li><a href="/?logout=true">Log Out</a></li>
</ul>
<? } ?>

<? 
// Agents dashboard menu
if($_SESSION['agent_id']){ 
	$agent = new agent;
	$agent->retrieve($_SESSION['agent_id']);
?>
<ul id="dashAgent" class="dashmenu">
  	<li><?=html($agent->contact_name);?></li>
  <li><a href="/agent/dashboard/">My Dashboard</a></li>
  <li><a href="/agent/messages/">Mailbox (<span class="msgCounter">0</span>)</a></li>
  <li><a href="/agent/my-details/">View/Edit my Details</a></li>
  <li><a href="/agent/vendors/">My OffMarket Vendors</a></li>
  <li><a href="javascript:void(0)">Settings</a>
    <ul class="submenu">
        <li><a href="/notfication-settings/">Notification settings</a></li>
        <li><a href="/subscription-details/">Subscription detais</a></li>
        <li><a href="/contact/">Contact OML</a></li>
    </ul>    
    </li>
  <li><a href="/?logout=true">Log Out</a></li>
</ul>
<? } ?>





<?
// Buyer's dashboard menu (MOBILE)
if($_SESSION['cust_id'] && $_SESSION['current_dash'] == 'buyer'){?>
<div class="dashmenuMob" id="dashBuyerMob">
	
    <div class="mobRack">
    	<h4>Navigation</h4>
    	<div></div>
        <div></div>
        <div></div>	
    </div>

	<ul style="display:none">
	    <li><a href="/buyer/dashboard/">My Dashboard</a></li>
	    <li><a href="/buyer/messages/">Mailbox (<span class="msgCounter">0</span>)</a></li>
	    <li><a href="/buyer/details/">View/Edit my Details</a></li>
	    <li><a href="/buyer/agents/">My OffMarket Agents</a></li>
	    <li><a href="javascript:void(0)">Settings</a>
        	<ul class="submenumob">
                <li><a href="/notfication-settings/">Notification settings</a></li>
                <? if($_SESSION['buyer'] && !$_SESSION['seller']){?>
        		<li><a href="/register-as-seller/">Register as seller</a></li>
        		<? } ?>
                <? if($_SESSION['seller'] && $_SESSION['buyer']){?>
        			<li><a href="/switch-profile/">Switch profile</a></li>
        		<? } ?>
                <li><a href="/delete-profile/">Delete profile</a></li>
              <li><a href="/contact/">Contact OML</a></li>
            </ul>
        </li>
	    <li><a href="/?logout=true">Log Out</a></li>
	</ul>
</div>
<? } ?>

<? 
// Seller's dashboard menu (MOBILE)
if($_SESSION['cust_id'] && $_SESSION['current_dash'] == 'seller'){ ?>
<div class="dashmenuMob" id="dashSellerMob">
	
    <div class="mobRack">
    	<h4>Navigation</h4>
    	<div></div>
        <div></div>
        <div></div>	
    </div>
    
	<ul style="display:none">
	    <li><a href="/seller/dashboard/">My Dashboard</a></li>
	    <li><a href="/seller/messages/">Mailbox (<span class="msgCounter">0</span>)</a></li>
	    <li><a href="/seller/details/">View/Edit my Details</a></li>
	    <li><a href="/seller/agents/">My OffMarket Agents</a></li>
	    <li><a href="javascript:void(0)">Settings</a>
        	<ul class="submenumob">
                <li><a href="/notfication-settings/">Notification settings</a></li>
                <? if($_SESSION['seller'] && !$_SESSION['buyer']){?>
                <li><a href="/register-as-buyer/">Register as buyer</a></li>
                <? } ?>
                <? if($_SESSION['seller'] && $_SESSION['buyer']){?>
        			<li><a href="/switch-profile/">Switch profile</a></li>
        		<? } ?>
                <li><a href="/delete-profile/">Delete profile</a></li>
                <li><a href="/contact/">Contact OML</a></li>
            </ul>
        </li>
	    <li><a href="/?logout=true">Sign Out</a></li>
	</ul>
</div>
<? } ?>

<? 
// Agents dashboard menu (MOBILE)
if($_SESSION['agent_id']){ ?>
<div class="dashmenuMob" id="dashAgentMob">
	
    <div class="mobRack">
    	<h4>Navigation</h4>
    	<div></div>
        <div></div>
        <div></div>	
    </div>
    
	<ul style="display:none">
   		<li><a href="/agent/dashboard/">My Dashboard</a></li>
	    <li><a href="/agent/messages/">Mailbox (<span class="msgCounter">0</span>)</a></li>
	    <li><a href="/agent/my-details/">View/Edit my Details</a></li>
        <li><a href="/agent/vendors/">My OffMarket Vendors</a></li>
	    <li><a href="javascript:void(0)">Settings</a>
        	<ul class="submenumob">
                <li><a href="/notfication-settings/">Notification settings</a></li>
                <li><a href="/subscription-details/">Subscription detais</a></li>
                <li><a href="/contact/">Contact OML</a></li>
            </ul>  
        </li>
	    <li><a href="/?logout=true">Log Out</a></li>
	</ul>
</div>
<? } ?>



<div id="content">





