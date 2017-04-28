<?php
	require '../../main.inc.php';
	
	$apikey = $_POST['apikey'];
	
	$url = 'https://dolimail.fr/server/credits_sup_validation.php';
	$fields = array(
		'apikey' => urlencode($apikey)
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
	
	echo $result;
?>