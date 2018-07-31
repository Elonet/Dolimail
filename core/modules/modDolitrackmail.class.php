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

 
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

/**
 * 	Class to describe module Warranty
 */
class modDolitrackmail extends DolibarrModules {
	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB	$db		Database handler
	 */
	function __construct($db) {
		global $conf, $langs;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 500122;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'Dolitrackmail';
		$this->editor_name = "<b>Dolimail</b>";
		$this->editor_web = "https://dolimail.fr/";

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = "Dolimail";
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = $langs->trans("InfoDescriptionDolitrackmail");
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.3.0';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_DOLITRACKMAIL';
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 0;
		// Name of png file (without png) used for this module.
		// Png file must be in theme/yourtheme/img directory under name object_pictovalue.png.
		$this->picto='img/logo.png@dolitrackmail';
		
		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array("dolitrackmailsetup.php@dolitrackmail");

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();				// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,1);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,7);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("dolitrackmail@dolitrackmail");

		// Constants
		$this->const = array();
		$this->const[0] = array("CF_DIS_CLASSIC","int","0","0"); 
		$this->const[2] = array("CF_AL_BY_EMAIL","int","1","1"); 
		$this->const[3] = array("CF_TRCK_DL","int","0","0"); 
		$this->const[4] = array("CF_VIEW_EXPIRY","int","10","10"); 
		$this->const[5] = array("CF_VIEW_EXPIRY_U","int","2","2"); 
		$this->const[6] = array("CF_VIEW_AUTH","int","0","0"); 
		$this->const[9] = array("DOLIMAIL_APIKEY","chaine","",""); 
		$this->const[10] = array("ADMIN_MAIL","chaine","","");
		$this->const[11] = array("API_VERSION","chaine","","1.3.0"); 
		$this->const[12] = array("ADMIN_PHONE","chaine","",""); 
		
		// hooks
		$this->module_parts = array(
			'triggers' => false,
            //'models' => 1,
			'hooks' => array('ordercard','propalcard','invoicecard','ordersuppliercard','invoicesuppliercard','expeditioncard','supplier_proposalcard','thirdpartycard'),  // Set here all hooks context managed by module
			'css' => array('/dolitrackmail/css/dolitrackmail.css')
            //,'js' => array('/notes/js/notes.js.php')
		);

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;
		$this->rights[$r][0] = 5001221;
		$this->rights[$r][1] = 'View tracking history of commercial proposals';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'propal';
		$this->rights[$r][5] = 'read';
		$r++;	
		$this->rights[$r][0] = 5001222;
		$this->rights[$r][1] = 'View tracking history of customers orders';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'order';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = 5001223;
		$this->rights[$r][1] = 'View tracking history of supplier orders';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supplier_order';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = 5001224;
		$this->rights[$r][1] = 'View tracking history of customer invoices';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = 5001225;
		$this->rights[$r][1] = 'View tracking history of supplier invoices';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supplier_invoice';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = 5001226;
		$this->rights[$r][1] = 'View tracking history of sendings';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delivery';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = 5001227;
		$this->rights[$r][1] = 'View tracking history of commercial margins';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supplier_proposal';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = 5001228;
		$this->rights[$r][1] = 'View tracking history of third parties';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'thirdparty';
		$this->rights[$r][5] = 'read';
		$r++;

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r = 0;

		// New pages on tabs
		$this->tabs = array(
			'propal:+tracking:historique:@dolitrackmail:$user->rights->Dolitrackmail->propal->read:/dolitrackmail/pages/propal.php?id=__ID__',
			'order:+tracking:historique:@dolitrackmail:$user->rights->Dolitrackmail->order->read:/dolitrackmail/pages/commande.php?id=__ID__',
			'supplier_order:+tracking:historique:@dolitrackmail:$user->rights->Dolitrackmail->supplier_order->read:/dolitrackmail/pages/commande_fourn.php?id=__ID__',
			'invoice:+tracking:historique:@dolitrackmail:$user->rights->Dolitrackmail->invoice->read:/dolitrackmail/pages/facture.php?id=__ID__',
			'supplier_invoice:+tracking:historique:@dolitrackmail:$user->rights->Dolitrackmail->supplier_invoice->read:/dolitrackmail/pages/facture_fourn.php?id=__ID__',
			'delivery:+tracking:historique:@dolitrackmail:$user->rights->Dolitrackmail->delivery->read:/dolitrackmail/pages/expedition.php?id=__ID__',
			'supplier_proposal:+tracking:historique:@dolitrackmail:$user->rights->Dolitrackmail->supplier_proposal->read:/dolitrackmail/pages/propal_fourn.php?id=__ID__',
			'thirdparty:+tracking:historique:@dolitrackmail:$user->rights->Dolitrackmail->thirdparty->read:/dolitrackmail/pages/tier.php?id=__ID__'
		);
	}

	/**
     *	Function called when module is enabled.
     *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *	It also creates data directories.
     *
     *	@return     int             1 if OK, 0 if KO
     */
	function init($options='') {
		global $conf, $langs, $user;
		$this->load_tables();
		$langs->load('dolitrackmail@dolitrackmail');
		$error = 0;
		
		/*
		 * API VERSION
		 */
		$api_version = "1.2.9";
		
		if(!empty($user->user_mobile)) {
			$phone = $user->user_mobile;
		} else if(!empty($user->office_phone)) {
			$phone = $user->office_phone;
		} else {
			setEventMessage($langs->trans("initializedError",html_entity_decode($langs->trans("initializedErrorPhone"))), 'errors');
			$error++;
		}
		
		if(empty($user->lastname)) {
			setEventMessage($langs->trans("initializedError",$langs->trans("initializedErrorLastname")), 'errors');
			$error++;
		}
		if(empty($user->firstname)) {
			setEventMessage($langs->trans("initializedError",$langs->trans("initializedErrorFirstname")), 'errors');
			$error++;
		}
		if(empty($user->email)) {
			setEventMessage($langs->trans("initializedError",$langs->trans("initializedErrorEmail")), 'errors');
			$error++;
		}		
		
		
		if(!$error) {
			//Lastname, Firstname, Email
			$url = 'https://dolimail.fr/server/api/'.$api_version.'/apikey.php';
			$fields = array(
				'lastname' => urlencode($user->lastname),
				'firstname' => urlencode($user->firstname),
				'email' => urlencode($user->email),
				'phone' => urlencode($phone),
				'lang' => $langs->defaultlang
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
			if ($info['http_code'] == 201 && $result['success']) {
				dolibarr_set_const($this->db, 'DOLIMAIL_APIKEY', $result['data']['apikey']);
				dolibarr_set_const($this->db, 'CF_DIS_CLASSIC', $result['data']['cf_dis_classic']);
				dolibarr_set_const($this->db, 'CF_AL_BY_EMAIL', $result['data']['cf_al_by_email']);
				dolibarr_set_const($this->db, 'CF_TRCK_DL', $result['data']['cf_trck_dl']);
				dolibarr_set_const($this->db, 'CF_VIEW_EXPIRY', $result['data']['cf_view_expiry']);
				dolibarr_set_const($this->db, 'CF_VIEW_EXPIRY_U', $result['data']['cf_view_expiry_u']);
				dolibarr_set_const($this->db, 'CF_VIEW_AUTH', $result['data']['cf_view_auth']);
				dolibarr_set_const($this->db, 'CF_TRCK_DL_O', $result['data']['cf_trck_dl_o']);
				dolibarr_set_const($this->db, 'CF_TRCK_DL_N', $result['data']['cf_trck_dl_n']);
				dolibarr_set_const($this->db, 'API_VERSION', $api_version);
				if($result['data']['admin_mail'] == "") {
					dolibarr_set_const($this->db, 'ADMIN_MAIL', $user->email);
				} else {
					dolibarr_set_const($this->db, 'ADMIN_MAIL', $result['data']['admin_mail']);
				}
				if($result['data']['admin_phone'] == "") {
					dolibarr_set_const($this->db, 'ADMIN_PHONE', $phone);
				} else {
					dolibarr_set_const($this->db, 'ADMIN_PHONE', $result['data']['admin_phone']);
				}
				if($result['data']['update'] == 0) {
					setEventMessage($langs->trans("initializedSuccessful",$user->email));
				} else if($result['data']['update'] == 1) {
					setEventMessage($langs->trans("initializedSuccessfulUpdate"));
				}
			} else {			
				if($result['data']['arg'] == "lastname") {
					setEventMessage($langs->trans("initializedError",$langs->trans("initializedErrorLastname")), 'errors');
				}
				if($result['data']['arg'] == "firstname") {
					setEventMessage($langs->trans("initializedError",$langs->trans("initializedErrorFirstname")), 'errors');
				}
				if($result['data']['arg'] == "email") {
					setEventMessage($langs->trans("initializedError",$langs->trans("initializedErrorEmail")), 'errors');
				}
				if($result['data']['arg'] == "phone") {
					setEventMessage($langs->trans("initializedError",$langs->trans("initializedErrorPhone")), 'errors');
				}
				if($result['data']['arg'] == "already") {
					setEventMessage($langs->trans("initializedError",html_entity_decode($langs->trans("initializedErrorAlready",$user->email))), 'errors');
				}
				return 0;
			}
		} else {
			return 0;
		}

        $sql = array();
		
        return $this->_init($sql);

  	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted.
	 *
	 *	@return     int             1 if OK, 0 if KO
 	 */
	function remove($options='') {
    	$sql = array();

        //$this->_load_tables('/notes/sql/disable/');

    	return $this->_remove($sql);
  	}


	/**
	 * 	Create tables and keys required by module
	 * 	Files mymodule.sql and mymodule.key.sql with create table and create keys
	 * 	commands must be stored in directory /mymodule/sql/
	 * 	This function is called by this->init.
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
  	function load_tables() {
		global $conf, $langs, $user;
		if($langs->defaultlang == "fr_FR") {
			$this->_load_tables('/dolitrackmail/sql/fr_FR/');
		} else {
			$this->_load_tables('/dolitrackmail/sql/en_EN/');
		}
	}
}

?>
