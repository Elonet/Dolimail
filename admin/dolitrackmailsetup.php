<?php
/*
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

/**
 *  \file       htdocs/admin/project.php
 *  \ingroup    project
 *  \brief      Page to setup project module
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

$action = GETPOST('action','alpha');
$cf_dis_classic=CF_DIS_CLASSIC;
$cf_al_by_sms=CF_AL_BY_SMS;
$cf_al_by_email=CF_AL_BY_EMAIL;
$cf_trck_dl=CF_TRCK_DL;
$cf_view_expiry=CF_VIEW_EXPIRY;
$cf_view_expiry_u=CF_VIEW_EXPIRY_U;
$cf_view_auth=CF_VIEW_AUTH;
$cf_trck_dl_o=CF_TRCK_DL_O;
$cf_trck_dl_n=CF_TRCK_DL_N;
$admin_mail=ADMIN_MAIL;
$DOLIMAIL_APIKEY=DOLIMAIL_APIKEY;
$module_info = new moddolitrackmail($db);
$current_version = $module_info->getVersion();

//Get premium
$url = 'https://dolimail.fr/server/getmylevels.php';
$fields = array(
	'apikey' => urlencode(DOLIMAIL_APIKEY)
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
		if($premium == 2) {
			$cf_al_by_sms=GETPOST('cf_al_by_sms','int');
		} else {
			$cf_al_by_sms=0;
		}
		$cf_al_by_email=GETPOST('cf_al_by_email','int');
		$cf_trck_dl=GETPOST('cf_trck_dl','int');
		if($premium == 1 || $premium == 2) {
			$cf_view_expiry=GETPOST('cf_view_expiry','int');
			$cf_view_expiry_u=GETPOST('cf_view_expiry_u','int');
		} else {
			$cf_view_expiry=10;
			$cf_view_expiry_u=2;
		}
		$cf_view_auth=GETPOST('cf_view_auth','int');
		$cf_trck_dl_o=GETPOST('cf_trck_dl_o','alpha');
		$cf_trck_dl_n=GETPOST('cf_trck_dl_n','alpha');
		$admin_mail=GETPOST('admin_mail','alpha');
		
		$res=dolibarr_set_const($db, "CF_DIS_CLASSIC", $cf_dis_classic, 'int', 0, '', $conf->entity);
        if (! $res > 0) $error++; 

		$res=dolibarr_set_const($db, "CF_AL_BY_SMS", $cf_al_by_sms, 'int', 0, '', $conf->entity);
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
    
        // error handling        
        if (! $error) {
			//Lastname, Firstname, Email
			$url = 'https://dolimail.fr/server/save.php';
			$fields = array(
				'cf_dis_classic' => $cf_dis_classic,
				'cf_al_by_sms' => $cf_al_by_sms,
				'cf_al_by_email' => $cf_al_by_email,
				'cf_trck_dl' => $cf_trck_dl,
				'cf_view_expiry' => $cf_view_expiry,
				'cf_view_expiry_u' => $cf_view_expiry_u,
				'cf_view_auth' => $cf_view_auth,
				'cf_trck_dl_o' => $cf_trck_dl_o,
				'cf_trck_dl_n' => $cf_trck_dl_n,
				'admin_mail' => $admin_mail,
				'apikey' => DOLIMAIL_APIKEY
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
$url = 'https://dolimail.fr/server/version.php';
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL, $url);
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
				'.$langs->trans("dolitrackmailNew").' <a href="https://dolimail.fr/module/" target="_blank">Dolimail.fr</a>
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

//Verification de l'activation du compte
$url = 'https://dolimail.fr/server/active.php';
$fields = array(
	'apikey' => urlencode($DOLIMAIL_APIKEY)
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
if($result['data']['active'] == 0) {
	$Form .='<tr class="pair"><th align="center" colspan="3"><b style="color:red;">'.$langs->trans("dolimail_notactive").'</b><br/><br/><button id="active" type="button" class="butAction" style="background:-webkit-linear-gradient(top, #5cb85c 0%, #419641 100%);border-color: #398439;color: #fff;" target="_blank">'.$langs->trans("dolimail_notactive_button").'</button><b id="active_message" style="color:green;display:none;">'.$langs->trans("dolimail_notactive_message").'</b></th>';
	$Form .= '
				<script>
					$(document).on("click","#active", function() {
						$.ajax({
							url: "'.DOL_URL_ROOT.'/dolitrackmail/ajax/activate.php'.'",
							type: "POST",
							data: {
								apikey: "'.DOLIMAIL_APIKEY.'",
								email: $("#admin_mail").val()
							},
							success: function(data) {
								$("#active").fadeOut(function() {
									$("#active_message").fadeIn();
								});
							}
						});
					});
				</script>';
}

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


if($premium == 1) {
	// AL_BY_SMS
	$Form .='<tr class="impair"><th align="left">'.$langs->trans("cf_al_by_sms_n").'</th>';
	$Form .='<th align="left">'.$langs->trans("cf_al_by_sms_d").'</th>';
	$Form .='<th align="left"><input type="checkbox" name="cf_al_by_sms" value="1" ';
	$Form .=(($cf_al_by_sms=='1')?'checked':'')."></th></tr>";
} else {
	// AL_BY_SMS
	$Form .='<tr class="impair"><th align="left">'.$langs->trans("cf_al_by_sms_n").'</th>';
	$Form .='<th align="left">'.$langs->trans("cf_al_by_sms_d").'<br/><b style="color:red;">'.$langs->trans("cf_al_by_sms_d_disable").'</b></th>';
	$Form .='<th align="left"><input type="checkbox" disabled=disabled name="cf_al_by_sms" value="1" ';
	$Form .=(($cf_al_by_sms=='1')?'checked':'')."></th></tr>";
}


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
$Form .='<option value="2" '.($cf_trck_dl=="2"?"selected":"").'> '.$langs->trans("cf_trck_dl_3")."</option>";
$Form .='<option value="3" '.($cf_trck_dl=="3"?"selected":"").'> '.$langs->trans("cf_trck_dl_4")."</option>";
$Form .="</select></th></tr>";

$Form .="<script>
			$('#cf_trck_dl').on('change',function() {
				var value = this.value
				if(value < 2) {
					$('#tr_trck_dl_o').fadeOut();
					$('#tr_trck_dl_n').fadeOut();
				} else if(value == 2) {
					$('#tr_trck_dl_n').fadeOut(function() {
						$('#tr_trck_dl_o').fadeIn();
					});					
				} else if(value == 3) {
					$('#tr_trck_dl_o').fadeOut(function() {
						$('#tr_trck_dl_n').fadeIn();
					});	
				}
			});
		</script>";

// ONLY_FILE_NAME
$Form .='<tr class="impair"  style="display:none;" id="tr_trck_dl_o"><th align="left">'.$langs->trans("cf_trck_dl_n_o");
$Form .='</th><th align="left">'.$langs->trans("cf_trck_dl_d_o").'</th>';
$Form .='<th align="left"><input type="text" name="cf_trck_dl_o" value="'.$cf_trck_dl_o.'" disabled title="'.$langs->trans("dolitrackmailFeature").'"></th></tr>';

// NOT_FILE_NAME
$Form .='<tr class="impair"  style="display:none;" id="tr_trck_dl_n"><th align="left">'.$langs->trans("cf_trck_dl_n_n");
$Form .='</th><th align="left">'.$langs->trans("cf_trck_dl_d_n").'</th>';
$Form .='<th align="left"><input type="text" name="cf_trck_dl_n" value="'.$cf_trck_dl_n.'" disabled title="'.$langs->trans("dolitrackmailFeature").'"></th></tr>';

if($premium == 1) {
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
} else {
	// VIEW_EXPIRY
	$Form .='<tr class="impair"><th align="left">'.$langs->trans("cf_view_expiry_n").'</th>';
	$Form .='<th align="left">'.$langs->trans("cf_view_expiry_d").'<br/><b style="color:red;">'.$langs->trans("cf_view_expiry_d_disable").'</b></th>';
	$Form .='<th align="left"><select name="cf_view_expiry" disabled="disabled">';
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
	$Form .='<input type="radio" disabled="disabled" name="cf_view_expiry_u" value="0" ';
	$Form .=($cf_view_expiry_u=="0"?"checked":"").'> '.$langs->trans("minutes")."<br/>";
	$Form .='<input type="radio" disabled="disabled" name="cf_view_expiry_u" value="1" ';
	$Form .=($cf_view_expiry_u=="1"?"checked":"").'> '.$langs->trans("hours")."<br/>";
	$Form .='<input type="radio" disabled="disabled" name="cf_view_expiry_u" value="2" ';
	$Form .=($cf_view_expiry_u=="2"?"checked":"").'> '.$langs->trans("days")."</th></tr>";
}

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

	$url = 'https://dolimail.fr/server/credits.php';
	$fields = array(
		'apikey' => urlencode(DOLIMAIL_APIKEY)
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
	if($result['data']['sms'] > 0) {
		$sms = $langs->trans("avaiblecreditsms", $result['data']['sms']);
	} else {
		$sms = $langs->trans("noavaiblecreditsms");
	}
	if($result['data']['one_sms'] > 0) {
		$one_sms = $result['data']['one_sms'];
	} else {
		$one_sms = 0.09;
	}
	if($result['data']['one_email'] > 0) {
		$one_email = $result['data']['one_email'];
	} else {
		$one_email = 0.016;
	}
	if($result['data']['min_buy'] > 0) {
		$min_buy = $result['data']['min_buy'];
	} else {
		$min_buy = 5.99;
	}
	
$Form .='<tr><td align="center">'.$credit.', '.$sms.' '.$langs->trans("sinceweek").'</td></tr>';
if($premium == 0) {
	$Form.='<tr><td align="center"><button id="pro" type="button" class="butAction" style="background:-webkit-linear-gradient(top, #5cb85c 0%, #419641 100%);border-color: #398439;color: #fff;" target="_blank">'.$langs->trans('modepro').'</button></td></tr>';
	$Form.='
		<script>
			$(document).ready(function() {
				$("#pro").click(function() {
					window.open("https://dolimail.fr/offre.php?apikey='.DOLIMAIL_APIKEY.'", "_blank");
					setTimeout(worker, 5000);
				});
				function worker() {
					$.ajax({
						url: "'.DOL_URL_ROOT.'/dolitrackmail/ajax/credits_sup_validation.php'.'",
						type: "POST",
						data: {
							apikey: "'.DOLIMAIL_APIKEY.'"
						},
						success: function(data) {
							if(data == 1) {
								location.reload();
							}
						},
						complete: function() {
							setTimeout(worker, 5000);
						}
					});
				}
			});
		</script>';
} else {
	$Form .= '<tr>
				<th>
					'.$langs->trans('credit_title').'
				</th>
			</tr>
			<tr>
				<td align="center">
					<table>
						<tr>
							<td><label for="name">'.$langs->trans('credit_more_send').'</label></td>
							<td>
								<input type="text" name="email" id="email" value="800">
								<img class="add buttoni" style="opacity:0.8;cursor:pointer;margin-bottom: -3px;" src="'.DOL_URL_ROOT.'/dolitrackmail/img/object_img/plus.png">
								<img class="remove buttoni" style="opacity:0.8;cursor:pointer;margin-bottom: -3px;" src="'.DOL_URL_ROOT.'/dolitrackmail/img/object_img/moins.png"/>
							</td>
						</tr>
						<tr>
							<td><label for="name">SMS</label></td>
							<td>
								<input type="text" name="sms" id="sms" value="45">
								<img class="add buttoni" style="opacity:0.8;cursor:pointer;margin-bottom: -3px;" src="'.DOL_URL_ROOT.'/dolitrackmail/img/object_img/plus.png">
								<img class="remove buttoni" style="opacity:0.8;cursor:pointer;margin-bottom: -3px;" src="'.DOL_URL_ROOT.'/dolitrackmail/img/object_img/moins.png"/>
							</td>
						</tr>
						<tr>
							<td colspan="2" align="center">
								<button type="button" id="buy_pack" class="butAction butActionGreen">'.$langs->trans('addcredit').' (<span id="price"></span>)</button><br/>
								<small><i>('.$langs->trans('minor_buy').' '.$min_buy.' €HT)</i></small>
							</td>
						</tr>
					</table>
				</td>
			</tr>';
	$Form .= '<script>
				function calculate_price() {
					var price = parseFloat($("#email").val()*'.$one_email.'+$("#sms").val()*'.$one_sms.');
					$("#price").html(parseFloat(price).toFixed(2)+" €HT");
					if(parseFloat(price).toFixed(2) < '.$min_buy.') {
						$("#buy_pack").removeClass("butActionGreen").addClass("butActionRefused").attr("title","'.$langs->trans('minor_buy_title').'");
					} else {
						$("#buy_pack").removeClass("butActionRefused").addClass("butActionGreen").attr("title","'.$langs->trans('paypal_title').'");
					}
				}
				function worker() {
					$.ajax({
						url: "'.DOL_URL_ROOT.'/dolitrackmail/ajax/credits_sup_validation.php'.'",
						type: "POST",
						data: {
							apikey: "'.DOLIMAIL_APIKEY.'"
						},
						success: function(data) {
							if(data == 1) {
								location.reload();
							}
						},
						complete: function() {
							setTimeout(worker, 5000);
						}
					});
				}
				$(document).ready(function() {
					calculate_price();
				});
				$(function() {
					$("#buy_pack").on("click",function(e) {
						if($(this).hasClass("butAction")) {
							$.ajax({
								url: "'.DOL_URL_ROOT.'/dolitrackmail/ajax/credits_sup.php'.'",
								type: "POST",
								data: {
									apikey: "'.DOLIMAIL_APIKEY.'",
									sms: $("#sms").val(),
									email: $("#email").val()
								},
								success: function() {
									$.jnotify("'.addslashes($langs->trans("credit_sup_success")).'","success",true,{ remove: function (){} } );
									$("#buy_pack").removeClass("butActionGreen").addClass("butActionRefused").attr("title","'.$langs->trans('wait_title').'");
									setTimeout(worker, 5000);
								}
							});
						}
					});
					$(".buttoni").on("click", function() {
						var $button = $(this);
						var oldValue = $button.parent().find("input").val();

						if ($button.hasClass("add")) {
							if($button.parent().find("input").attr("id") == "email") {
								var newVal = parseFloat(oldValue) + 50;
							} else {
								var newVal = parseFloat(oldValue) + 5;
							}
						} else {
							if (oldValue > 0) {
								if($button.parent().find("input").attr("id") == "email") {
									var newVal = parseFloat(oldValue) - 50;
								} else {
									var newVal = parseFloat(oldValue) - 5;
								}
							} else {
								newVal = 0;
							}
						}
						$button.parent().find("input").val(newVal);
						calculate_price();
					});
					$("#email, #sms").on("change",function() {
						calculate_price();
					});
				});
		</script>';
		
	$Form .= '
		<style>
			.butActionGreen {background:-webkit-linear-gradient(top, #5cb85c 0%, #419641 100%);border-color: #398439;color: #fff;}
			.butActionGreen:hover {background:-webkit-linear-gradient(top, #5cb85c 0%, #419641 100%);border-color: #398439;color: #fff;}
		</style>';
}

$Form.="</table><br/>";

$Form .='<div class="tabsAction"><input type="submit" class="butAction" value="'.$langs->trans('Save')."\"></div>";
$Form .='</form>';
print $Form;
llxFooter();
?>