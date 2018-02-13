<?php
/**
 * cat-tms-patch start
 */

use ILIAS\TMS\Booking;

require_once("Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("Services/TMS/Booking/classes/ilTMSBookingGUI.php");

/**
 * Displays the TMS self booking
 *
 * @author Richard Klees <richard.klees@concepts-and-training.de>
 */
class ilTMSSelfBookingGUI extends \ilTMSBookingGUI {
	/**
	 * @inheritdocs
	 */
	protected function getComponentClass() {
		return Booking\SelfBookingStep::class;
	}

	/**
	 * @inheritdocs
	 */
	protected function setParameter($crs_ref_id, $usr_id) {
		$this->g_ctrl->setParameterByClass("ilTMSSelfBookingGUI", "crs_ref_id", $crs_ref_id);
		$this->g_ctrl->setParameterByClass("ilTMSSelfBookingGUI", "usr_id", $usr_id);
	}

	/**
	 * @inheritdocs
	 */
	protected function getPlayerTitle() {
		return $this->g_lng->txt("booking");
	}
}

/**
 * cat-tms-patch end
 */