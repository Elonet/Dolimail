<?php
/* Copyright (C) 2016 Elonet <contact@elonet.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
	$langs->load('dolitrackmail@dolitrackmail');
	
	$element = GETPOST('el');

	global $conf;
	
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formorder.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
	require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
	require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
	
	//Transmission des variables GET en POST pour conserver le message lors d'un upload
	if(!$_REQUEST['modelselectedtrack'] && GETPOST("mode") != 'init') {
		$_POST['message'] = rawurldecode($_GET['message']);
		$_POST['subject'] = $_GET['subject'];
	}
	
	if ($element == 'commande') {
		$langs->load('orders');
		$langs->load('commercial');
		require_once DOL_DOCUMENT_ROOT . '/core/modules/commande/modules_commande.php';
		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';

		$object = new Commande($db);
		$extrafields = new ExtraFields($db);
		// fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $object->fetch($_GET['id']);
		$trackid = "ord".$_GET['id'];
		
		// Load object
		include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; 
		$object->fetch_projet();
		$soc = new Societe($db);
		$soc->fetch($object->socid);
			
		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals($object->id, $extralabels);
		$object->fetch_thirdparty();
		
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->commande->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
		$file = $fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->thirdparty->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('commercial');
		}

		// Build document if it not exists
		if (! $file || ! is_readable($file)) {
			$result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0) {
				dol_print_error($db, $object->error, $object->errors);
				exit();
			}
			$fileparams = dol_most_recent_file($conf->commande->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file = $fileparams['fullname'];
		}

		$text =  '<div class="clearboth"></div>';
		$text .=  '<br>';
		$text .=  load_fiche_titre($langs->trans('SendOrderByMail'));
		$text .= '<div class="tabs" data-role="controlgroup" data-type="horizontal"><div class="tabBar">';

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
		{
			$formmail->trackid='ord'.$object->id;
		}
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'ord'.$object->id);
		}
		$formmail->withfrom = 1;
		$liste = array();
		$formmail->withto = GETPOST('sendto') ? GETPOST('sendto') : $liste;
		$formmail->withtocc = $liste;
		$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
		if (empty($object->ref_client)) {
			$formmail->withtopic = $outputlangs->trans('SendOrderRef', '__ORDERREF__');
		} else if (! empty($object->ref_client)) {
			$formmail->withtopic = $outputlangs->trans('SendOrderRef', '__ORDERREF__ (__REFCLIENT__)');
		}
		$formmail->withfile = 2;
		$formmail->withbody = 1;
		$formmail->withdeliveryreceipt = 1;
		$formmail->withcancel = 1;
		// Tableau des substitutions
		$formmail->substit ['__ORDERREF__'] = $object->ref;
		$formmail->substit ['__SIGNATURE__'] = $user->signature;
		$formmail->substit ['__REFCLIENT__'] = $object->ref_client;
		$formmail->substit ['__THIRDPARTY_NAME__'] = $object->thirdparty->name;
		$formmail->substit ['__PROJECT_REF__'] = (is_object($object->projet)?$object->projet->ref:'');
		$formmail->substit ['__PERSONALIZED__'] = '';
		$formmail->substit ['__CONTACTCIVNAME__'] = '';

		$custcontact = '';
		$contactarr = array();
		$contactarr = $object->liste_contact(- 1, 'external');

		if (is_array($contactarr) && count($contactarr) > 0)
		{
			foreach ($contactarr as $contact)
			{
				if ($contact['libelle'] == $langs->trans('TypeContact_commande_external_CUSTOMER')) {	// TODO Use code and not label
					$contactstatic = new Contact($db);
					$contactstatic->fetch($contact ['id']);
					$custcontact = $contactstatic->getFullName($langs, 1);
				}
			}

			if (! empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__'] = $custcontact;
			}
		}

		// Tableau des parametres complementaires
		$formmail->param['action'] = 'sendtrack';
		$formmail->param['models'] = 'order_send';
		$formmail->param['models_id']=GETPOST('modelmailselectedtrack','int');
		$formmail->param['orderid'] = $object->id;
		$formmail->param['returnurl'] = DOL_URL_ROOT .'/commande/card.php?id=' . $object->id;

		// Init list of files
		if (GETPOST("mode") == 'init') {
			$formmail->param['models_id']=-1;
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
		}

		// Show form
		$text .=  $formmail->get_form();
		$text .= "</div></div>";
		//  Removes multi-line comments and does not create
		//  a blank line, also treats white spaces/tabs 
		$text = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $text);

		//  Removes single line '//' comments, treats blank characters
		$text = preg_replace("/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/m", '', $text);

		//  Strip blank lines
		$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);
		$text = str_replace("'", '"', $text);
		list($before_textarea, $textarea) = explode("<textarea", $text, 2);
		list($textarea, $after_textarea) = explode("</textarea>", $textarea, 2);
		$text = str_replace(array("\n"), "", addslashes($before_textarea))."<textarea".str_replace(array("\n"), "|", addslashes($textarea))."</textarea>".str_replace(array("\n"), "", addslashes($after_textarea));	}
	
	if ($element == 'propale') {
		$langs->load('propal');
		$langs->load('orders');
		$langs->load('commercial');
		require_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';

		$object = new Propal($db);
		$extrafields = new ExtraFields($db);
		// fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $object->fetch($_GET['id']);
		$trackid = "pro".$_GET['id'];
				
		// Load object
		include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; 
		$object->fetch_projet();
		$soc = new Societe($db);
		$soc->fetch($object->socid);
			
		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals($object->id, $extralabels);
		$object->fetch_thirdparty();

		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->propal->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
		$file = $fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->thirdparty->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('commercial');
		}

		// Build document if it not exists
		if (! $file || ! is_readable($file)) {
			$result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0) {
				dol_print_error($db, $object->error, $object->errors);
				exit();
			}
			$fileparams = dol_most_recent_file($conf->propal->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file = $fileparams['fullname'];
		}

		$text = '<div class="clearboth"></div>';
		$text .=  '<br>';
		$text .=  load_fiche_titre($langs->trans('SendOrderByMail'));
		$text .= '<div class="tabs" data-role="controlgroup" data-type="horizontal"><div class="tabBar">';

		// Create form object
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
		{
			$formmail->trackid='pro'.$object->id;
		}
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'pro'.$object->id);
		}		
		$formmail->withfrom = 1;
		$liste = array();
		$formmail->withto = GETPOST("sendto") ? GETPOST("sendto") : $liste;
		$formmail->withtocc = $liste;
		$formmail->withtoccc = (! empty($conf->global->MAIN_EMAIL_USECCC) ? $conf->global->MAIN_EMAIL_USECCC : false);
		if (empty($object->ref_client)) {
			$formmail->withtopic = $outputlangs->trans('SendPropalRef', '__PROPREF__');
		} else if (! empty($object->ref_client)) {
			$formmail->withtopic = $outputlangs->trans('SendPropalRef', '__PROPREF__ (__REFCLIENT__)');
		}
		$formmail->withfile = 2;
		$formmail->withbody = 1;
		$formmail->withdeliveryreceipt = 1;
		$formmail->withcancel = 1;

		// Tableau des substitutions
		$formmail->substit['__PROPREF__'] = $object->ref;
		$formmail->substit['__SIGNATURE__'] = $user->signature;
		$formmail->substit['__REFCLIENT__'] = $object->ref_client;
		$formmail->substit['__THIRDPARTY_NAME__'] = $object->thirdparty->name;
		$formmail->substit['__PROJECT_REF__'] = (is_object($object->projet)?$object->projet->ref:'');
		$formmail->substit['__PERSONALIZED__'] = '';
		$formmail->substit['__CONTACTCIVNAME__'] = '';

		// Find the good contact adress
		$custcontact = '';
		$contactarr = array();
		$contactarr = $object->liste_contact(- 1, 'external');

		if (is_array($contactarr) && count($contactarr) > 0) {
			foreach ($contactarr as $contact) {
				if ($contact ['libelle'] == $langs->trans('TypeContact_propal_external_CUSTOMER')) {	// TODO Use code and not label
					$contactstatic = new Contact($db);
					$contactstatic->fetch($contact ['id']);
					$custcontact = $contactstatic->getFullName($langs, 1);
				}
			}

			if (! empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__'] = $custcontact;
			}
		}

		// Tableau des parametres complementaires
		$formmail->param['action'] = 'sendtrack';
		$formmail->param['models'] = 'propal_send';
		$formmail->param['models_id']=GETPOST('modelmailselectedtrack','int');
		$formmail->param['id'] = $object->id;
		//COrrection version
		$version = versiondolibarrarray();
		if($version[0] < 4) {
			$formmail->param['returnurl'] = DOL_URL_ROOT . '/comm/propal.php?id=' . $object->id;
		} else {
			$formmail->param['returnurl'] = DOL_URL_ROOT . '/comm/propal/card.php?id=' . $object->id;
		}
		// Init list of files
		if (GETPOST("mode") == 'init') {
			$formmail->param['models_id']=-1;
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
		}

		$text .=  $formmail->get_form();
		$text .= "</div></div>";
		//  Removes multi-line comments and does not create
		//  a blank line, also treats white spaces/tabs 
		$text = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $text);

		//  Removes single line '//' comments, treats blank characters
		$text = preg_replace("/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/m", '', $text);

		//  Strip blank lines
		$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);
		$text = str_replace("'", '"', $text);
		list($before_textarea, $textarea) = explode("<textarea", $text, 2);
		list($textarea, $after_textarea) = explode("</textarea>", $textarea, 2);
		$text = str_replace(array("\n"), "", addslashes($before_textarea))."<textarea".str_replace(array("\n"), "|", addslashes($textarea))."</textarea>".str_replace(array("\n"), "", addslashes($after_textarea));
	}
	
	if ($element == 'facture') {
		$langs->load('bills');
		require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';

		$object = new Facture($db);
		$extrafields = new ExtraFields($db);
		// fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $object->fetch($_GET['id']);
		$trackid = "inv".$_GET['id'];
				
		// Load object
		include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; 
		$object->fetch_projet();
		$soc = new Societe($db);
		$soc->fetch($object->socid);
			
		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals($object->id, $extralabels);
		$object->fetch_thirdparty();

		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
		$file = $fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->thirdparty->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('bills');
		}

		// Build document if it not exists
		if (! $file || ! is_readable($file)) {
			$result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0) {
				dol_print_error($db, $object->error, $object->errors);
				exit();
			}
			$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file = $fileparams['fullname'];
		}

		$text = '<div class="clearboth"></div>';
		$text .=  '<br>';
		$text .=  load_fiche_titre($langs->trans('SendBillByMail'));
		$text .= '<div class="tabs" data-role="controlgroup" data-type="horizontal"><div class="tabBar">';

		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
		{
			$formmail->trackid='inv'.$object->id;
		}
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'inv'.$object->id);
		}		
		$formmail->withfrom = 1;
		$liste = array();
		$formmail->withto = GETPOST('sendto') ? GETPOST('sendto') : $liste; // List suggested for send to
		$formmail->withtocc = $liste; // List suggested for CC
		$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
		if (empty($object->ref_client)) {
			$formmail->withtopic = $outputlangs->transnoentities('SendBillRef', '__REF__');
		} else if (! empty($object->ref_client)) {
			$formmail->withtopic = $outputlangs->transnoentities('SendBillRef', '__REF__ (__REFCLIENT__)');
		}

		$formmail->withfile = 2;
		$formmail->withbody = 1;
		$formmail->withdeliveryreceipt = 1;
		$formmail->withcancel = 1;
		// Tableau des substitutions
		$formmail->substit['__REF__'] = $object->ref;
		$formmail->substit['__SIGNATURE__'] = $user->signature;
		$formmail->substit['__REFCLIENT__'] = $object->ref_client;
		$formmail->substit['__THIRDPARTY_NAME__'] = $object->thirdparty->name;
		$formmail->substit['__PROJECT_REF__'] = (is_object($object->projet)?$object->projet->ref:'');
		$formmail->substit['__PROJECT_NAME__'] = (is_object($object->projet)?$object->projet->title:'');
		$formmail->substit['__PERSONALIZED__'] = '';
		$formmail->substit['__CONTACTCIVNAME__'] = '';

		// Find the good contact adress
		$custcontact = '';
		$contactarr = array();
		$contactarr = $object->liste_contact(- 1, 'external');

		if (is_array($contactarr) && count($contactarr) > 0) {
			foreach ($contactarr as $contact) {
				if ($contact['libelle'] == $langs->trans('TypeContact_facture_external_BILLING')) {	// TODO Use code and not label

					require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

					$contactstatic = new Contact($db);
					$contactstatic->fetch($contact ['id']);
					$custcontact = $contactstatic->getFullName($langs, 1);
				}
			}

			if (! empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__'] = $custcontact;
			}
		}

		// Tableau des parametres complementaires du post
		$formmail->param['action'] = 'sendtrack';
		$formmail->param['models'] = 'facture_send';
		$formmail->param['models_id']=GETPOST('modelmailselectedtrack','int');
		$formmail->param['facid'] = $object->id;
		//COrrection version
		$version = versiondolibarrarray();
		if($version[0] < 6) {
			$formmail->param['returnurl'] = DOL_URL_ROOT . '/compta/facture.php?id=' . $object->id;
		} else {
			$formmail->param['returnurl'] = DOL_URL_ROOT . '/compta/facture/card.php?id=' . $object->id;
		}

		// Init list of files
		if (GETPOST("mode") == 'init') {
			$formmail->param['models_id']=-1;
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
		}

		$text .=  $formmail->get_form();
		$text .= "</div></div>";
		//  Removes multi-line comments and does not create
		//  a blank line, also treats white spaces/tabs 
		$text = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $text);

		//  Removes single line '//' comments, treats blank characters
		$text = preg_replace("/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/m", '', $text);

		//  Strip blank lines
		$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);
		$text = str_replace("'", '"', $text);
		list($before_textarea, $textarea) = explode("<textarea", $text, 2);
		list($textarea, $after_textarea) = explode("</textarea>", $textarea, 2);
		$text = str_replace(array("\n"), "", addslashes($before_textarea))."<textarea".str_replace(array("\n"), "|", addslashes($textarea))."</textarea>".str_replace(array("\n"), "", addslashes($after_textarea));	}
	
	if ($element == 'order_supplier') {
		$langs->load('supplier_proposal');
		$langs->load('commercial');
		require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';

		$object = new CommandeFournisseur($db);
		$extrafields = new ExtraFields($db);
		// fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $object->fetch($_GET['id']);
		$trackid = "sor".$_GET['id'];
				
		// Load object
		include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; 
		$object->fetch_projet();
		$soc = new Societe($db);
		$soc->fetch($object->socid);
			
		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals($object->id, $extralabels);
		$object->fetch_thirdparty();
		
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->fournisseur->commande->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
		$file=$fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->client->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('commercial');
		}

		// Build document if it not exists
		if (! $file || ! is_readable($file))
		{
			$result= $object->generateDocument(GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0)
			{
				dol_print_error($db,$result);
				exit;
			}
			$fileparams = dol_most_recent_file($conf->fournisseur->commande->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file=$fileparams['fullname'];
		}

		$text = '<div class="clearboth"></div>';
		$text .=  '<br>';
		$text .=  load_fiche_titre($langs->trans('SendOrderByMail'));
		$text .= '<div class="tabs" data-role="controlgroup" data-type="horizontal"><div class="tabBar">';

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid   = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
		{
			$formmail->trackid='sor'.$object->id;
		}
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'sor'.$object->id);
		}		
		$formmail->withfrom=1;
		$liste=array();
		$formmail->withto=GETPOST("sendto")?GETPOST("sendto"):$liste;
		$formmail->withtocc=$liste;
		$formmail->withtoccc=(! empty($conf->global->MAIN_EMAIL_USECCC)?$conf->global->MAIN_EMAIL_USECCC:false);
		$formmail->withtopic=$outputlangs->trans('SendOrderRef','__ORDERREF__');
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;

		$object->fetch_projet();
		// Tableau des substitutions
		$formmail->substit['__ORDERREF__']=$object->ref;
		$formmail->substit['__ORDERSUPPLIERREF__']=$object->ref_supplier;
		$formmail->substit['__THIRDPARTY_NAME__'] = $object->thirdparty->name;
		$formmail->substit['__PROJECT_REF__'] = (is_object($object->projet)?$object->projet->ref:'');
		$formmail->substit['__SIGNATURE__']=$user->signature;
		$formmail->substit['__PERSONALIZED__']='';
		$formmail->substit['__CONTACTCIVNAME__']='';

		//Find the good contact adress
		$custcontact='';
		$contactarr=array();
		$contactarr=$object->liste_contact(-1,'external');

		if (is_array($contactarr) && count($contactarr)>0) {
			foreach($contactarr as $contact) {
				if ($contact['libelle']==$langs->trans('TypeContact_order_supplier_external_BILLING')) {
					require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
					$contactstatic=new Contact($db);
					$contactstatic->fetch($contact['id']);
					$custcontact=$contactstatic->getFullName($langs,1);
				}
			}

			if (!empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__']=$custcontact;
			}
		}

		// Tableau des parametres complementaires
		$formmail->param['action']='sendtrack';
		$formmail->param['models']='order_supplier_send';
		$formmail->param['models_id']=GETPOST('modelmailselectedtrack','int');
		$formmail->param['orderid']=$object->id;
		$formmail->param['returnurl']=DOL_URL_ROOT.'/fourn/commande/card.php?id='.$object->id;

		// Init list of files
		if (GETPOST("mode")=='init')
		{
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
		}

		// Show form
		$text .=  $formmail->get_form();
		$text .= "</div></div>";
		//  Removes multi-line comments and does not create
		//  a blank line, also treats white spaces/tabs 
		$text = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $text);

		//  Removes single line '//' comments, treats blank characters
		$text = preg_replace("/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/m", '', $text);

		//  Strip blank lines
		$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);
		$text = str_replace("'", '"', $text);
		list($before_textarea, $textarea) = explode("<textarea", $text, 2);
		list($textarea, $after_textarea) = explode("</textarea>", $textarea, 2);
		$text = str_replace(array("\n"), "", addslashes($before_textarea))."<textarea".str_replace(array("\n"), "|", addslashes($textarea))."</textarea>".str_replace(array("\n"), "", addslashes($after_textarea));	}
	
	if ($element == 'invoice_supplier') {
		$langs->load('bills');
		require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';

		$object = new FactureFournisseur($db);
		$extrafields = new ExtraFields($db);
		// fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $object->fetch($_GET['id']);
		$trackid = "sin".$_GET['id'];
				
		// Load object
		include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; 
		$object->fetch_projet();
		$soc = new Societe($db);
		$soc->fetch($object->socid);
			
		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals($object->id, $extralabels);
		$object->fetch_thirdparty();
		
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2,0,0,$object,'invoice_supplier').$ref, preg_quote($ref,'/').'([^\-])+');
		$file=$fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->client->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('bills');
		}

		// Build document if it not exists
		if (! $file || ! is_readable($file))
		{
			$result = $object->generateDocument(GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0)
			{
				dol_print_error($db,$object->error,$object->errors);
				exit;
			}
			$fileparams = dol_most_recent_file($conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2,0,0,$object,'invoice_supplier').$ref, preg_quote($ref,'/').'([^\-])+');
			$file=$fileparams['fullname'];
		}

		$text = '<div class="clearboth"></div>';
		$text .=  '<br>';
		$text .=  load_fiche_titre($langs->trans('SendBillByMail'));
		$text .= '<div class="tabs" data-role="controlgroup" data-type="horizontal"><div class="tabBar">';

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid   = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
		{
			$formmail->trackid='sin'.$object->id;
		}
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'sin'.$object->id);
		}            
		$formmail->withfrom=1;
		$liste=array();
		$formmail->withto=GETPOST("sendto")?GETPOST("sendto"):$liste;
		$formmail->withtocc=$liste;
		$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
		$formmail->withtopic=$outputlangs->trans('SendBillRef','__REF__');
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;
		// Tableau des substitutions
		$formmail->substit['__REF__']=$object->ref;
		$formmail->substit['__SIGNATURE__']=$user->signature;
		$formmail->substit['__PERSONALIZED__']='';
		$formmail->substit['__CONTACTCIVNAME__']='';

		//Find the good contact adress
		$custcontact='';
		$contactarr=array();
		$contactarr=$object->liste_contact(-1,'external');

		if (is_array($contactarr) && count($contactarr)>0) {
			foreach($contactarr as $contact) {
				if ($contact['libelle']==$langs->trans('TypeContact_invoice_supplier_external_BILLING')) {
					require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
					$contactstatic=new Contact($db);
					$contactstatic->fetch($contact['id']);
					$custcontact=$contactstatic->getFullName($langs,1);
				}
			}

			if (!empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__']=$custcontact;
			}
		}

		// Tableau des parametres complementaires
		$formmail->param['action']='sendtrack';
		$formmail->param['models']='invoice_supplier_send';
		$formmail->param['models_id']=GETPOST('modelmailselectedtrack','int');
		$formmail->param['facid']=$object->id;
		$formmail->param['returnurl']=DOL_URL_ROOT.'/fourn/facture/card.php?id='.$object->id;

		// Init list of files
		if (GETPOST("mode")=='init')
		{
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
		}

		// Show form
		$text .=  $formmail->get_form();
		$text .= "</div></div>";
		//  Removes multi-line comments and does not create
		//  a blank line, also treats white spaces/tabs 
		$text = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $text);

		//  Removes single line '//' comments, treats blank characters
		$text = preg_replace("/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/m", '', $text);

		//  Strip blank lines
		$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);
		$text = str_replace("'", '"', $text);
		list($before_textarea, $textarea) = explode("<textarea", $text, 2);
		list($textarea, $after_textarea) = explode("</textarea>", $textarea, 2);
		$text = str_replace(array("\n"), "", addslashes($before_textarea))."<textarea".str_replace(array("\n"), "|", addslashes($textarea))."</textarea>".str_replace(array("\n"), "", addslashes($after_textarea));	}
	
	if ($element == 'shipping') {
		$langs->load('sendings');
		$langs->load('deliveries');
		require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';

		$object = new Expedition($db);
		$extrafields = new ExtraFields($db);
		// fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $object->fetch($_GET['id']);
		$trackid = "shi".$_GET['id'];
				
		// Load object
		include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; 
		$object->fetch_projet();
		$soc = new Societe($db);
		$soc->fetch($object->socid);
			
		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals($object->id, $extralabels);
		$object->fetch_thirdparty();
		
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->expedition->dir_output . '/sending/' . $ref, preg_quote($ref, '/').'[^\-]+');
		$file=$fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->client->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('sendings');
		}

		// Build document if it not exists
		if (! $file || ! is_readable($file))
		{
			$result = $object->generateDocument(GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0)
			{
				dol_print_error($db,$object->error,$object->errors);
				exit;
			}
			$fileparams = dol_most_recent_file($conf->expedition->dir_output . '/sending/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file=$fileparams['fullname'];
		}

		$text = '<div class="clearboth"></div>';
		$text .=  '<br>';
		$text .=  load_fiche_titre($langs->trans('SendShippingByEMail'));
		$text .= '<div class="tabs" data-role="controlgroup" data-type="horizontal"><div class="tabBar">';

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid   = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
		{
			$formmail->trackid='shi'.$object->id;
		}
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'shi'.$object->id);
		}		
		$formmail->withfrom=1;
		$liste=array();
		$formmail->withto=GETPOST("sendto")?GETPOST("sendto"):$liste;
		$formmail->withtocc=$liste;
		$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
		$formmail->withtopic=$outputlangs->trans('SendShippingRef','__SHIPPINGREF__');
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;
		// Tableau des substitutions
		$formmail->substit['__SHIPPINGREF__']=$object->ref;
		$formmail->substit['__SIGNATURE__']=$user->signature;
		$formmail->substit['__PERSONALIZED__']='';
		$formmail->substit['__CONTACTCIVNAME__']='';

		//Find the good contact adress
		if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled))	{
			$objectsrc=new Commande($db);
			$objectsrc->fetch($object->$typeobject->id);
		}
		if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled))	{
			$objectsrc=new Propal($db);
			$objectsrc->fetch($object->$typeobject->id);
		}
		$custcontact='';
		$contactarr=array();
		if (is_object($objectsrc))    // For the case the shipment was created without orders
		{
    		$contactarr=$objectsrc->liste_contact(-1,'external');
		}

		if (is_array($contactarr) && count($contactarr)>0) {
			foreach($contactarr as $contact) {

				if ($contact['libelle']==$langs->trans('TypeContact_commande_external_CUSTOMER')) {

					require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

					$contactstatic=new Contact($db);
					$contactstatic->fetch($contact['id']);
					$custcontact=$contactstatic->getFullName($langs,1);
				}
			}

			if (!empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__']=$custcontact;
			}
		}

		// Tableau des parametres complementaires
		$formmail->param['action']='sendtrack';
		$formmail->param['models']='shipping_send';
		$formmail->param['models_id']=GETPOST('modelmailselectedtrack','int');
		$formmail->param['shippingid']=$object->id;
		$formmail->param['returnurl']=DOL_URL_ROOT.'/expedition/card.php?id='.$object->id;

		// Init list of files
		if (GETPOST("mode")=='init')
		{
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
		}

		// Show form
		$text .=  $formmail->get_form();
		$text .= "</div></div>";
		//  Removes multi-line comments and does not create
		//  a blank line, also treats white spaces/tabs 
		$text = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $text);

		//  Removes single line '//' comments, treats blank characters
		$text = preg_replace("/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/m", '', $text);

		//  Strip blank lines
		$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);
		$text = str_replace("'", '"', $text);
		list($before_textarea, $textarea) = explode("<textarea", $text, 2);
		list($textarea, $after_textarea) = explode("</textarea>", $textarea, 2);
		$text = str_replace(array("\n"), "", addslashes($before_textarea))."<textarea".str_replace(array("\n"), "|", addslashes($textarea))."</textarea>".str_replace(array("\n"), "", addslashes($after_textarea));	}
	
	if ($element == 'supplier_proposal') {
		$langs->load('supplier_proposal');
		$langs->load('orders');
		$langs->load('commercial');
		require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_proposal/modules_supplier_proposal.php';
		require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/supplier_proposal.lib.php';

		$object = new SupplierProposal($db);
		$extrafields = new ExtraFields($db);
		// fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $object->fetch($_GET['id']);
		$trackid = "spr".$_GET['id'];

		// Load object
		include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; 
		$object->fetch_projet();
		$soc = new Societe($db);
		$soc->fetch($object->socid);
			
		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals($object->id, $extralabels);
		$object->fetch_thirdparty();
		
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->supplier_proposal->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
		$file = $fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->thirdparty->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('commercial');
		}

		// Build document if it not exists
		if (! $file || ! is_readable($file)) {
			$result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0) {
				dol_print_error($db, $object->error, $object->errors);
				exit();
			}
			$fileparams = dol_most_recent_file($conf->supplier_proposal->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file = $fileparams['fullname'];
		}

		$text = '<div class="clearboth"></div>';
		$text .=  '<br>';
		$text .=  load_fiche_titre($langs->trans('SendAskByMail'));
		$text .= '<div class="tabs" data-role="controlgroup" data-type="horizontal"><div class="tabBar">';

		// Create form object
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
		{
			$formmail->trackid='spr'.$object->id;
		}
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'spr'.$object->id);
		}	
		$formmail->withfrom = 1;
		$liste = array();
		$formmail->withto = GETPOST("sendto") ? GETPOST("sendto") : $liste;
		$formmail->withtocc = $liste;
		$formmail->withtoccc = (! empty($conf->global->MAIN_EMAIL_USECCC) ? $conf->global->MAIN_EMAIL_USECCC : false);
		if (empty($object->ref_client)) {
			$formmail->withtopic = $outputlangs->trans('SendPropalRef', $object->ref);
		} else if (! empty($object->ref_client)) {
			$formmail->withtopic = $outputlangs->trans('SendPropalRef', '__ASKREF__ (__REFCLIENT__)');
		}
		
		$formmail->withfile = 2;
		$formmail->withbody = 1;
		$formmail->withdeliveryreceipt = 1;
		$formmail->withcancel = 1;

		// Tableau des substitutions
		$formmail->substit['__SUPPLIERPROPREF__'] = $object->ref;
		$formmail->substit['__SIGNATURE__'] = $user->signature;
		$formmail->substit['__REFCLIENT__'] = $object->ref_client;
		$formmail->substit['__THIRDPARTY_NAME__'] = $object->thirdparty->name;
		$formmail->substit['__PROJECT_REF__'] = (is_object($object->projet)?$object->projet->ref:'');
		$formmail->substit['__PERSONALIZED__'] = '';
		$formmail->substit['__CONTACTCIVNAME__'] = '';

		// Find the good contact adress
		$custcontact = '';
		$contactarr = array();
		$contactarr = $object->liste_contact(- 1, 'external');

		if (is_array($contactarr) && count($contactarr) > 0) {
			foreach ($contactarr as $contact) {
				if ($contact ['libelle'] == $langs->trans('TypeContact_propal_external_CUSTOMER')) {	// TODO Use code and not label
					$contactstatic = new Contact($db);
					$contactstatic->fetch($contact ['id']);
					$custcontact = $contactstatic->getFullName($langs, 1);
				}
			}

			if (! empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__'] = $custcontact;
			}
		}

		// Tableau des parametres complementaires
		$formmail->param['action'] = 'sendtrack';
		$formmail->param['models'] = 'propal_send';
		$formmail->param['models_id']=GETPOST('modelmailselectedtrack','int');
		$formmail->param['id'] = $object->id;
		$formmail->param['returnurl'] = DOL_URL_ROOT . '/supplier_proposal/card.php?id=' . $object->id;
		// Init list of files
		if (GETPOST("mode") == 'init') {
			$formmail->param['models_id']=-1;
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
		}

		$text .=  $formmail->get_form();
		$text .= "</div></div>";
		//  Removes multi-line comments and does not create
		//  a blank line, also treats white spaces/tabs 
		$text = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $text);

		//  Removes single line '//' comments, treats blank characters
		$text = preg_replace("/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/m", '', $text);

		//  Strip blank lines
		$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);
		$text = str_replace("'", '"', $text);
		list($before_textarea, $textarea) = explode("<textarea", $text, 2);
		list($textarea, $after_textarea) = explode("</textarea>", $textarea, 2);
		$text = str_replace(array("\n"), "", addslashes($before_textarea))."<textarea".str_replace(array("\n"), "|", addslashes($textarea))."</textarea>".str_replace(array("\n"), "", addslashes($after_textarea));	}
	
	if ($element == 'societe') {
		$langs->load("companies");
		$langs->load("commercial");
		$langs->load("bills");
		$langs->load("banks");
		$langs->load("users");
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		$object = new Societe($db);
		$extrafields = new ExtraFields($db);
		// fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $object->fetch($_GET['id']);
		$trackid = "thi".$_GET['id'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->thirdparty->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('commercial');
		}

		// Create form object
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
		{
			$formmail->trackid='thi'.$object->id;
		}
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'thi'.$object->id);
		}		
		$formmail->withfrom = 1;
		$formmail->withtopic=1;
		$liste = array();
		foreach ($object->thirdparty_and_contact_email_array(1) as $key=>$value) $liste[$key]=$value;
		$formmail->withto = GETPOST("sendto") ? GETPOST("sendto") : $liste;
		$formmail->withtofree=0;
		$formmail->withtocc=$liste;
		$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;

		// Tableau des substitutions
		$formmail->substit['__THIRDPARTY_NAME__']=$object->name;
		$formmail->substit['__SIGNATURE__']=$user->signature;
		$formmail->substit['__PERSONALIZED__']='';
		$formmail->substit['__CONTACTCIVNAME__']='';

		// Tableau des parametres complementaires
		$formmail->param['action'] = 'sendtrack';
		$formmail->param['models'] = 'thirdparty';
		$formmail->param['models_id']=GETPOST('modelmailselectedtrack','int');
		$formmail->param['id'] = $object->id;
		//COrrection version
		$version = versiondolibarrarray();
		if($version[0] < 6) {
			$formmail->param['returnurl'] = DOL_URL_ROOT . '/societe/soc.php?socid=' . $object->id;
		} else {
			$formmail->param['returnurl'] = DOL_URL_ROOT . '/societe/card.php?socid=' . $object->id;
		}
		// Init list of files
		if (GETPOST("mode") == 'init') {
			$formmail->param['models_id']=-1;
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
		}

		$text .=  $formmail->get_form();
		$text .= "</div></div><br/>";
		//  Removes multi-line comments and does not create
		//  a blank line, also treats white spaces/tabs 
		$text = preg_replace('!^[ \t]*/\*.*?\*/[ \t]*[\r\n]!s', '', $text);

		//  Removes single line '//' comments, treats blank characters
		$text = preg_replace("/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/m", '', $text);

		//  Strip blank lines
		$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);
		$text = str_replace("'", '"', $text);
		list($before_textarea, $textarea) = explode("<textarea", $text, 2);
		list($textarea, $after_textarea) = explode("</textarea>", $textarea, 2);
		$text = str_replace(array("\n"), "", addslashes($before_textarea))."<textarea".str_replace(array("\n"), "|", addslashes($textarea))."</textarea>".str_replace(array("\n"), "", addslashes($after_textarea));
	}
	
	$loading = addslashes("<div id='loading' style='display: none;position: fixed;top: 0%;left: 0%;width: 100%;height: 100%;background-color: black;z-index: 1001;-moz-opacity: 0.8;opacity: .80;filter: alpha(opacity=80);'></div><div id='loading_content' style='display: none;position: fixed;top: 40%;width: 100%;padding: 16px;border-radius:10px;z-index: 1002;overflow: auto;'><center><h1 style='color:white;'><b>".$langs->trans("loading")."</b></h1></center></div>");
	$input_to = addslashes("<input type='text' class='form-control email' name='email' placeholder='example@email.fr'>");
	$path_img = DOL_URL_ROOT.'/dolitrackmail/img/object_img/';
	$title_to_attachment = "<img src='".$path_img."icone_track_file_open.png' class='transparency' align='left' style='margin-right:4px;'/>".$langs->trans("al_o_a")."<br/><br/>";
	$title_to_attachment .="<img src='".$path_img."icone_track_file_open_email.png' align='left' style='margin-right:4px;'/>".$langs->trans("al_e_o_a")."<br/><br/>";
	$title_to_mail = "<img src='".$path_img."icone_alerte_ouverture.png' class='transparency' align='left' style='margin-right:4px;'/>".$langs->trans("al_o_e")."<br/><br/>";
	$title_to_mail .="<img src='".$path_img."icone_alerte_email_ouverture.png' align='left' style='margin-right:4px;'/>".$langs->trans("al_e_o_e")."<br/><br/>";
	$icone_interface = addslashes("<div class='option'><img src='".$path_img."icone_track_file_open.png' class='al_e_o_a_i transparency title_to_attachment' title=''/><img src='".$path_img."icone_alerte_ouverture.png' class='al_e_o_e_i transparency title_to_mail' title=''/><input type='checkbox' class='al_s_o_a' name='al_s_o_a' style='display:none;'/><input type='checkbox' class='al_e_o_a' name='al_e_o_a' style='display:none;'/><input type='checkbox' class='al_e_o_e' name='al_e_o_e' style='display:none;'/><input type='checkbox' class='al_s_o_e' name='al_s_o_e' style='display:none;'/></div>");
	$mobile_valid = true;
	if(!preg_match("/^[0]{1}[6-7]{1}[0-9]{8}$/", $user->user_mobile) && !preg_match("/^\+[1-9]{1}[0-9]{3,14}$/", $user->user_mobile)) {
		$mobile_valid = false;
	}
	
	if($conf->global->CF_AL_BY_EMAIL == 1 && $user->email != "") {
		?>
		$(document).on("click", ".al_e_o_e_i", function() {	
			if($(this).hasClass("disable")) {
				$.jnotify("<?php echo addslashes($langs->trans("nobusiness")); ?>","error",true,{ remove: function (){} } );
			} else {
				if(!$(this).parent().children(".al_e_o_e").is(":checked")) {
					$(this).attr("src","<?php echo $path_img."icone_alerte_email_ouverture.png" ;?>").removeClass("transparency");
					$(this).parent().children(".al_e_o_e").prop("checked", true);
				} else if($(this).parent().children(".al_e_o_e").is(":checked")) {
					$(this).attr("src","<?php echo $path_img."icone_alerte_ouverture.png" ;?>").addClass("transparency");
					$(this).parent().children(".al_e_o_e").prop("checked", false);
				}
			}
		});
		$(document).on("click", ".al_e_o_a_i", function() {	
			if($(this).hasClass("disable")) {
				$.jnotify("<?php echo addslashes($langs->trans("nobusiness")); ?>","error",true,{ remove: function (){} } );
			} else {
				if(!$(this).parent().children(".al_e_o_a").is(":checked")) {
					$(this).attr("src","<?php echo $path_img."icone_track_file_open_email.png" ;?>").removeClass("transparency");
					$(this).parent().children(".al_e_o_a").prop("checked", true);
				} else if($(this).parent().children(".al_e_o_a").is(":checked")) {
					$(this).attr("src","<?php echo $path_img."icone_track_file_open.png" ;?>").addClass("transparency");
					$(this).parent().children(".al_e_o_a").prop("checked", false);
				}
			}
		});
		<?php
	}
	
	$icone_interface_attachment_pdf = addslashes("<input type='checkbox' class='no_dl' name='no_dl' style='display:none;'/><img src='".$path_img."icone_dlok.png' class='no_dl_i transparency title_attachment_dl' height='24' title=''/><input type='checkbox' class='auth' name='auth' style='display:none;'/><img src='".$path_img."icone_lockoff.png' class='auth_i transparency title_attachment_auth' height='24' title=''/><input type='checkbox' class='no_t' name='no_t' style='display:none;'/><img src='".$path_img."icone_trackoff.png' class='no_t_i transparency title_attachment_notk' height='24' title=''/>");
	$icone_interface_attachment_disable = addslashes("<input type='checkbox' class='no_dl' name='no_dl' style='display:none;'/><input type='checkbox' class='auth' name='auth' style='display:none;'/><input type='checkbox' class='no_t' name='no_t' style='display:none;' checked/>");
	$icone_interface_attachment_other = addslashes("<input type='checkbox' class='no_dl' name='no_dl' style='display:none;'/><input type='checkbox' class='auth' name='auth' style='display:none;'/><img src='".$path_img."icone_lockoff.png' class='auth_i transparency title_attachment_auth' height='24' title=''/><input type='checkbox' class='no_t' name='no_t' style='display:none;'/><img src='".$path_img."icone_trackoff.png' class='no_t_i transparency title_attachment_notk' height='24' title=''/>");
	$icone_interface_attachment_pdf_auth = addslashes("<input type='checkbox' class='no_dl' name='no_dl' style='display:none;'/><img src='".$path_img."icone_dlok.png' class='no_dl_i transparency title_attachment_dl' height='24' title=''/><input type='checkbox' class='auth' name='auth' style='display:none;' checked/><input type='checkbox' class='no_t' name='no_t' style='display:none;'/><img src='".$path_img."icone_trackoff.png' class='no_t_i transparency title_attachment_notk' height='24' title=''/>");
	$icone_interface_attachment_other_auth = addslashes("<input type='checkbox' class='no_dl' name='no_dl' style='display:none;'/><input type='checkbox' class='auth' name='auth' style='display:none;' checked/><input type='checkbox' class='no_t' name='no_t' style='display:none;'/><img src='".$path_img."icone_trackoff.png' class='no_t_i transparency title_attachment_notk' height='24' title=''/>");
	$title_attachment_dl = "<img src='".$path_img."icone_dlok.png' class='transparency' height='24' align='left' style='margin-right:4px;'/>".$langs->trans("no_dl_i")."<br/><br/>";
	$title_attachment_dl.= "<img src='".$path_img."icone_nodl.png' class='' height='24' align='left' style='margin-right:4px;'/>".$langs->trans("no_dl_i_2");
	$title_attachment_auth = "<img src='".$path_img."icone_lockoff.png' class='transparency' height='24' align='left' style='margin-right:4px;'/>".$langs->trans("auth_i")."<br/><br/>";
	$title_attachment_auth.= "<img src='".$path_img."icone_lockon.png' class='' height='24' align='left' style='margin-right:4px;'/>".$langs->trans("auth_i_2");
	$title_attachment_notk = "<img src='".$path_img."icone_trackoff.png' class='transparency' height='24' align='left' style='margin-right:4px;'/>".$langs->trans("no_t_i")."<br/><br/>";
	$title_attachment_notk.= "<img src='".$path_img."icone_trackon.png' class='' height='24' align='left' style='margin-right:4px;'/>".$langs->trans("no_t_i_2");
	
?>
$(document).ready(function() {
<?php if($user->email == "") { ?>
	$.jnotify("<?php echo addslashes($langs->trans("nomail",DOL_URL_ROOT."/user/card.php?id=".$user->id."&action=edit")); ?>","error",true,{ remove: function (){} } );
<?php } ?>
	$(".tabsAction").css("display","none");
	$(".fichecenter").css("display","none");
	$div = $('<div class=""></div>');
	$div.attr('id','twittor-panel');	
	$div.append('<?php echo $text; ?>');
	$("body").append('<?php echo $loading; ?>');

	$('#id-right').append($div);
	
	//Gestion du addtrack et du removetrack et des modèles
	$("#addfile").attr("name","addfiletrack").attr("id","addfiletrack");
	$("[name='removedfile']").attr("name","removedfiletrack");
	$("#modelmailselected").attr("name","modelmailselectedtrack").attr("id","modelmailselectedtrack");
	$("#modelselected").attr("name","modelselectedtrack").attr("id","modelselectedtrack");
	
	$(".removedfile").css("height","20px").css("margin-bottom","4px").attr("src","<?php echo $path_img."corbeille.png"; ?>");

	//Changement de l'interface
	$("#sendtocc").parent().parent().fadeOut(); //Hide Cc
	$("#sendto").fadeOut(); //Hide To
	$("#receiver").fadeOut(); //Hide To
	$("#deliveryreceipt").parent().parent().fadeOut(); //Hide AccRecep
	if($("#sendto").length) {
		$("#sendto").parent().append("<input type='hidden' name='option' id='option'/>");
		$("#sendto").parent().parent().find(".classfortooltip").remove();
		$("#sendto").parent().append("<div style='width:100%;display:inline-block;'><img id='add' class='buttoni' style='opacity:0.8;' src='<?php echo $path_img."plus.png"; ?>'/><div class='email-div' id='first'><?php echo $input_to; ?></div><?php echo $icone_interface; ?></div>");
	} else {
		$("#receiver").parent().append("<input type='hidden' name='option' id='option'/>");
		$("#receiver").parent().parent().find(".classfortooltip").remove();
		$("#receiver").parent().append("<div style='width:100%;display:inline-block;'><img id='add' class='buttoni' style='opacity:0.8;' src='<?php echo $path_img."plus.png"; ?>'/><div class='email-div' id='first'><?php echo $input_to; ?></div><?php echo $icone_interface; ?></div>");
		$("#receiver").after('<input size="30" id="sendto" name="sendto" value="" style="display: none;">');
	}
	$("#cancel").attr("type","button");
<?php if($conf->global->CF_TRCK_DL == 0) { ?>
	$("#addfiletrack").parent().children("div").each(function() {
		if($(this).children("img[title='Mime type: pdf']").length) {
		<?php if($conf->global->CF_VIEW_AUTH == 1) { ?>
			$(this).find(".removedfile").before("<?php echo $icone_interface_attachment_pdf_auth; ?>");
		<?php } else { ?>
			$(this).find(".removedfile").before("<?php echo $icone_interface_attachment_pdf; ?>");
		<?php } ?>
		} else {
		<?php if($conf->global->CF_VIEW_AUTH == 1) { ?>
			$(this).find(".removedfile").before("<?php echo $icone_interface_attachment_other_auth; ?>");
		<?php } else { ?>
			$(this).find(".removedfile").before("<?php echo $icone_interface_attachment_other; ?>");
		<?php } ?>
		}
	});
<?php } else { ?>
	$("#addfiletrack").parent().children("div").each(function() {
		$(this).find(".removedfile").before("<?php echo $icone_interface_attachment_disable; ?>");
	});
<?php } ?>
	//Gestion du multiinput To
	var counter = 1;
	var limit = 100;
	$(document).on("click", "#add", function(){
		$.ajax({
			url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/credits.php'; ?>",
			type: "POST",
			data: "apikey=<?php echo $conf->global->DOLIMAIL_APIKEY; ?>",
			dataType: "text",
			success: function(html){
				if(html == 1) {
					if (counter == limit)  {
						alert("max atteint");
					}
					else {
						var newdiv = "<div style='width:100%;display:inline-block;'><img id='remove' class='buttoni' style='opacity:0.8;' src='<?php echo $path_img."moins.png"; ?>'/><div style='margin-bottom:8px;' class='email-div'><?php echo $input_to; ?></div><?php echo $icone_interface; ?></div>";
						$('#first').parent().parent().append(newdiv);
						// var emails = $("#sendto").val().split(",");
						// $(".email:last").val(emails[counter-1]);
						counter++;
						//Gestion de l'autocompletion
						$('.email').on("focus", function(){
							$(this).autocomplete({
								source: availableEmail,
								minLength: 0,
								focus: function( event, ui ) {
									$(this).val( ui.item.email );
									return false;
								},
								select: function( event, ui ) {
									$(this).val( ui.item.email );						 
									return false;
								}
							}).autocomplete( "instance" )._renderItem = function( ul, item ) {
								return $( "<li>" )
								.append( item.label + " &lt;" + item.email + "&gt;" )
								.appendTo( ul );
							};
							$(this).trigger(jQuery.Event("keydown"));
						});
					}
				} else {
					 $.jnotify("<?php echo addslashes($langs->trans("notenoughdest")); ?>",'error',true,{ remove: function (){} } );
				}
			}
		});
	});
	$(document).on('click', '#remove', function(){
		if( counter > 1 ) {
			$(this).parent('div').remove();
			counter--;
		}
		return false;
	});
	//Gestion de l'autocompletion
	$('.email').on("focus", function(){
		$(this).autocomplete({
			source: availableEmail,
			minLength: 0,
			focus: function( event, ui ) {
				$(this).val( ui.item.email );
				return false;
			},
			select: function( event, ui ) {
				$(this).val( ui.item.email );						 
				return false;
			}
		}).autocomplete( "instance" )._renderItem = function( ul, item ) {
			return $( "<li>" )
			.append( item.label + " &lt;" + item.email + "&gt;" )
			.appendTo( ul );
		};
		$(this).trigger(jQuery.Event("keydown"));
	});
	
	//Gestion email sur upload
	// if($("#sendto").val() != "") {
		// var emails = $("#sendto").val().split(",");
		// if(emails.length>1) {
			// for(var i=1;i<emails.length;i++) {
				// $("#add").click();
			// }
		// } else {
			// $(".email:last").val(emails[0]);
		// }
	// }
	
	
	//Gestion des clics images	
	$(document).on("click", ".no_dl_i", function() {	
		if($(this).hasClass("disable")) {
			$.jnotify("<?php echo addslashes($langs->trans("nobusiness")); ?>","error",true,{ remove: function (){} } );
		} else {
			if($(this).hasClass("transparency")) {
				$(this).removeClass("transparency");
				$(this).attr("src","<?php echo $path_img."icone_nodl.png"; ?>");
				$(this).prev().prop("checked", true);
			} else {
				$(this).addClass("transparency");
				$(this).attr("src","<?php echo $path_img."icone_dlok.png"; ?>");
				$(this).prev().prop("checked", false);
			}
		}
	});
	$(document).on("click", ".auth_i", function() {	
		if($(this).hasClass("disable")) {
			$.jnotify("<?php echo addslashes($langs->trans("nobusiness")); ?>","error",true,{ remove: function (){} } );
		} else {
			if($(this).hasClass("transparency")) {
				$(this).removeClass("transparency");
				$(this).attr("src","<?php echo $path_img."icone_lockon.png"; ?>");
				$(this).prev().prop("checked", true);
			} else {
				$(this).addClass("transparency");
				$(this).attr("src","<?php echo $path_img."icone_lockoff.png"; ?>");
				$(this).prev().prop("checked", false);
			}
		}
	});
	$(document).on("click", ".no_t_i", function() {	
		if($(this).hasClass("disable")) {
			$.jnotify("<?php echo addslashes($langs->trans("nobusiness")); ?>","error",true,{ remove: function (){} } );
		} else {
			if($(this).hasClass("transparency")) {
				$(this).removeClass("transparency");
				$(this).attr("src","<?php echo $path_img."icone_trackon.png"; ?>");
				$(this).prev().prop("checked", true);
			} else {
				$(this).addClass("transparency");
				$(this).attr("src","<?php echo $path_img."icone_trackoff.png"; ?>");
				$(this).prev().prop("checked", false);
			}
		}
	});
	
	$(document).on("click", "#cancel", function() {
		window.location.replace("<?php echo $formmail->param['returnurl']; ?>");
	});
  
	//Gestion du submit
	var submitActor = null;
	var $form = $('#mailform');
	var $submitActors = $form.find('input[type=submit]');
	var form_submit = false;
	$submitActors.click(function(event) {
		submitActor = this;
	});
	$("#mailform").on("submit", function(e){
		if (null === submitActor) {
		  // If no actor is explicitly clicked, the browser will
		  // automatically choose the first in source-order
		  // so we do the same here
		  submitActor = $submitActors[0];
		}
		var $remove = $(this).find('[name=removedfiletrack]');
		var $add = $(this).find('[name=addedfile]');
		//Récupération des emails
		var mail = [];
		var option_mail = [];
		$(".email").each(function() {
			if($(this).val() !== "") {
				mail.push($(this).val());
				if($(this).parent().parent().children(".option").children(".al_s_o_a").is(":checked")) {
					var al_s_o_a = 1;
				} else {
					var al_s_o_a = 0;
				}
				if($(this).parent().parent().children(".option").children(".al_e_o_a").is(":checked")) {
					var al_e_o_a = 1;
				} else {
					var al_e_o_a = 0;
				}
				if($(this).parent().parent().children(".option").children(".al_s_o_e").is(":checked")) {
					var al_s_o_e = 1;
				} else {
					var al_s_o_e = 0;
				}
				if($(this).parent().parent().children(".option").children(".al_e_o_e").is(":checked")) {
					var al_e_o_e = 1;
				} else {
					var al_e_o_e = 0;
				}
				option_mail.push($(this).val()+"¤"+al_s_o_a+"¤"+al_e_o_a+"¤"+al_s_o_e+"¤"+al_e_o_e);
			}
		});
		$("#sendto").val(mail.join(","));
		if($add.val() == "" && $remove.val() == "") {
			e.preventDefault();
			//Si le bouton modele est cliqué, ajout d'un variable pour éviter l'envoi d'email
			if(submitActor.name == "modelselectedtrack") {
				$("<input />").attr("type", "hidden").attr("name", "modelselectedtrack").attr("value", "modelselectedtrack").appendTo("#mailform");
			}
			//Si le bouton modele est clique, go submit pour éviter la tentative d'upload
			if (form_submit || submitActor.name != "sendmail") {
				this.submit();
			} else {
				//Ajax envoi des fichiers
				$("#loading").fadeIn();
				$("#loading_content").fadeIn();
				var option_send=[];
				$("div[id*='attachfile_']").each(function() {
					var text = btoa($.trim(($(this).text())));
					if($(this).children(".no_t").is(":checked")) {
						var notrack = 1;
					} else {
						var notrack = 0;
					}
					if($(this).children(".no_dl").is(":checked")) {
						var nodl = 1;
					} else {
						var nodl = 0;
					}
					if($(this).children(".auth").is(":checked")) {
						var auth = 1;
					} else {
						var auth = 0;
					}
					option_send.push(text+"¤"+notrack+"¤"+nodl+"¤"+auth);
				});
				$.ajax({
					url: "<?php echo DOL_URL_ROOT.'/dolitrackmail/ajax/attachment.php'; ?>",
					type: "POST",
					data: {
							id: "<?php echo $object->id; ?>",
							element : "<?php echo $object->element; ?>",
							to : $("#sendto").val(),
							option : option_send.join("|"),
							trackid : "<?php echo $trackid; ?>"
					},
					async: false,
					dataType: "json",
					error: function(){
						return true;
					},
					complete: function(msg){
						//Ajout des liens dans les messages
						var input = $("<input>").attr({"type":"hidden","name":"message_array"}).val(msg.responseText);
						$('#mailform').append(input);
						
						//POST de la liste des fichiers trackés
						var attach=[];
						$("div[id*='attachfile_']").each(function() {
							if(!$(this).children(".no_t").is(":checked")) {
								attach.push(btoa($.trim($(this).text())));
							}
						});
						var attach_input = $("<input>").attr({"type":"hidden","name":"send_array"}).val(attach.join("|"));
						$('#mailform').append(attach_input);
						
						//Ajout des options dans le formulaire
						var input = $("<input>").attr({"type":"hidden","name":"option_mail"}).val(option_mail.join("|"));
						$('#mailform').append(input);						
						
						form_submit = true;
						$("#mailform").submit();
					}
				});
				return false;
			}			
		}
	});	
});
//Retour des breaklines
$(document).ready(function() {
	var message = $("#message").val();
	var result = message.replace(/\|/g, "\n");
	$("#message").val(result);	
	
	//Tooltip
	$(".title_to_attachment").tooltip({
		content: "<?php echo $title_to_attachment; ?>"
	});
	$(".title_to_mail").tooltip({
		content: "<?php echo $title_to_mail; ?>"
	});
	$(".title_attachment_dl").tooltip({
		content: "<?php echo $title_attachment_dl; ?>"
	});
	$(".title_attachment_auth").tooltip({
		content: "<?php echo $title_attachment_auth; ?>"
	});
	$(".title_attachment_notk").tooltip({
		content: "<?php echo $title_attachment_notk; ?>"
	});
});
//Ajout de la légende et déplacement du sujet dans l'interface & ajout du bouton de désactivation des liens & ajout du bouton de désactivation des copies
$(window).load(function() {
	// $(".side-nav").append('<?php echo $legende; ?>');
	$('.border').moveRow(4, 5);
	$('.border tr:last').after('<tr><td><?php echo $langs->trans("desactivatelink"); ?><img src="<?php echo $path_img; ?>info.png" border="0" alt="" title="<?php echo $langs->trans("desactivatelinkdesc"); ?>" class="hideonsmartphone"></td><td><input type="checkbox" name="disable_old" id="disable_old"/></td></tr>');
	$('.border tr:last').after('<tr><td><?php echo $langs->trans("desactivatefollow"); ?><img src="<?php echo $path_img; ?>info.png" border="0" alt="" title="<?php echo $langs->trans("desactivatefollowdesc"); ?>" class="hideonsmartphone"></td><td><input type="checkbox" name="disable_follow" id="disable_follow"/></td></tr>');
});
//Fonction de déplacement de ligne dans un tableau
$.fn.extend({ 
  moveRow: function(oldPosition, newPosition) { 
    return this.each(function(){ 
      var row = $(this).find('tr').eq(oldPosition).remove(); 
      $(this).find('tr').eq(newPosition).before(row); 
    }); 
   } 
 });
