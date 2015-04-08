<?php

require_once("Services/Cron/classes/class.ilCronManager.php");
require_once("Services/Cron/classes/class.ilCronJob.php");
require_once("Services/Cron/classes/class.ilCronJobResult.php");
require_once("Services/Calendar/classes/class.ilDateTime.php");


class gevDeferredMailingJob extends ilCronJob {
	public function getId() {
		return "gev_deferred_mailing";
	}
	
	public function getTitle() {
		return "Sends deferred Mails for Trainings.";
	}

	public function hasAutoActivation() {
		return true;
	}
	
	public function hasFlexibleSchedule() {
		return false;
	}
	
	public function getDefaultScheduleType() {
		return ilCronJob::SCHEDULE_TYPE_DAILY;
	}
	
	public function getDefaultScheduleValue() {
		return 1;
	}
	
	public function run() {
		require_once("Services/GEV/Mailing/classes/class.gevDeferredMails.php");
		$df = gevDeferredMails::getInstance();
		$df->sendDeferredMails();
		
		$cron_result = new ilCronJobResult();
		$cron_result->setStatus(ilCronJobResult::STATUS_OK);
		return $cron_result;
	}
}

?>