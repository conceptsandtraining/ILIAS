<?php
/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilUserCourseStatusHistorizingHelper
 * 
 * @author Maximilian Becker <mbecker@databay.de>
 * @author Richard Klees <richard.klees@concepts-and-training.de>
 * @version $Id$
 */

require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
require_once("Services/GEV/Utils/classes/class.gevSettings.php");
require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");
require_once("Services/GEV/Utils/classes/class.gevBillingUtils.php");

class ilUserCourseStatusHistorizingHelper 
{
	#region Singleton

	/** Defunct member for singleton */
	private function __clone() {}

	/** Defunct member for singleton */
	private function __construct() {}

	/** @var ilUserCourseStatusHistorizingHelper $instance */
	private static $instance;

	/**
	 * Singleton accessor
	 * 
	 * @static
	 * 
	 * @return ilUserHistorizingHelper
	 */
	public static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new self;
		}

		return self::$instance;
	}

	#endregion

	/**
	 * Returns the creditpoints for the given user-course-relation.
	 *
	 * @param integer|ilObjUser   $user
	 * @param integer|ilObjCourse $course
	 *
	 * @return integer
	 */
	public static function getCreditPointsOf($user, $course)
	{
		return 0;
	}

	/**
	 * Returns the booking status of the given user-course-relation.
	 *
	 * @param integer|ilObjUser   $user
	 * @param integer|ilObjCourse $course
	 *
	 * @return string
	 */
	public static function getBookingStatusOf($user, $course)
	{
		return gevCourseUtils::getInstanceByObjOrId($course)
							 ->getBookingStatusLabelOf(self::getId($user));
	}

	/**
	 * Returns the participation status of the given user-course-relation.
	 *
	 * @param integer|ilObjUser   $user
	 * @param integer|ilObjCourse $course
	 *
	 * @return string
	 */
	public static function getParticipationStatusOf($user, $course)
	{
		return gevCourseUtils::getInstanceByObjOrId($course)
							 ->getParticipationStatusLabelOf(self::getId($user));
	}

	/**
	 * Returns the overnights of the given user-course-relation.
	 *
	 * @param integer|ilObjUser   $user
	 * @param integer|ilObjCourse $course
	 *
	 * @return integer
	 */
	public static function getOvernightsOf($user, $course)
	{

		$user_utils = gevUserUtils::getInstanceByObjOrId($user);
		$crs_utils = gevCourseUtils::getInstanceByObjOrId($course);
		return $user_utils->getOvernightAmountForCourse($crs_utils->getCourse());
	}

	/**
	 * Returns the function of the given user-course-relation.
	 *
	 * @param integer|ilObjUser   $user
	 * @param integer|ilObjCourse $course
	 *
	 * @return string
	 */
	public static function getFunctionOf($user, $course)
	{

		$function = gevUserUtils::getInstanceByObjOrId($user)
						   ->getFunctionAtCourse(self::getId($course));


		/*
		2014-11-20 
		0000751: UserCourseStatusHistorizing: "canceled"-Wert im Feld "function" entfernen
		also see: class.gevEduBiographyGUI.php

		//function asks for roles, actually;
		//this should be historized otherwise, though.
		if($function === null){
			$status = gevCourseUtils::getInstanceByObjOrId($course)
							 ->getBookingStatusOf(self::getId($user));

			if(	   $status == ilCourseBooking::STATUS_BOOKED
				|| $status == ilCourseBooking::STATUS_CANCELLED_WITH_COSTS
				|| $status == ilCourseBooking::STATUS_CANCELLED_WITHOUT_COSTS
			){
				$function = "canceled";
			}

		}
		
		*/
		return $function;
	}

	/**
	 * Returns the bill id of the given user-course-relation.
	 * 
	 * Use of method "hasBillId" and definition of a meaningful "no-value" should be done here.
	 * 
	 * @param integer|ilObjUser   $user
	 * @param integer|ilObjCourse $course
	 *
	 * @return string
	 */
	public static function getBillIdOf($user, $course)
	{

		//getBillNumberOf
		
		$bills = gevBillingUtils::getInstance()
							   ->getBillsForCourseAndUser( self::getId($user)
							   							 , self::getId($course)
							   							 );
		if (count($bills) == 0) {
			return null;
		}
		
		// search for latest bill, that is the one with the highest id.
		$id = $bills[0];
		$nr = null;
		foreach ($bills as $bill) {
			$nr = $bill->getBillNumber();
			if ($_id > $id) {
				$id = $_id;
			}
		}
		return $nr;
	}



	
	protected static function getId($obj) {
		if (is_int($obj) || is_numeric($obj)) {
			return (int)$obj;
		}
		else {
			return $obj->getId();
		}
	}
	
	/**
	 * Returns true when the start and end of the course should be tracked individually per user.
	 * 
	 * @param integer|ilObjUser   $user
	 * @param integer|ilObjCourse $course
	 *
	 * @return bool
	 */
	public function courseHasIndividualStartAndEnd($course) {
		return gevCourseUtils::getInstanceByObjOrId($course)->getType() == gevSettings::ONLINE_TRAINING;
	}
	
	/**
	 * Sets the individual start and end date based on the booking and participation status.
	 *
	 * @param array 	$payload	according to layout in ilUserCourseStatusHistorizingAppEventListener::getStateData
	 *
	 * @return null
	 */
	public function setIndividualStartAndEnd($user_id, $course_id, &$payload) {
		require_once("Services/UserCourseStatusHistorizing/classes/class.ilUserCourseStatusHistorizing.php");
		require_once("Services/Calendar/classes/class.ilDateTime.php");
		
		$case_id = array( 'usr_id'	 =>	(int)$user_id
						, 'crs_id'	 =>	(int)$course_id
						);
		
		if (!ilUserCourseStatusHistorizing::caseExists($case_id)) {
			$payload["begin_date"] = date("Y-m-d");
			return;
		}

		if (ilUserCourseStatusHistorizing::caseExists($case_id)
			&& $payload["booking_status"] == "status_cancelled_without_costs"
			&& $payload["event"] == "addParticipant")
		{
			$payload["begin_date"] = date("Y-m-d");
			return;
		}
		
		if ($payload["participation_status"] !== "status_successful") {
			return;
		}
		
		$cur = ilUserCourseStatusHistorizing::getCurrentRecordByCase($case_id);
		
		if ($cur["participation_status"] !== "status_successful") {
			$payload["end_date"] = date("Y-m-d");
		}
	}

	public static function hasCertificate($user, $course)
	{
		require_once("Services/Certificate/classes/class.ilCertificate.php");
		return ilCertificate::isActive() && ilCertificate::areObjectsActive(array($course))
		    && (gevCourseUtils::getInstance($course)->getParticipationStatusLabelOf($user) == "status_successful");
	}

	public static function getCertificateOf($user, $course)
	{
		require_once './Modules/Course/classes/class.ilCourseCertificateAdapter.php';
		$course_class = ilObjectFactory::getClassByType('crs');
		$course_obj = new $course_class($course, false);
		$certificate_adapter = new ilCourseCertificateAdapter($course_obj);
		include_once "./Services/Certificate/classes/class.ilCertificate.php";
		$certificate = new ilCertificate($certificate_adapter);
		return $certificate->outCertificate(array("user_id" => $user), false);
	}
}