<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Table/classes/class.ilTable2GUI.php';
// gev-patch start
require_once("Services/ParticipationStatus/classes/class.ilParticipationStatus.php");
require_once("Services/ParticipationStatus/classes/class.ilParticipationStatusHelper.php");
// gev-patch end


/**
 * List all users from course
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @ingroup ServicesParticipationStatus
 */
class ilParticipationStatusTableGUI extends ilTable2GUI
{
	protected $status; // [ilParticipationStatus]
	protected $helper; // [ilParticipationStatusHelper]
	protected $may_write; // [bool]
	protected $invalid; // [array]
	
	/**
	 * Constructor
	 *
	 * @param ilObject $a_parent_obj
	 * @param string $a_parent_cmd
	 * @param ilObjCourse $a_course
	 * @param bool $a_may_write
	 * @param bool $a_may_finalize
	 * @param array $a_invalid
	 */
	public function  __construct($a_parent_obj, $a_parent_cmd, ilObjCourse $a_course, $a_may_write = false, $a_may_finalize = false, array $a_invalid = null)
	{
		global $ilCtrl;
		
		parent::__construct($a_parent_obj, $a_parent_cmd);			
		
		if ($_GET["crsrefid"]) {
			$ilCtrl->setParameter($this, "crsrefid", $_GET["crsrefid"]);
		}
		
		$this->status = ilParticipationStatus::getInstance($a_course);		
		$this->helper = ilParticipationStatusHelper::getInstance($a_course);
		$this->may_write = (bool)$a_may_write;
		$this->invalid = $a_invalid;
		
		$this->addColumn($this->lng->txt("name"), "name");
		$this->addColumn($this->lng->txt("login"), "login");
		$this->addColumn($this->lng->txt("objs_orgu"), "org");
		$this->addColumn($this->lng->txt("ptst_admin_status"), "status");
		// spx-patch start
		//$this->addColumn($this->lng->txt("ptst_admin_credit_points"), "cpoints");
		// spx-patch end
		$this->addColumn($this->lng->txt("ptst_admin_changed_by"), "changed_on");
		
		$this->setDefaultOrderField("name");
						
		$this->setRowTemplate("tpl.members_row.html", "Services/ParticipationStatus");
		$this->setFormAction($ilCtrl->getFormAction($this->getParentObject(), $this->getParentCmd()));	

		if($this->may_write || (bool)$a_may_finalize)
		{
			$this->setFormAction($ilCtrl->getFormAction($this->getParentObject(), $this->getParentCmd()));	
			
			if($this->may_write)
			{
				$this->addCommandButton("saveStatusAndPoints", $this->lng->txt("gev_presave"));
			}			
			if((bool)$a_may_finalize /* && 
				$this->status->allStatusSet() && 
				$this->status->getAttendanceList() */)
			{
				$this->addCommandButton("confirmFinalize", $this->lng->txt("ptst_admin_finalize"));
			}
		}
		
		$this->getItems();
	}

	/**
	 * Get user data
	 */
	protected function getItems()
	{					
		$data = array();
		
		ilDatePresentation::setUseRelativeDates(false);
		
		$max = $this->helper->getMaxCreditPoints();
		
		foreach($this->status->getCourseTableData() as $item)
		{			
			if($item["status"] === null)
			{
				$item["status"] = ilParticipationStatus::STATUS_NOT_SET;
			} 	
			if($item["points"] === null)
			{
				$item["points"] = $max;
			} 			
			
			$data[$item["user_id"]] = array(
				"id" => $item["user_id"]
				,"name" => $item["lastname"].", ".$item["firstname"]
				,"login" => $item["login"]			
				,"org" => $item["org_unit"]
				,"org_txt" => $item["org_unit_txt"]
				,"status" => $item["status"]			
				,"cpoints" => $item["points"]						
				,"changed_by_txt" => "-"					
			);
			
			if($item["changed_by"])
			{
				$data[$item["user_id"]]["changed_by"] = $item["changed_on"];
				$data[$item["user_id"]]["changed_by_txt"] = 
					ilDatePresentation::formatDate(new ilDateTime($item["changed_on"], IL_CAL_UNIX)).
					", ". // $this->lng->txt("by").
					" ".$item["changed_by_txt"];
			};
			
			// re-using POST on validation error
			if($this->invalid)
			{
				$data[$item["user_id"]]["status"] = $_POST["status"][$item["user_id"]];
				$data[$item["user_id"]]["cpoints"] = $_POST["cpoints"][$item["user_id"]];
			}
		}
		
		$this->setData($data);
	}

	/**
	 * Fill template row
	 * 
	 * @param array $a_set
	 */
	protected function fillRow($a_set)
	{										
		$this->tpl->setVariable("TXT_NAME", $a_set["name"]);		
		$this->tpl->setVariable("TXT_LOGIN", $a_set["login"]);		
		$this->tpl->setVariable("TXT_ORG", $a_set["org_txt"]);	
		$this->tpl->setVariable("TXT_CHANGED_BY", $a_set["changed_by_txt"]);	
		
		if(!$this->may_write)
		{
			$this->tpl->setCurrentBlock("read_only_bl");
			$this->tpl->setVariable("STATUS_STATIC", $this->status->statusToString($a_set["status"]));	
			$this->tpl->setVariable("POINTS_STATIC", $a_set["cpoints"]);	
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$this->tpl->setCurrentBlock("option_bl");		
			foreach($this->status->getValidStatus(true) as $value => $txt)
			{
				/* :TODO: cannot return to not set?
				if($a_set["status"] != ilParticipationStatus::STATUS_NOT_SET &&
					$value == ilParticipationStatus::STATUS_NOT_SET)
				{
					continue;
				}
				*/
				$this->tpl->setVariable("OPTION_VALUE", $value);
				$this->tpl->setVariable("OPTION_TXT", $txt);
				if($a_set["status"] == $value)
				{
					$this->tpl->setVariable("OPTION_SEL", ' selected="selected"');
				}
				
				$this->tpl->parseCurrentBlock();
			}
			
			$this->tpl->setCurrentBlock("edit_bl");		
			$this->tpl->setVariable("ID", $a_set["id"]);			
			$this->tpl->setVariable("POINTS", $a_set["cpoints"]);	
			$this->tpl->setVariable("PMAX", strlen($this->helper->getMaxCreditPoints()));							
			
			if(is_array($this->invalid["points"]) &&
				in_array($a_set["id"], $this->invalid["points"]))
			{
				$this->tpl->setVariable("POINTS_ALERT", ' style="border-color: #C04000;"');
			}
							
			$this->tpl->parseCurrentBlock();
		}	
	}
}

?>