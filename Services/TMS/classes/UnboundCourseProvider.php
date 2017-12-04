<?php

use \CaT\Ente\ILIAS\UnboundProvider as Base;
use \CaT\Ente\ILIAS\Entity;
use \ILIAS\TMS\CourseInfo;
use \ILIAS\TMS\CourseInfoImpl;

class UnboundCourseProvider extends Base {
	/**
	 * @inheritdocs
	 */
	public function componentTypes() {
		return [CourseInfo::class];
	}

	/**
	 * Build the component(s) of the given type for the given object.
	 *
	 * @param   string    $component_type
	 * @param   Entity    $provider
	 * @return  Component[]
	 */
	public function buildComponentsOf($component_type, Entity $entity) {
		global $DIC;
		$this->lng = $DIC["lng"];
		$this->g_access = $DIC->access();
		$this->lng->loadLanguageModule("tms");
		$this->lng->loadLanguageModule("crs");
		$this->user = $DIC->user();
		$object = $entity->object();

		if ($component_type === CourseInfo::class) {
			$ret = array();

			$ret[] = $this->getCourseInfoForTitle($entity, $object);
			$ret = $this->getCourseInfoForPeriodDate($ret, $entity, $object);
			$ret = $this->getCourseInfoForPeriodTimes($ret, $entity, $object);
			$ret = $this->getCourseInfoForBookingStatus($ret, $entity, $object);
			$ret = $this->getCourseInfoForVenue($ret, $entity, (int)$object->getId());
			$ret = $this->getCourseInfoForTrainingProvider($ret, $entity, (int)$object->getId());
			$ret = $this->getCourseInfoForImportantInformation($ret, $entity, $object);
			$ret = $this->getCourseInfoForTutors($ret, $entity, $object);
			$ret = $this->getCourseInfoForToCourseButton($ret, $entity, $object);
			$ret = $this->getCourseInfoForCourseMemberButton($ret, $entity, $object);

			return $ret;
		}
		throw new \InvalidArgumentException("Unexpected component type '$component_type'");
	}

	/**
	 * Get a course info with course title
	 *
	 * @param Entity $entity
	 * @param Object 	$object
	 *
	 * @return CourseInfo
	 */
	protected function getCourseInfoForTitle(Entity $entity, $object) {
		return $this->createCourseInfoObject($entity
				, $this->lng->txt("title")
				, $object->getTitle()
				, 100
				, [CourseInfo::CONTEXT_SEARCH_SHORT_INFO,
					CourseInfo::CONTEXT_BOOKING_DEFAULT_INFO,
					CourseInfo::CONTEXT_USER_BOOKING_SHORT_INFO
				  ]
			);
	}

	/**
	 * Get a course info with period date
	 *
	 * @param CourseInfo[]
	 * @param Entity $entity
	 * @param Object 	$object
	 *
	 * @return CourseInfo[]
	 */
	protected function getCourseInfoForPeriodDate(array $ret, Entity $entity, $object) {
		$crs_start = $object->getCourseStart();
		if($crs_start === null) {
			return $ret;
		}

		$date = $this->formatPeriod($crs_start, $object->getCourseEnd());
		$ret[] = $this->createCourseInfoObject($entity
			, $this->lng->txt("date").":"
			, $date
			, 300
			, [CourseInfo::CONTEXT_SEARCH_SHORT_INFO,
				CourseInfo::CONTEXT_USER_BOOKING_SHORT_INFO
			  ]
		);

		$ret[] = $this->createCourseInfoObject($entity
			, $this->lng->txt("date").":"
			, $date
			, 300
			, [CourseInfo::CONTEXT_SEARCH_FURTHER_INFO,
				CourseInfo::CONTEXT_USER_BOOKING_FURTHER_INFO
			  ]
		);

		$ret[] = $this->createCourseInfoObject($entity
			, $this->lng->txt("date")
			, $date
			, 900
			, [CourseInfo::CONTEXT_BOOKING_DEFAULT_INFO]
		);

		return $ret;
	}

	/**
	 * Get a course info with course period
	 *
	 * @param CourseInfo[]
	 * @param Entity $entity
	 * @param Object 	$object
	 *
	 * @return CourseInfo[]
	 */
	protected function getCourseInfoForPeriodTimes(array $ret, Entity $entity, $object) {
		$crs_start = $object->getCourseStart();
		if($crs_start === null) {
			return $ret;
		}

		$times = $this->getSessionAppointments($entity, $object);
		if(count($times) > 0) {
			$first_time = current($times);
			$times_formatted = $this->getAppointmentOutput($times);

			$ret[] = $this->createCourseInfoObject($entity
				, $this->lng->txt("period").":"
				, $times_formatted
				, 310
				, [CourseInfo::CONTEXT_SEARCH_DETAIL_INFO,
					CourseInfo::CONTEXT_USER_BOOKING_DETAIL_INFO
				  ]
			);

			$ret[] = $this->createCourseInfoObject($entity
				, $this->lng->txt("period")
				, $times_formatted
				, 910
				, [CourseInfo::CONTEXT_BOOKING_DEFAULT_INFO]
			);
		}

		return $ret;
	}

	/**
	 * Get a course info with booking status
	 *
	 * @param CourseInfo[]
	 * @param Entity $entity
	 * @param Object 	$object
	 *
	 * @return CourseInfo[]
	 */
	protected function getCourseInfoForBookingStatus(array $ret, Entity $entity, $object) {
		require_once("Modules/Course/classes/class.ilCourseParticipants.php");
		require_once("Services/Membership/classes/class.ilWaitingList.php");
		if(\ilCourseParticipants::_isParticipant($object->getRefId(), $this->user->getId())) {
			$ret[] = $this->createCourseInfoObject($entity
					, $this->lng->txt("status").":"
					, $this->lng->txt("booked_as_member")
					, 600
					, [
						CourseInfo::CONTEXT_USER_BOOKING_FURTHER_INFO
					  ]
				);
		}

		if(\ilWaitingList::_isOnList($this->user->getId(), $object->getId())) {
			$ret[] = $this->createCourseInfoObject($entity
					, $this->lng->txt("status").":"
					, $this->lng->txt("booked_on_waitinglist")
					, 600
					, [
						CourseInfo::CONTEXT_USER_BOOKING_FURTHER_INFO
					  ]
				);
		}

		return $ret;
	}

	/**
	 * Get a course info with course important infos
	 *
	 * @param CourseInfo[]
	 * @param Entity $entity
	 * @param Object 	$object
	 *
	 * @return CourseInfo[]
	 */
	protected function getCourseInfoForImportantInformation(array $ret, Entity $entity, $object) {
		$crs_important_info = nl2br(trim($object->getImportantInformation()));
		if($crs_important_info != "") {
			$ret[] = $this->createCourseInfoObject($entity
					, $this->lng->txt("crs_important_info")
					, $crs_important_info
					, 1000
					, [
						CourseInfo::CONTEXT_SEARCH_DETAIL_INFO,
						CourseInfo::CONTEXT_USER_BOOKING_DETAIL_INFO
					  ]
				);

			$ret[] = $this->createCourseInfoObject($entity
					, $this->lng->txt("crs_important_info")
					, $crs_important_info
					, 700
					, [
						CourseInfo::CONTEXT_BOOKING_DEFAULT_INFO
					  ]
				);
		}

		return $ret;
	}

	/**
	 * Get a course info with listed tutors
	 *
	 * @param CourseInfo[]
	 * @param Entity $entity
	 * @param Object 	$object
	 *
	 * @return CourseInfo[]
	 */
	protected function getCourseInfoForTutors(array $ret, Entity $entity, $object) {
		$tutor_ids = $object->getMembersObject()->getTutors();
		if(count($tutor_ids) > 0) {
			foreach ($tutor_ids as $tutor_id) {
				$tutor_names[] = \ilObjUser::_lookupFullname($tutor_id);
			}

			$tutor_names = join(", ", $tutor_names);
			$ret[] = $this->createCourseInfoObject($entity
					, $this->lng->txt("trainer")
					, $tutor_names
					, 1500
					, [
						CourseInfo::CONTEXT_BOOKING_DEFAULT_INFO
					  ]
				);
		}

		return $ret;
	}

	/**
	 * Get a course info with session appointments
	 *
	 * @param Entity $entity
	 * @param Object 	$object
	 *
	 * @return string
	 */
	protected function getSessionAppointments(Entity $entity, $object) {
		$vals = array();

		$sessions = $this->getAllChildrenOfByType($object->getRefId(), "sess");
		if(count($sessions) > 0) {
			foreach ($sessions as $session) {
				$appointment = $session->getFirstAppointment();
				$date = $this->formatDate($appointment->getStart());
				$start_time = $appointment->getStart()->get(IL_CAL_FKT_DATE, "H:i");
				$end_time = $appointment->getEnd()->get(IL_CAL_FKT_DATE, "H:i");
				$offset = $appointment->getDaysOffset();

				$vals[$offset] = array("start_time" => $start_time, "end_time" => $end_time);
			}
		}

		ksort($vals);
		return $vals;
	}

	/**
	 * Transofrom session appointments to output string
	 *
	 * @param string[]
	 *
	 * @return string[]
	 */
	protected function getAppointmentOutput($appointments) {
		return array_map(function($offset, $times) {
			return $offset
				.". ".$this->lng->txt("day")
				.": ".$times["start_time"]
				." - ".$times["end_time"]
				." ".$this->lng->txt("time_suffix");
			}, array_keys($appointments), $appointments
		);
	}

	/**
	 * Get all child by type recursive
	 *
	 * @param int 	$ref_id
	 * @param string 	$search_type
	 *
	 * @return Object 	of search type
	 */
	protected function getAllChildrenOfByType($ref_id, $search_type) {
		global $DIC;
		$g_tree = $DIC->repositoryTree();
		$g_objDefinition = $DIC["objDefinition"];

		$childs = $g_tree->getChilds($ref_id);
		$ret = array();

		foreach ($childs as $child) {
			$type = $child["type"];
			if($type == $search_type) {
				$ret[] = \ilObjectFactory::getInstanceByRefId($child["child"]);
			}

			if($g_objDefinition->isContainer($type)) {
				$rec_ret = $this->getAllChildrenOfByType($child["child"], $search_type);
				if(! is_null($rec_ret)) {
					$ret = array_merge($ret, $rec_ret);
				}
			}
		}

		return $ret;
	}

	/**
	 * Form date.
	 *
	 * @param ilDateTime 	$dat
	 * @param bool 	$use_time
	 *
	 * @return string
	 */
	protected function formatDate(\ilDateTime $date) {
		require_once("Services/Calendar/classes/class.ilCalendarUtil.php");
		$out_format = ilCalendarUtil::getUserDateFormat($use_time, true);
		$ret = $date->get(IL_CAL_FKT_DATE, $out_format, $this->user->getTimeZone());
		if(substr($ret, -5) === ':0000') {
			$ret = substr($ret, 0, -5);
		}

		return $ret;
	}

	/**
	 * Form date period.
	 *
	 * @param ilDateTime 	$dat
	 * @param bool 	$use_time
	 *
	 * @return string
	 */
	protected function formatPeriod(\ilDateTime $date1, \ilDateTime $date2) {
		return $this->formatDate($date1)." - ".$this->formatDate($date2);
	}

	/**
	 * Checks venue plugin is aktive and returns component objects
	 *
	 * @param int 	$crs_id
	 *
	 * @return CourseInfoImpl[]
	 */
	protected function getCourseInfoForVenue($ret, Entity $entity, $crs_id) {
		assert('is_int($crs_id)');
		if(ilPluginAdmin::isPluginActive('venues')) {
			$vplug = ilPluginAdmin::getPluginObjectById('venues');
			$txt = $vplug->txtClosure();
			list($venue_id, $city, $address, $name, $postcode) = $vplug->getVenueInfos($crs_id);

			if($city != "") {
				$ret[] = $this->createCourseInfoObject($entity
				, ""
				, $city
				, 400
				, [CourseInfo::CONTEXT_SEARCH_SHORT_INFO,
					CourseInfo::CONTEXT_USER_BOOKING_SHORT_INFO
				  ]
				);
			}

			if($name != "") {
				$val = array();
				$val[] = $name;

				if($address != "") {
					$val[] = $address;
				}

				if($postcode != "" || $city != "") {
					$val[] = $postcode." ".$city;
				}

				$ret[] = $this->createCourseInfoObject($entity
					, $txt("title").":"
					, join("<br />", $val)
					, 350
					, [CourseInfo::CONTEXT_SEARCH_FURTHER_INFO,
						CourseInfo::CONTEXT_USER_BOOKING_FURTHER_INFO
					  ]
				);

				$ret[] = $this->createCourseInfoObject($entity
					, $txt("title")
					, join("<br />", $val)
					, 1200
					, [CourseInfo::CONTEXT_BOOKING_DEFAULT_INFO]
				);
			}
		}
		return $ret;
	}

	/**
	 * Checks training provider plugin is activ and returns component objects
	 *
	 * @param int 	$crs_id
	 *
	 * @return CourseInfoImpl[]
	 */
	protected function getCourseInfoForTrainingProvider($ret, Entity $entity, $crs_id) {
		assert('is_int($crs_id)');
		if(ilPluginAdmin::isPluginActive('trainingprovider')) {
			$vplug = ilPluginAdmin::getPluginObjectById('trainingprovider');
			$txt = $vplug->txtClosure();
			list($provider_id, $provider) = $vplug->getProviderInfos($crs_id);

			if($provider != "") {
				$ret[] = $this->createCourseInfoObject($entity
					, $txt("title")
					, $provider
					, 1100
					, [CourseInfo::CONTEXT_BOOKING_DEFAULT_INFO]
					);
			}
		}
		return $ret;
	}

	/**
	 * Get a course infomation object to decide showing a to course button or not
	 *
	 * @param CourseInfo[]
	 * @param Entity $entity
	 * @param Object 	$object
	 *
	 * @return CourseInfo[]
	 */
	protected function getCourseInfoForToCourseButton(array $ret, Entity $entity, $object) {
		$show_button = 0;
		if($this->g_access->checkAccess("read", "", $object->getRefId())) {
			$show_button = 1;
		}

		$ret[] = $this->createCourseInfoObject($entity
			, ""
			, $show_button
			,1
			, [CourseInfo::CONTEXT_TO_COURSE_BUTTON]
		);

		return $ret;
	}

	/**
	 * Get a course infomation object to decide showing a course member button or not
	 *
	 * @param CourseInfo[]
	 * @param Entity $entity
	 * @param Object 	$object
	 *
	 * @return CourseInfo[]
	 */
	protected function getCourseInfoForCourseMemberButton(array $ret, Entity $entity, $object) {
		$course_member_objects = $this->getAllChildrenOfByType($object->getRefId(), "xcmb");
		if(count($course_member_objects) === 0) {
			return [];
		}
		$course_member_object = array_shift($course_member_objects);
		$link = $course_member_object->getLinkToMemberView();

		if($link !== null) {
			$ret[] = $this->createCourseInfoObject($entity
				, ""
				, $link
				,1
				, [CourseInfo::CONTEXT_COURSE_MEMBER_BUTTON]
			);
		}

		return $ret;
	}

	/**
	 * Create a course inforamtion object
	 *
	 * @param Entity 	$entity
	 * @param string 	$label
	 * @param int | array | string 	$value
	 * @param int 	$step
	 * @param string[] 	$contexte
	 *
	 * @return CourseInfoImpl
	 */
	protected function createCourseInfoObject(Entity $entity, $label, $value, $step, $contexts) {
		return new CourseInfoImpl
					( $entity
					, $label
					, $value
					, ""
					, $step
					, $contexts
				);
	}


}