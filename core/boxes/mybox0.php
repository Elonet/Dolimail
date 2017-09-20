<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * \file    core/boxes/mybox.php
 * \ingroup mymodule
 * \brief   Example box definition.
 *
 * Put detailed description here.
 */
/** Includes */
include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";
/**
 * Class to manage the box
 *
 * Warning: for the box to be detected correctly by dolibarr,
 * the filename should be the lowercase classname
 */
class MyBox0 extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = "mybox";
	/**
	 * @var string Box icon (in configuration page)
	 * Automatically calls the icon named with the corresponding "object_" prefix
	 */
	public $boximg = "dolitrackmail@dolitrackmail";
	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel;
	/**
	 * @var string[] Module dependencies
	 */
	public $depends = array('dolitrackmail');
	/**
	 * @var DoliDb Database handler
	 */
	public $db;
	/**
	 * @var mixed More parameters
	 */
	public $param;
	/**
	 * @var array Header informations. Usually created at runtime by loadBox().
	 */
	public $info_box_head = array();
	/**
	 * @var array Contents informations. Usually created at runtime by loadBox().
	 */
	public $info_box_contents = array();
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		global $langs;
		$langs->load("boxes");
		$langs->load('dolitrackmail@dolitrackmail');
		parent::__construct($db, $param);
		$this->boxlabel = $langs->transnoentitiesnoconv("dolitrackmail");
		$this->param = $param;
	}
	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @param int $max Maximum number of records to load
	 * @return void
	 */
	public function loadBox($max = 5)
	{
		global $langs,$conf;
		// Use configuration value for max lines count
		$this->max = $max;
		//include_once DOL_DOCUMENT_ROOT . "/mymodule/class/mymodule.class.php";
		// Populate the head at runtime
		$text = $langs->trans("MyBoxDescription", $max);
		$this->info_box_head = array(
			// Title text
			'text' => $text,
			// Add a link
			'sublink' => 'http://example.com',
			// Sublink icon placed after the text
			'subpicto' => 'object_mymodule@dolitrackmail',
			// Sublink icon HTML alt text
			'subtext' => '',
			// Sublink HTML target
			'target' => '',
			// HTML class attached to the picto and link
			'subclass' => 'center',
			// Limit and truncate with "…" the displayed text lenght, 0 = disabled
			'limit' => 0,
			// Adds translated " (Graph)" to a hidden form value's input (?)
			'graph' => false
		);
		$url = 'https://dolimail.fr/server/logs.php';
		$fields = array(
			'apikey' => $conf->global->DOLIMAIL_APIKEY,
			'id' => 1,
			'type' => 'commande'
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
		// print_r($result);
		
		$line = 0;
		$this->info_box_contents[$line][] = array(
			'th' => 'class="liste_titre"',
			'text' => "Date/Heure d'envoi",
			'asis' => 1,
		);
		$this->info_box_contents[$line][] = array(
			'th' => 'class="liste_titre"',
			'text' => "Date/Heure d'envoi",
			'asis' => 1,
		);
		$this->info_box_contents[$line][] = array(
			'th' => 'class="liste_titre"',
			'text' => "Date/Heure d'envoi",
			'asis' => 1,
		);
		$this->info_box_contents[$line][] = array(
			'th' => 'class="liste_titre"',
			'text' => "Date/Heure d'envoi",
			'asis' => 1,
		);
	}
	/**
	 * Method to show box. Called by Dolibarr eatch time it wants to display the box.
	 *
	 * @param array $head Array with properties of box title
	 * @param array $contents Array with properties of box lines
	 * @return void
	 */
	public function showBox($head = null, $contents = null)
	{
		// You may make your own code here…
		// … or use the parent's class function using the provided head and contents templates
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}