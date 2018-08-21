<?php
/**
 * cat-tms-patch start
 */

use ILIAS\TMS\Booking;

require_once("Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("Services/TMS/Cancel/classes/ilTMSCancelGUI.php");

/**
 * Displays the TMS cancel for superiors
 *
 * @author Richard Klees <richard.klees@concepts-and-training.de>
 */
class ilTMSSuperiorCancelGUI extends \ilTMSCancelGUI {
	/**
	 * @inheritdocs
	 */
	protected function getComponentClass() {
		return Booking\SuperiorBookingStep::class;
	}

	/**
	 * @inheritdocs
	 */
	protected function setParameter($crs_ref_id, $usr_id) {
		$this->g_ctrl->setParameterByClass("ilTMSSuperiorCancelGUI", "crs_ref_id", $crs_ref_id);
		$this->g_ctrl->setParameterByClass("ilTMSSuperiorCancelGUI", "usr_id", $usr_id);
	}

	/**
	 * @inheritdoc
	 */
	protected function callOnFinish($acting_usr_id, $target_usr_id, $crs_ref_id){
		$event = Booking\Actions::EVENT_SUPERIOR_CANCELED_COURSE;

		require_once("Services/Membership/classes/class.ilWaitingList.php");
		$this->fireBookingEvent($event, $target_usr_id, $crs_ref_id);
	}

	/**
	 * Get the title of the player.
	 *
	 * @return string
	 */
	protected function getPlayerTitle() {
		assert('is_numeric($_GET["usr_id"])');
		$usr_id = (int)$_GET["usr_id"];

		require_once("Services/User/classes/class.ilObjUser.php");
		return sprintf($this->g_lng->txt("canceling_for"), ilObjUser::_lookupFullname($usr_id));
	}

	/**
	 * @inheritdoc
	 */
	protected function getTranslations() {
		$trans = new \ILIAS\TMS\TranslationsImpl(
			array(
				static::TXT_TITLE => $this->getPlayerTitle(),
			),
			parent::getTranslations()
		);
		return $trans;
	}
}

/**
 * cat-tms-patch end
 */
