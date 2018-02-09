<?php
// Connection to DB

$mysqli = new mysqli($db_host, $db_user, $db_auth, $db_name);
if ($mysqli->connect_errno) {
	exit("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}



?>
