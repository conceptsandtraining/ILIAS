<?php

require_once("Services/GEV/Mailing/classes/class.gevCrsAutoMail.php");

class gevMinParticipantsNotReached extends gevCrsAutoMail {
	const DAYS_BEFORE_COURSE_START = 11;
	
	public function getTitle() {
		return "Info Admin";
	}
	
	public function _getDescription() {
		// Mail is send after the 11th day before training is over.
		// Thus we need to subtract, since after the 11th day is on the
		// 10th day.
		return (self::DAYS_BEFORE_COURSE_START - 1)." Days before Begin of Training if Minimum Number of Participants is not reached";
	}
	
	public function getScheduledFor() {
		$date = $this->getCourseUtils()->getStartDate();
		if ($date !== null) {
			$date->increment(IL_CAL_DAY, -1 * self::DAYS_BEFORE_COURSE_START);
		}
		return $date;
	}
	
	public function shouldBeSend() {
		$utils = $this->getCourseUtils();
		if ($utils->getMinParticipants() <= count($utils->getParticipants())) {
			return false;
		}
		
		return parent::shouldBeSend();
	}

	
	public function getTemplateCategory() {
		return "R02";
	}
	
	public function getRecipientUserIDs() {
		return $this->getCourseAdmins();
	}
	
	public function getCC($a_recipient) {
		return array();
	}
}

?>