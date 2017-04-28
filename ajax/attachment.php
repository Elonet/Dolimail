<?php
	require '../../main.inc.php';
	require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

	$id = GETPOST('id','int');
	$element = GETPOST('element','alpha');
	$to = GETPOST('to','alpha');
	$trackid = GETPOST('trackid','aZ09');
	
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
			$url = 'https://dolimail.fr/server/upload.php';
			$fileName = realpath($file);
			$fileSize = filesize($fileName);

			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$finfo = finfo_file($finfo, $fileName);
			
			$cFile = new CURLFile($fileName, $finfo, basename($fileName));
			
			$headers = array("Content-Type:multipart/form-data");
			$fields = array(
				'apikey' => DOLIMAIL_APIKEY,
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
					$url = 'https://dolimail.fr/server/getfile.php?uuid='.$value;
					$filelink = '<li><a href="'.$url.'">'.$cFile->postname.'</a></li>';
					$fileassociation[$key] = $fileassociation[$key].$filelink."(".$value.")";
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