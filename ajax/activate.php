<?php
	if (false === (@include '../../main.inc.php')) {  // From htdocs directory
		require '../../../main.inc.php'; // From "custom" directory
	}
	
	$apikey = $_POST['apikey'];
	$email = $_POST['email'];
	
	$url = 'https://dolimail.fr/server/reactivate.php';
	$fields = array(
		'email' => $email,
		'apikey' => $apikey
	);

	foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
	rtrim($fields_string, '&');

	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_POST, count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_HEADER, false);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 120);
	curl_setopt($ch,CURLOPT_TIMEOUT, 120);

	$result = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
?>