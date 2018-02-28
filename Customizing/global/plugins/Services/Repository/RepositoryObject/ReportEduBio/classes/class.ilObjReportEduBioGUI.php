<?php

require_once 'Customizing/global/plugins/Services/Cron/CronHook/ReportMaster/classes/ReportBase/class.ilObjReportBaseGUI.php';
require_once 'Services/Form/classes/class.ilNumberInputGUI.php';
require_once 'Services/GEV/Utils/classes/class.gevCourseUtils.php';
/**
* User Interface class for example repository object.
* ...
* @ilCtrl_isCalledBy ilObjReportEduBioGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
* @ilCtrl_Calls ilObjReportEduBioGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, gevDBVReportGUI
* @ilCtrl_Calls ilObjReportEduBioGUI: ilCommonActionDispatcherGUI
*/
class ilObjReportEduBioGUI extends ilObjReportBaseGUI {
	protected static $get_cert_img;
	protected static $get_bill_img;
	protected static $success_img;
	protected static $in_progress_img;
	protected static $failed_img;
	protected static $target_user_id;

	public function getType() {
		return 'xreb';
	}

	public function performCommand() {
		if ($this->gUser->getId() === $_GET["target_user_id"]) {
			global $ilMainMenu;
			$ilMainMenu->setActive("gev_me_menu");
		}
		parent::performCommand();
	}

	public function performCustomCommand($cmd) {
		switch ($cmd) {
			case "getCertificate":
				$this->object->prepareRelevantParameters();
				$this->getCertificate();
				break;
			case "getBill":
				$this->object->prepareRelevantParameters();
				$this->getBill();
				break;
			default:
				return false;
		}
		return true;
	}

	protected function prepareTitle($a_title) {
		if ( (string)$this->gUser->getId() == (string)$this->object->target_user_id) {
			$a_title->title($this->object->plugin->txt("my_edu_bio"))
							->subTitle($this->object->plugin->txt("my_edu_bio_desc"))
							->image("GEV_img/ico-head-edubio.png");
		} else {
			$a_title->title(sprintf($this->object->plugin->txt("others_edu_bio"), $this->object->target_user_utils->getFullName()))
					->subTitle(sprintf($this->object->plugin->txt("others_edu_bio_desc"), $this->object->target_user_utils->getFullName()))
					->image("GEV_img/ico-head-edubio.png");
		}
		$a_title->useLng(false)
				->setVideoLink($this->object->settings['video_link'])
				->setVideoLinkText($this->object->master_plugin->txt("rep_video_desc"))
				->setPdfLink($this->object->settings['pdf_link'])
				->setPdfLinkText($this->object->master_plugin->txt("rep_pdf_desc"))
				->setToolTipText($this->object->settings['tooltip_info'])
				->legend(catLegendGUI::create()
					->item(self::$get_cert_img, "gev_get_certificate")
					->item(self::$get_bill_img, "gev_get_bill")
					->item(self::$success_img, "gev_passed")
					->item(self::$in_progress_img, "gev_in_progress")
					->item(self::$failed_img, "gev_failed")
					);
		return $a_title;
	}

	/**
	 * render report.
	 */
	public function renderReport() {
		self::$get_cert_img = '<img src="'.ilUtil::getImagePath("GEV_img/ico-key-get_cert.png").'" />';
		self::$get_bill_img = '<img src="'.ilUtil::getImagePath("GEV_img/ico-key-get_bill.png").'" />';
		self::$success_img  = '<img src="'.ilUtil::getImagePath("GEV_img/ico-key-green.png").'" />';
		self::$in_progress_img = '<img src="'.ilUtil::getImagePath("GEV_img/ico-key-orange.png").'" />';
		self::$failed_img = '<img src="'.ilUtil::getImagePath("GEV_img/ico-key-red.png").'" />';
		self::$target_user_id = $this->object->target_user_id;
		parent::renderReport();
	}

	public function exportExcel() {
		self::$target_user_id = $this->object->target_user_id;
		parent::exportExcel();
	}

	protected function render() {
		$this->gTpl->setTitle(null);
		if(!$this->object->getWBD()->userTPStatusOK() &&
			!$this->object->getWBD()->wbdRegistrationIsPending()) {
			$tpl = new ilTemplate("tpl.wbd_role_no_service_warning.html", true, true, $this->object->plugin->getDirectory());
			$tpl->setVariable("MESSAGE", $this->plugin->txt("wbd_role_no_service_warning"));
			$tpl_html = $tpl->get();
			ilUtil::sendFailure($tpl_html);
		}
		return 	$this->title->render()
				.($this->object->deliverFilter() !== null ? $this->object->deliverFilter()->render() : "")
				.($this->spacer !== null ? $this->spacer->render() : "")
				.($this->object->getWBD()->getWBDTPType() !== gevWBD::WBD_NO_SERVICE ?
					$this->renderOverview().($this->spacer !== null ? $this->spacer->render() : "") : "")
				.$this->renderTable();
	}

	protected function renderOverview() {
		$tpl = new ilTemplate("tpl.gev_edu_bio_overview.html", true, true, $this->object->plugin->getDirectory());
		$this->insertAcademyPoints($tpl);
		if($this->object->getWBD()->getWBDTPType() === gevWBD::WBD_TP_SERVICE ) {
			$this->insertWBDPoints($tpl);
			if ($this->object->getWBD()->transferPointsFromWBD()) {
				$tpl->setVariable("WBDPOINTSVISIBIBLE", "visible");
			} else {
				$tpl->setVariable("WBDPOINTSVISIBIBLE", "invisible");
				if($this->object->getWBD()->transferPointsToWBD()) {
					ilUtil::sendInfo($this->plugin->txt("wbd_transfer_on"));
				} elseif($this->object->getWBD()->wbdRegistrationIsPending()) {
					ilUtil::sendInfo($this->plugin->txt("wbd_reg_pending"));
				}
			}
		} else {
			$tpl->setVariable("WBDPOINTSVISIBIBLE", "invisible");
		}
		return $tpl->get();
	}

	protected function insertAcademyPoints($tpl) {
		$tpl->setVariable("ACA_TRANSFERED_SUM_TITLE", $this->object->plugin->txt("aca_transferred_points_filter"));
		$tpl->setVariable("ACA_TO_TRANSFER_SUM_TITLE", $this->object->plugin->txt("aca_to_transdfer_points_filter"));
		$aux = $this->object->academy_points["transfered_sum"];
		$tpl->setVariable("ACA_TRANSFERED_SUM", $aux ? $aux : 0);
		$aux = $this->object->academy_points["to_transfer_sum"];
		$tpl->setVariable("ACA_TO_TRANSFER_SUM", $aux ? $aux : 0);
	}

	protected function insertWBDPoints($tpl) {
		$tpl->setVariable("WBD_SUM_TITLE", $this->object->plugin->txt("points_in_wbd"));
		$tpl->setVariable("WBD_SUM_CERT_PERIOD_TITLE", $this->object->plugin->txt("points_in_wbd_cert_period"));
		$tpl->setVariable("WBD_CERT_PERIOD", $this->object->wbd_data["cert_period"]);
		$aux = $this->object->wbd_data["sum"];
		$tpl->setVariable("WBD_SUM", $aux ? $aux : 0);
		$aux = $this->object->wbd_data["sum_cert_period"];
		$tpl->setVariable("WBD_SUM_CERT_PERIOD", $aux ? $aux : 0);
	}

	protected function getBill() {
		// check weather this bill really belongs to an edu bio record of the current user.
		$bill_id = $_GET["bill_id"];
		
		if (!$this->object->validateBill($bill_id)) {
			$this->gCtrl->redirect($this, "showContent");
		}
		require_once("Services/GEV/Utils/classes/class.gevBillStorage.php");
		$year = ilBill::getInstanceByBillNumber($bill_id)->getBillYear();
		$fname = "Rechnung_".$bill_id.".pdf";
		$bill_storage = gevBillStorage::getInstance($year);
		$path = $bill_storage->getPathByBillNumber($bill_id);
		ilUtil::deliverFile($path, $fname, 'application/pdf', false, false, true);
	}

	protected function getCertificate() {
		// check weather this cert really belongs to an edu bio of the current user
		$crs_id = $_GET["crs_id"];
		$usr_id = $_GET["target_user_id"];
		$cert_name = $_GET["cert_name"];
		if (!$this->object->validateCertificate($crs_id,$usr_id,$cert_name)) {
			$this->gCtrl->redirect($this, "showContent");
		}
		if ($this->object->deliverCertificate($cert_name)) {
			
		} else {
			$this->gCtrl->redirect($this, "showContent");
		}
	}

	public static function transformResultRow($rec) {
		global $lng;
		$no_entry = $lng->txt("gev_table_no_entry");

		$rec["fee"] = (($rec["bill_id"] != -1 || gevUserUtils::getInstance(self::$target_user_id)->paysFees()) && $rec["fee"] != -1)
					? gevCourseUtils::formatFee($rec["fee"])." &euro;"
					: "-empty-";

		if ($rec["participation_status"] == "teilgenommen") {
			$rec["status"] = self::$success_img;
		} elseif (in_array($rec["participation_status"], array("fehlt entschuldigt", "fehlt ohne Absage"))
			 ||  in_array($rec["booking_status"], array("kostenpflichtig storniert", "kostenfrei storniert"))
			) {
			$rec["status"] = self::$failed_img;
		} else {
			$rec["status"] = self::$in_progress_img;
		}
		$rec['credit_points'] = gevCourseUtils::convertCreditpointsToFormattedDuration((int)$rec['credit_points']);
		if ($rec["begin_date"] == "0000-00-00" && $rec["end_date"] == "0000-00-00") {
			$rec["date"] = $no_entry;
		} elseif ($rec["end_date"] == "0000-00-00") {
			$dt = new ilDate($rec["begin_date"], IL_CAL_DATE);
			$rec["date"] = $lng->txt("gev_from")." ".ilDatePresentation::formatDate($dt);
		} elseif ($rec["begin_date"] == "0000-00-00") {
			$dt = new ilDate($rec["end_date"], IL_CAL_DATE);
			$rec["date"] = $lng->txt("gev_until")." ".ilDatePresentation::formatDate($dt);
		} else {
			$start = new ilDate($rec["begin_date"], IL_CAL_DATE);
			$end = new ilDate($rec["end_date"], IL_CAL_DATE);
			$rec["date"] = ilDatePresentation::formatDate($start)." - <br/>".ilDatePresentation::formatDate($end);
		}

		$rec["action"] = "";
		if ($rec["bill_id"] != -1 && $rec["bill_id"] != "-empty-") {
			$params = array("bill_id" => $rec["bill_id"],  "target_user_id" => self::$target_user_id);
			$rec["action"] .= "<a href='".self::getLinkToThis("getBill",$params)."'>". self::$get_bill_img."</a>";
		}
		if ($rec["certificate_filename"] != null && $rec["certificate_filename"] != '-empty-') {
			$params = array("crs_id" => $rec["crs_id"], 
							"cert_name" => $rec["certificate_filename"],
							"target_user_id" => self::$target_user_id);
			$rec["action"] .= "<a href='".self::getLinkToThis("getCertificate",$params)."'>". self::$get_cert_img."</a>";
		}
		if ($rec["ref_id"] !== null) {
			$rec["link_open"] = "<a href='goto.php?target=crs_".$rec["ref_id"]."'>";
			$rec["link_close"] = "</a>";
		}
		else {
			$rec["link_open"] = "";
			$rec["link_close"] = "";
		}
		return parent::transformResultRow($rec);
	}

	public static function transformResultRowXLSX($rec) {
		global $lng;
		$no_entry = $lng->txt("gev_table_no_entry");

		$rec["fee"] = (($rec["bill_id"] != -1 || gevUserUtils::getInstance(self::$target_user_id)->paysFees()) && $rec["fee"] != -1)
					? gevCourseUtils::formatFee($rec["fee"])
					: "-empty-";

		$rec['status'] = $rec['participation_status'];

		if ($rec["begin_date"] == "0000-00-00" && $rec["end_date"] == "0000-00-00") {
			$rec["date"] = $no_entry;
		} elseif ($rec["end_date"] == "0000-00-00") {
			$dt = new ilDate($rec["begin_date"], IL_CAL_DATE);
			$rec["date"] = $lng->txt("gev_from")." ".ilDatePresentation::formatDate($dt);
		} elseif ($rec["begin_date"] == "0000-00-00") {
			$dt = new ilDate($rec["end_date"], IL_CAL_DATE);
			$rec["date"] = $lng->txt("gev_until")." ".ilDatePresentation::formatDate($dt);
		} else {
			$start = new ilDate($rec["begin_date"], IL_CAL_DATE);
			$end = new ilDate($rec["end_date"], IL_CAL_DATE);
			$rec["date"] = ilDatePresentation::formatDate($start)." - <br/>".ilDatePresentation::formatDate($end);
		}
		$rec['credit_points'] = gevCourseUtils::convertCreditpointsToFormattedDuration((int)$rec['credit_points']);
		$rec["wbd"] = in_array($rec["okz"], array("OKZ1", "OKZ2", "OKZ3"))
					? $lng->txt("yes")
					: $lng->txt("no");
		return parent::transformResultRowXLSX($rec);
	}

	protected static function getLinkToThis($cmd,$params) {
		global $ilCtrl;
		foreach ($params as $key => $value) {
			$ilCtrl->setParameterByClass("ilObjReportEduBioGUI",  $key, $value);
		}
		$link = $ilCtrl->getLinkTargetByClass(array("ilObjPluginDispatchGUI","ilObjReportEduBioGUI"), $cmd);
		foreach ($params as $key => $value) {
			$ilCtrl->setParameterByClass("ilObjReportEduBioGUI",  $key, null);
		}
		return $link;
	}
}
