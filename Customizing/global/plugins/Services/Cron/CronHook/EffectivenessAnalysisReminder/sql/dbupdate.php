<#1>
<?php
global $ilDB;
require_once("Customizing/global/plugins/Services/Cron/CronHook/EffectivenessAnalysisReminder/classes/ilEffectivenessAnalysisReminderDB.php");
$db = new ilEffectivenessAnalysisReminderDB($ilDB);
$db->install();
?>

<#2>
<?php
global $ilDB;
require_once("Customizing/global/plugins/Services/Cron/CronHook/EffectivenessAnalysisReminder/classes/ilEffectivenessAnalysisReminderDB.php");
$db = new ilEffectivenessAnalysisReminderDB($ilDB);
$db->updateTable();
?>

<#3>
<?php
global $ilDB;
require_once("Customizing/global/plugins/Services/Cron/CronHook/EffectivenessAnalysisReminder/classes/ilEffectivenessAnalysisReminderDB.php");
$db = new ilEffectivenessAnalysisReminderDB($ilDB);
$db->createSequence();
?>