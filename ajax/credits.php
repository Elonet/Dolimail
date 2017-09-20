<?php
/* Copyright (C) 2016 Elonet <contact@elonet.fr>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
 
	if (false === (@include '../../main.inc.php')) {  // From htdocs directory
		require '../../../main.inc.php'; // From "custom" directory
	}
	
	$apikey = $_POST['apikey'];
	
	global $conf;
	
	$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/credits.php';
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

	$result = json_decode($result,true);
	if($result['data']['total'] > 0) {
		echo 1;
	} else {
		echo 0;
	}
?>