<?php

require_once 'Services/Form/classes/class.ilTextAreaInputGUI.php';
require_once 'Services/Form/classes/class.ilTextInputGUI.php';
require_once 'Services/Form/classes/class.ilCheckboxInputGUI.php';
require_once 'Services/Form/classes/class.ilNonEditableValueGUI.php';
require_once 'Services/Form/classes/class.ilSelectInputGUI.php';
require_once 'Modules/ManualAssessment/classes/LearningProgress/class.ilManualAssessmentLPInterface.php';
require_once 'Modules/ManualAssessment/classes/Notification/class.ilManualAssessmentPrimitiveInternalNotificator.php';
require_once 'Modules/ManualAssessment/classes/class.ilManualAssessmentLP.php';
require_once "Services/Calendar/classes/class.ilDateTime.php";
require_once 'Modules/ManualAssessment/classes/class.ilObjManualAssessment.php';

/**
 * For the purpose of streamlining the grading and learning-process status definition
 * outside of tests, SCORM courses e.t.c. the ManualAssessment is used.
 * It caries a LPStatus, which is set manually.
 *
 * @author Denis Klöpfer <denis.kloepfer@concepts-and-training.de>
 */
class ilManualAssessmentMemberGUI
{
	protected $notificator;

	public function __construct($members_gui, $a_parent_gui, $a_ref_id)
	{
		$this->notificator = new ilManualAssessmentPrimitiveInternalNotificator();
		global $ilCtrl, $tpl, $lng, $ilUser, $ilTabs;
		$this->ctrl = $ilCtrl;
		$this->members_gui = $members_gui;
		$this->parent_gui = $a_parent_gui;
		$this->object = $a_parent_gui->object;
		$this->ref_id = $a_ref_id;
		$this->tpl =  $tpl;
		$this->lng = $lng;
		$this->ctrl->saveParameter($this, 'usr_id');
		$this->examinee = new ilObjUser($_GET['usr_id']);
		$this->examiner = $ilUser;
		$this->setTabs($ilTabs);
		$this->member = $this->object->membersStorage()
						->loadMember($this->object, $this->examinee);
		$this->settings = $this->object->getSettings();
	}

	public function executeCommand()
	{
		$edited_by_other = $this->targetWasEditedByOtherUser($this->member);
		$read_permission = $this->object->accessHandler()->checkAccessToObj($this->object, 'read_learning_progress');
		$edit_permission = $this->object->accessHandler()->checkAccessToObj($this->object, 'edit_learning_progress');
		if (!$read_permission && !$edit_permission) {
			$a_parent_gui->handleAccessViolation();
		}

		$cmd = $this->ctrl->getCmd();
		switch ($cmd) {
			case 'edit':
			case 'save':
			case 'finalize':
			case 'finalizeConfirmation':
			case 'cancelFinalize':
				if ($edited_by_other || !$edit_permission) {
					$a_parent_gui->handleAccessViolation();
				}
				break;
			case 'view':
				if (($edited_by_other || !$edit_permission) && !$read_permission) {
					$a_parent_gui->handleAccessViolation();
				}
				break;
			case 'cancel':
				break;
			default:
				$a_parent_gui->handleAccessViolation();
		}
		$this->$cmd();
	}

	protected function setTabs(ilTabsGUI $tabs)
	{
		$tabs->clearTargets();
		$tabs->setBackTarget(
			$this->lng->txt('back'),
			$this->getBackLink()
		);
	}

	protected function getBackLink()
	{
		return $this->ctrl->getLinkTargetByClass(
			array(get_class($this->parent_gui)
					,get_class($this->members_gui)),
			'view'
		);
	}

	protected function cancel()
	{
		$this->ctrl->redirect($this->members_gui);
	}

	protected function cancelFinalize()
	{
		$this->ctrl->setParameter($this, "usr_id", $_POST['usr_id']);
		$this->ctrl->redirect($this, "edit");
	}

	protected function finalizeConfirmation()
	{
		if ($this->mayBeEdited()) {
			$form = $this->initGradingForm();
			$post = $_POST;
			$post['event_time'] = $this->formDateTimePost($post['event_time']['date'], $post['event_time']['time']);
			$form->setValuesByArray($post);
			if ($form->checkInput()) {
				$member = $this->updateDataInMemberByArray($this->member, $post);
				$this->object->membersStorage()->updateMember($member);
				if ($member->mayBeFinalized()) {
					include_once './Services/Utilities/classes/class.ilConfirmationGUI.php';
					$confirm = new ilConfirmationGUI();
					$confirm->addHiddenItem('usr_id', $_GET['usr_id']);
					$confirm->setHeaderText($this->lng->txt('mass_finalize_user_qst'));
					$confirm->setFormAction($this->ctrl->getFormAction($this));
					$confirm->setConfirm($this->lng->txt('mass_finalize'), 'finalize');
					$confirm->setCancel($this->lng->txt('cancel'), 'cancelFinalize');
					$this->tpl->setContent($confirm->getHTML());
				} else {
					ilUtil::sendFailure($this->lng->txt('mass_may_not_finalize'));
					$this->edit();
				}
			} else {
				$this->edit();
			}
		} else {
			$this->view();
		}
	}

	protected function finalize()
	{
		if ($this->mayBeEdited()) {
			if ($this->member->mayBeFinalized()) {
				$this->member = $this->member->withFinalized()->maybeSendNotification($this->notificator);
				$this->object->membersStorage()->updateMember($this->member);
				ilUtil::sendSuccess($this->lng->txt('mass_membership_finalized'));
				if ($this->object->isActiveLP()) {
					ilManualAssessmentLPInterface::updateLPStatusOfMember($this->member);
				}
				$this->view();
			} else {
				ilUtil::sendFailure($this->lng->txt('mass_may_not_finalize'));
				$this->edit();
			}
		} else {
			$this->view();
		}
	}

	protected function mayBeEdited()
	{
		return !$this->member->finalized() && !$this->targetWasEditedByOtherUser($this->member);
	}

	protected function edit()
	{
		if ($this->mayBeEdited()) {
			$form = $this->fillForm($this->initGradingForm(), $this->member);
			$this->renderForm($form);
		} else {
			$this->view();
		}
	}

	protected function renderForm(ilPropertyFormGUI $a_form)
	{
		$this->tpl->setContent($a_form->getHTML());
	}

	protected function view()
	{
		$form = $this->fillForm($this->initGradingForm(false), $this->member);
		$this->renderForm($form);
	}

	protected function save()
	{
		if ($this->mayBeEdited()) {
			$form = $this->initGradingForm();
			$post = $_POST;
			$post['event_time'] = $this->formDateTimePost($post['event_time']['date'], $post['event_time']['time']);
			$form->setValuesByArray($post);
			if ($form->checkInput()) {
				$this->member = $this->updateDataInMemberByArray($this->member, $post);
				$this->object->membersStorage()->updateMember($this->member);
				ilUtil::sendSuccess($this->lng->txt('mass_membership_saved'));
				if ($this->object->isActiveLP()) {
					ilManualAssessmentLPInterface::updateLPStatusOfMember($this->member);
				}
			}
			$this->renderForm($form);
		} else {
			$this->view();
		}
	}

	protected function updateDataInMemberByArray(ilManualAssessmentMember $member, $data)
	{
		$member = $member->withRecord($data['record'])
					->withInternalNote($data['internal_note'])
					->withPlace($data['place'])
					->withEventTime($this->createDatetime($data['event_time']))
					->withLPStatus($data['learning_progress'])
					->withExaminerId($this->examiner->getId())
					->withNotify(($data['notify']  == 1 ? true : false));
		return $member;
	}

	protected function initGradingForm($may_be_edited = true)
	{
		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->lng->txt('mass_edit_record'));

		$examinee_name = $this->examinee->getLastname().', '.$this->examinee->getFirstname();

		$usr_name = new ilNonEditableValueGUI($this->lng->txt('name'), 'name');
		$form->addItem($usr_name);
		// record
		$ti = new ilTextAreaInputGUI($this->lng->txt('mass_record'), 'record');
		$ti->setInfo($this->lng->txt('mass_record_info'));
		$ti->setCols(40);
		$ti->setRows(5);
		$ti->setDisabled(!$may_be_edited);
		$form->addItem($ti);

		// description
		$ta = new ilTextAreaInputGUI($this->lng->txt('mass_internal_note'), 'internal_note');
		$ta->setInfo($this->lng->txt('mass_internal_note_info'));
		$ta->setCols(40);
		$ta->setRows(5);
		$ta->setDisabled(!$may_be_edited);
		$form->addItem($ta);

		$txt = new ilTextInputGUI($this->lng->txt('mass_place'), 'place');
		$txt->setRequired($this->settings->eventTimePlaceRequired());
		$txt->setDisabled(!$may_be_edited);
		$form->addItem($txt);

		$date = new ilDateTimeInputGUI($this->lng->txt('mass_event_time'), 'event_time');
		$date->setShowTime(true);
		$date->setRequired($this->settings->eventTimePlaceRequired());
		$date->setDisabled(!$may_be_edited);
		$form->addItem($date);

		$learning_progress = new ilSelectInputGUI($this->lng->txt('grading'), 'learning_progress');
		$learning_progress->setOptions(
			array(ilManualAssessmentMembers::LP_IN_PROGRESS => $this->lng->txt('mass_status_pending')
				, ilManualAssessmentMembers::LP_COMPLETED => $this->lng->txt('mass_status_completed')
			,
			ilManualAssessmentMembers::LP_FAILED => $this->lng->txt('mass_status_failed'))
		);
		$learning_progress->setDisabled(!$may_be_edited);
		$form->addItem($learning_progress);

		// notify examinee
		$notify = new ilCheckboxInputGUI($this->lng->txt('mass_notify'), 'notify');
		$notify->setInfo($this->lng->txt('mass_notify_explanation'));
		$notify->setDisabled(!$may_be_edited);
		$form->addItem($notify);

		require_once("Services/Form/classes/class.ilFileInputGUI.php");
		$file = new ilFileInputGUI($this->lng->txt('mass_upload_file'), 'file');
		$file->setRequired($this->object->getSettings()->fileRequired());
		$form->addItem($file);

		if ($may_be_edited) {
			$form->addCommandButton('save', $this->lng->txt('save'));
			$form->addCommandButton('finalizeConfirmation', $this->lng->txt('mass_finalize'));
		}
		$form->addCommandButton('cancel', $this->lng->txt('mass_return'));
		return $form;
	}

	protected function fillForm(ilPropertyFormGUI $a_form, ilManualAssessmentMember $member)
	{
		$dt = $member->eventTime()->get(IL_CAL_DATETIME);
		$dt = explode(" ", $dt);
		$event_time = ["date" => $dt[0], "time" => $dt[1]];

		$a_form->setValuesByArray(array(
			  'name' => $member->name()
			, 'record' => $member->record()
			, 'internal_note' => $member->internalNote()
			, 'place' => $member->place()
			, 'event_time' => $event_time
			, 'notify' => $member->notify()
			, 'learning_progress' => (int)$member->LPStatus()
			));
		return $a_form;
	}

	protected function targetWasEditedByOtherUser(ilManualAssessmentMember $member)
	{
		return (int)$member->examinerId() !== (int)$this->examiner->getId()
				&& 0 !== (int)$member->examinerId();
	}

	private function formDateTimePost(array $date, array $time)
	{
		$date['d'] = str_pad($date['d'], 2, '0', STR_PAD_LEFT);
		$date['m'] = str_pad($date['m'], 2, '0', STR_PAD_LEFT);
		$time['h'] = str_pad($time['h'], 2, '0', STR_PAD_LEFT);
		$time['m'] = str_pad($time['m'], 2, '0', STR_PAD_LEFT);
		return array("date" => $date['y'] . "-" . $date['m'] . "-" . $date['d']
			, "time" => $time['h'] . ":" . $time['m'] .":00");
	}

	private function createDatetime(array $datetime)
	{
		$date = $datetime["date"];
		$time = $datetime["time"];

		return new ilDateTime($date . " " . $time, IL_CAL_DATETIME);
	}
}
