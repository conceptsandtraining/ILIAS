<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './Services/MailTemplates/classes/class.ilMailData.php';
require_once("Services/Calendar/classes/class.ilDatePresentation.php");
require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");


/**
 * Generali mail data for Orgunit Superiors
 *
 * @author Stefan Hecken <stefan.hecken@concepts-and-training.de>
 * @version $Id$
 */

class gevOrguSuperiorMailData extends ilMailData
{
	protected $cache;

	public function __construct($a_recipient, $a_rec_name, $a_gender)
	{
		$this->usr_utils = gevUserUtils::getInstance($a_recipient);
		$this->start_timestamp = null;
		$this->end_timestamp = null;
		$this->end_date_str = "";
		$this->firstname = $a_rec_name["firstname"];
		$this->lastname = $a_rec_name["lastname"];
		$this->gender = $a_gender;
	}

	public function getRecipientMailAddress()
	{
		return null;
	}
	public function getRecipientFullName()
	{
		return null;
	}

	public function getStartTimestamp()
	{
		if ($this->start_timestamp === null) {
			if ($this->end_date_str == "") {
				$this->createEndTimestamp();
			}

			$start_date = new DateTime($this->end_date_str);
			$start_date->sub(date_interval_create_from_date_string('7 Days'));
			$this->start_timestamp = $start_date->getTimestamp();
		}

		return $this->start_timestamp;
	}

	public function getEndTimestamp()
	{
		if ($this->end_timestamp === null) {
			$this->createEndTimestamp();
		}

		return $this->end_timestamp;
	}

	public function createEndTimestamp()
	{
		$timestamp_today = time();
		$this->end_date_str = date("Y-m-d", $timestamp_today);
		$end_date = new DateTime($this->end_date_str." 23:59:59");

		if (date("l", $timestamp_today) == "Monday") {
			$end_date->sub(date_interval_create_from_date_string('1 Day'));
			$this->end_date_str = $end_date->format("Y-m-d");
		}

		$this->end_timestamp = $end_date->getTimestamp();
	}

	public function hasCarbonCopyRecipients()
	{
		return false;
	}

	public function getCarbonCopyRecipients()
	{
		return array();
	}

	public function hasBlindCarbonCopyRecipients()
	{
		return false;
	}

	public function getBlindCarbonCopyRecipients()
	{
		return array();
	}

	public function getPlaceholderLocalized($a_placeholder_code, $a_lng, $a_markup = false)
	{
		if (array_key_exists($a_placeholder_code, $this->cache)) {
			return $this->cache[$a_placeholder_code];
		}

		$val = null;

		switch ($a_placeholder_code) {
			case "SALUTATION":
				if ($this->gender == "m") {
					$val = "Sehr geehrter Herr";
				} else {
					$val = "Sehr geehrte Frau";
				}
				break;
			case "LOGIN":
				$val = $this->login;
				break;
			case "FIRST_NAME":
				$val = $this->firstname;
				break;
			case "LAST_NAME":
				$val = $this->lastname;
				break;
			case "BERICHT":
				$val = $this->getReportDataString();
				break;
		}

		if ($val === null) {
			$val = $a_placeholder_code;
		}

		$this->cache[$a_placeholder_code] = $val;

		if (!$a_markup) {
			$val = strip_tags($val);
		}

		return $val;
	}

	public function hasAttachments()
	{
		return false;
	}
	public function getAttachments($a_lng)
	{
		return array();
	}

	public function getRecipientUserId()
	{
		return null;
	}

	public function deliversStandardPlaceholders()
	{
		return true;
	}

	public function getReportDataString()
	{
		require_once("Services/Calendar/classes/class.ilDate.php");
		require_once("Services/Calendar/classes/class.ilDatePresentation.php");
		require_once("Services/UICore/classes/class.ilTemplateHTMLITX.php");
		require_once("Services/PEAR/lib/HTML/Template/ITX.php");
		require_once("Services/PEAR/lib/HTML/Template/IT.php");

		$user_data = $this->getReportData();

		$show_sections = array
			( "gebucht" => "Buchungen"
			, "kostenfrei_storniert" => "Kostenfreie Stornierungen"
			, "kostenpflichtig_storniert" => "Kostenpflichtige Stornierungen"
			, "teilgenommen" => "Erfolgreiche Teilnahmen"
			, "fehlt_ohne_Absage" => "Unentschuldigtes Fehlen"
			);

		$ret = "";

		$has_entries = false;

		foreach ($show_sections as $key => $title) {
			$section_data = $user_data[$key];
			if (count($section_data) <= 0) {
				continue;
			}

			$tpl = $this->getTemplate();
			$tpl->setCurrentBlock("header");
			$tpl->setVariable("TITLE", $title);
			$tpl->parseCurrentBlock();
			$ret .= $tpl->get();

			foreach ($section_data as $entry_data) {
				$has_entries = true;
				$tpl = $this->getTemplate();
				$tpl->setCurrentBlock("entry");
				$tpl->setVariable("USR_FIRSTNAME", $entry_data["firstname"]);
				$tpl->setVariable("USR_LASTNAME", $entry_data["lastname"]);
				$tpl->setVariable("CRS_TITLE", $entry_data["title"]);
				$tpl->setVariable("CRS_TYPE", $this->mergeEduProgramAndType($entry_data["edu_program"], $entry_data["type"]));

				if ($begin_date != "0000-00-00") {
					$begin_date = new ilDate($entry_data["begin_date"], IL_CAL_DATE);
				} else {
					$begin_date = null;
				}

				if ($end_date != "0000-00-00") {
					$end_date = new ilDate($entry_data["end_date"], IL_CAL_DATE);
				} else {
					if ($begin_date !== null) {
						$end_date = $begin_date;
					} else {
						$end_date = null;
					}
				}

				if ($end_date !== null && $begin_date !== null && $entry_data["type"] !== "Selbstlernkurs") {
					$date = ilDatePresentation::formatPeriod($begin_date, $end_date);
					$tpl->setVariable("CRS_DATE", ", $date");
				}

				if ((!in_array($entry_data["type"], array("Selbstlernkurs", "Webinar", "Virtuelles Training"))) && $key == "gebucht") {
					$tpl->setCurrentBlock("overnights");
					$tpl->setVariable("OVERNIGHTS_CAPTION", "Übernachtungen");
					$tpl->setVariable("USR_OVERNIGHTS_AMOUNT", $entry_data["overnights"]);
					$tpl->setVariable("PREARRIVAL_CAPTION", "Vorabendanreise");
					$tpl->setVariable("USR_HAS_PREARRIVAL", $entry_data["prearrival"] ? "Ja" : "Nein");
					$tpl->setVariable("POSTDEPARTURE_CAPTION", "Abreise am Folgetag");
					$tpl->setVariable("USR_HAS_POSTDEPARTURE", $entry_data["postdeparture"] ? "Ja" : "Nein");
					$tpl->parseCurrentBlock();
				}
				$tpl->parseCurrentBlock();
				$ret .= $tpl->get();
			}
		}

		if (!$has_entries) {
			throw new Exception("There is no content in the weekly report for the superior.");
		}

		return $ret;
	}

	// This implements the requirement to output the type of the program and
	// the edu program together (#1689), e.g. "Präsenztraining" from "zentrales
	// Training" should be displayed as "zentrales Präsenztraining"
	public function mergeEduProgramAndType($a_edu_program, $a_type)
	{
		if (!in_array($a_type, array("Webinar", "Präsenztraining"))) {
			return $a_type;
		}

		switch ($a_edu_program) {
			case "zentrales Training":
				return "zentrales $a_type";
			case "dezentrales Training":
				return "dezentrales $a_type";
			case "Grundausbildung":
				return $a_type." (Grundausbildung)";
			case "Azubi-Ausbildung":
				return $a_type." (Azubi-Ausbildung)";
			default:
				return $a_type;
		}
	}

	public function getReportData()
	{
		return $this->usr_utils->getUserDataForSuperiorWeeklyReport($this->getStartTimestamp(), $this->getEndTimestamp());
	}

	public function getTemplate()
	{
		require_once("Services/UICore/classes/class.ilTemplate.php");
		return new ilTemplate("tpl.superior_mail.html", true, true, "Services/GEV/Mailing");
	}
}
