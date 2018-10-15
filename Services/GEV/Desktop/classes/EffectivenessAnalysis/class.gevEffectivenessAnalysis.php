<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Utils class for effectiveness analysis.
 * Handle communication between GUI's and ILIAS (db abstraction)
 *
 * @author Stefan Hecken <stefan.hecken@concepts-and-training.de>
 */
class gevEffectivenessAnalysis {
	const F_TITLE = "title";
	const F_ORG_UNIT = "org_unit";
	const F_LOGIN = "login";
	const F_FINISHED = "finished";
	const F_PERIOD = "period";
	const F_PREFIX = "filter_";
	const F_RESULT = "result";
	const F_STATUS = "status";
	const F_SUPERIOR = "superior";
	const F_SCHEDULED = "scheduled";
	const RESULT_PREFIX = "result";
	const STATE_FNISHED = "gev_eff_analysis_rep_finished";
	const STATE_OPEN = "gev_eff_analysis_rep_open";
	const STATE_FILTER_FINISHED = "rep_finished";
	const STATE_FILTER_OPEN = "rep_open";
	const STATE_FILTER_ALL = "rep_all";

	/**
	 * @var self | null
	 */
	static $instance = null;

	/**
	 * @var string[]
	 */
	static $reason_for_eff_analysis = array("D/D - Resulting from Deficits", "R/LR - Legal Requirements");

	/**
	 * @var gevEffectivenessAnalysisDB
	 */
	protected $eff_analysis_db;

	public function __construct() {
		global $ilCtrl, $lng, $ilDB, $ilUser;

		$this->gLng = $lng;
		$this->gCtrl = $ilCtrl;
		$this->gDB = $ilDB;
		$this->gUser = $ilUser;

		require_once("Services/GEV/Desktop/classes/EffectivenessAnalysis/class.gevEffectivenessAnalysisDB.php");
		$this->eff_analysis_db = new gevEffectivenessAnalysisDB($ilDB);
	}

	/**
	 * Get an instance of this
	 */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new gevEffectivenessAnalysis();
		}

		return self::$instance;
	}

	/**
	 * Get effectivness analysis for user
	 *
	 * @param int 		$user_id
	 * @param mixed[] 	$filter
	 * @param int 		$offset
	 * @param ini 		$limit
	 * @param string 	$order
	 * @param string 	$order_direction
	 *
	 * @return array<mixed[]>
	 */
	public function getEffectivenessAnalysis($user_id, array $filter, $offset, $limit, $order, $order_direction) {
		$my_employees = $this->getMyEmployees($user_id, $filter[self::F_ORG_UNIT], $filter[self::F_LOGIN]);

		if(empty($my_employees)) {
			return array();
		}

		return $this->eff_analysis_db->getEffectivenessAnalysisData($my_employees, self::$reason_for_eff_analysis, $filter, $offset, $limit, $order, $order_direction);
	}

	/**
	 * Get number of possible effectiveness analysis without offset and limit
	 *
	 * @param int 		$user_id
	 * @param mixed[]	$filter
	 *
	 * @return int
	 */
	public function getCountEffectivenessAnalysis($user_id, array $filter) {
		$my_employees = $this->getMyEmployees($user_id, $filter[self::F_ORG_UNIT], $filter[self::F_LOGIN]);

		if(empty($my_employees) || count($my_employees) == 1 && !$my_employees[0]) {
			return 0;
		}

		return $this->eff_analysis_db->getCountEffectivenessAnalysisData($my_employees, self::$reason_for_eff_analysis, $filter);
	}

	/**
	 * Save result for effectiveness analysis for each user
	 *
	 * @param int 		$crs_id
	 * @param int 		$user_id
	 * @param int 		$result
	 * @param string 	$result_info
	 */
	public function saveResult($crs_id, $user_id, $result, $result_info) {
		$this->eff_analysis_db->saveResult($crs_id, $user_id, $result, $result_info);
	}

	/**
	 * Get data for the effectiveness analyis report
	 *
	 * @param int 		$user_id
	 * @param mixed[] 	$filter
	 * @param string 	$order
	 * @param string 	$order_direction
	 *
	 * @return mixed[]
	 */
	public function getEffectivenessAnalysisReportData($user_id, array $filter, $order, $order_direction) {
		if($superior_id = $this->getIdOfSearchedSupervisor($filter)) {
			$my_employees = $this->getMyEmployees($superior_id, $filter[self::F_ORG_UNIT], $filter[self::F_LOGIN]);
		} else {
			$my_employees = $this->getMyEmployees($user_id, $filter[self::F_ORG_UNIT], $filter[self::F_LOGIN]);
		}

		if(empty($my_employees)) {
			return array();
		}

		return $this->eff_analysis_db->getEffectivenessAnalysisReportData($my_employees, self::$reason_for_eff_analysis, $filter, $order, $order_direction);
	}

	/**
	 * Get user_ids for first Mail
	 *
	 * @param int 		$superior_id
	 *
	 * @return mixed[]
	 */
	public function getUserIdsForFirstMail($superior_id) {
		$my_employees = $this->getEmployeesForMailOf($superior_id);

		return $this->eff_analysis_db->getUserIdsForFirstMail($my_employees, $superior_id, self::$reason_for_eff_analysis);
	}

	/**
	 * Get user_ids for reminder
	 *
	 * @param int 		$superior_id
	 *
	 * @return mixed[]
	 */
	public function getUserIdsForReminder($superior_id) {
		$my_employees = $this->getEmployeesForMailOf($superior_id);

		return $this->eff_analysis_db->getUserIdsForReminder($my_employees, $superior_id, self::$reason_for_eff_analysis);
	}

	/**
	 * Get all employess of user.
	 *
	 * @param int 		$user_id
	 * @param string[] 	$filter_orgunit 	values from org unit filter
	 * @param string 	$filter_user		value from member filter
	 *
	 * @return int[]
	 */
	protected function getMyEmployees($user_id, $filter_orgunit, $filter_user) {
		require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");
		require_once("Modules/OrgUnit/classes/class.ilObjOrgUnit.php");
		$user_utils = gevUserUtils::getInstance($user_id);

		$login_id = -1;
		if(isset($filter_user) && $filter_user != "") {
			$login_id = ilObjUser::_lookupId($filter_user);
		}

		$my_employees = array();

		if($user_utils->isAdmin()) {
			$orgus = $this->getOrgunitsOf($user_id, $filter_orgunit);
			$my_employees = $this->getAllPeopleIn($orgus, $login_id, true);

		} else {
			$my_employees = $this->getEmployeesOf($user_id, $filter_orgunit, $login_id);
		}

		return $my_employees;
	}

	/**
	 * Get orgunit where user is superior, filtered by orgu filter if needed
	 *
	 * @param int 		$user_id
	 * @param string[] 	$filter_orgus
	 *
	 * @return int[]
	 */
	protected function getOrgunitsOf($user_id, $filter_orgus) {
		$user_utils = gevUserUtils::getInstance($user_id);
		//admins will see all OrgUs
		if($user_utils->isAdmin()) {
			require_once("Modules/OrgUnit/classes/class.ilObjOrgUnit.php");
			require_once("Services/GEV/Utils/classes/class.gevOrgUnitUtils.php");
			$orgus = gevOrgUnitUtils::getAllChildren(array(ilObjOrgUnit::getRootOrgRefId()));

		//others will see their OrgUs recursively
		} else {
			$orgus = $user_utils->getOrgUnitsWhereUserIsSuperior();
		}


		$orgus = array_map(function($orgu) use ($filter_orgus) {
			if(!empty($filter_orgus)) {
				require_once("Services/GEV/Utils/classes/class.gevOrgUnitUtils.php");
				$orgu_utils = gevOrgUnitUtils::getInstance($orgu["obj_id"]);
				if(in_array($orgu_utils->getTitle(), $filter_orgus)) {
					return $orgu["ref_id"];
				}
			} else {
				return $orgu["ref_id"];
			}
		}, $orgus);

		return $orgus;
	}

	/**
	 * Get titles of all existing orguntis
	 *
	 * @return string[]
	 */
	public function getOrgunitTitles($user_id) {
		$user_utils = gevUserUtils::getInstance($user_id);

		$orgus = array();

		if($user_utils->isAdmin()) {
			require_once("Modules/OrgUnit/classes/class.ilObjOrgUnit.php");
			require_once("Services/GEV/Utils/classes/class.gevOrgUnitUtils.php");
			$orgus = gevOrgUnitUtils::getAllChildren(array(ilObjOrgUnit::getRootOrgRefId()));
			$orgus = array_map(function($orgu) {
				require_once("Services/Object/classes/class.ilObject.php");
				return ilObject::_lookupTitle($orgu["obj_id"]);
			}, $orgus);
		} else {
			$orgus = $user_utils->getOrgUnitNamesWhereUserIsSuperior();
		}

		return $orgus;
	}

	/**
	 * Get employees of user filtered by login id org unit
	 *
	 * @param int 		$user_id
	 * @param string[] 	$filter_orgunit 	values from org unit filter
	 * @param int 		$login_id			login id of filtered user
	 *
	 * @return int[]
	 */
	protected function getEmployeesOf($user_id, $filter_orgunit = array(), $login_id = -1) {
		require_once("Services/GEV/Utils/classes/class.gevOrgUnitUtils.php");
		require_once("Modules/OrgUnit/classes/class.ilObjOrgUnit.php");

		$orgus = $this->getOrgunitsOf($user_id, $filter_orgunit);
		if(empty($orgus) || (count($orgus) == 1 && !$orgus[0])) {
			return array();
		}

		$empl = gevOrgUnitUtils::getEmployeesIn($orgus);

		if($login_id != -1) {
			$empl = $this->reduceToFilteredUser($empl, $login_id);
		}

		if(empty($empl) || (count($empl) == 1 && !$empl[0])) {
			return array();
		}

		return $empl;
	}

	/**
	 * Get employees of user filtered by login id org unit
	 *
	 * @param int 		$user_id
	 * @param string[] 	$filter_orgunit 	values from org unit filter
	 * @param int 		$login_id			login id of filtered user
	 *
	 * @return int[]
	 */
	protected function getEmployeesForMailOf($user_id) {
		require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");
		require_once("Services/GEV/Utils/classes/class.gevOrgUnitUtils.php");

		$user_utils = gevUserUtils::getInstance($user_id);

		$orgus = array_map(
			function($orgu) {
				return $orgu["ref_id"];
			},
			$user_utils->getOrgUnitsWhereUserIsDirectSuperior()
		);

		$empl = gevOrgUnitUtils::getEmployeesIn($orgus);

		return $empl;
	}

	/**
	 * Get all members of org unit
	 *
	 * @param int[] 	$org_unit_ref_ids
	 * @param int 		$login_id 	id of filtered user
	 *
	 * @return int[]
	 */
	protected function getAllPeopleIn(array $org_unit_ref_ids, $login_id) {
		require_once("Services/GEV/Utils/classes/class.gevOrgUnitUtils.php");
		require_once("Modules/OrgUnit/classes/class.ilObjOrgUnit.php");

		$people = gevOrgUnitUtils::getAllPeopleIn($org_unit_ref_ids);

		if($login_id != -1) {
			$people = $this->reduceToFilteredUser($people, $login_id);
		}

		return $people;
	}

	/**
	 * Reduce employees to searched from filter
	 *
	 * @param int[] 		$employess
	 * @param int 			$login_id
	 */
	protected function reduceToFilteredUser($employees, $login_id) {
		return array_filter($employees, function($id) use ($login_id){
			if($id == $login_id) {
				return $id;
			}
		});
	}

	/**
	 * Get filter for my effectiveness analysis
	 *
	 * @param int 		$user_id
	 *
	 * @return catFilter
	 */
	public function getFilter($user_id) {
		require_once("Services/GEV/Reports/classes/class.catFilter.php");
		return catFilter::create()
						->dateperiod( self::F_PERIOD
									, $this->gLng->txt("gev_period")
									, $this->gLng->txt("gev_eff_analysis_show_until")
									, null
									, null
									, date("Y")."-01-01"
									, date("Y")."-12-31"
									, false
									, null
									)
						->checkbox(self::F_FINISHED
									, $this->gLng->txt("gev_eff_analysis_show_finished")
									, ""
									, ""
									, "")
						->multiselect(self::F_ORG_UNIT
									 , $this->gLng->txt("gev_eff_analysis_org_unit")
									 , null
									 , $this->getOrgunitTitles($this->gUser->getId())
									 , array()
									 )
						->textinput(self::F_TITLE
									, $this->gLng->txt("gev_eff_analysis_title").":"
									, "")
						->textinput(self::F_LOGIN
									, $this->gLng->txt("gev_eff_analysis_login").":"
									, ""
									, $this->gCtrl->getLinkTargetByClass(array("gevMyEffectivenessAnalysisGUI"), "userfieldAutocomplete", "", true));
	}

	/**
	 * Get the filter for effectiveness analysis report
	 *
	 * @return catFilter
	 */
	public function getReportFilter() {
		require_once("Services/GEV/Reports/classes/class.catFilter.php");
		return catFilter::create()
						->dateperiod( self::F_PERIOD
									, $this->gLng->txt("gev_period")
									, $this->gLng->txt("gev_eff_analysis_show_until")
									, "usrcrs.begin_date"
									, "usrcrs.end_date"
									, date("Y")."-01-01"
									, date("Y")."-12-31"
									)
						->multiselect(self::F_STATUS
									 , $this->gLng->txt("gev_eff_analysis_status")
									 , null
									 , $this->getStatusOptions()
									 , array()
									 )
						->multiselect(self::F_ORG_UNIT
									 , $this->gLng->txt("gev_eff_analysis_org_unit")
									 , null
									 , $this->getOrgunitTitles($this->gUser->getId())
									 , array()
									 )
						->multiselect(self::F_RESULT
									 , $this->gLng->txt("gev_eff_analysis_result")
									 , null
									 , $this->getResultOptions()
									 , array()
									 )
						->textinput(self::F_TITLE
									, $this->gLng->txt("gev_eff_analysis_title").":"
									, "")
						->textinput(self::F_LOGIN
									, $this->gLng->txt("gev_eff_analysis_member").":"
									, ""
									, $this->gCtrl->getLinkTargetByClass(array("gevEffectivenessAnalysisReportGUI"), "userfieldAutocomplete", "", true))
						->textinput(self::F_SUPERIOR
									, $this->gLng->txt("gev_eff_analysis_superior").":"
									, ""
									, $this->gCtrl->getLinkTargetByClass(array("gevEffectivenessAnalysisReportGUI"), "userfieldAutocomplete", "", true))
						;
	}

	/**
	 * Get user filter inputs
	 *
	 * @param catFilter
	 *
	 * @return mixed[]
	 */
	public function buildFilterValuesFromFilter($filter) {
		$filter_values = array();

		if($filter->filterExists(self::F_FINISHED) && $filter->get(self::F_FINISHED)) {
			$filter_values[self::F_FINISHED] = self::STATE_FILTER_FINISHED;
		} else {
			$filter_values[self::F_FINISHED] = self::STATE_FILTER_OPEN;
		}

		if($filter->filterExists(self::F_PERIOD)) {
			$date = $filter->get(self::F_PERIOD);
			$filter_values[self::F_PERIOD] = array("start"=>$this->createDate($date["start"]->get(IL_CAL_DATE), "00:00:00")
											 , "end"=>$this->createDate($date["end"]->get(IL_CAL_DATE), "23:59:59"));
		}

		if($filter->filterExists(self::F_TITLE)) {
			$title = $filter->get(self::F_TITLE);
			if($title != "") {
				$filter_values[self::F_TITLE] = $title;
			}
		}

		if($filter->filterExists(self::F_LOGIN)) {
			$login = $filter->get(self::F_LOGIN);
			if($login != "") {
				$filter_values[self::F_LOGIN] = $login;
			}
		}

		if($filter->filterExists(self::F_ORG_UNIT)) {
			$orgunit = $filter->get(self::F_ORG_UNIT);
			if(!empty($orgunit)) {
				$filter_values[self::F_ORG_UNIT] = $orgunit;
			}
		}

		//additional filter values for report
		if($filter->filterExists(self::F_SUPERIOR)) {
			$superior = $filter->get(self::F_SUPERIOR);
			if($superior != "") {
				$filter_values[self::F_SUPERIOR] = $superior;
			}
		}

		if($filter->filterExists(self::F_STATUS)) {
			$filter_values[self::F_STATUS] = self::STATE_FILTER_ALL;
			$status = $filter->get(self::F_STATUS);
			if(!empty($status)) {
				if(count($status) == 1) {
					$val = $status[0];

					if($val == $this->gLng->txt(self::STATE_FNISHED)) {
						$filter_values[self::F_STATUS] = self::STATE_FILTER_FINISHED;
					} elseif($val == $this->gLng->txt(self::STATE_OPEN)) {
						$filter_values[self::F_STATUS] = self::STATE_FILTER_OPEN;
					}
				}
			}
		}

		if($filter->filterExists(self::F_RESULT)) {
			$result = $filter->get(self::F_RESULT);
			if(!empty($result)) {
				$result = array_map(function($res) {
					$ar = explode("-", $res);
					return trim($ar[0]);
				}, $result);
				$filter_values[self::F_RESULT] = $result;
			}
		}

		return $filter_values;
	}

	/**
	 * Get a new date with time
	 *
	 * @param string 		$date
	 * @param string 		$time
	 *
	 * @return ilDateTime
	 */
	protected function createDate($date, $time) {
		return new ilDateTime($date." ".$time, IL_CAL_DATETIME);
	}

	/**
	 * Get full text for result value
	 *
	 * @param int 		$result_id
	 *
	 * @return string
	 */
	public function getResultText($result_id) {
		return $this->gLng->txt(self::RESULT_PREFIX."_".$result_id);
	}

	/**
	 * Check if a result info text is needed
	 *
	 * @param int 		$result
	 * @param string 	$result_text
	 */
	public function checkInfoIsRequired($result, $result_text) {
		if(in_array($result, array(1,2,3)) && $result_text == "") {
			return true;
		}

		return false;
	}

	/**
	 * get possible results for effectiveness analysis
	 *
	 * @return array<int, string> 		<result_value, result_text>
	 */
	public function getResultOptions() {
		return array(0 => $this->getResultText(0)
					,1 => $this->getResultText(1)
					,2 => $this->getResultText(2)
					,3 => $this->getResultText(3)
					,4 => $this->getResultText(4)
					,5 => $this->getResultText(5)
					,6 => $this->getResultText(6)
				);
	}

	/**
	 * Get options for effectivenes status filter
	 *
	 * @return string[]
	 */
	protected function getStatusOptions() {
		return array($this->gLng->txt(self::STATE_FNISHED), $this->gLng->txt(self::STATE_OPEN));
	}

	/**
	 * Get the user_id of searched supervisor
	 *
	 * @param mixed[]
	 *
	 * @return int
	 */
	public function getIdOfSearchedSupervisor($filter_values) {
		if(isset($filter_values[self::F_SUPERIOR])) {
			return ilObjUser::_lookupId($filter_values[self::F_SUPERIOR]);
		}

		return false;
	}

	public function userfieldAutocomplete() {
		include_once './Services/User/classes/class.ilUserAutoComplete.php';
		$auto = new ilUserAutoComplete();
		$auto->setSearchFields(array('login','firstname','lastname','email'));
		$auto->enableFieldSearchableCheck(false);
		if(($_REQUEST['fetchall']))
		{
			$auto->setLimit(ilUserAutoComplete::MAX_ENTRIES);
		}
		echo $auto->getList($_REQUEST['term']);
		exit();
	}

	public function getResultDataFor($crs_id, $user_id) {
		return $this->eff_analysis_db->getResultData($crs_id, $user_id);
	}
}