<?php

namespace CaT\Plugins\TalentAssessment;

require_once("./Services/GEV/Utils/classes/class.gevOrgUnitUtils.php");

/**
 * Action class for commiunication between plugin (gui, object, ...) and ilias
 */
class ilActions {
	const F_TITLE = "title";
	const F_DESCRIPTION = "desc";
	const F_CAREER_GOAL = "career_goal";
	const F_USERNAME = "username";
	const F_DATE = "date";
	const F_VENUE = "venue";
	const F_ORG_UNIT = "org_unit";
	const F_STATE = "state";
	const F_FIRSTNAME = "firstname";
	const F_LASTNAME = "lastname";
	const F_EMAIL = "email";
	const F_RESULT_COMMENT = "resultComment";
	const F_POTENTIAL = "potential";
	const F_JUDGEMENT_TEXT = "judgement_text";

	const START_DATE = "start_date";
	const END_DATE = "end_date";

	const OBSERVATOR_ROLE_NAME = "pl_xtas_observator";
	const OBSERVATOR_ROLE_DESCRIPTION = "Local role for observator at obj_id: ";

	const SI_PREFIX = "req_id";

	const TA_FAILED = "ta_failed";
	const TA_PASSED = "ta_passed";
	const TA_MAYBE = "ta_maybe";
	const TA_IN_PROGRESS = "ta_in_progress";

	public function __construct(\CaT\Plugins\TalentAssessment\ObjTalentAssessment $object
								, \CaT\Plugins\TalentAssessment\Settings\DB $settings_db
								, \CaT\Plugins\TalentAssessment\Observator\DB $observator_db
								, \CaT\Plugins\TalentAssessment\Observations\DB $observations_db) 
	{
		global $rbacadmin, $rbacreview;

		$this->object = $object;
		$this->settings_db = $settings_db;
		$this->observator_db = $observator_db;
		$this->observations_db = $observations_db;

		$this->gRbacadmin = $rbacadmin;
		$this->gRbacreview = $rbacreview;
	}

	/**
	 * Update the object with the values from the array.
	 *
	 * @param	array	filled with fields according to F_*-constants
	 * @return  null
	 */
	public function update(array &$values) {
		assert('array_key_exists(self::F_TITLE, $values)');
		assert('is_string($values[self::F_TITLE])');
		$this->object->setTitle($values[self::F_TITLE]);
		if (array_key_exists(self::F_DESCRIPTION, $values)) {
			assert('is_string($values[self::F_DESCRIPTION])');
			$this->object->setDescription($values[self::F_DESCRIPTION]);
		}
		else {
			$this->object->setDescription("");
		}

		if(array_key_exists(self::F_DATE, $values)) {
			$start_date = $values[self::F_DATE]["start"]["date"];
			$start_time = $values[self::F_DATE]["start"]["time"];
			$values[self::START_DATE] = new \ilDateTime($start_date." ".$start_time,IL_CAL_DATETIME);

			$end_date = $values[self::F_DATE]["end"]["date"];
			$end_time = $values[self::F_DATE]["end"]["time"];
			$values[self::END_DATE] = new \ilDateTime($end_date." ".$end_time,IL_CAL_DATETIME);


			$this->object->updateSettings(function($s) use (&$values) {
			return $s
				->withStartDate($values[self::START_DATE])
				->withEndDate($values[self::END_DATE])
				;
			});
		}

		$this->object->updateSettings(function($s) use (&$values) {
			return $s
				->withCareerGoalID((int)$values[self::F_CAREER_GOAL])
				->withUsername($values[self::F_USERNAME])
				->withVenue((int)$values[self::F_VENUE])
				->withOrgUnit((int)$values[self::F_ORG_UNIT])
				;
		});

		$this->object->update();
	}

	/**
	 * Read the object to an array.
	 *
	 * @return array
	 */
	public function read() {
		$values = array();
		$values[self::F_TITLE] = $this->object->getTitle();
		$values[self::F_DESCRIPTION] = $this->object->getDescription();

		$settings = $this->object->getSettings();
		$values[self::F_CAREER_GOAL] = $settings->getCareerGoalId();
		$values[self::F_USERNAME] = $settings->getUSername();

		$start_date = $settings->getStartDate()->get(IL_CAL_DATETIME);
		$start_date = explode(" ", $start_date);
		$end_date = $settings->getEndDate()->get(IL_CAL_DATETIME);
		$end_date = explode(" ", $end_date);

		$date = array("start" => array("date" => $start_date[0], "time" => $start_date[1])
					, "end" => array("date" => $end_date[0], "time" => $end_date[1])
									);

		$values[self::F_DATE] = $date;
		$values[self::F_VENUE] = $settings->getVenue();
		$values[self::F_ORG_UNIT] = $settings->getOrgUnit();
		$values[self::F_FIRSTNAME] = $settings->getFirstname();
		$values[self::F_LASTNAME] = $settings->getLastname();
		$values[self::F_EMAIL] = $settings->getEmail();

		return $values;
	}

	/**
	 * Get options of career goals for settings
	 *
	 * @return array<int, string>
	 */
	public function getCareerGoalsOptions() {
		return $this->settings_db->getCareerGoalsOptions();
	}

	/**
	 * Get options of venue for settings
	 *
	 * @return array<int, string>
	 */
	public function getVenueOptions() {
		return $this->settings_db->getVenueOptions();
	}

	/**
	 * Get options of org units for settings
	 *
	 * @return array<int, string>
	 */
	public function getOrgUnitOptions() {
		return $this->settings_db->getOrgUnitOptions();
	}

	/**
	 * create local observator role for $obj_id
	 *
	 * @param 	\ilObject 	$newObj
	 */
	public function createLocalRole(\ilObject $newObj) {
		$rolf_obj = $newObj->createRoleFolder();

		// CREATE ADMIN ROLE
		$role_obj = $rolf_obj->createRole($this->getLocalRoleNameFor($newObj->getId()), self::OBSERVATOR_ROLE_DESCRIPTION.$newObj->getId());
		$admin_id = $role_obj->getId();

		$rolt_obj_id = $this->observator_db->getRoltId(self::OBSERVATOR_ROLE_NAME);
		$this->gRbacadmin->copyRoleTemplatePermissions($rolt_obj_id, ROLE_FOLDER_ID, $rolf_obj->getRefId(), $role_obj->getId());

		// SET OBJECT PERMISSIONS OF COURSE OBJECT
		$ops = $this->gRbacreview->getOperationsOfRole($role_obj->getId(),"xtas",$rolf_obj->getRefId());
		$this->gRbacadmin->grantPermission($role_obj->getId(),$ops,$newObj->getRefId());
	}

	/**
	 * assign user to local observator role
	 *
	 * @param int 	$user_id
	 */
	public function assignObservator($user_id, $obj_id) {
		$role_id = $this->getLocalRoleId($obj_id);

		$this->gRbacadmin->assignUser($role_id, $user_id);
	}

	/**
	 * deassign user to local observator role
	 *
	 * @param int 	$user_id
	 */
	public function deassignObservator($user_id, $obj_id) {
		$this->observations_db->deleteObservationResults($obj_id, $user_id);
		$middle = $this->requestsMiddle();
		$this->updatePotential($middle);
		$role_id = $this->getLocalRoleId($obj_id);

		$this->gRbacadmin->deassignUser($role_id, $user_id);
	}

	// TODO: Why can this return null? An empty array would be enough.
	public function getAssignedUser($obj_id) {
		$role_id = $this->getLocalRoleId($obj_id);

		return $this->gRbacreview->assignedUsers($role_id, array("usr_id", "firstname", "lastname", "login", "email"));
	}

	/**
	 * Get the local role id for obj
	 *
	 * @param int 	$obj_id
	 *
	 * @return int 	$role_id
	 */
	public function getLocalRoleId($obj_id) {
		$role_name = $this->getLocalRoleNameFor($obj_id);

		if(!$role_id = $this->gRbacreview->roleExists($role_name)) {
			throw new \Exception("Role does not exist ".$role_name);
		}

		return $role_id;
	}

	protected function getLocalRoleNameFor($obj_id) {
		return self::OBSERVATOR_ROLE_NAME."_".$obj_id;
	}

	/**
	 * Is the observation sarted or not
	 *
	 * @param int 	$obj_id
	 */
	public function observationStarted($obj_id) {
		return $this->settings_db->isStarted($obj_id);
	}

	/**
	 * Set the satte of observation to startetd
	 *
	 * @param boolean 	$started
	 */
	public function setObservationStarted($started) {
		$this->object->updateSettings(function($s) use ($started) {
			return $s
				->withStarted($started)
				;
		});
		$this->object->update();
	}

	/**
	 * Copy the default texts from career foal to ta
	 *
	 * @param int 	$career_goal_id
	 */
	public function copyCopyDefaultText($career_goal_id) {
		$default_texts = $this->settings_db->getCareerGoalDefaultText($career_goal_id);

		$this->updateDefaultText($default_texts);
	}

	protected function updateDefaultText(array &$values) {
		$this->object->updateSettings(function($s) use (&$values) {
			return $s
				->withDefaultTextFailed($values["default_text_failed"])
				->withDefaultTextPartial($values["default_text_partial"])
				->withDefaultTextSuccess($values["default_text_success"])
				;
		});

		$this->object->update();
	}

	/**
	 * Copy observations from career goal
	 *
	 * @param int 	$obj_id
	 * @param int 	$career_goal_id
	 */
	public function copyObservations($obj_id, $career_goal_id) {
		$this->observations_db->copyObservations($obj_id, $career_goal_id);
	}

	/**
	 * Get observations from career goal
	 *
	 * @param int 	$career_goal_id
	 *
	 * @return array
	 */
	public function getBaseObservations($career_goal_id) {
		return $this->observations_db->getBaseObservations($career_goal_id);
	}

	/**
	 * Get observation for overview
	 *
	 * @param int 	$obj_id
	 *
	 * @return array
	 */
	public function getObservationListData($obj_id) {
		return $this->observations_db->getObservations($obj_id);
	}

	/**
	 * Set the notice for observation
	 *
	 * @param int 		$obj_id
	 * @param string 	$notice
	 */
	public function setNoticeFor($obs_id, $notice) {
		$this->observations_db->setNotice((int)$obs_id, $notice);
	}

	/**
	 * Set points for observation
	 *
	 * @param array 	$post
	 */
	public function setPoints($post) {
		$points = $post[self::SI_PREFIX];

		foreach ($points as $req_id => $points) {
			$this->observations_db->setPoints((int)$req_id, (float)$points);
		}
	}

	/**
	 * Get data for observation overview
	 *
	 * @param int 	$obj_id
	 * @param int 	$role_id
	 *
	 * @return array
	 */
	public function getObservationOverviewData($obj_id, $role_id) {
		return $this->observations_db->getObservationOverviewData($obj_id, $role_id);
	}

	/**
	 * Get data for cumulative view
	 *
	 * @param int 	$obj_id
	 *
	 * @return array
	 */
	public function getObservationsCumulative($obj_id) {
		return $this->observations_db->getObservationsCumulative($obj_id);
	}

	/**
	 * Get request result for cumulative view
	 *
	 * @param int 	$obs_ids
	 *
	 * @return array
	 */
	public function getRequestresultCumulative($obs_ids) {
		return $this->observations_db->getRequestresultCumulative($obs_ids);
	}

	/**
	 * Copy lowmark and should from career goal
	 *
	 * @param int 	$career_goal_id
	 */
	public function copyClassificationValues($career_goal_id) {
		$career_goal_obj = \ilObjectFactory::getInstanceByObjId($career_goal_id);

		$this->object->updateSettings(function($s) use ($career_goal_obj) {
			return $s
				->withLowmark($career_goal_obj->getSettings()->getLowmark())
				->withShouldSpecifiaction($career_goal_obj->getSettings()->getShouldSpecification())
				;
		});
		$this->object->update();
	}

	/**
	 * Save the values for report
	 *
	 * @param array 	$post
	 */
	public function saveReportData($post) {
		$settings = $this->object->getSettings();
		$potential = $settings->getPotential();
		$lowmark = $settings->getLowmark();
		$should = $settings->getShouldSpecification();

		if($potential < $lowmark) {
			$this->object->updateSettings(function($s) use ($post) {
				return $s
					->withDefaultTextFailed($post[self::F_JUDGEMENT_TEXT])
					;
			});
		} else if($potential > $should) {
			$this->object->updateSettings(function($s) use ($post) {
				return $s
					->withDefaultTextSuccess($post[self::F_JUDGEMENT_TEXT])
					;
			});
		} else {
			$this->object->updateSettings(function($s) use ($post) {
				return $s
					->withDefaultTextPartial($post[self::F_JUDGEMENT_TEXT])
					;
			});
		}

		$this->object->updateSettings(function($s) use ($post) {
			return $s
				->withResultComment($post[self::F_RESULT_COMMENT])
				;
		});
		$this->object->update();
	}

	/**
	 * Finish the talent assessment
	 */
	public function finishTA() {
		$this->object->updateSettings(function($s) {
			return $s
				->withFinished(true)
				;
		});
		$this->object->update();
	}

	/**
	 * Update the potential of tested user
	 *
	 * @param int 	$potential
	 */
	public function updatePotential($potential) {
		$this->object->updateSettings(function($s) use ($potential) {
			return $s
				->withPotential($potential)
				;
		});
		$this->object->update();
	}

	/**
	 * Get the potential of user for form
	 *
	 * @param array 	$values
	 * @param int 		$potential
	 *
	 * @return array
	 */
	public function setPotentialToValues($values, $potential) {
		$values[self::F_STATE] = $potential;

		return $values;
	}

	/**
	 * Get the potential text according to current potential
	 *
	 * @return string
	 */
	public function potentialText() {
		$settings = $this->object->getSettings();

		if(!$settings->Finished()) {
			return self::TA_IN_PROGRESS;
		}

		if(!$middle = $settings->getPotential()) {
			$middle = $this->requestsMiddle();
		}

		if($middle <= $settings->getLowmark()) {
			return self::TA_FAILED;
		} else if($middle >= $settings->getShouldSpecification()) {
			return self::TA_PASSED;
		} else {
			return self::TA_MAYBE;
		}
	}

	/**
	 * Get the middle of all observations
	 *
	 * @return float
	 */
	public function requestsMiddle() {
		$obs = $this->getObservationsCumulative($this->object->getId());
		$req_res = $this->getRequestresultCumulative(array_keys($obs));

		$middle_total = 0;
		foreach ($req_res as $key => $req_det) {
			$sum += $req_det["sum"];
		}

		$middle = $sum / count($req_res);
		$middle_total += $middle;

		return round($middle_total,1);
	}

	/**
	 * Get the name of the venue
	 *
	 * @param int 	$venue_id
	 *
	 * @return string
	 */
	public function getVenueName($venue_id) {
		$org_unit_utils = \gevOrgUnitUtils::getInstance($venue_id);

		return $org_unit_utils->getLongTitle();
	}

	/**
	 * Get the title of the org unit
	 *
	 * @param int 	$org_unit_id
	 *
	 * @return string
	 */
	public function getOrgUnitTitle($org_unit_id) {
		$org_unit_utils = \gevOrgUnitUtils::getInstance($org_unit_id);

		return $org_unit_utils->getTitle();
	}

	/**
	 * Get the title of career goal
	 *
	 * @param int 	$career_goal_id
	 *
	 * @return string
	 */
	public function getCareerGoalTitle($career_goal_id) {
		$obj = \ilObjectFactory::getInstanceByObjId($career_goal_id);
		return $obj->getTitle();
	}

	public function getSettings() {
		return $this->object->getSettings();
	}
}
