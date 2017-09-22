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
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
function duree($time) {
	$tabTemps = array("jours" => 86400,"heures" => 3600,"minutes" => 60,"secondes" => 1);
	$result = "";
	foreach($tabTemps as $uniteTemps => $nombreSecondesDansUnite) {
		$$uniteTemps = floor($time/$nombreSecondesDansUnite);
		$time = $time%$nombreSecondesDansUnite;
		if($$uniteTemps > 0 || !empty($result))
		$result .= $$uniteTemps." $uniteTemps ";
	}
	return $result;
}
$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');
$langs->load('dolitrackmail@dolitrackmail');

global $conf;

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');

// Security check
if(!$user->rights->Dolitrackmail->supplier_order->read) {
	accessforbidden();
}


/*
 *	View
 */

llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");

$commande = new CommandeFournisseur($db);
$commande->fetch($id);

$head = ordersupplier_prepare_head($commande);
dol_fiche_head($head, 'tracking', $langs->trans('CustomerOrder'), 0, 'order');

$url = 'https://dolimail.fr/server/api/'.$conf->global->API_VERSION.'/logs.php';
$fields = array(
	'apikey' => $conf->global->DOLIMAIL_APIKEY,
	'id' => $id,
	'type' => 'order_supplier'
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
// print_r($result['data']);
print "<style>
			.no_border {
				border-bottom:0px !important;
			}
		</style>";

print "<table class='liste'>
			<tr class='liste_titre'>
				<th class='liste_titre'>".$langs->trans('d_h_send')."</th>
				<th class='liste_titre'>".$langs->trans('subject_send')."</th>
				<th class='liste_titre'>".$langs->trans('to_send')."</th>
				<th class='liste_titre'>".$langs->trans('d_h_event')."</th>
				<th class='liste_titre'>".$langs->trans('locate_event')."</th>
			</tr>";
if ($info['http_code'] == 200 && $result['success']) {
	foreach($result['data'] as $key=>$data_array) {
		if($key%2 == 0) {
			$class = "pair";
		} else {
			$class = "impair";
		}
		print "<tr class='$class'>
					<td>".dol_print_date($data_array['datesend'],"dayhour","tzuser")."</td>
					<td>".$data_array['subject']."</td>
					<td>".$data_array['target']."</td>";
		$table_event = "";
		$table_date = "";
		foreach($data_array as $numeric=>$data) {
			if(is_int($numeric)) {
				$nb_pages = 0;
				$table_event .= "<tr>";
				if($data['type'] == "mail") {
					$table_event .= "<td class='no_border'>".dol_print_date($data['datec'],"dayhour","tzuser")." : ".$langs->trans('open_mail')."</td>";
				}
				if($data['type'] == "download") {
					$table_event .= "<td class='no_border'>".dol_print_date($data['datec'],"dayhour","tzuser")." : ".$langs->trans('download_attachment')." ".$data['filename']."</td>";
				}
				if($data['type'] == "viewer") {
					if($data['datec'] == $data['tms']) {
						$table_event .= "<td class='no_border'>".dol_print_date($data['datec'],"dayhour","tzuser")." : ".$langs->trans('reading_attachment',$data['filename'])."</td>";
					} else if($data['datec'] < $data['tms']) {
						if(!empty($data['page'])) {
							$pages = $data['page'];
							$text_page = "";
							foreach($pages as $page) {
								$text_page .= "<li>".$langs->trans('page',$page["page"]).duree($page['during'])."</li>";
								$nb_pages++;
							}
							if($data['during'] > 0) {
								$table_event .= "<td class='no_border'>".dol_print_date($data['datec'],"dayhour","tzuser")." : ".$langs->trans('read_attachment',$data['filename'])." ".duree($data['during'])." :<ul style='margin-left:20%;margin-top: 5px;margin-bottom: 5px;'>".$text_page."</ul></td>";
							} else {
								$table_event .= "<td class='no_border'>".dol_print_date($data['datec'],"dayhour","tzuser")." : ".$langs->trans('read_attachment',$data['filename'])." ".duree($data['tms']-$data['datec'])." :<ul style='margin-left:20%;margin-top: 5px;margin-bottom: 5px;'>".$text_page."</ul></td>";
							}
						} else {
							if($data['during'] > 0) {
								$table_event .= "<td class='no_border'>".dol_print_date($data['datec'],"dayhour","tzuser")." : ".$langs->trans('read_attachment',$data['filename'])." ".duree($data['during'])."</td>";
							} else {
								$table_event .= "<td class='no_border'>".dol_print_date($data['datec'],"dayhour","tzuser")." : ".$langs->trans('read_attachment',$data['filename'])." ".duree($data['tms']-$data['datec'])."</td>";
							}
						}
					}
				}
				$table_event .= "</tr>";
			}
			if(is_int($numeric) && $data['coordinate'] != ""){
				$table_date .= "";
				$table_date .= "<tr><td class='no_border'><a href='https://dolimail.fr/map.php?id=".$data['pk']."&api=".$conf->global->DOLIMAIL_APIKEY."' target='_blank' class='tooltip'>".$data['location']."</a></td></tr>";
				if($nb_pages > 0) {
					for($i=0; $i < $nb_pages; $i++) {
						$table_date .= "<tr><td class='no_border'>&nbsp;</td></tr>";
					}
				}
			}
			if(is_int($numeric) && $data['coordinate'] == "") {
				$table_date .= "<tr><td class='no_border'>".$data['location']."</td></tr>";
				if($nb_pages > 0) {
					for($i=0; $i < $nb_pages; $i++) {
						$table_date .= "<tr><td class='no_border'>&nbsp;</td></tr>";
					}
				}
			}
		}
		print "<td><table>".$table_event."</table></td>";
		print "<td valign='top'><table>".$table_date."</table></td>";
		print "</tr>";

	}

}		
print "</table>";
print "<iframe id='tooltip' src='' style='height:400px;display:none;'></iframe>";
print "<script>
			$('.tooltip').hover(function (e) {
				var src = $(this).attr('href')+'&tooltip=1';
				$('#tooltip').attr('src',src);
				var mouseLeft = e.pageX < (document.body.clientWidth / 2);
				var mouseTop = e.pageY < (document.body.clientHeight / 2);

				var css = {};
				if (mouseLeft)
					css.left = e.pageX + 10 + 'px';
				else
					css.left = e.pageX - (7 + $('#tooltip').width()) + 'px';
				if (mouseTop)
					css.top = e.pageY + 10 + 'px';
				else
					css.top = e.pageY - (7 + $('#tooltip').height()) + 'px';

				$('#tooltip').css(css);
				$('#tooltip').stop().fadeIn();
			}, function () {
				$('#tooltip').stop().attr('src','').fadeOut();
			});
		</script>";	
print '</div>';


llxFooter();
$db->close();
?>