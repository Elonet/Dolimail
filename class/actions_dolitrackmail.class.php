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
 
class ActionsDolitrackmail {

	function __construct($db) {
		global $langs;

		$this->db = $db;
		$langs->load("dolitrackmail@dolitrackmail");
	}

	function addMoreActionsButtons($parameters=false, &$object, &$action='') {
		global $conf,$user,$langs,$mysoc,$soc,$societe;

		if (is_array($parameters) && ! empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}
		
		if(empty($soc->id)) {
			$socid = $societe->id;
		} else {
			$socid = $soc->id;
		}

		$element = $object->element;
		if ($element == 'propal') $element = 'propale';

		if ($element == 'propale') {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$c = new Categorie($this->db);
			$cats = $c->containing($socid,2);
			if (count($cats) > 0) {
				$tmp_tags = false;
				foreach($cats as $cat) {
					if($cat->label == "NoTrackingEmail" || $cat->label == "PasdeSuiviEmail") {
						$tmp_tags = true;
					}
				}
			}
			if (($object->statut == Propal::STATUS_VALIDATED || $object->statut == Propal::STATUS_SIGNED) && $tmp_tags == false) {
				if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->propal->propal_advance->send) {
					print '<div class="inline-block divButAction"><a class="butAction" target="_blank" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=presendtrack&amp;mode=init">' . $langs->trans('SendByMailTrack') . '</a></div>';
				} else {
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('SendByMailTrack') . '</a></div>';
				}
			}
		}
		if ($element == 'commande') {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$c = new Categorie($this->db);
			$cats = $c->containing($socid,2);
			if (count($cats) > 0) {
				$tmp_tags = false;
				foreach($cats as $cat) {
					if($cat->label == "NoTrackingEmail" || $cat->label == "PasdeSuiviEmail") {
						$tmp_tags = true;
					}
				}
			}
			if ($object->statut > Commande::STATUS_DRAFT && $tmp_tags == false) {
				if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->commande->order_advance->send)) {
					print '<div class="inline-block divButAction"><a class="butAction" target="_blank" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=presendtrack&amp;mode=init">' . $langs->trans('SendByMailTrack') . '</a></div>';
				} else {
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('SendByMailTrack') . '</a></div>';
				}
			}
		}
		if ($element == 'facture') {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$c = new Categorie($this->db);
			$cats = $c->containing($socid,2);
			if (count($cats) > 0) {
				$tmp_tags = false;
				foreach($cats as $cat) {
					if($cat->label == "NoTrackingEmail" || $cat->label == "PasdeSuiviEmail") {
						$tmp_tags = true;
					}
				}
			}
			if ((($object->statut == 1 || $object->statut == 2) || ! empty($conf->global->FACTURE_SENDBYEMAIL_FOR_ALL_STATUS)) && $tmp_tags == false) {
				if ($objectidnext) {
					print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('SendByMailTrack') . '</span></div>';
				} else {
					if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send) {
						print '<div class="inline-block divButAction"><a class="butAction" target="_blank" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=presendtrack&amp;mode=init">' . $langs->trans('SendByMailTrack') . '</a></div>';
					} else {
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('SendByMailTrack') . '</a></div>';
					}
				}
			}
		}
		if ($element == 'order_supplier') {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$c = new Categorie($this->db);
			$cats = $c->containing($socid,1);
			if (count($cats) > 0) {
				$tmp_tags = false;
				foreach($cats as $cat) {
					if($cat->label == "NoTrackingEmail" || $cat->label == "PasdeSuiviEmail") {
						$tmp_tags = true;
					}
				}
			}
			if (in_array($object->statut, array(2, 3, 4, 5)) && $tmp_tags == false) {
				if ($user->rights->fournisseur->commande->commander) {
					print '<a class="butAction" target="_blank" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presendtrack&amp;mode=init">'.$langs->trans('SendByMailTrack').'</a>';
				}
			}
		}
		if ($element == 'invoice_supplier') {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$c = new Categorie($this->db);
			$cats = $c->containing($socid,1);
			if (count($cats) > 0) {
				$tmp_tags = false;
				foreach($cats as $cat) {
					if($cat->label == "NoTrackingEmail" || $cat->label == "PasdeSuiviEmail") {
						$tmp_tags = true;
					}
				}
			}
			if (($object->statut == FactureFournisseur::STATUS_VALIDATED || $object->statut == FactureFournisseur::STATUS_CLOSED) && $tmp_tags == false) {
				if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->fournisseur->supplier_invoice_advance->send) {
					print '<a class="butAction" target="_blank" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=presendtrack&amp;mode=init">'.$langs->trans('SendByMailTrack').'</a>';
				} else {
					print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMailTrack').'</a>';
				}
			}
		}
		if ($element == 'shipping') {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$c = new Categorie($this->db);
			$cats = $c->containing($socid,2);
			if (count($cats) > 0) {
				$tmp_tags = false;
				foreach($cats as $cat) {
					if($cat->label == "NoTrackingEmail" || $cat->label == "PasdeSuiviEmail") {
						$tmp_tags = true;
					}
				}
			}
			if ($object->statut > 0 && $tmp_tags == false) {
				if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->expedition->shipping_advance->send)	{
					print '<a class="butAction" target="_blank" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presendtrack&amp;mode=init">'.$langs->trans('SendByMailTrack').'</a>';
				} else {
					print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMailTrack').'</a>';
				}
			}
		}
		if ($element == 'supplier_proposal') {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$c = new Categorie($this->db);
			$cats = $c->containing($socid,1);
			if (count($cats) > 0) {
				$tmp_tags = false;
				foreach($cats as $cat) {
					if($cat->label == "NoTrackingEmail" || $cat->label == "PasdeSuiviEmail") {
						$tmp_tags = true;
					}
				}
			}
			if (($object->statut == 1 || $object->statut == 2) && $tmp_tags == false) {
				if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->supplier_proposal->send_advance) {
					print '<div class="inline-block divButAction"><a class="butAction" target="_blank" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=presendtrack&amp;mode=init">' . $langs->trans('SendByMailTrack') . '</a></div>';
				} else {
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('SendByMailTrack') . '</a></div>';
				}
			}
		}
		if ($element == 'societe') {
			$at_least_one_email_contact = false;
			$TContact = $object->contact_array_objects();
			foreach ($TContact as &$contact) {
				if (!empty($contact->email)) {
					$at_least_one_email_contact = true;
					break;
				}
			}
			if (! empty($object->email) || $at_least_one_email_contact) {
	        	print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'&amp;action=presendtrack&amp;mode=init">'.$langs->trans('SendByMailTrack').'</a></div>';
	        } else {
	       		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("SendByMailTrack")).'">'.$langs->trans('SendByMailTrack').'</a></div>';
	        }
		}
        return 0;
	}

	function doActions($parameters=false, &$object, &$action='') {
		global $conf,$user,$langs,$mysoc,$soc,$societe,$db;


		if (is_array($parameters) && ! empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}
		$element = $object->element;

		if ($element == 'propal') $element = 'propale';

		if ($element == 'propale') {
			$actiontypecode='AC_PROP';
			$trigger_name='PROPAL_SENTBYMAIL';
			$paramname='id';
			$mode='emailfromproposal';
		}
		if ($element == 'commande') {
			$actiontypecode='AC_COM';
			$trigger_name='ORDER_SENTBYMAIL';
			$paramname='id';
			$mode='emailfromorder';
		}
		if ($element == 'facture') {
			$actiontypecode='AC_FAC';
			$trigger_name='BILL_SENTBYMAIL';
			$paramname='id';
			$mode='emailfrominvoice';
		}
		if ($element == 'order_supplier') {
			$actiontypecode='AC_SUP_ORD';
			$trigger_name='ORDER_SUPPLIER_SENTBYMAIL';
			$paramname='id';
			$mode='emailfromordersupplier';			
		}
		if ($element == 'invoice_supplier') {
			$actiontypecode='AC_SUP_INV';
			$trigger_name='BILL_SUPPLIER_SENTBYMAIL';
			$paramname='id';
			$mode='emailfromordersupplierinvoice';	
		}
		if ($element == 'shipping') {
			$actiontypecode='AC_SHIP';
			$trigger_name='SHIPPING_SENTBYMAIL';
			$paramname='id';
			$mode='emailfromshipment';
		}
		if ($element == 'supplier_proposal') {
			$actiontypecode='AC_ASKPRICE';
			$trigger_name='SUPPLIER_PROPOSAL_SENTBYMAIL';
			$paramname='id';
			$mode='emailfromsupplierproposal';
		}
		if ($element == 'societe') {
			$actiontypecode='AC_OTH_AUTO';
			$trigger_name='COMPANY_SENTBYMAIL';
			$paramname='socid';
			$mode='emailfromthirdparty';
		}
		
		if (GETPOST('modelselectedtrack')) $action = 'presendtrack';

		/*
		 * Add file in email form
		 */
		if (GETPOST('addfiletrack'))
		{
			$trackid = GETPOST('trackid','aZ09');	
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

			// Set tmp user directory
			$vardir=$conf->user->dir_output."/".$user->id;
			$upload_dir_tmp = $vardir.'/temp';             // TODO Add $keytoavoidconflict in upload_dir path

			dol_add_file_process($upload_dir_tmp, 0, 0, 'addedfile', '', null, $trackid);
			$action='presendtrack';
	}

		/*
		 * Remove file in email form
		 */
		if (! empty($_POST['removedfiletrack']) && empty($_POST['removAlltrack']))
		{
			$trackid = GETPOST('trackid','aZ09');
			
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

			// Set tmp user directory
			$vardir=$conf->user->dir_output."/".$user->id;
			$upload_dir_tmp = $vardir.'/temp';
			// TODO Delete only files that was uploaded from email form
			dol_remove_file_process(GETPOST('removedfiletrack','alpha'), 0, 1, $trackid);   // We do not delete because if file is the official PDF of doc, we don't want to remove it physically
			$action='presendtrack';
		}

		/*
		 * Remove all files in email form
		 */

		if(! empty($_POST['removAlltrack']))
		{
			$trackid = GETPOST('trackid','aZ09');
			
			$listofpaths=array();
			$listofnames=array();
			$listofmimes=array();
			$keytoavoidconflict = empty($trackid)?'':'-'.$trackid;
			if (! empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths=explode(';',$_SESSION["listofpaths".$keytoavoidconflict]);
			if (! empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames=explode(';',$_SESSION["listofnames".$keytoavoidconflict]);
			if (! empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes=explode(';',$_SESSION["listofmimes".$keytoavoidconflict]);

			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
			$formmail->trackid = $trackid;
			
			foreach($listofpaths as $key => $value)
			{
				$pathtodelete = $value;
				$filetodelete = $listofnames[$key];
				$result = dol_delete_file($pathtodelete,1); // Delete uploded Files

				$langs->load("other");
				setEventMessages($langs->trans("FileWasRemoved",$filetodelete), null, 'mesgs');

				$formmail->remove_attached_files($key); // Update Session
			}
		}
		
		/*
		 * Send mail
		 */
		if (($action == 'sendtrack') && ! $_POST['addfiletrack'] && ! $_POST['removAlltrack'] && ! $_POST['removedfiletrack'] && ! $_POST['canceltrack']) {
			if($conf->dolimail->enabled) $langs->load("dolimail@dolimail");
			$langs->load('mails');

			$trackid = GETPOST('trackid','aZ09');
			if(!empty($_POST['disable_old'])) {
				$disable_old =1;
			} else {
				$disable_old =0;
			}
			
			$subject='';$actionmsg='';$actionmsg2='';
			$result=$object->fetch($id);
			$sendtosocid=0;
			if (method_exists($object,"fetch_thirdparty") && $object->element != 'societe')	{
				$result=$object->fetch_thirdparty();
				$thirdparty=$object->thirdparty;
				$sendtosocid=$thirdparty->id;
			} else if ($object->element == 'societe') {
				$thirdparty=$object;
				if ($thirdparty->id > 0) {
					$sendtosocid=$thirdparty->id;
				} elseif($conf->dolimail->enabled) {
					// $dolimail = new Dolimail($db);
					// $possibleaccounts=$dolimail->get_societe_by_email($_POST['sendto'],"1");
					// $possibleuser=$dolimail->get_from_user_by_mail($_POST['sendto'],"1"); // suche in llx_societe and socpeople
					// if (!$possibleaccounts && !$possibleuser) {
						// setEventMessages($langs->trans('ErrorFailedToFindSocieteRecord',$_POST['sendto']), null, 'errors');
					// } elseif (count($possibleaccounts)>1) {
						// $sendtosocid=$possibleaccounts[1]['id'];
						// $result=$object->fetch($sendtosocid);
						// setEventMessages($langs->trans('ErrorFoundMoreThanOneRecordWithEmail',$_POST['sendto'],$object->name), null, 'mesgs');
					// } else {
						// if($possibleaccounts) {
							// $sendtosocid=$possibleaccounts[1]['id'];
							// $result=$object->fetch($sendtosocid);
						// } elseif($possibleuser) {
							// $sendtosocid=$possibleuser[0]['id'];

							// $result=$uobject->fetch($sendtosocid);
							// $object=$uobject;
						// }
					// }
				}
			} else dol_print_error('','Use actions_sendmails.in.php for a type that is not supported');

			if ($result > 0) {
				if (trim($_POST['sendto']))	{
					// Recipient is provided into free text
					$sendto = trim($_POST['sendto']);
					$sendtoid = 0;
				} elseif ($_POST['receiver'] != '-1') {
					// Recipient was provided from combo list
					if ($_POST['receiver'] == 'thirdparty') {
						$sendto = $thirdparty->email;
						$sendtoid = 0;
					} else {
						$sendto = $thirdparty->contact_get_property((int) $_POST['receiver'],'email');
						$sendtoid = $_POST['receiver'];
					}
				}
				if (trim($_POST['sendtocc'])) {
					$sendtocc = trim($_POST['sendtocc']);
				} elseif ($_POST['receivercc'] != '-1')	{
					// Recipient was provided from combo list
					if ($_POST['receivercc'] == 'thirdparty') {
						$sendtocc = $thirdparty->email;
					} else {
						$sendtocc = $thirdparty->contact_get_property((int) $_POST['receivercc'],'email');
					}
				}
				

				if (dol_strlen($sendto)) {
					$sendtos = explode(",",$sendto);
					$sendtoccs = explode(",",$sendtocc);
					$tmp_array = explode("|",$_POST['option_mail']);
					$option_array = array();
					if(count($tmp_array) > 0) {
						foreach($tmp_array as $option) {
							$values = explode("¤",$option);
							$option_array[$values[0]] = array("al_s_o_a"=>$values[1],"al_e_o_a"=>$values[2],"al_s_o_e"=>$values[3],"al_e_o_e"=>$values[4]);
						}
					}
					foreach($sendtos as $to) {
						$langs->load("commercial");

						$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
						$replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
						$message = $_POST['message'];

						if (!dol_textishtml($message)) {
							$message = '<p>'.$message.'</p>';
						} else {
							$message = '<div>'.$message.'</div>';
						}

						$sendtobcc= GETPOST('sendtoccc');
						if ($mode == 'emailfromproposal') $sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO));
						if ($mode == 'emailfromorder')    $sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO));
						if ($mode == 'emailfrominvoice')  $sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO));

						$deliveryreceipt = $_POST['deliveryreceipt'];

						if ($action == 'sendtrack' || $action == 'relance')	{
							if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
							$actionmsg2=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto;
							if ($message) {
								$actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto;
								if ($sendtocc) $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
								$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
								$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
								$actionmsg = dol_concatdesc($actionmsg, $message);
							}
						}

						// Create form object
						include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
						$formmail = new FormMail($db);
						$formmail->trackid = $trackid; 
						
						$attachedfiles=$formmail->get_attached_files();

						//Recherche des fichiers ayant été envoyé sur les serveurs de Dolimail et retrait de la liste
						if($_POST['send_array'] != "" && $conf->global->CF_TRCK_DL != 1) {
							$send_array = explode("|",$_POST['send_array']);
							
							foreach($send_array as $send_file) {
								$key = array_search(base64_decode($send_file), $attachedfiles['names']);
								if(isset($key)) {
									unset($attachedfiles['paths'][$key]);
									unset($attachedfiles['names'][$key]);
									unset($attachedfiles['mimes'][$key]);
								}
							}
							$attachedfiles['paths'] = array_values($attachedfiles['paths']);
							$attachedfiles['names'] = array_values($attachedfiles['names']);
							$attachedfiles['mimes'] = array_values($attachedfiles['mimes']);
						}
						
						
						$filepath = $attachedfiles['paths'];
						$filename = $attachedfiles['names'];
						$mimetype = $attachedfiles['mimes'];
						//Ici on demonte les fichiers joints
						$message_array = json_decode($_POST['message_array'],true);
						$regex = '#\((([^()]+|(?R))*)\)#';
						if(count($message_array) > 0) {
							$message .= "<p>".$langs->trans("attachfile")."</p><ul>";						
							$message .= preg_replace($regex, "", $message_array[$to]);
							$message .= '</ul>';
						}
						
						$trackid = GETPOST('trackid','aZ09');

						// Feature to push mail sent into Sent folder
						/*if (! empty($conf->dolimail->enabled)) {
							$mailfromid = explode("#", $_POST['frommail'],3);	// $_POST['frommail'] = 'aaa#Sent# <aaa@aaa.com>'	// TODO Use a better way to define Sent dir.
							if (count($mailfromid)==0) $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
							else {
								$mbid = $mailfromid[1];
								
								//IMAP Postbox
								$mailboxconfig = new IMAP($db);
								$mailboxconfig->fetch($mbid);
								if ($mailboxconfig->mailbox_imap_host) $ref=$mailboxconfig->get_ref();

								$mailboxconfig->folder_id=$mailboxconfig->mailbox_imap_outbox;
								$mailboxconfig->userfolder_fetch();

								if ($mailboxconfig->mailbox_save_sent_mails == 1) {
									$folder=str_replace($ref, '', $mailboxconfig->folder_cache_key);
									if (!$folder) $folder = "Sent";	// Default Sent folder

									$mailboxconfig->mbox = imap_open($mailboxconfig->get_connector_url().$folder, $mailboxconfig->mailbox_imap_login, $mailboxconfig->mailbox_imap_password);
									if (FALSE === $mailboxconfig->mbox)	{
										$info = FALSE;
										$err = $langs->trans('Error3_Imap_Connection_Error');
										setEventMessages($err,$mailboxconfig->element, null, 'errors');
									} else {
										$mailboxconfig->mailboxid=$_POST['frommail'];
										$mailboxconfig->foldername=$folder;
										$from = $mailfromid[0] . $mailfromid[2];
										$imap=1;
										
									}

								}
							}
						}*/
						//Preparation association fichier/mail
						$regex = '#\((([^()]+|(?R))*)\)#';
						if (preg_match_all($regex, $message_array[$to] ,$matches)) {
							$id_file = implode(';', $matches[1]);
						}
						// Get shortlink mail
						$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/newshortlinkmail.php';
						$fields = array(
							'apikey' => $conf->global->DOLIMAIL_APIKEY,
							'id' => $object->id,
							'type' => $object->element,
							'target'=> $to,
							'al_s_o_a'=> $option_array[$to]["al_s_o_a"],
							'al_e_o_a'=> $option_array[$to]["al_e_o_a"],
							'al_s_o_e'=> $option_array[$to]["al_s_o_e"],
							'al_e_o_e'=> $option_array[$to]["al_e_o_e"],
							'id_file' => $id_file,
							'subject' => $subject,
							'source' => $user->email,
							'phone' => $user->user_mobile,
							'lang' => $langs->defaultlang
						);

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL,$url);
						curl_setopt($ch, CURLOPT_POST,1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
						curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch,CURLOPT_HEADER, false);
						curl_setopt($ch,CURLOPT_FOLLOWLOCATION, false);
						$result = curl_exec ($ch);
						$info = curl_getinfo($ch);
						curl_close ($ch);

						$result = json_decode($result,true);
						if ($info['http_code'] == 201 && $result['success']) {
							$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/getshortlinkmail.php?uuid='.$result['data']['id'];
							$message .= '<img src="'.$url.'" style="display: none;" />';
						} else {
							$langs->load("errors");
							setEventMessages($result['data']['message'], null, 'warnings');
							$action = 'presendtrack';
							$error_limit = 1;
							if($conf->dolimail->enabled) header('Location: '.$_SERVER["PHP_SELF"].'?'.($paramname?$paramname:'id').'='.$object->id.'&'.($paramname2?$paramname2:'mid').'='.$parm2val);
							else	header('Location: '.$_SERVER["PHP_SELF"].'?'.($paramname?$paramname:'id').'='.$object->id);
							exit;
						}
						// Send mail
						require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
						//Construction de la liste des destinataires
						if(count($sendtos) > 1 && empty($_POST['disable_follow'])) {
							$footmessage = $sendtos;
							if(count($sendtoccs) > 0) {
								$footmessage = array_merge($footmessage,$sendtoccs);
							}
							$index = array_search($to, $footmessage);
							if($index !== false) {
								array_splice($footmessage, $index, 1);
							}
							$message .= "<p><i>".$langs->trans("mailsendto").implode(", ",array_filter($footmessage))."</i></p>";
						}
						
						$message .= "<br/><p><small><i>".$langs->trans("dolimail_footer")."</i></small></p>";
						global $mailfile;
						
						$mailfile = new CMailFile($subject,$to,$from,$message,$filepath,$mimetype,$filename,$sendtocc,$sendtobcc,$deliveryreceipt,-1,'','',$trackid);
						if ($mailfile->error) {
							$mesgs[]='<div class="error">'.$mailfile->error.'</div>';
						} else {
							$result=$mailfile->sendfile();
							if ($result) {
								$error=0;

								/*// FIXME This must be moved into a trigger for action $trigger_name
								if (! empty($conf->dolimail->enabled)) {
									$mid = (GETPOST('mid','int') ? GETPOST('mid','int') : 0);	// Original mail id is set ?
									if ($mid) {
										// set imap flag answered if it is an answered mail
										$dolimail=new DoliMail($db);
										$dolimail->id = $mid;
										$res=$dolimail->set_prop($user, 'answered',1);
									}
									if ($imap==1) {
										// write mail to IMAP Server
										$movemail = $mailboxconfig->putMail($subject,$to,$from,$message,$filepath,$mimetype,$filename,$sendtocc,$folder,$deliveryreceipt,$mailfile);
										if ($movemail) setEventMessages($langs->trans("MailMovedToImapFolder",$folder), null, 'mesgs');
										else setEventMessages($langs->trans("MailMovedToImapFolder_Warning",$folder), null, 'warnings');
									}
								}*/

								// Initialisation of datas
								if (is_object($object))	{
									$object->socid			= $sendtosocid;	// To link to a company
									$object->sendtoid		= $sendtoid;	// To link to a contact/address
									$object->actiontypecode	= $actiontypecode;
									$object->actionmsg		= $actionmsg;  // Long text
									$object->actionmsg2		= $actionmsg2; // Short text
									$object->trackid        = $trackid;
									$object->fk_element		= $object->id;
									$object->elementtype	= $object->element;
									// Call of triggers
									include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
									$interface=new Interfaces($db);
									$result=$interface->run_triggers($trigger_name,$object,$user,$langs,$conf);
									if ($result < 0) {
										$error++; $errors=$interface->errors;
									}
									// End call of triggers
								}

								if ($error)	{
									dol_print_error($db);
								}
							} else {
								$langs->load("other");
								$mesg='<div class="error">';
								if ($mailfile->error) {
									$mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
									$mesg.='<br>'.$mailfile->error;
								} else {
									$mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
								}
								$mesg.='</div>';

								setEventMessages($mesg, null, 'warnings');
								$action = 'presendtrack';
							}
						}
					}
					if($disable_old == 1) {
						$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/delete_old.php';
						$fields = array(
							'apikey' => $conf->global->DOLIMAIL_APIKEY,
							'id' => $object->id,
							'type' => $object->element
						);
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL,$url);
						curl_setopt($ch, CURLOPT_POST,1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
						curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch,CURLOPT_HEADER, false);
						curl_setopt($ch,CURLOPT_FOLLOWLOCATION, false);
						$result = curl_exec ($ch);
					}
					// Redirect here
					// This avoid sending mail twice if going out and then back to page
					$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));
					setEventMessages($mesg, null, 'mesgs');
					if($conf->dolimail->enabled) header('Location: '.$_SERVER["PHP_SELF"].'?'.($paramname?$paramname:'id').'='.$object->id.'&'.($paramname2?$paramname2:'mid').'='.$parm2val);
					else	header('Location: '.$_SERVER["PHP_SELF"].'?'.($paramname?$paramname:'id').'='.$object->id);
					exit;
				} else {
					$langs->load("errors");
					setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
					dol_syslog('Try to send email with no recipiend defined', LOG_WARNING);
					$action = 'presendtrack';
				}
			} else {
				$langs->load("other");
				setEventMessages($langs->trans('ErrorFailedToReadEntity',$object->element), null, 'errors');
				dol_syslog('Failed to read data of object id='.$object->id.' element='.$object->element);
				$action = 'presendtrack';
			}
		}
	}
	
	function contact_property_array_dolitrackmail($mode='email', $hidedisabled=0, $id) {
        global $langs,$db;
  
        $contact_property = array();  
  
        $sql = "SELECT rowid, email, statut, phone_mobile, lastname, poste, firstname";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople";
        $sql.= " WHERE fk_soc = '".$id."'";
  
        $resql=$db->query($sql);
        if ($resql) {
            $nump = $db->num_rows($resql);
            if ($nump) {
                $sepa="("; $sepb=")";
                if ($mode == 'email') {
                  $sepa="&lt;"; $sepb="&gt;";
                }
                $i = 0;
                while ($i < $nump) {
                    $obj = $db->fetch_object($resql);
                    if ($mode == 'email') $property=$obj->email;
                    else if ($mode == 'mobile') $property=$obj->phone_mobile;
                    else $property=$obj->$mode;
                    if ($obj->statut == 1 || empty($hidedisabled)) {
                        if (empty($property)) {
							if ($mode == 'email') $property=$langs->trans("NoEMail");
							else if ($mode == 'mobile') $property=$langs->trans("NoMobilePhone");
                        }  
                        if (!empty($obj->poste)) {
							$contact_property[$property] = trim(dolGetFirstLastname($obj->firstname,$obj->lastname)).($obj->poste?" - ".$obj->poste:"");
						} else {
							$contact_property[$property] = trim(dolGetFirstLastname($obj->firstname,$obj->lastname));
						}
                    }
                    $i++;
                }
            }
        } else {
            dol_print_error($db);
        }
        return $contact_property;
    }
	  
	function thirdparty_and_contact_email_array_dolitrackmail($addthirdparty=0,$object) {
        global $langs;
		$id = $object->id;
        $contact_emails = $this->contact_property_array_dolitrackmail('email',1,$id);
        if ($object->email && $addthirdparty) {
            if (empty($object->name)) $object->name=$object->nom;
            $contact_emails[$object->email]=$langs->trans("ThirdParty").': '.dol_trunc($object->name,16);
        }
        return $contact_emails;
    }

	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		global $conf,$user,$langs,$mysoc;

		if (is_array($parameters) && ! empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}
		$element = $object->element;
		$request = '&mode='.$_REQUEST['mode'].'&modelmailselectedtrack='.$_REQUEST['modelmailselectedtrack'].'&modelselectedtrack='.$_REQUEST['modelselectedtrack'].'&sendto='.$_REQUEST['sendto'].'&message='.rawurlencode($_REQUEST['message']).'&subject='.$_REQUEST['subject'];
		if ($element == 'propal') $element = 'propale';

		if ($element == 'propale') {
			if ($action == 'presendtrack') {
				?>
				<script>
					var availableEmail = [
						<?php foreach ($this->thirdparty_and_contact_email_array_dolitrackmail(1,$object->thirdparty) as $key=>$value) echo '{ email:"'.$key.'", label:"'.$value.'"},'; ?>
					];	
				</script>
				<script type="text/javascript" src="<?php echo dol_buildpath('/dolitrackmail/js/dolitrackmail.js.php?id='.$object->id.'&el='.$element.$request,1) ?>"></script>
				<style>
					.buttoni {margin: 5px 5px 0 5px;cursor: pointer;width: 18px;height: 18px;float: left;text-align: center;}
					.option img {height:24px;margin-left:4px;margin-right:4px;}
					.option {float:left;}
					.email-div{float:left;}
					.transparency{opacity:0.4;}
					.border-bottom {border-bottom:1px solid #e0e0e0;}
					.legend{border-spacing : 0;border-collapse : collapse;margin-right:8px;margin-left:4px;}
					.legend img{margin-right:2px;}
				</style>
				<script>
					$(document).ready(function() {
						$("html, body").animate({ scrollTop: $(document).height() }, "slow");
						//Redirection vers l'ajax normal si les crédits sont utilisés
						$.ajax({
							url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/active.php'; ?>",
							type: "POST",
							data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
							dataType: "text",
							success: function(html){
								if(html == 0) {
									$.jnotify("<?php echo $langs->trans("notactive"); ?>",'error',6000 );
									setTimeout(function () {
									   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
									}, 5000);
								} else {
									$.ajax({
										url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/credits.php'; ?>",
										type: "POST",
										data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
										dataType: "text",
										success: function(html){
											if(html == 0) {
												$.jnotify("<?php echo $langs->trans("notenoughcredit"); ?>",'error',6000 );
												setTimeout(function () {
												   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
												}, 5000);
											}
										}
									});
								}
							}
						});
					});
				</script>
				<?php
			}
			if($conf->global->CF_DIS_CLASSIC == 1) {
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('a[href*="presend&"]').parent().remove();
					});
				</script>
				<?php
			}
		}
		if ($element == 'commande') {
			if ($action == 'presendtrack') {
				?>
				<script>
					var availableEmail = [
						<?php foreach ($this->thirdparty_and_contact_email_array_dolitrackmail(1,$object->thirdparty) as $key=>$value) echo '{ email:"'.$key.'", label:"'.$value.'"},'; ?>
					];	
				</script>
				<script type="text/javascript" src="<?php echo dol_buildpath('/dolitrackmail/js/dolitrackmail.js.php?id='.$object->id.'&el='.$element.$request,1) ?>"></script>
				<style>
					.buttoni {margin: 5px 5px 0 5px;cursor: pointer;width: 18px;height: 18px;float: left;text-align: center;}
					.option img {height:24px;margin-left:4px;margin-right:4px;}
					.option {float:left;}
					.email-div{float:left;}
					.transparency{opacity:0.4;}
					.border-bottom {border-bottom:1px solid #e0e0e0;}
					.legend{border-spacing : 0;border-collapse : collapse;margin-right:8px;margin-left:4px;}
					.legend img{margin-right:2px;}
				</style>
				<script>
					$(document).ready(function() {
						$("html, body").animate({ scrollTop: $(document).height() }, "slow");
						//Redirection vers l'ajax normal si les crédits sont utilisés
						$.ajax({
							url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/active.php'; ?>",
							type: "POST",
							data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
							dataType: "text",
							success: function(html){
								if(html == 0) {
									$.jnotify("<?php echo $langs->trans("notactive"); ?>",'error',6000 );
									setTimeout(function () {
									   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
									}, 5000);
								} else {
									$.ajax({
										url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/credits.php'; ?>",
										type: "POST",
										data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
										dataType: "text",
										success: function(html){
											if(html == 0) {
												$.jnotify("<?php echo $langs->trans("notenoughcredit"); ?>",'error',6000 );
												setTimeout(function () {
												   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
												}, 5000);
											}
										}
									});
								}
							}
						});
					});
				</script>
				<?php
			}
			if($conf->global->CF_DIS_CLASSIC == 1) {
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('a[href*="presend&"]').parent().remove();
					});
				</script>
				<?php
			}
		}
		if ($element == 'facture') {
			if ($action == 'presendtrack') {
				?>
				<script>
					var availableEmail = [
						<?php foreach ($this->thirdparty_and_contact_email_array_dolitrackmail(1,$object->thirdparty) as $key=>$value) echo '{ email:"'.$key.'", label:"'.$value.'"},'; ?>
					];	
				</script>
				<script type="text/javascript" src="<?php echo dol_buildpath('/dolitrackmail/js/dolitrackmail.js.php?id='.$object->id.'&el='.$element.$request,1) ?>"></script>
				<style>
					.buttoni {margin: 5px 5px 0 5px;cursor: pointer;width: 18px;height: 18px;float: left;text-align: center;}
					.option img {height:24px;margin-left:4px;margin-right:4px;}
					.option {float:left;}
					.email-div{float:left;}
					.transparency{opacity:0.4;}
					.border-bottom {border-bottom:1px solid #e0e0e0;}
					.legend{border-spacing : 0;border-collapse : collapse;margin-right:8px;margin-left:4px;}
					.legend img{margin-right:2px;}
				</style>
				<script>
					$(document).ready(function() {
						$("html, body").animate({ scrollTop: $(document).height() }, "slow");
						//Redirection vers l'ajax normal si les crédits sont utilisés
						$.ajax({
							url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/active.php'; ?>",
							type: "POST",
							data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
							dataType: "text",
							success: function(html){
								if(html == 0) {
									$.jnotify("<?php echo $langs->trans("notactive"); ?>",'error',6000 );
									setTimeout(function () {
									   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
									}, 5000);
								} else {
									$.ajax({
										url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/credits.php'; ?>",
										type: "POST",
										data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
										dataType: "text",
										success: function(html){
											if(html == 0) {
												$.jnotify("<?php echo $langs->trans("notenoughcredit"); ?>",'error',6000 );
												setTimeout(function () {
												   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
												}, 5000);
											}
										}
									});
								}
							}
						});
					});
				</script>
				<?php
			}
			if($conf->global->CF_DIS_CLASSIC == 1) {
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('a[href*="presend&"]').parent().remove();
					});
				</script>
				<?php
			}
		}
		if ($element == 'order_supplier') {
			if ($action == 'presendtrack') {
				?>
				<script>
					var availableEmail = [
						<?php foreach ($this->thirdparty_and_contact_email_array_dolitrackmail(1,$object->thirdparty) as $key=>$value) echo '{ email:"'.$key.'", label:"'.$value.'"},'; ?>
					];	
				</script>
				<script type="text/javascript" src="<?php echo dol_buildpath('/dolitrackmail/js/dolitrackmail.js.php?id='.$object->id.'&el='.$element.$request,1) ?>"></script>
				<style>
					.buttoni {margin: 5px 5px 0 5px;cursor: pointer;width: 18px;height: 18px;float: left;text-align: center;}
					.option img {height:24px;margin-left:4px;margin-right:4px;}
					.option {float:left;}
					.email-div{float:left;}
					.transparency{opacity:0.4;}
					.border-bottom {border-bottom:1px solid #e0e0e0;}
					.legend{border-spacing : 0;border-collapse : collapse;margin-right:8px;margin-left:4px;}
					.legend img{margin-right:2px;}
				</style>
				<script>
					$(document).ready(function() {
						$("html, body").animate({ scrollTop: $(document).height() }, "slow");
						//Redirection vers l'ajax normal si les crédits sont utilisés
						$.ajax({
							url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/active.php'; ?>",
							type: "POST",
							data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
							dataType: "text",
							success: function(html){
								if(html == 0) {
									$.jnotify("<?php echo $langs->trans("notactive"); ?>",'error',6000 );
									setTimeout(function () {
									   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
									}, 5000);
								} else {
									$.ajax({
										url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/credits.php'; ?>",
										type: "POST",
										data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
										dataType: "text",
										success: function(html){
											if(html == 0) {
												$.jnotify("<?php echo $langs->trans("notenoughcredit"); ?>",'error',6000 );
												setTimeout(function () {
												   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
												}, 5000);
											}
										}
									});
								}
							}
						});
					});
				</script>
				<?php
			}
			if($conf->global->CF_DIS_CLASSIC == 1) {
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('a[href*="presend&"]').parent().remove();
					});
				</script>
				<?php
			}
		}
		if ($element == 'invoice_supplier') {
			if ($action == 'presendtrack') {
				?>
				<script>
					var availableEmail = [
						<?php foreach ($this->thirdparty_and_contact_email_array_dolitrackmail(1,$object->thirdparty) as $key=>$value) echo '{ email:"'.$key.'", label:"'.$value.'"},'; ?>
					];	
				</script>
				<script type="text/javascript" src="<?php echo dol_buildpath('/dolitrackmail/js/dolitrackmail.js.php?id='.$object->id.'&el='.$element.$request,1) ?>"></script>
				<style>
					.buttoni {margin: 5px 5px 0 5px;cursor: pointer;width: 18px;height: 18px;float: left;text-align: center;}
					.option img {height:24px;margin-left:4px;margin-right:4px;}
					.option {float:left;}
					.email-div{float:left;}
					.transparency{opacity:0.4;}
					.border-bottom {border-bottom:1px solid #e0e0e0;}
					.legend{border-spacing : 0;border-collapse : collapse;margin-right:8px;margin-left:4px;}
					.legend img{margin-right:2px;}
				</style>
				<script>
					$(document).ready(function() {
						$("html, body").animate({ scrollTop: $(document).height() }, "slow");
						//Redirection vers l'ajax normal si les crédits sont utilisés
						$.ajax({
							url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/active.php'; ?>",
							type: "POST",
							data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
							dataType: "text",
							success: function(html){
								if(html == 0) {
									$.jnotify("<?php echo $langs->trans("notactive"); ?>",'error',6000 );
									setTimeout(function () {
									   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
									}, 5000);
								} else {
									$.ajax({
										url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/credits.php'; ?>",
										type: "POST",
										data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
										dataType: "text",
										success: function(html){
											if(html == 0) {
												$.jnotify("<?php echo $langs->trans("notenoughcredit"); ?>",'error',6000 );
												setTimeout(function () {
												   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
												}, 5000);
											}
										}
									});
								}
							}
						});
					});
				</script>
				<?php
			}
			if($conf->global->CF_DIS_CLASSIC == 1) {
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('a[href*="presend&"]').parent().remove();
					});
				</script>
				<?php
			}
		}
		if ($element == 'shipping') {
			if ($action == 'presendtrack') {
				?>
				<script>
					var availableEmail = [
						<?php foreach ($this->thirdparty_and_contact_email_array_dolitrackmail(1,$object->thirdparty) as $key=>$value) echo '{ email:"'.$key.'", label:"'.$value.'"},'; ?>
					];	
				</script>
				<script type="text/javascript" src="<?php echo dol_buildpath('/dolitrackmail/js/dolitrackmail.js.php?id='.$object->id.'&el='.$element.$request,1) ?>"></script>
				<style>
					.buttoni {margin: 5px 5px 0 5px;cursor: pointer;width: 18px;height: 18px;float: left;text-align: center;}
					.option img {height:24px;margin-left:4px;margin-right:4px;}
					.option {float:left;}
					.email-div{float:left;}
					.transparency{opacity:0.4;}
					.border-bottom {border-bottom:1px solid #e0e0e0;}
					.legend{border-spacing : 0;border-collapse : collapse;margin-right:8px;margin-left:4px;}
					.legend img{margin-right:2px;}
				</style>
				<script>
					$(document).ready(function() {
						$("html, body").animate({ scrollTop: $(document).height() }, "slow");
						//Redirection vers l'ajax normal si les crédits sont utilisés
						$.ajax({
							url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/active.php'; ?>",
							type: "POST",
							data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
							dataType: "text",
							success: function(html){
								if(html == 0) {
									$.jnotify("<?php echo $langs->trans("notactive"); ?>",'error',6000 );
									setTimeout(function () {
									   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
									}, 5000);
								} else {
									$.ajax({
										url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/credits.php'; ?>",
										type: "POST",
										data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
										dataType: "text",
										success: function(html){
											if(html == 0) {
												$.jnotify("<?php echo $langs->trans("notenoughcredit"); ?>",'error',6000 );
												setTimeout(function () {
												   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
												}, 5000);
											}
										}
									});
								}
							}
						});
					});
				</script>
				<?php
			}
			if($conf->global->CF_DIS_CLASSIC == 1) {
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('a[href*="presend&"]').parent().remove();
					});
				</script>
				<?php
			}
		}
		if ($element == 'supplier_proposal') {
			if ($action == 'presendtrack') {
				?>
				<script>
					var availableEmail = [
						<?php foreach ($this->thirdparty_and_contact_email_array_dolitrackmail(1,$object->thirdparty) as $key=>$value) echo '{ email:"'.$key.'", label:"'.$value.'"},'; ?>
					];	
				</script>
				<script type="text/javascript" src="<?php echo dol_buildpath('/dolitrackmail/js/dolitrackmail.js.php?id='.$object->id.'&el='.$element.$request,1) ?>"></script>
				<style>
					.buttoni {margin: 5px 5px 0 5px;cursor: pointer;width: 18px;height: 18px;float: left;text-align: center;}
					.option img {height:24px;margin-left:4px;margin-right:4px;}
					.option {float:left;}
					.email-div{float:left;}
					.transparency{opacity:0.4;}
					.border-bottom {border-bottom:1px solid #e0e0e0;}
					.legend{border-spacing : 0;border-collapse : collapse;margin-right:8px;margin-left:4px;}
					.legend img{margin-right:2px;}
				</style>
				<script>
					$(document).ready(function() {
						$("html, body").animate({ scrollTop: $(document).height() }, "slow");
						//Redirection vers l'ajax normal si les crédits sont utilisés
						$.ajax({
							url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/active.php'; ?>",
							type: "POST",
							data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
							dataType: "text",
							success: function(html){
								if(html == 0) {
									$.jnotify("<?php echo $langs->trans("notactive"); ?>",'error',6000 );
									setTimeout(function () {
									   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
									}, 5000);
								} else {
									$.ajax({
										url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/credits.php'; ?>",
										type: "POST",
										data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
										dataType: "text",
										success: function(html){
											if(html == 0) {
												$.jnotify("<?php echo $langs->trans("notenoughcredit"); ?>",'error',6000 );
												setTimeout(function () {
												   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
												}, 5000);
											}
										}
									});
								}
							}
						});
					});
				</script>
				<?php
			}
			if($conf->global->CF_DIS_CLASSIC == 1) {
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('a[href*="presend&"]').parent().remove();
					});
				</script>
				<?php
			}
		}
		if ($element == 'societe') {
			if ($action == 'presendtrack') {
				?>
				<script>
					var availableEmail = [
						<?php foreach ($this->thirdparty_and_contact_email_array_dolitrackmail(1,$object) as $key=>$value) echo '{ email:"'.$key.'", label:"'.$value.'"},'; ?>
					];	
				</script>
				<script type="text/javascript" src="<?php echo dol_buildpath('/dolitrackmail/js/dolitrackmail.js.php?id='.$object->id.'&el='.$element.$request,1) ?>"></script>
				<style>
					.buttoni {margin: 5px 5px 0 5px;cursor: pointer;width: 18px;height: 18px;float: left;text-align: center;}
					.option img {height:24px;margin-left:4px;margin-right:4px;}
					.option {float:left;}
					.email-div{float:left;}
					.transparency{opacity:0.4;}
					.border-bottom {border-bottom:1px solid #e0e0e0;}
					.legend{border-spacing : 0;border-collapse : collapse;margin-right:8px;margin-left:4px;}
					.legend img{margin-right:2px;}
				</style>
				<script>
					$(document).ready(function() {
						$("html, body").animate({ scrollTop: $(document).height() }, "slow");
						//Redirection vers l'ajax normal si les crédits sont utilisés
						$.ajax({
							url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/active.php'; ?>",
							type: "POST",
							data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
							dataType: "text",
							success: function(html){
								if(html == 0) {
									$.jnotify("<?php echo $langs->trans("notactive"); ?>",'error',6000 );
									setTimeout(function () {
									   window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
									}, 5000);
								} else {
									$.ajax({
										url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/credits.php'; ?>",
										type: "POST",
										data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
										dataType: "text",
										success: function(html){
											if(html == 0) {
												$.jnotify("<?php echo $langs->trans("notenoughcredit"); ?>",'error',6000 );
												// setTimeout(function () {
												   // window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init'; ?>";
												// }, 5000);
											}
										}
									});
								}
							}
						});
					});
				</script>
				<?php
			}
			if($conf->global->CF_DIS_CLASSIC == 1) {
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('a[href*="presend&"]').parent().remove();
					});
				</script>
				<?php
			}
			$conf->global->SOCIETE_DISABLE_CONTACTS = true;
			$conf->global->SOCIETE_ADDRESSES_MANAGEMENT = false;
			$conf->global->SOCIETE_DISABLE_BUILDDOC = false;
		}
	}
}

?>