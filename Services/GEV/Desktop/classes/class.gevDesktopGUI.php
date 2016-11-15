<?php

/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */#

/**
* Desktop for the Generali
*
* @author	Richard Klees <richard.klees@concepts-and-training.de>
* @version	$Id$
*
* @ilCtrl_Calls gevDesktopGUI: gevMyCoursesGUI
* @ilCtrl_Calls gevDesktopGUI: gevCourseSearchGUI
* @ilCtrl_Calls gevDesktopGUI: ilAdminSearchGUI
* @ilCtrl_Calls gevDesktopGUI: gevBookingGUI
* @ilCtrl_Calls gevDesktopGUI: gevStaticpagesGUI
* @ilCtrl_Calls gevDesktopGUI: gevEduBiographyGUI
* @ilCtrl_Calls gevDesktopGUI: gevWBDTPServiceRegistrationGUI
* @ilCtrl_Calls gevDesktopGUI: gevWBDTPBasicRegistrationGUI
* @ilCtrl_Calls gevDesktopGUI: gevAttendanceByEmployeeGUI
* @ilCtrl_Calls gevDesktopGUI: gevBillingReportGUI
* @ilCtrl_Calls gevDesktopGUI: gevBookingsByVenueGUI
* @ilCtrl_Calls gevDesktopGUI: gevMyTrainingsApGUI
* @ilCtrl_Calls gevDesktopGUI: gevWBDEdupointsReportedGUI
* @ilCtrl_Calls gevDesktopGUI: gevEmployeeBookingsGUI
* @ilCtrl_Calls gevDesktopGUI: gevDecentralTrainingGUI
* @ilCtrl_Calls gevDesktopGUI: gevEmployeeEduBiosGUI
* @ilCtrl_Calls gevDesktopGUI: ilFormPropertyDispatchGUI
* @ilCtrl_Calls gevDesktopGUI: gevMyEffectivenessAnalysisGUI
* @ilCtrl_Calls gevDesktopGUI: gevEffectivenessAnalysisReportGUI
*/

class gevDesktopGUI {
	public function __construct() {
		global $lng, $ilCtrl, $tpl;
		
		$this->lng = &$lng;
		$this->ctrl = &$ilCtrl;
		$this->tpl = &$tpl;

		$this->lng->loadLanguageModule("gev");
		$this->tpl->getStandardTemplate();
	}
	
	public function executeCommand() {
		$next_class = $this->ctrl->getNextClass();
		$cmd = $this->ctrl->getCmd();
				
		if($cmd == "") {
			$cmd = "toMyCourses";
		}

		global $ilMainMenu;

		switch($next_class) {
			case "gevmycoursesgui":
				$ilMainMenu->setActive("gev_me_menu");
				require_once("Services/GEV/Desktop/classes/class.gevMyCoursesGUI.php");
				$gui = new gevMyCoursesGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			case "gevcoursesearchgui":
				$ilMainMenu->setActive("gev_search_menu");
				require_once("Services/GEV/Desktop/classes/class.gevCourseSearchGUI.php");
				$gui = new gevCourseSearchGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			case "iladminsearchgui":
				$ilMainMenu->setActive("gev_admin_menu");
				require_once("Services/GEV/Desktop/classes/class.ilAdminSearchGUI.php");
				$gui = new ilAdminSearchGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;

			case "gevbookinggui":
				require_once("Services/GEV/Desktop/classes/class.gevBookingGUI.php");
				$gui = new gevBookingGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			case "gevstaticpagesgui":			
				require_once("Services/GEV/Desktop/classes/class.gevStaticPagesGUI.php");
				$gui = new gevStaticpagesGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			case "gevedubiographygui":
				$ilMainMenu->setActive("gev_me_menu");
				require_once("Services/GEV/Reports/classes/class.gevEduBiographyGUI.php");
				$gui = new gevEduBiographyGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			case "gevmytrainingsapgui":
				$ilMainMenu->setActive("gev_me_menu");
				require_once("Services/GEV/Desktop/classes/class.gevMyTrainingsApGUI.php");
				$gui = new gevMyTrainingsApGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			case "gevwbdtpserviceregistrationgui":
				require_once("Services/GEV/Registration/classes/class.gevWBDTPServiceRegistrationGUI.php");
				$gui = new gevWBDTPServiceRegistrationGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			case "gevwbdtpbasicregistrationgui":
				require_once("Services/GEV/Registration/classes/class.gevWBDTPBasicRegistrationGUI.php");
				$gui = new gevWBDTPBasicRegistrationGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			case "gevattendancebyemployeegui":
				$ilMainMenu->setActive("gev_reporting_menu");
				require_once("Services/GEV/Reports/classes/class.gevAttendanceByEmployeeGUI.php");
				$gui = new gevAttendanceByEmployeeGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			case "gevbillingreportgui":
				$ilMainMenu->setActive("gev_reporting_menu");
				require_once("Services/GEV/Reports/classes/class.gevBillingReportGUI.php");
				$gui = new gevBillingReportGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			case "gevbookingsbyvenuegui":
				$ilMainMenu->setActive("gev_reporting_menu");
				require_once("Services/GEV/Reports/classes/class.gevBookingsByVenueGUI.php");
				$gui = new gevBookingsByVenueGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;

			case "gevwbdedupointsreportedgui":
				$ilMainMenu->setActive("gev_reporting_menu");
				require_once("Services/GEV/Reports/classes/class.gevWBDEdupointsReportedGUI.php");
				$gui = new gevWBDEdupointsReportedGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;

			case "gevemployeebookingsgui":
				$ilMainMenu->setActive("gev_others_menu");
				require_once("Services/GEV/Reports/classes/class.gevEmployeeBookingsGUI.php");
				$gui = new gevEmployeeBookingsGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;

			case "gevdecentraltraininggui":
				$ilMainMenu->setActive("gev_others_menu");
				require_once("Services/GEV/Desktop/classes/class.gevDecentralTrainingGUI.php");
				$gui = new gevDecentralTrainingGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
				
			case "gevemployeeedubiosgui":
				$ilMainMenu->setActive("gev_reporting_menu");
				require_once("Services/GEV/Reports/classes/class.gevEmployeeEduBiosGUI.php");
				$gui = new gevEmployeeEduBiosGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;

			case "gevmyeffectivenessanalysisgui":
				$ilMainMenu->setActive("gev_me_menu");
				require_once("Services/GEV/Desktop/classes/EffectivenessAnalysis/class.gevMyEffectivenessAnalysisGUI.php");
				$gui = new gevMyEffectivenessAnalysisGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;

			case "geveffectivenessanalysisreportgui":
				$ilMainMenu->setActive("gev_reporting_menu");
				require_once("Services/GEV/Reports/classes/class.gevEffectivenessAnalysisReportGUI.php");
				$gui = new gevEffectivenessAnalysisReportGUI();
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			default:	
				$this->dispatchCmd($cmd);
				break;
		}
		
		if (isset($ret)) {
			$this->tpl->setContent($ret);
		}
		
		$this->tpl->show();
	}
	
	public function dispatchCmd($a_cmd) {
		switch($a_cmd) {
			case "toCourseSearch":
			case "toAdmCourseSearch":
			case "toMyCourses":
			case "toMyProfile":
			case "toStaticPages":
			case "toMyTrainingsAp":
			case "toReportAttendanceByEmployee":
			case "toBillingReport":
			case "toReportBookingsByVenue":
			case "toBooking":
			case "toReportWBDEdupoints":
			case "toEmployeeBookings":
			case "toReportEmployeeEduBios":
			case "createHAUnit":
			case "toMyEffectivenessAnalysis":
			case "toReportEffectivenessAnalysis":
				$this->$a_cmd();
			default:
				throw new Exception("Unknown command: ".$a_cmd);
		}
	}
	
	protected function toCourseSearch() {
		if (array_key_exists("target_user_id", $_GET)) {
			$this->ctrl->setParameterByClass("gevCourseSearchGUI", "target_user_id", $_GET["target_user_id"]);
		}
		$this->ctrl->redirectByClass("gevCourseSearchGUI");
	}
	
	protected function toAdmCourseSearch() {
		$this->ctrl->redirectByClass("ilAdminSearchGUI");
	}
	
	protected function toMyCourses() {
		$this->ctrl->redirectByClass("gevMyCoursesGUI");
	}	

	protected function toStaticPages() {
		$this->ctrl->redirectByClass("gevStaticPagesGUI", $_REQUEST['ctpl_file']);
	}

	protected function toMyTrainingsAp() {
		$this->ctrl->redirectByClass("gevMyTrainingsApGUI");
	}

	protected function toReportAttendanceByEmployee() {
		$this->ctrl->redirectByClass("gevAttendanceByEmployeeGUI");
	}
	
	protected function toBillingReport() {
		$this->ctrl->redirectByClass("gevBillingReportGUI");
	}
	protected function toReportBookingsByVenue() {
		$this->ctrl->redirectByClass("gevBookingsByVenueGUI");
	}
	protected function toReportWBDEdupoints() {
		$this->ctrl->redirectByClass("gevWBDEdupointsReportedGUI");
	}
	
	protected function toEmployeeBookings() {
		$this->ctrl->redirectByClass("gevEmployeeBookingsGUI");
	}
	
	protected function toReportEmployeeEduBios() {
		$this->ctrl->redirectByClass("gevEmployeeEduBiosGUI");
	}

	protected function toBooking() {
		if (!$_GET["crs_id"]) {
			ilUtil::redirect("");
		}
		
		global $ilUser;
		
		$crs_id = intval($_GET["crs_id"]);
		$usr_id = $ilUser->getId();
		
		$this->ctrl->setParameterByClass("gevBookingGUI", "user_id", $usr_id);
		$this->ctrl->setParameterByClass("gevBookingGUI", "crs_id", $crs_id);
		$this->ctrl->redirectByClass("gevBookingGUI", "book");
	}

	protected function toMyEffectivenessAnalysis() {
		$this->ctrl->redirectByClass("gevMyEffectivenessAnalysisGUI");
	}

	protected function toReportEffectivenessAnalysis() {
		$this->ctrl->redirectByClass("gevEffectivenessAnalysisReportGUI");
	}
}

?>