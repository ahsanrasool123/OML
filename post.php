<?
// Send variables as $_POST
include('config.php');

$fields = array(
	'secret_key' => 'somevalue&more',
	'auth_key' => 'othervalue',
	'charge_total' => 3345
);

$url = 'http://www.mdmdata.co.uk/test.php';

echo postData($fields, $url);
echo ""

?>