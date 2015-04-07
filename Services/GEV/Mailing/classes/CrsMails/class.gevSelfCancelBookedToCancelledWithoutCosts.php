<?php

require_once("Services/GEV/Mailing/classes/class.gevCrsAutoMail.php");

class gevSelfCancelBookedToCancelledWithoutCosts extends gevCrsAutoMail {
	public function getTitle() {
		return "Info Participant";
	}
	
	public function _getDescription() {
		return "Participant (booked) gets Status 'cancelled' by Self-Cancellation";
	}
	
	public function getScheduledFor() {
		return null;
	}
	
	public function getTemplateCategory() {
		return "C01";
	}
	
	public function getRecipientUserIDs() {
		return $this->getCourseCancelledWithoutCostsMembers();
	}
	
	public function getCC($a_recipient) {
		return $this->maybeSuperiorsCC($a_recipient);
	}
	
	public function getMail($a_recipient) {
		if ($this->getAdditionalMailSettings()->getSuppressMails()) {
			return null;
		}
		
		return parent::getMail($a_recipient);
	}
}

?>