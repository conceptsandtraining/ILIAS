<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './Services/MailTemplates/classes/class.ilMailTypeAdapter.php';

/**
 * GEV mail placeholders for courses
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 */
class gevCrsMailTypeAdapter extends ilMailTypeAdapter {
	private $placeholders = null;
	
	public function getCategoryNameLocalized($category_name, $lng) {
		return 'GEVCrs';
	}

	public function getTemplateTypeLocalized($category_name, $template_type, $lng) {
		return 'Generisch';
	}

	protected function getPlaceholders() {
		if ($this->placeholders == null) {
			$this->placeholders = array(
				  array("Mobil"							, "Mobilfunknummer des Teilnehmers")
				, array("OD"							, "OD des Teilnehmers")
				, array("Vermittlernummer"				, "Vermittlernummer des Teilnehmers")
				, array("ADP GEV"						, "ADP-Nummer GEV des Teilnehmers")
				, array("ADP VFS"						, "ADP-Nummer VFS des Teilnehmers")
				, array("Trainingstitel"				, "Titel des Trainings")
				, array("Trainingsuntertitel"			, "Untertitel des Trainings")
				//, array("Lernart"						, "Lernart des Training")
				, array("Trainingstyp"					, "Typ des Training")
				, array("Trainingsthemen"				, "alle Themen des Trainings aus Mehrfachauswahl")
				, array("WP"							, "maximale Anzahl von Bildungspunkte, die für das Training erreicht werden können")
				, array("Methoden"						, "Methoden des Trainings aus Mehrfachauswahl")
				, array("Medien"						, "beim Training verwendete Medien aus Mehrfachauswahl")
				, array("Zielgruppen"					, "Zielgruppen des Trainings aus Mehrfachauswahl")
				, array("Inhalt"						, "Inhalt des Trainings aus Freitext")
				, array("Ziele und Nutzen"				, "Ziele und Nutzen des Trainings aus Freitext")
				, array("ID"							, "Maßnahmennummer des Trainings")
				, array("Startdatum"					, "Beginndatum des Trainings")
				, array("Startzeit"						, "Uhrzeit des Beginns des Trainings")
				, array("Enddatum"						, "Enddatum des Trainings")
				, array("Endzeit"						, "Uhrzeit des Ende des Trainings")
				, array("Zeitplan"						, "Zeitplan des Trainings")
				, array("TV-Name"						, "Name des Themenverantwortlichen des Trainings")
				, array("TV-Telefon"					, "Telefonnummer des Themenverantwortlichen")
				, array("TV-Email"						, "Emailadresse des Themenverantwortlichen")
				, array("Trainingsbetreuer-Vorname"		, "Vorname des Trainingsbetreuers")
				, array("Trainingsbetreuer-Nachname"	, "Nachname des Trainingsbetreuers")
				, array("Trainingsbetreuer-Telefon"		, "Telefonnummer des Trainingsadministrators")
				, array("Trainingsbetreuer-Email"		, "Emailadresse des Trainingsadministrators")
				, array("Trainingsersteller-Vorname"	, "Vorname des Trainingserstellers")
				, array("Trainingsersteller-Nachname"	, "Nachname des Trainingserstellers")
				, array("Trainingsersteller-Telefon"	, "Telefonnummer des Trainingserstellers")
				, array("Trainingsersteller-Email"		, "Emailadresse des Trainingserstellers")
				, array("Trainer-Name"					, "Name des Trainers")
				, array("Trainer-Telefon"				, "Telefonnummer des Trainers")
				, array("Trainer-Email"					, "Email des Trainers")
				, array("Alle Trainer"					, "Alle Trainer des Trainings mit Kontaktinformationen")
				, array("VO-Name"						, "Name des Veranstaltungsorts des Trainings")
				, array("VO-Straße"						, "Straße des Veranstaltungsorts")
				, array("VO-Hausnummer"					, "Hausnummer des Veranstaltungsorts")
				, array("VO-PLZ"						, "Postleitzahl des Veranstaltungsorts")
				, array("VO-Ort"						, "Ort des Veranstaltungsorts")
                                , array("VO-Raumnummer"                                       , "Raumnummer des Veranstaltungsorts")
				, array("VO-Telefon"					, "Telefonnummer des Veranstaltungsorts")
				, array("VO-Internet"					, "Internetseite des Veranstaltungsorts")
				, array("Webinar-Link"					, "Am Training hinterlegter Weblink zum WebEx-Klassenraum")
				, array("Webinar-Passwort"				, "Am Training hinterlegter Weblink für den WebEx-Klassenraum")
				, array("Webinar-Login-Trainer"			, "Am Training hinterlegtes Trainerlogin zum WebEx-Klassenraum")
				, array("Webinar-Passwort-Trainer"		, "Am Training hinterlegtes Trainerpasswort für den WebEx-Klassenraum")
				//, array("CSN-Link"					, "Am Training hinterlegter Weblink zu CSN")
				, array("Hotel-Name"					, "Name des Übernachtungsorts des Trainings")
				, array("Hotel-Straße"					, "Straße des Übernachtungsorts")
				, array("Hotel-Hausnummer"				, "Hausnummer des Übernachtungsorts")
				, array("Hotel-PLZ"						, "Postleitzahl des Übernachtungsorts")
				, array("Hotel-Ort"						, "Ort des Übernachtungsorts")
				, array("Hotel-Telefon"					, "Telefonnummer des Übernachtungsorts")
				, array("Hotel-Email"					, "Emailadresse des Übernachtungsorts")
				, array("Buchender_Vorname"				, "Vorname des Benutzers, der eine Buchung vorgenommen hat")
				, array("Buchender_Nachname"			, "Nachname des Benutzers, der eine Buchung vorgenommen hat")
				, array("Einsatztage"					, "Einsatztage des Trainers beim Training")
				, array("Uebernachtungen"				, "Übernachtungen des Benutzers beim Training")
				, array("Vorabendanreise"				, "Ja, wenn der Benutzer eine Vorabendanreise angegeben hat, sonst Nein.")
				, array("Nachtagabreise"				, "Ja, wenn der Benutzer eine Nachtagabreise angegeben hat, sonst Nein.")
				, array("Liste"							, "Liste der Teilnehmer am Training")
				, array("Organisatorisches"				, "Feld Organisatorisches aus den Trainingsdetails")
				);
		}
	
		return $this->placeholders;
	}

	public function getPlaceholdersLocalized($category_name = '', $template_type = '', $lng = '') {
		$ret = array();

		foreach($this->getPlaceholders() as $item)
		{
			$ret[] = array(
				'placeholder_code'          => strtoupper($item[0]),
				'placeholder_name'          => $item[0],
				'placeholder_description'   => $item[1]
			);
		};

		return $ret;
	}

	public function getPlaceHolderPreviews($category_name = '', $template_type = '', $lng = '') {
		$ret = array();

		foreach($this->getPlaceholders() as $item)
		{
			$ret[] = array(
				'placeholder_code'			=> strtoupper($item[0]),
				'placeholder_content'       => $item[0]
			);
		}

		return $ret;
	}

	public function hasAttachmentsPreview() {
		return false;
	}

	public function getAttachmentsPreview() {

	}
}

?>