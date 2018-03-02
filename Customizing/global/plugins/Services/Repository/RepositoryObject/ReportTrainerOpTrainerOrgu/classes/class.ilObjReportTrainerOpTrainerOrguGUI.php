<?php

require_once 'Customizing/global/plugins/Services/Cron/CronHook/ReportMaster/classes/ReportBase/class.ilObjReportBaseGUI.php';
/**
* User Interface class for example repository object.
* ...
* @ilCtrl_isCalledBy ilObjReportTrainerOpTrainerOrguGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
* @ilCtrl_Calls ilObjReportTrainerOpTrainerOrguGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI
* @ilCtrl_Calls ilObjReportTrainerOpTrainerOrguGUI: ilCommonActionDispatcherGUI
*/
class ilObjReportTrainerOpTrainerOrguGUI extends ilObjReportBaseGUI {

	public function getType() {
		return 'xoto';
	}

	protected function afterConstructor() {
		parent::afterConstructor();
		if($this->object->plugin) {
			$this->tpl->addCSS($this->object->plugin->getStylesheetLocation('css/report.css'));
		}

		if($this->object) {
			$this->filter = $this->object->filter();
			$this->display = new \CaT\Filter\DisplayFilter
						( new \CaT\Filter\FilterGUIFactory
						, new \CaT\Filter\TypeFactory
						);
		}
		$this->loadFilterSettings();
	}

	protected function loadFilterSettings() {
		if(isset($_POST['filter'])) {
			$this->filter_settings = $_POST['filter'];
		}
		if(isset($_GET['filter'])) {
			$this->filter_settings = unserialize(base64_decode($_GET['filter']));
		}
		if($this->filter_settings) {
			$this->object->addRelevantParameter('filter', base64_encode(serialize($this->filter_settings)));
			$this->object->filter_settings = $this->display->buildFilterValues($this->filter, $this->filter_settings);
		}
	}

	protected function render() {
		$this->gTpl->setTitle(null);
		$res = $this->title->render();
		$res .= $this->renderFilter()."<br />";
		$res .= $this->renderTable();
		return $res;
	}

	protected function renderFilter() {
		require_once("Customizing/global/plugins/Services/Cron/CronHook/ReportMaster/classes/ReportBase/class.catFilterFlatViewGUI.php");
		$filter_flat_view = new catFilterFlatViewGUI($this, $this->filter, $this->display, $this->gCtrl->getCmd());
		return $filter_flat_view->render($this->filter_settings);
	}

	protected function prepareTitle($a_title) {
		$a_title = parent::prepareTitle($a_title);
		$a_title->image("GEV_img/ico-head-edubio.png");
		return $a_title;
	}

	public function renderQueryView()
	{
		include_once "Services/Form/classes/class.ilNonEditableValueGUI.php";
		$this->object->prepareReport();
		$content = $this->renderFilter();
		$form = new ilNonEditableValueGUI($this->gLng->txt("report_query_text"));
		$form->setValue($this->object->buildQueryStatement());
		$settings_form = new ilPropertyFormGUI();
		$settings_form->addItem($form);
		$content .= $settings_form->getHTML();
		$this->gTpl->setContent($content);
	}

	public static function transformResultRow($rec) {
		return $rec;
	}

	public static function transformResultRowXLSX($rec) {
		$rec["title"] =  preg_replace("#&[a-z0-9]{2,8};#i","",strip_tags($rec['title']));
		return $rec;
	}
}