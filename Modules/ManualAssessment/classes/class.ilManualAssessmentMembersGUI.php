<?php
require_once 'Modules/ManualAssessment/classes/class.ilManualAssessmentMembersTableGUI.php';
require_once 'Modules/ManualAssessment/classes/LearningProgress/class.ilManualAssessmentLPInterface.php';
/**
 * For the purpose of streamlining the grading and learning-process status definition
 * outside of tests, SCORM courses e.t.c. the ManualAssessment is used.
 * It caries a LPStatus, which is set manually.
 *
 * @author Denis Klöpfer <denis.kloepfer@concepts-and-training.de>
 *
 * @ilCtrl_Calls ilManualAssessmentMembersGUI: ilRepositorySearchGUI
 * @ilCtrl_Calls ilManualAssessmentMembersGUI: ilManualAssessmentMemberGUI
 */
class ilManualAssessmentMembersGUI
{

	protected $ctrl;
	protected $parent_gui;
	protected $ref_id;
	protected $tpl;
	protected $lng;

	/**
	 * @var ilUser
	 */
	protected $user;

	public function __construct($a_parent_gui, $a_ref_id)
	{
		global $ilCtrl, $tpl, $lng, $ilToolbar, $ilUser;
		$this->ctrl = $ilCtrl;
		$this->parent_gui = $a_parent_gui;
		$this->object = $a_parent_gui->object;
		$this->ref_id = $a_ref_id;
		$this->tpl =  $tpl;
		$this->lng = $lng;
		$this->toolbar = $ilToolbar;
		$this->access_handler = $this->object->accessHandler();
		$this->user = $ilUser;

		$this->superior_examinate = $a_parent_gui->object->getSettings()->superiorExaminate();
		$this->superior_view = $a_parent_gui->object->getSettings()->superiorView();
	}

	public function executeCommand()
	{
		if (!$this->userMayEditMembers()
			&& !$this->userMayEditGrades()
			&& !$this->userMayViewGrades()) {
			$this->parent_gui->handleAccessViolation();
		}
		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass();
		switch ($next_class) {
			case "ilrepositorysearchgui":
				require_once 'Services/Search/classes/class.ilRepositorySearchGUI.php';
				$rep_search = new ilRepositorySearchGUI();
				$rep_search->setCallback($this, "addUsersFromSearch");
				$this->ctrl->forwardCommand($rep_search);
				break;
			case "ilmanualassessmentmembergui":
				require_once 'Modules/ManualAssessment/classes/class.ilManualAssessmentMemberGUI.php';
				$member = new ilManualAssessmentMemberGUI($this, $this->parent_gui, $this->ref_id);
				$this->ctrl->forwardCommand($member);
				break;
			default:
				if (!$cmd) {
					$cmd = 'view';
				}
				$this->$cmd();
				break;
		}
	}

	protected function addedUsers()
	{
		if (!$_GET['failure']) {
			ilUtil::sendSuccess($this->lng->txt('mass_add_user_success'));
		} else {
			ilUtil::sendFailure($this->lng->txt('mass_add_user_failure'));
		}
		$this->view();
	}

	protected function view()
	{
		if ($this->userMayEditMembers()) {
			require_once './Services/Search/classes/class.ilRepositorySearchGUI.php';

			$search_params = ['crs', 'grp'];
			$container_id = $this->object->getParentContainerIdByType($this->ref_id, $search_params);
			if ($container_id !== 0) {
				ilRepositorySearchGUI::fillAutoCompleteToolbar(
					$this,
					$this->toolbar,
					array(
						'auto_complete_name'	=> $this->lng->txt('user'),
						'submit_name'			=> $this->lng->txt('add'),
						'add_search'			=> true,
						'add_from_container'		=> $container_id
					)
				);
			} else {
				ilRepositorySearchGUI::fillAutoCompleteToolbar(
					$this,
					$this->toolbar,
					array(
						'auto_complete_name'	=> $this->lng->txt('user'),
						'submit_name'			=> $this->lng->txt('add'),
						'add_search'			=> true
					)
				);
			}
		}

		$employees = null;
		if ($this->isSuperior($this->user->getId())
			&& ($this->superior_examinate || $this->superior_view)
			&& (!$this->access_handler->checkAccessToObj($this->object, 'edit_learning_progress')
				&& !$this->access_handler->checkAccessToObj($this->object, 'read_learning_progress'))
		) {
			$employees = $this->getEmployeesOf($this->user->getId());
		} elseif ($this->isSuperior($this->user->getId())
			&& !$this->superior_examinate
			&& !$this->superior_view
			&& !$this->access_handler->checkAccessToObj($this->object, 'edit_learning_progress')
			&& !$this->access_handler->checkAccessToObj($this->object, 'read_learning_progress')
		) {
			$employees = array();
		}

		$table = new ilManualAssessmentMembersTableGUI($this, $employees);
		$this->tpl->setContent($table->getHTML());
	}

	public function addUsersFromSearch($user_ids)
	{
		if ($user_ids && is_array($user_ids) && !empty($user_ids)) {
			$this->addUsers($user_ids);
		}

		ilUtil::sendInfo($this->lng->txt("search_no_selection"), true);
		$this->ctrl->redirectByClass(array(get_class($this->parent_gui),get_class($this)), 'view');
	}

	/**
	 * Add users to corresponding mass-object. To be used by repository search.
	 *
	 * @param	int|string[]	$user_ids
	 */
	public function addUsers(array $user_ids)
	{

		if (!$this->userMayEditMembers()) {
			$a_parent_gui->handleAccessViolation();
		}
		$mass = $this->object;
		$members = $mass->loadMembers();
		$failure = null;
		if (count($user_ids) === 0) {
			$failure = 1;
		}
		foreach ($user_ids as $user_id) {
			$user = new ilObjUser($user_id);
			if (!$members->userAllreadyMember($user)) {
				$members = $members->withAdditionalUser($user);
			} else {
				$failure = 1;
			}
		}
		$members->updateStorageAndRBAC($mass->membersStorage(), $mass->accessHandler());
		ilManualAssessmentLPInterface::updateLPStatusByIds($mass->getId(), $user_ids);
		$this->ctrl->setParameter($this, 'failure', $failure);
		$this->ctrl->redirectByClass(array(get_class($this->parent_gui),get_class($this)), 'addedUsers');
	}

	protected function removeUserConfirmation()
	{
		if (!$this->userMayEditMembers()) {
			$a_parent_gui->handleAccessViolation();
		}
		include_once './Services/Utilities/classes/class.ilConfirmationGUI.php';
		$confirm = new ilConfirmationGUI();
		$confirm->addItem('usr_id', $_GET['usr_id'], ilObjUser::_lookupFullname($_GET['usr_id']));
		$confirm->setHeaderText($this->lng->txt('mass_remove_user_qst'));
		$confirm->setFormAction($this->ctrl->getFormAction($this));
		$confirm->setConfirm($this->lng->txt('remove'), 'removeUser');
		$confirm->setCancel($this->lng->txt('cancel'), 'view');
		$this->tpl->setContent($confirm->getHTML());
	}

	/**
	 * Remove users from corresponding mass-object. To be used by repository search.
	 *
	 * @param	int|string[]	$user_ids
	 */
	public function removeUser()
	{
		if (!$this->userMayEditMembers()) {
			$a_parent_gui->handleAccessViolation();
		}
		$usr_id = $_POST['usr_id'];
		$mass = $this->object;
		$mass->loadMembers()
			->withoutPresentUser(new ilObjUser($usr_id))
			->updateStorageAndRBAC($mass->membersStorage(), $mass->accessHandler());
		ilManualAssessmentLPInterface::updateLPStatusByIds($mass->getId(), array($usr_id));
		$this->ctrl->redirectByClass(array(get_class($this->parent_gui),get_class($this)), 'view');
	}

	protected function isSuperior($user_id)
	{
		require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");
		$user_utils = gevUserUtils::getInstance((int)$user_id);
		return $user_utils->isSuperior();
	}

	protected function getEmployeesOf($user_id)
	{
		require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");
		$user_utils = gevUserUtils::getInstance((int)$user_id);
		return $user_utils->getDirectEmployees();
	}

	public function userMayEditGrades()
	{
		return (($this->isSuperior($this->user->getId()) && $this->superior_examinate)
			|| $this->object->accessHandler()->checkAccessToObj($this->object, 'edit_learning_progress'));
	}

	public function userMayViewGrades()
	{
		return (($this->isSuperior($this->user->getId()) && $this->superior_view)
			|| $this->object->accessHandler()->checkAccessToObj($this->object, 'read_learning_progress'));
	}

	public function userMayEditMembers()
	{
		return $this->object->accessHandler()
			->checkAccessToObj($this->object, 'edit_members');
	}
}
