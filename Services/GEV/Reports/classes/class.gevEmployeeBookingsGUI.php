<?php
/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */#

/**
* Report Employee Bookings
* for Generali
*
* @author	Richard Klees <nhaagen@concepts-and-training.de>
* @version	$Id$
*/

require_once("Services/GEV/Reports/classes/class.catBasicReportGUI.php");
require_once("Services/GEV/Reports/classes/class.catFilter.php");
require_once("Services/CaTUIComponents/classes/class.catTitleGUI.php");
require_once("Services/GEV/Utils/classes/class.gevSettings.php");

class gevEmployeeBookingsGUI extends catBasicReportGUI{
	public function __construct() {
		
		parent::__construct();

		$this->title = catTitleGUI::create()
						->title("gev_employee_booking")
						->subTitle("gev_employee_booking_desc")
						->image("GEV_img/ico-head-emplbookings.png")
						;

		$this->action_img = '<img src="'.ilUtil::getImagePath("gev_action.png").'" />';
		$this->cancel_img = '<img src="'.ilUtil::getImagePath("gev_cancel_action.png").'" />';

		$cancel_deadline_amd_field_id = 
			gevSettings::getInstance()->getAMDFieldId(gevSettings::CRS_AMD_CANCEL_DEADLINE);

		$this->table = catReportTable::create()
						->column("lastname", "lastname")
						->column("firstname", "firstname")
						->column("od_bd", "gev_od_bd")
						->column("org_unit", "gev_org_unit_short")
						->column("custom_id", "gev_training_id")
						->column("title", "title")
						->column("type", "gev_learning_type")
						->column("date", "date")
						->column("venue", "gev_location")
						->column("provider", "gev_provider")
						->column("booking_status", "gev_booking_status")
						->column("action", $this->action_img, true, "", true)
						->template("tpl.gev_employee_bookings_row.html", "Services/GEV/Reports")
						->group_by(array("lastname", "firstname", "od_bd", "org_unit")
								  , "tpl.gev_employee_bookings_group_header.html"
								  , "Services/GEV/Reports"
								  )
						;

		$this->query = catReportQuery::create()
						->select("crs.crs_id")
						->select("usr.user_id")
						->select("usr.firstname")
						->select("usr.lastname")
						->select("crs.custom_id")
						->select("crs.title")
						->select("crs.type")
						->select("crs.begin_date")
						->select("crs.end_date")
						->select("crs.venue")
						->select("crs.provider")
						->select("usr.org_unit_above1")
						->select("usr.org_unit_above2")
						->select("usr.org_unit")
						->select("usrcrs.booking_status")
						->select_raw("(crs.begin_date - INTERVAL amd.value DAY) "
									."as cancel_deadline_date")
						->from("hist_usercoursestatus usrcrs")
						->join("hist_user usr")
							->on("usr.user_id = usrcrs.usr_id AND usr.hist_historic = 0")
						->join("hist_course crs")
							->on("crs.crs_id = usrcrs.crs_id AND crs.hist_historic = 0")
						->join("object_reference oref")
							->on("crs.crs_id = oref.obj_id AND oref.deleted IS NULL")
						->left_join("adv_md_values_int amd")
							->on("amd.obj_id = crs.crs_id "
								."AND amd.field_id = ".$this->db->quote($cancel_deadline_amd_field_id, "integer"))
						->compile()
						;

		$allowed_user_ids = $this->user_utils->getEmployeeIdsForBookingCancellations();
		$this->filter = catFilter::create()
						->static_condition($this->db->in("usr.user_id", $allowed_user_ids, false, "integer"))
						->static_condition("usrcrs.hist_historic = 0")
						->static_condition("usrcrs.booking_status IN ('gebucht', 'auf Warteliste')")
						->static_condition("crs.begin_date > CURDATE()")
						->action($this->ctrl->getLinkTarget($this, "view"))
						->compile()
						;
	}
	
	protected function executeCustomCommand($a_cmd) {
		switch($a_cmd) {
			case "confirmCancelBooking":
			case "finalizeCancellation":
				return $this->$a_cmd();
			default:
				return null;
		}
	}
	
	protected function renderView() {
		if (count($this->getData()) == 0) {
			return $this->lng->txt("gev_no_employee_bookings");
		}
		return parent::renderView();
	}


	protected function transformResultRow($rec) {
		//date
		if( $rec["begin_date"] && $rec["end_date"] 
			&& ($rec["begin_date"] != '0000-00-00' && $rec["end_date"] != '0000-00-00' )
			){
			$start = new ilDate($rec["begin_date"], IL_CAL_DATE);
			$end = new ilDate($rec["end_date"], IL_CAL_DATE);
			$date = '<nobr>' .ilDatePresentation::formatPeriod($start,$end) .'</nobr>';
			//$date = ilDatePresentation::formatPeriod($start,$end);
		} else {
			$date = '-';
		}
		$rec["date"] = $date;
		
		// od_bd
		if ( $rec["org_unit_above2"] == "-empty-") {
			if ($rec["org_unit_above1"] == "-empty-") {
				$rec["od_bd"] = $this->lng->txt("gev_table_no_entry");
			}
			else {
				$rec["od_bd"] = $rec["org_unit_above1"];
			}
		}
		else {
			$rec["od_bd"] = $rec["org_unit_above1"]."/".$rec["org_unit_above2"];
		}
		
		$this->ctrl->setParameter($this, "usr_id", $rec["user_id"]);
		$this->ctrl->setParameter($this, "crs_id", $rec["crs_id"]);
		$now = @date("Y-m-d");
		if ($rec["cancel_deadline_date"] === null
		|| ($rec["type"] != gevSettings::WBT && $rec["cancel_deadline_date"] > $now)) {
			// Code starts here!
			$rec["action"] = "<a href='".$this->ctrl->getLinkTarget($this, "confirmCancelBooking")."'>"
							. $this->cancel_img."</a>";
		}
		else {
			$rec["action"] = "";
		}
		$this->ctrl->setParameter($this, "usr_id", null);
		$this->ctrl->setParameter($this, "crs_id", null);

		return $this->replaceEmpty($rec);
	}
	
	protected function confirmCancelBooking() {
		$this->loadCourseAndTargetUserId();
		$this->checkIfUserIsAllowedToCancelCourseForOtherUser();
		$this->ctrl->setParameter($this, "usr_id", $this->target_user_id);
		return $this->crs_utils->renderCancellationForm($this, $this->target_user_id);
	}
	
	protected function finalizeCancellation() {
		require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");
		
		$this->loadCourseAndTargetUserId();
		$this->checkIfUserIsAllowedToCancelCourseForOtherUser();
		
		$o_status = $this->crs_utils->getBookingStatusOf($this->target_user_id);
		
		if (!$this->crs_utils->cancelBookingOf($this->target_user_id)) {
			$this->log->write("gevEmployeeBookingsGUI::finalizeCancellation: ".
							  "Someone managed to get here but not being able to cancel the course.");
			ilUtil::sendFailure($this->lng->txt("gev_finalize_cancellation_error"), true);
			return $this->render();
		}
		
		$n_status = $this->crs_utils->getBookingStatusOf($this->target_user_id);
		
		if (!in_array($n_status, array(ilCourseBooking::STATUS_CANCELLED_WITH_COSTS
									  , ilCourseBooking::STATUS_CANCELLED_WITHOUT_COSTS))) {
			$this->log->write("gevEmployeeBookingsGUI::finalizeCancellation: User ".$this->target_user_id
							 ." has status ".$n_status." at course ".$this->crs_id." after cancellation.");
			ilUtil::sendFailure($this->lng->txt("gev_finalize_cancellation_error"), true);
			return $this->render();
		}
		
		$user_utils = gevUserUtils::getInstance($this->target_user_id);
		ilUtil::sendSuccess(sprintf( $this->lng->txt("gev_cancellation_other_success")
								   , $this->crs_utils->getTitle()
								   , $user_utils->getFirstname()
								   , $user_utils->getLastname()
								   )
						   );
		
		require_once("Services/GEV/Mailing/classes/class.gevCrsAutoMails.php");
		$automails = new gevCrsAutoMails($this->crs_id);
		
		if ($o_status == ilCourseBooking::STATUS_BOOKED) {
			if ($n_status == ilCourseBooking::STATUS_CANCELLED_WITH_COSTS) {
				throw new ilException("Workflow 'Cancellation with Costs' not implemented.");
			}
			else {
				$automails->send("superior_cancel_booked_to_cancelled_without_costs"
								, array($this->target_user_id));
			}
		}
		else {
			$automails->send("superior_cancel_waiting_to_cancelled_without_costs"
							, array($this->target_user_id));
		}
		
		return $this->render();
	}
	
	protected function loadCourseAndTargetUserId() {
		require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
		
		$this->crs_id = intval($_GET["crs_id"]);
		$this->target_user_id = intval($_GET["usr_id"]);
		$this->crs_utils = gevCourseUtils::getInstance($this->crs_id);
	}
	
	protected function checkIfUserIsAllowedToCancelCourseForOtherUser() {
		if (   !$this->crs_utils->canCancelCourseForOther($this->user->getId(), $this->target_user_id)
			|| $this->crs_utils->isCancelDeadlineExpired()) {
			ilUtil::sendFailure($this->lng->txt("gev_not_allowed_to_cancel_crs_for_other"), true);
			$this->ctrl->redirect($this);
		}
	}
	
	protected function _process_xls_date($val) {
		$val = str_replace('<nobr>', '', $val);
		$val = str_replace('</nobr>', '', $val);
		return $val;
	}
}

?>
