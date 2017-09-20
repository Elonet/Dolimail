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
	require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

	$id = GETPOST('id','int');
	$element = GETPOST('element','alpha');
	$to = GETPOST('to','alpha');
	$trackid = GETPOST('trackid','aZ09');
	
	global $conf;
	
	// Create form object
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);
	$formmail->trackid = $trackid;
	
	$attachedfiles=$formmail->get_attached_files();
	$tmp_array = explode("|",$_POST['option']);
	$option_array = array();
	if(count($tmp_array) > 0) {
		foreach($tmp_array as $option) {
			$values = explode("¤",$option);
			$option_array[base64_decode($values[0])] = array("notrack"=>$values[1],"nodl"=>$values[2],"auth"=>$values[3]);
		}
	}
	
	
	$filepath = $attachedfiles['paths'];
	$filename = $attachedfiles['names'];
	//Ici on demonte les fichiers joints
	$fileassociation = array();
	foreach($filepath as $idx => $file) {
		if($option_array[utf8_decode($filename[$idx])]["notrack"] == 0) { 
			$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/upload.php';
			$fileName = realpath($file);
			$fileSize = filesize($fileName);

			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$finfo = finfo_file($finfo, $fileName);
			
			$cFile = new CURLFile($fileName, $finfo, basename($fileName));
			
			$headers = array("Content-Type:multipart/form-data");
			$fields = array(
				'apikey' => $conf->global->DOLIMAIL_APIKEY,
				'id' => $id,
				'type' => $element,
				'filedata'=> $cFile,
				'filename'=> $cFile->postname,
				'recipients'=> $to,
				'nodl'=>$option_array[utf8_decode($filename[$idx])]["nodl"],
				'auth'=>$option_array[utf8_decode($filename[$idx])]["auth"]
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_HEADER,false);
			curl_setopt($ch, CURLOPT_POST, count($fields));
			curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$result = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close ($ch);
			
			$result = json_decode($result, true);
			if ($info['http_code'] == 201 && $result['success'])
			{
				foreach($result['data'] as $key=>$value) {
					$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/getfile.php?uuid='.$value['uuid']."&c=".$value['key'];
					$filelink = '<li><a href="'.$url.'">'.$cFile->postname.'</a></li>';
					$fileassociation[$key] = $fileassociation[$key].$filelink."(".$value['uuid'].")";
				}
			}
			else
			{
				error_log($langs->trans("uploadError").$cFile->postname.': '.$result['data']['message']);
			}
		}
	}
	echo json_encode($fileassociation);
?>