<?
// This is the dynamic page stub for virtual pages
include('config.php');

include('inc.header.php');

$e = new email;

#$e->sendAgentWelcomeEmail(39);


$e->sendSellerWelcomeEmail(46);
$e->sendBuyerWelcomeEmail(46);

$agent=new agent;
echo $agent->customersAgentCount('buyer');

#$e->sendNewMessageNotification(142);

include('inc.footer.php');
?>