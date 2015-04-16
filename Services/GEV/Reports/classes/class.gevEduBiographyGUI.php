<?php

require_once("Services/GEV/Reports/classes/class.catBasicReportGUI.php");
require_once("Services/CaTUIComponents/classes/class.catLegendGUI.php");

class gevEduBiographyGUI extends catBasicReportGUI {
	public function __construct() {
		parent::__construct();

		require_once("Services/CaTUIComponents/classes/class.catTitleGUI.php");

		$this->target_user_id = $_POST["target_user_id"]
							  ? $_POST["target_user_id"]
							  : ( $_GET["target_user_id"]
							  	? $_GET["target_user_id"]
							  	: $this->user->getId()
							  	);
		$this->target_user_utils = gevUserUtils::getInstance($this->target_user_id);

		if ($this->user->getId() == $this->target_user_id) {
			$this->title = catTitleGUI::create()
							->title("gev_my_edu_bio")
							->subTitle("gev_my_edu_bio_desc")
							->image("GEV_img/ico-head-edubio.png")
							;
		}
		else {
			$this->title = catTitleGUI::create()
							->title(sprintf($this->lng->txt("gev_others_edu_bio"), $this->target_user_utils->getFullName()))
							->subTitle(sprintf($this->lng->txt("gev_others_edu_bio_desc"), $this->target_user_utils->getFullName()))
							->image("GEV_img/ico-head-edubio.png")
							->useLng(false)
							;
		}
		
		$this->get_cert_img = '<img src="'.ilUtil::getImagePath("GEV_img/ico-key-get_cert.png").'" />';
		$this->success_img  = '<img src="'.ilUtil::getImagePath("GEV_img/ico-key-green.png").'" />';
		$this->in_progress_img = '<img src="'.ilUtil::getImagePath("GEV_img/ico-key-orange.png").'" />';
		$this->failed_img = '<img src="'.ilUtil::getImagePath("GEV_img/ico-key-red.png").'" />';
		$this->action_img = '<img src="'.ilUtil::getImagePath("gev_action.png").'" />';
		
		$this->title->legend(catLegendGUI::create()
						->item($this->get_cert_img, "gev_get_certificate")
						->item($this->success_img, "gev_passed")
						->item($this->in_progress_img, "gev_in_progress")
						->item($this->failed_img, "gev_failed")
						);
		
		$this->table = catReportTable::create()
						->column("custom_id", "gev_training_id")
						->column("title", "title")
						->column("type", "gev_learning_type")
						->column("date", "date", false, "112px")
						->column("venue", "gev_location")
						->column("provider", "gev_provider")
						->column("tutor", "il_crs_tutor")
						->column("status", "status")
						->column("action", $this->action_img, true, "", true)
						->template('tpl.gev_edu_bio_row.html', 'Services/GEV/Reports')
						;
		
		$this->query = catReportQuery::create()
						->select("crs.custom_id")
						->select("crs.title")
						->select("crs.type")
						->select("usrcrs.begin_date")
						->select("usrcrs.end_date")
						->select("crs.venue")
						->select("crs.provider")
						->select("crs.tutor")
						->select("usrcrs.credit_points")
						->select("crs.fee")
						->select("usrcrs.participation_status")
						->select("usrcrs.okz")
						->select("usrcrs.bill_id")
						->select("usrcrs.certificate")
						->select("usrcrs.booking_status")
						->select("oref.ref_id")
						->from("hist_usercoursestatus usrcrs")
						->join("hist_user usr")
							->on("usr.user_id = usrcrs.usr_id AND usr.hist_historic = 0")
						->join("hist_course crs")
							->on("crs.crs_id = usrcrs.crs_id AND crs.hist_historic = 0")
						->left_join("object_reference oref")
							->on("crs.crs_id = oref.obj_id")
						->compile();
		
		$this->ctrl->setParameter($this, "target_user_id", $this->target_user_id);
		$this->filter = catFilter::create()
						->dateperiod( "period"
									, $this->lng->txt("gev_period")
									, $this->lng->txt("gev_until")
									, "usrcrs.begin_date"
									, "usrcrs.end_date"
									, date("Y")."-01-01"
									, date("Y")."-12-31"
									)
						->static_condition("usr.user_id = ".$this->db->quote($this->target_user_id, "integer"))
						->static_condition("usrcrs.hist_historic = 0")
						->static_condition($this->db->in( "usrcrs.booking_status"
														, array( "gebucht"
															   , "kostenpflichtig storniert"
															   )
														, false, "text")
										  )
						->static_condition("(crs.crs_id < 0 OR oref.deleted IS NULL)")
						->action($this->ctrl->getLinkTarget($this, "view"))
						->compile();
	}
	
	public function executeCustomCommand($cmd) {
		switch ($cmd) {
			case "getCertificate":
				return $this->getCertificate();
			case "getBill":
				return $this->getBill();
		}
	}
	
	protected function checkPermission() {
		if(    $this->user->getId() == $this->target_user_id
			|| $this->target_user_utils->isEmployeeOf($this->user->getId())
			|| $this->user_utils->isAdmin()) {
			return;
		}
		ilUtil::sendFailure($this->lng->txt("no_edu_bio_permission"), true);
		ilUtil::redirect("ilias.php?baseClass=gevDesktopGUI&cmdClass=toMyCourses");
	}
	
	public function renderView() {
		$spacer = new catHSpacerGUI();
		return	  $this->renderOverview()
				. $spacer->render()
				. $this->renderTable();
				;
	}
	
	public function renderOverview() {
		$user_utils = gevUserUtils::getInstance($this->target_user_id);
		$tpl = new ilTemplate("tpl.gev_edu_bio_overview.html", true, true, "Services/GEV/Reports");

		$this->renderAcademyPoints($tpl);

		return $tpl->get();
	}
	
	protected function renderAcademyPoints($tpl) {
		require_once("Services/Calendar/classes/class.ilDatePresentation.php");
		
		$tpl->setVariable("ACADEMY_SUM_TITLE", $this->lng->txt("gev_points_in_academy"));
		$tpl->setVariable("ACADEMY_SUM_FIVE_YEAR_TITLE", $this->lng->txt("gev_points_in_five_years"));
		
		$period = $this->filter->get("period");
		
		$start_date = $period["start"]->get(IL_CAL_FKT_GETDATE);
		$fy_start = new ilDate($start_date["year"]."-01-01", IL_CAL_DATE); 
		$fy_end = new ilDate($start_date["year"]."-12-31", IL_CAL_DATE);
		$fy_end->increment(ilDateTime::YEAR, 4);
		
		$tpl->setVariable("ACADEMY_FIVE_YEAR", ilDatePresentation::formatPeriod($fy_start, $fy_end));
		
		$query = $this->academyQuery($period["start"], $period["end"]);
		$res = $this->db->query($query);
		if ($rec = $this->db->fetchAssoc($res)) {
			$tpl->setVariable("ACADEMY_SUM", $rec["sum"] ? $rec["sum"] : 0);
		}
		
		$query = $this->academyQuery($fy_start, $fy_end);
		$res = $this->db->query($query);
		if ($rec = $this->db->fetchAssoc($res)) {
			$tpl->setVariable("ACADEMY_SUM_FIVE_YEAR", $rec["sum"] ? $rec["sum"] : 0);
		}
	}
	
	protected function academyQuery(ilDate $start, ilDate $end) {
		return   "SELECT SUM(usrcrs.credit_points) sum "
				.$this->query->sqlFrom()
				.$this->queryWhere($start, $end)
				." AND usrcrs.participation_status = 'teilgenommen'"
				." AND crs.crs_id > 0" // only academy points
				." AND usrcrs.credit_points > 0"
				;
	}
	
	protected function transformResultRow($rec) {
		$no_entry = $this->lng->txt("gev_table_no_entry");
		
		$rec["fee"] = (($rec["bill_id"] != -1 || $this->target_user_utils->paysFees())&& $rec["fee"] != -1)
					? $rec["fee"] = gevCourseUtils::formatFee($rec["fee"])." &euro;"
					: $rec["fee"] == "-empty-";
					
		if ($rec["participation_status"] == "teilgenommen") {
			$rec["status"] = $this->success_img;
		}
		else if (in_array($rec["participation_status"], array("fehlt entschuldigt", "fehlt ohne Absage"))
			 ||  in_array($rec["booking_status"], array("kostenpflichtig storniert", "kostenfrei storniert"))
			) {
			$rec["status"] = $this->failed_img;
		}
		else {
			$rec["status"] = $this->in_progress_img;
		}

		if ($rec["begin_date"] == "0000-00-00" && $rec["end_date"] == "0000-00-00") {
			$rec["date"] = $no_entry;
		}
		else if ($rec["end_date"] == "0000-00-00") {
			$dt = new ilDate($rec["begin_date"], IL_CAL_DATE);
			$rec["date"] = $this->lng->txt("gev_from")." ".ilDatePresentation::formatDate($dt);
		}
		else if ($rec["begin_date"] == "0000-00-00") {
			$dt = new ilDate($rec["end_date"], IL_CAL_DATE);
			$rec["date"] = $this->lng->txt("gev_until")." ".ilDatePresentation::formatDate($dt);
		}
		else {
			$start = new ilDate($rec["begin_date"], IL_CAL_DATE);
			$end = new ilDate($rec["end_date"], IL_CAL_DATE);
			$rec["date"] = ilDatePresentation::formatDate($start)." - <br/>".ilDatePresentation::formatDate($end);
		}
		
		$rec["action"] = "";
		if ($rec["bill_id"] != -1 && $rec["bill_id"] != "-empty-") {
			$this->ctrl->setParameter($this, "bill_id", $rec["bill_id"]);
			$this->ctrl->setParameter($this, "target_user_id", $this->target_user_id);
			$rec["action"] = "<a href='".$this->ctrl->getLinkTarget($this, "getBill")."'>"
						   . $this->get_bill_img."</a>";
			$this->ctrl->setParameter($this,  "bill_id", null);
			$this->ctrl->setParameter($this,  "target_user_id", null);
		}
		if ($rec["certificate"] != -1 && $rec["certificate"] != 0) {
			$this->ctrl->setParameter($this, "cert_id", $rec["certificate"]);
			$this->ctrl->setParameter($this, "target_user_id", $this->target_user_id);
			$rec["action"] .= "<a href='".$this->ctrl->getLinkTarget($this, "getCertificate")."'>"
						   . $this->get_cert_img."</a>";
			$this->ctrl->setParameter($this, "cert_id", null);
			$this->ctrl->setParameter($this, "target_user_id", null);
		}
		if ($rec["ref_id"] !== null) {
			$rec["link_open"] = "<a href='goto.php?target=crs_".$rec["ref_id"]."'>";
			$rec["link_close"] = "</a>";
		}
		else {
			$rec["link_open"] = "";
			$rec["link_close"] = "";
		}
		
		foreach ($rec as $key => $value) {
			if ($value == '-empty-' || $value == -1) {
				$rec[$key] = $no_entry;
				continue;
			}
		}
		
		return $rec;
	}

	protected function getBill() {
		// check weather this bill really belongs to an edu bio record of the current user.
		$bill_id = $_GET["bill_id"];
		$res = $this->db->query( "SELECT crs_id"
								."  FROM hist_usercoursestatus "
								." WHERE usr_id = ".$this->db->quote($this->target_user_id, "integer")
								."   AND bill_id = ".$this->db->quote($bill_id, "text")
								."   AND hist_historic = 0"
								);
		
		if ($this->db->numRows($res) != 1) {
			return $this->render();
		}
		$rec = $this->db->fetchAssoc($res);
		

		require_once("Services/GEV/Utils/classes/class.gevBillStorage.php");
		require_once 'Services/Utilities/classes/class.ilUtil.php';
		
		/*
		require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
		$crs_utils = gevCourseUtils::getInstance($rec["crs_id"]);
		$fname = "Rechnung_".$crs_utils->getCustomId().".pdf";
		*/
		$fname = "Rechnung_".$bill_id.".pdf";
		$bill_storage = gevBillStorage::getInstance();
		$path = $bill_storage->getPathByBillNumber($bill_id);
		ilUtil::deliverFile($path, $fname, 'application/pdf', false, false, true);
	}
	
	protected function getCertificate() {
		// check weather this cert really belongs to an edu bio of the current user
		$cert_id = $_GET["cert_id"];
		$res = $this->db->query( "SELECT COUNT(*) cnt"
								."  FROM hist_usercoursestatus "
								." WHERE usr_id = ".$this->db->quote($this->target_user_id, "integer")
								."   AND certificate = ".$this->db->quote($cert_id, "integer"));
		if ($rec = $this->db->fetchAssoc($res)) {
			if ($rec["cnt"] == 0) {
				return $this->render();
			}
		}
		
		// query certificate data
		$res = $this->db->query( "SELECT hc.certfile, hs.crs_id "
								."  FROM hist_certfile hc"
								." JOIN hist_usercoursestatus hs ON hs.certificate = hc.row_id"
								." WHERE hc.row_id = ".$this->db->quote($cert_id, "integer"));
		if ($rec = $this->db->fetchAssoc($res)) {
			require_once("Services/Utilities/classes/class.ilUtil.php");
			require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
			$crs_utils = gevCourseUtils::getInstance($rec["crs_id"]);
			ilUtil::deliverData(base64_decode($rec["certfile"]), "Zertifikat_".$crs_utils->getCustomId().".pdf", "application/pdf");
		}
		else {
			return $this->render();
		}
	}
	
	protected function queryWhere(ilDate $start = null, ilDate $end = null) {
		if ($start === null) {
			return parent::queryWhere();
		}
		
		return		 " WHERE usr.user_id = ".$this->db->quote($this->target_user_id, "integer")
					."   AND usrcrs.hist_historic = 0 "
					."   AND ( usrcrs.end_date >= ".$this->db->quote($start->get(IL_CAL_DATE), "date")
					."        OR usrcrs.end_date = '0000-00-00')"
					."   AND usrcrs.begin_date <= ".$this->db->quote($end->get(IL_CAL_DATE), "date")
					."   AND ".$this->db->in("usrcrs.booking_status", array("gebucht", "kostenpflichtig storniert", "kostenfrei storniert"), false, "text")
					;
	}
}

?>