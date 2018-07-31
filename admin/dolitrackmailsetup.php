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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/dolitrackmail/core/modules/modDolitrackmail.class.php';

$langs->load("admin");
$langs->load("errors");
$langs->load("other");
$langs->load('dolitrackmail@dolitrackmail');

if (!$user->admin) {
    $accessforbidden = accessforbidden("You need to be admin");           
}

global $conf;

$action = GETPOST('action','alpha');
$cf_dis_classic=$conf->global->CF_DIS_CLASSIC;
$cf_al_by_email=$conf->global->CF_AL_BY_EMAIL;
$cf_trck_dl=$conf->global->CF_TRCK_DL;
$cf_view_expiry=$conf->global->CF_VIEW_EXPIRY;
$cf_view_expiry_u=$conf->global->CF_VIEW_EXPIRY_U;
$cf_view_auth=$conf->global->CF_VIEW_AUTH;
$cf_trck_dl_o=$conf->global->CF_TRCK_DL_O;
$cf_trck_dl_n=$conf->global->CF_TRCK_DL_N;
$admin_mail=$conf->global->ADMIN_MAIL;
$admin_phone=$conf->global->ADMIN_PHONE;
$DOLIMAIL_APIKEY=$conf->global->DOLIMAIL_APIKEY;
$module_info = new moddolitrackmail($db);
$current_version = $module_info->getVersion();

//Get premium
$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/getmylevels.php';
$fields = array(
	'apikey' => urlencode($conf->global->DOLIMAIL_APIKEY)
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
$premium = $result['data']['premium'];

switch($action)
{
    case save:
        //general option
		$cf_dis_classic=GETPOST('cf_dis_classic','int');
		$cf_al_by_email=GETPOST('cf_al_by_email','int');
		$cf_trck_dl=GETPOST('cf_trck_dl','int');
		$cf_view_expiry=GETPOST('cf_view_expiry','int');
		$cf_view_expiry_u=GETPOST('cf_view_expiry_u','int');
		$cf_view_auth=GETPOST('cf_view_auth','int');
		$cf_trck_dl_o=GETPOST('cf_trck_dl_o','alpha');
		$cf_trck_dl_n=GETPOST('cf_trck_dl_n','alpha');
		$admin_mail=GETPOST('admin_mail','alpha');
		$admin_phone=GETPOST('admin_phone','alpha');
		
		$res=dolibarr_set_const($db, "CF_DIS_CLASSIC", $cf_dis_classic, 'int', 0, '', $conf->entity);
        if (! $res > 0) $error++; 

		$res=dolibarr_set_const($db, "CF_AL_BY_EMAIL", $cf_al_by_email, 'int', 0, '', $conf->entity);
        if (! $res > 0) $error++;

   		$res=dolibarr_set_const($db, "CF_TRCK_DL", $cf_trck_dl, 'int', 0, '', $conf->entity);
        if (! $res > 0) $error++; 
		
   		$res=dolibarr_set_const($db, "CF_VIEW_EXPIRY", $cf_view_expiry, 'int', 0, '', $conf->entity);
        if (! $res > 0) $error++; 
		
   		$res=dolibarr_set_const($db, "CF_VIEW_EXPIRY_U", $cf_view_expiry_u, 'int', 0, '', $conf->entity);
        if (! $res > 0) $error++;  
		
   		$res=dolibarr_set_const($db, "CF_VIEW_AUTH", $cf_view_auth, 'int', 0, '', $conf->entity);
        if (! $res > 0) $error++;

		$res=dolibarr_set_const($db, "CF_TRCK_DL_O", $cf_trck_dl_o, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;  

		$res=dolibarr_set_const($db, "CF_TRCK_DL_N", $cf_trck_dl_n, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
		
		$res=dolibarr_set_const($db, "ADMIN_MAIL", $admin_mail, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++; 

		$res=dolibarr_set_const($db, "ADMIN_PHONE", $admin_phone, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++; 
    
        // error handling        
        if (! $error) {
			//Lastname, Firstname, Email
			$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/save.php';
			$fields = array(
				'cf_dis_classic' => $cf_dis_classic,
				'cf_al_by_email' => $cf_al_by_email,
				'cf_trck_dl' => $cf_trck_dl,
				'cf_view_expiry' => $cf_view_expiry,
				'cf_view_expiry_u' => $cf_view_expiry_u,
				'cf_view_auth' => $cf_view_auth,
				'cf_trck_dl_o' => $cf_trck_dl_o,
				'cf_trck_dl_n' => $cf_trck_dl_n,
				'admin_mail' => $admin_mail,
				'admin_phone' => $admin_phone,
				'apikey' => $conf->global->DOLIMAIL_APIKEY
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
			if ($info['http_code'] == 201 && $result['success'])
			{
				setEventMessage($langs->trans("SetupSaved"));
			}
			else
			{
				setEventMessage($langs->trans("initializedError",$result['data']['message']), 'errors');
			}
        } else {
            setEventMessage($langs->trans("Error"),'errors');
        }
        break;
    default:
        break;
}


/* 
 *  VIEW
 *  */
//permet d'afficher la structure dolibarr
$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/version.php';
$fields = array(
	'apikey' => $conf->global->DOLIMAIL_APIKEY,
	'version' => $current_version
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
$last_version = curl_exec($ch);
curl_close($ch);
llxHeader("",$langs->trans("dolitrackmailSetup"),'','','','','','',0,0);
if($current_version < $last_version) {
	print '<div class="info hideonsmartphone">
				<img src="'.DOL_URL_ROOT.'/theme/eldy/img/info_black.png" border="0" alt="" title="'.$langs->trans("dolitrackmailNewTitle").'" class="hideonsmartphone">
				'.$langs->trans("dolitrackmailNew").' <a href="https://www.dolistore.com/fr/modules/783-Dolimail.html" target="_blank">Dolistore</a>
		   </div>';
}
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("dolitrackmailSetup"),$linkback,'title_setup');
print "<br/>".$langs->trans("dolitrackmailDescription")."<br/><br/>";
print "<br/>";
print_titre($langs->trans("GeneralOption"));
print "<span style='font-size:11px;'><i><b>".$langs->trans("GeneralOptionAdvice")."</b></i></span></span>";
print "<br/><br/>";
$Form ='<form name="settings" action="?action=save" method="POST" style="margin-bottom:40px;">';
$Form .='<table class="noborder" width="100%">';
$Form .='<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
$Form .=$langs->trans("Description").'</th><th width="200px">'.$langs->trans("Value")."</th></tr>";

// DIS_CLASSIC
$Form .='<tr class="pair"><th align="left">'.$langs->trans("cf_dis_classic_n").'</th>';
$Form .='<th align="left">'.$langs->trans("cf_dis_classic_d").'</th>';
$Form .='<th align="left"><input type="checkbox" name="cf_dis_classic" value="1" ';
$Form .=(($cf_dis_classic=='1')?'checked':'')."></th></tr>";

//ADMIN_MAIL
$Form .='<tr class="impair"><th align="left">'.$langs->trans("dolimail_admin_mail_n").'</th>';
$Form .='<th align="left">'.$langs->trans("dolimail_admin_mail_d").'</th>';
$Form .='<th align="left"><input type="text" name="admin_mail" id="admin_mail" value="'.$admin_mail.'"></th></tr>';

//ADMIN_PHONE
$Form .='<tr class="impair"><th align="left">'.$langs->trans("dolimail_admin_phone_n").'</th>';
$Form .='<th align="left">'.$langs->trans("dolimail_admin_phone_d").'</th>';
$Form .='<th align="left"><input type="text" name="admin_phone" id="admin_phone" value="'.$admin_phone.'"></th></tr>';

// APIKEY
$Form .='<tr class="pair"><th align="left">'.$langs->trans("DOLIMAIL_APIKEY_n").'</th>';
$Form .='<th align="left">'.$langs->trans("DOLIMAIL_APIKEY_d").'</th>';
$Form .='<th align="left"><input type="text" name="DOLIMAIL_APIKEY" value="'.$DOLIMAIL_APIKEY.'"></th></tr>';

$Form.="</table><br>";

print $Form;

print "<br/><br/><br/>";
print_titre($langs->trans("AlarmOption"));
print "<span style='font-size:11px;'><i><b>".$langs->trans("AlarmOptionAdvice")."</b></i></span>";
print "<br/><br/>";
$Form ='<table class="noborder" width="100%">';
$Form .='<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
$Form .=$langs->trans("Description").'</th><th width="200px">'.$langs->trans("Value")."</th></tr>";


// AL_BY_EMAIL
$Form .='<tr class="pair"><th align="left">'.$langs->trans("cf_al_by_email_n").'</th>';
$Form .='<th align="left">'.$langs->trans("cf_al_by_email_d").'</th>';
$Form .='<th align="left"><input type="checkbox" name="cf_al_by_email" value="1" ';
$Form .=(($cf_al_by_email=='1')?'checked':'')."></th></tr>";

$Form.="</table><br>";

print $Form;

print "<br/><br/><br/>";
print_titre($langs->trans("TrackingViewOption"));
print "<span style='font-size:11px;'><i><b>".$langs->trans("TrackingViewOptionAdvice")."</b></i></span>";
print "<br/><br/>";
$Form ='<table class="noborder" width="100%">';
$Form .='<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
$Form .=$langs->trans("Description").'</th><th width="200px">'.$langs->trans("Value")."</th></tr>";

// TRACK_DL
$Form .='<tr class="pair"><th align="left">'.$langs->trans("cf_trck_dl_n").'</th>';
$Form .='<th align="left">'.$langs->trans("cf_trck_dl_d").'</th>';
$Form .='<th align="left"><select name="cf_trck_dl" id="cf_trck_dl">';
$Form .='<option value="0" '.($cf_trck_dl=="0"?"selected":"").'> '.$langs->trans("cf_trck_dl_1").'</option>';
$Form .='<option value="1" '.($cf_trck_dl=="1"?"selected":"").'> '.$langs->trans("cf_trck_dl_2")."</option>";
$Form .="</select></th></tr>";

// VIEW_EXPIRY
$Form .='<tr class="impair"><th align="left">'.$langs->trans("cf_view_expiry_n").'</th>';
$Form .='<th align="left">'.$langs->trans("cf_view_expiry_d").'</th>';
$Form .='<th align="left"><select name="cf_view_expiry">';
$Form .='<option value="10" '.($cf_view_expiry=="10"?"selected":"").'>10</option>';
$Form .='<option value="20" '.($cf_view_expiry=="20"?"selected":"").'>20</option>';
$Form .='<option value="30" '.($cf_view_expiry=="30"?"selected":"").'>30</option>';
$Form .='<option value="40" '.($cf_view_expiry=="40"?"selected":"").'>40</option>';
$Form .='<option value="50" '.($cf_view_expiry=="50"?"selected":"").'>50</option>';
$Form .='<option value="60" '.($cf_view_expiry=="60"?"selected":"").'>60</option>';
$Form .='<option value="70" '.($cf_view_expiry=="70"?"selected":"").'>70</option>';
$Form .='<option value="80" '.($cf_view_expiry=="80"?"selected":"").'>80</option>';
$Form .='<option value="90" '.($cf_view_expiry=="90"?"selected":"").'>90</option>';
$Form .="</select>"."<br/>";
$Form .='<input type="radio" name="cf_view_expiry_u" value="0" ';
$Form .=($cf_view_expiry_u=="0"?"checked":"").'> '.$langs->trans("minutes")."<br/>";
$Form .='<input type="radio" name="cf_view_expiry_u" value="1" ';
$Form .=($cf_view_expiry_u=="1"?"checked":"").'> '.$langs->trans("hours")."<br/>";
$Form .='<input type="radio" name="cf_view_expiry_u" value="2" ';
$Form .=($cf_view_expiry_u=="2"?"checked":"").'> '.$langs->trans("days")."</th></tr>";

// VIEW_AUTH
$Form .='<tr class="pair"><th align="left">'.$langs->trans("cf_view_auth_n").'</th>';
$Form .='<th align="left">'.$langs->trans("cf_view_auth_d").'</th>';
$Form .='<th align="left"><input type="checkbox" name="cf_view_auth" value="1" ';
$Form .=(($cf_view_auth=='1')?'checked':'')."></th></tr>";

$Form.="</table><br>";
print $Form;


print "<br/><br/><br/>";
print_titre($langs->trans("BudgetViewOption"));
print "<span style='font-size:11px;'><i><b>".$langs->trans("BudgetViewOptionAdvice")."</b></i></span>";
print "<br/><br/>";
$Form ='<table class="noborder" width="100%">';

$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/credits.php';
$fields = array(
	'apikey' => urlencode($conf->global->DOLIMAIL_APIKEY)
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
	$credit = $langs->trans("avaiblecredit", $result['data']['total']);
} else {
	$credit = $langs->trans("noavaiblecredit");
}
	
$Form .='<tr><td align="center">'.$credit.' '.$langs->trans("sinceweek").'</td></tr>';	
$Form .= '
	<style>
		.butActionGreen {background:-webkit-linear-gradient(top, #5cb85c 0%, #419641 100%);border-color: #398439;color: #fff;}
		.butActionGreen:hover {background:-webkit-linear-gradient(top, #5cb85c 0%, #419641 100%);border-color: #398439;color: #fff;}
	</style>';

$Form.="</table><br/>";

$Form .='<div class="tabsAction"><input type="submit" class="butAction" value="'.$langs->trans('Save')."\"></div>";
$Form .='</form>';
print $Form;
llxFooter();
?>