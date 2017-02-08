<?php

require_once("./Services/EventHandling/classes/class.ilEventHookPlugin.php");
		require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");

class ilGEVMailingPlugin extends ilEventHookPlugin
{
	const SELF_BOOKING = 0;
	const ADMIN_BOOKING = 1;
	const SUPERIOR_BOOKING = 2;

	final public function getPluginName()
	{
		return "GEVMailing";
	}

	final public function handleEvent($a_component, $a_event, $a_parameter)
	{
		switch ($a_component) {
			case "Services/CourseBooking":
				return $this->bookingEvent($a_event, $a_parameter);
			case "Services/ParticipationStatus":
				return $this->participationStatusEvent($a_event, $a_parameter);
			case "Modules/Course":
				return $this->courseEvent($a_event, $a_parameter);
			default:
				break;
		}
	}

	protected function bookingEvent($a_event, $a_parameter)
	{
		if ($a_event !== "setStatus") {
			return;
		}
		require_once("Services/CourseBooking/classes/class.ilCourseBooking.php");
		require_once("Services/GEV/Mailing/classes/class.gevCrsAutoMails.php");

		$os = $a_parameter["old_status"];
		$ns = $a_parameter["new_status"];
		$usr_id = intval($a_parameter["user_id"]);
		$crs_id = intval($a_parameter["crs_obj_id"]);
		$bt = $this->getBookingType($usr_id, $crs_id);
		$mails = new gevCrsAutoMails($crs_id);

		require_once "Services/GEV/Utils/classes/class.gevCourseUtils.php";
		$crs_utils = gevCourseUtils::getInstance($crs_id);

		if ($os == ilCourseBooking::STATUS_WAITING && $ns == ilCourseBooking::STATUS_BOOKED) {
			if (!$crs_utils->isSelflearning() && !$crs_utils->isCoaching()) {
				$mails->send("participant_waiting_to_booked", array($usr_id));
			}
			$mails->send("invitation", array($usr_id));
		}
	}

	protected function getBookingType($a_user_id, $a_crs_id)
	{
		global $ilUser;

		if ($ilUser->getId() == $a_user_id) {
			return self::SELF_BOOKING;
		}
		require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
		if (in_array($ilUser->getId(), gevCourseUtils::getInstance($a_crs_id)->getAdmins())) {
			return self::ADMIN_BOOKING;
		}

		return self::ADMIN_BOOKING;
	}

	protected function participationStatusEvent($a_event, $a_parameter)
	{
		if ($a_event == "deleteStatus") {
			require_once("Services/GEV/Mailing/classes/class.gevDeferredMails.php");
			gevDeferredMails::getInstance()->removeDeferredMails(array($a_parameter["crs_obj_id"]), array( "participant_successfull"
																	   , "na_successfull"
																	   , "participant_absent_excused"
																	   , "participant_absent_not_excused"
																	   ), array($a_parameter["user_id"]));
			return;
		}

		if ($a_event != "setStatusAndPoints") {
			return;
		}

		require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");
		require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
		require_once("Services/ParticipationStatus/classes/class.ilParticipationStatus.php");
		require_once("Services/GEV/Mailing/classes/class.gevCrsAutoMails.php");

		$usr_id = intval($a_parameter["user_id"]);
		$crs_id = intval($a_parameter["crs_obj_id"]);
		$crs_utils = gevCourseUtils::getInstance($crs_id);
		$usr_utils = gevUserUtils::getInstance($usr_id);
		$status = $crs_utils->getParticipationStatusOf($usr_id);
		$type = $crs_utils->getType();
		$mails = new gevCrsAutoMails($crs_id);

		if ($type == "Webinar" || $type == "Spezialistenschulung Webinar") {
			return;
		}

		if ($status == ilParticipationStatus::STATUS_SUCCESSFUL) {
			$mails->sendDeferred("participant_successfull", array($usr_id));
			if (gevUserUtils::getInstance($usr_id)->isNA()) {
				$mails->sendDeferred("na_successfull", array($usr_id));
			}
		} elseif ($status == ilParticipationStatus::STATUS_ABSENT_EXCUSED) {
			$mails->sendDeferred("participant_absent_excused", array($usr_id));
		} elseif ($status == ilParticipationStatus::STATUS_ABSENT_NOT_EXCUSED) {
			$mails->sendDeferred("participant_absent_not_excused", array($usr_id));
		}
	}

	protected function courseEvent($a_event, $a_parameter)
	{
		if ($a_event != "addParticipant"
		   && $a_event != "deleteParticipant") {
			return;
		}

		require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
		require_once("Services/ParticipationStatus/classes/class.ilParticipationStatus.php");
		require_once("Services/GEV/Mailing/classes/class.gevCrsAutoMails.php");

		$usr_id = intval($a_parameter["usr_id"]);
		$crs_id = intval($a_parameter["obj_id"]);
		$role_id = $a_parameter["role_id"];
		$crs_utils = gevCourseUtils::getInstance($crs_id);

		if ($role_id != $crs_utils->getCourse()->getDefaultTutorRole()
		&&  $role_id != IL_CRS_TUTOR) {
			return;
		}

		$mails = new gevCrsAutoMails($crs_id);

		if ($a_event == "addParticipant") {
			$mails->sendDeferred("trainer_added", array($usr_id));
		} elseif ($a_event == "deleteParticipant") {
			require_once("Services/GEV/Mailing/classes/class.gevDeferredMails.php");
			$deferredMails = gevDeferredMails::getInstance();
			$send_remove = $deferredMails->deferredMailNeedsToBeSend($crs_id, "trainer_removed", $usr_id);
			$deferredMails->removeDeferredMails(array($crs_id), array( "trainer_added"
																	   , "invitation"
																	   ), array($usr_id));
			if ($send_remove) {
				$mails->send("trainer_removed", array($usr_id));
			}
		}
	}
}
