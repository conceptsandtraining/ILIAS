<?php

require_once 'Customizing/global/plugins/Services/Cron/CronHook/ReportMaster/classes/ReportBase/class.ilReportBasePlugin.php';

class ilReportStudyProgrammeOverviewPlugin extends ilReportBasePlugin
{
	// must correspond to the plugin subdirectory
	protected function getReportName()
	{
		return "ReportStudyProgrammeOverview";
	}
}
