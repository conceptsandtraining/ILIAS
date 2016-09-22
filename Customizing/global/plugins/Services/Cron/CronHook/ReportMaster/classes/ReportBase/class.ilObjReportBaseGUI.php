<?php

require_once 'Services/Repository/classes/class.ilObjectPluginGUI.php';
require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
require_once 'Services/Form/classes/class.ilTextInputGUI.php';
require_once 'Services/Form/classes/class.ilTextAreaInputGUI.php';
require_once 'Services/CaTUIComponents/classes/class.catTitleGUI.php';
require_once("Services/CaTUIComponents/classes/class.catTableGUI.php");
require_once("Services/CaTUIComponents/classes/class.catHSpacerGUI.php");
require_once("Customizing/global/plugins/Services/Cron/CronHook/ReportMaster/interfaces/interface.ExcelWriter.php");
require_once("Customizing/global/plugins/Services/Cron/CronHook/ReportMaster/classes/ReportSettings/class.reportSettingsFormHandler.php");
require_once("Customizing/global/plugins/Services/Cron/CronHook/ReportMaster/classes/ReportSettings/class.settingFactory.php");

abstract class ilObjReportBaseGUI extends ilObjectPluginGUI {

	protected $gLng;
	protected $gCtrl;
	protected $gTpl;
	protected $gUser;
	protected $gLog;
	protected $gAccess;

	protected function afterConstructor() {
		global $lng, $ilCtrl, $tpl, $ilUser, $ilLog, $ilAccess, $ilTabs;	
		$this->gLng = $lng;
		$this->gCtrl = $ilCtrl;
		$this->gTpl = $tpl;
		$this->gUser = $ilUser;
		$this->gLog = $ilLog;
		$this->gAccess = $ilAccess;
		$this->gTabs = $ilTabs;
		$this->s_f = new settingFactory($this->gIldb);
		$this->settings_form_handler = $this->s_f->reportSettingsFormHandler();
		// TODO: this is crapy. The root cause of this problem is, that the
		// filter should no need to know about it's action. The _rendering_
		// of the filter needs to know about the action.

		$this->title = null;
	}

	public function setTabs() {
		if($this->gAccess->checkAccess("write", "", $this->object->getRefId())) {
			
			// tab for the "show content" command
			if ($this->gAccess->checkAccess("read", "", $this->object->getRefId())) {
				$this->gTabs->addTab("content", $this->object->plugin->txt($this->getType()."_content"),
				$this->gCtrl->getLinkTarget($this, "showContent"));
			}

			// standard info screen tab
			$this->addInfoTab();

			// a "properties" tab
			$this->gTabs->addTab("properties", $this->object->plugin->txt($this->getType()."_properties"),
			$this->gCtrl->getLinkTarget($this, "settings"));			

			// standard epermission tab
			$this->addPermissionTab();
		}
	}

	/**
	* Besides usual report commands (exportXLS, view, ...) showMenu goes here
	*/
	public function performCommand() {
		$cmd = $this->gCtrl->getCmd("showContent");
		switch ($cmd) {
			case "saveSettings":
				if($this->gAccess->checkAccess("write", "", $this->object->getRefId())) {
					$this->gTabs->activateTab("properties");
					return $this->saveSettings();
				}
				break;
			case "settings":
				if($this->gAccess->checkAccess("write", "", $this->object->getRefId())) {
					$this->gTabs->addSubTabTarget("edit_settings",
												 $this->gCtrl->getLinkTarget($this,'settings'),
												 "write", get_class($this));
					$this->gTabs->addSubTabTarget("report_query_view",
												 $this->gCtrl->getLinkTarget($this,'query_view'),
												 "write", get_class($this));
					$this->gTabs->activateTab("properties");
					$this->gTabs->activateSubTab("edit_settings");
					return $this->renderSettings();
				}
				break;
			case "query_view":
				if($this->gAccess->checkAccess("write", "", $this->object->getRefId())) {
					$this->gTabs->addSubTabTarget("edit_settings",
												 $this->gCtrl->getLinkTarget($this,'settings'),
												 "write", get_class($this));
					$this->gTabs->addSubTabTarget("report_query_view",
												 $this->gCtrl->getLinkTarget($this,'settings'),
												 "write", get_class($this));
					$this->gTabs->activateTab("properties");
					$this->gTabs->activateSubTab("report_query_view");
					$this->object->prepareRelevantParameters();
					$this->setFilterAction($cmd);
					return $this->renderQueryView();
				}
				break;
			case "exportexcel":
				if($this->gAccess->checkAccess("read", "", $this->object->getRefId())) {
					$this->object->prepareRelevantParameters();
					$this->setFilterAction($cmd);
					$this->exportExcel();
				}
				exit();
			case "showContent":
				if($this->gAccess->checkAccess("read", "", $this->object->getRefId())) {
					$this->gTabs->activateTab("content");
					$this->object->prepareRelevantParameters();
					$this->setFilterAction($cmd);
					return $this->renderReport();
				}
				break;
			default:
				if (!$this->performCustomCommand($cmd)) {
					throw new ilException("Unknown Command '$cmd'.");
				}
		}
	}

	public function performCustomCommand($cmd) {
		return false;
	}

	public function getAfterCreationCmd() {
		return "settings";
	}

	public function getStandardCmd() {
		return "showContent";
	}

	/**
	 * render query for debugging purposes
	 * a filter is present and may be modified to observe the effects on query
	 */
	public function renderQueryView() {
		include_once "Services/Form/classes/class.ilNonEditableValueGUI.php";
		$this->object->prepareReport();
		$content = $this->object->deliverFilter() !== null ? $this->object->deliverFilter()->render() : "";
		$form = new ilNonEditableValueGUI($this->gLng->txt("report_query_text"));
		$form->setValue($this->object->buildQueryStatement());
		$settings_form = new ilPropertyFormGUI();
		$settings_form->addItem($form);
		$content .= $settings_form->getHTML();
		$this->gTpl->setContent($content);
	}

	/**
	 * render report.
	 */
	public function renderReport() {
		$this->object->prepareReport();
		$this->title = $this->prepareTitle(catTitleGUI::create());
		$this->spacer = $this->prepareSpacer(new catHSpacerGUI());
		$this->table = $this->prepareTable(new catTableGUI($this, "showContent"));
		$this->gTpl->setContent($this->render());
	}
		
	protected function render() {
		$this->gTpl->setTitle(null);
		return 	($this->title !== null ? $this->title->render() : "")
				. ($this->object->deliverFilter() !== null ? $this->object->deliverFilter()->render() : "")
				. ($this->spacer !== null ? $this->spacer->render() : "")
				. $this->renderTable();
	}
	
	protected function renderTable() {
		$callback = get_class($this).'::transformResultRow';
		if ($this->object->deliverTable()->_group_by !== null) {
			$data = $this->object->deliverGroupedData($callback);
			$content = $this->renderGroupedTable($data);
		} else {
			$data = $this->object->deliverData($callback);
			$content = $this->renderUngroupedTable($data);
		}
		//export-button
		$export_btn = "";
		if (count($data) > 0) {
			$export_btn = $this->renderExportButton();
		}

		return	 $export_btn
				.$content
				.$export_btn;
	}
	
	protected function renderExportButton() {
		$this->enableRelevantParametersCtrl();
		$export_btn = '<a class="submit exportXlsBtn"'
						. 'href="'
						.$this->gCtrl->getLinkTarget($this, "exportexcel")
						.'">'
						.$this->gLng->txt("gev_report_excel_export")
						.'</a>';
		$this->disableRelevantParametersCtrl();
		return $export_btn;
	}

	protected function prepareTable($a_table) {
		$a_table->setEnableTitle(false);
		$a_table->setTopCommands(false);
		$a_table->setEnableHeader(true);
		return $a_table;
	}

	protected function prepareTitle($a_title) {
		$a_title->title($this->object->getTitle())
				->subTitle($this->object->getDescription())
				->setVideoLink($this->object->settings['video_link'])
				->setVideoLinkText($this->object->master_plugin->txt("rep_video_desc"))
				->setPdfLink($this->object->settings['pdf_link'])
				->setPdfLinkText($this->object->master_plugin->txt("rep_pdf_desc"))
				->setToolTipText($this->object->settings['tooltip_info'])
				->useLng(false);
		return $a_title;
	}

	protected function prepareSpacer($a_spacer) {
		return $a_spacer;
	}

	protected function renderUngroupedTable($data) {
		if(!$this->object->deliverTable()->row_template_filename) {
			throw new Exception("No template defined for table ".get_class($this));
		}
		$this->table->setRowTemplate(
			$this->object->deliverTable()->row_template_filename, 
			$this->object->deliverTable()->row_template_module
		);

		$this->table->addColumn("", "blank", "0px", false);
		foreach ($this->object->deliverTable()->columns as $col) {
			$this->table->addColumn( $col[2] ? $col[1] : $this->lng->txt($col[1])
							 , $col[5] ? $col[0] : ""
							 , $col[3]
							 );
		}
		
		if ($this->object->deliverOrder() !== null) {
			$this->table->setOrderField($this->object->deliverOrder()->getOrderField());
			$this->table->setOrderDirection($this->object->deliverOrder()->getOrderDirection());
		}
		
		$cnt = count($data);
		$this->table->setLimit($cnt);
		$this->table->setMaxCount($cnt);
		$external_sorting = true;

		if($this->object->deliverOrder() === null || 
			in_array($this->object->deliverOrder()->getOrderField(), 
				$this->internal_sorting_fields ? $this->internal_sorting_fields : array())
			) {
				$external_sorting = false;	
		}
		
		$this->table->setExternalSorting($external_sorting);
		if ($this->internal_sorting_numeric) {
			foreach ($this->internal_sorting_numeric as $col) {
				$table->numericOrdering($col);
			}
		}

		$this->table->setData($data);
		$this->enableRelevantParametersCtrl();
		$return = $this->table->getHtml();
		$this->disableRelevantParametersCtrl();
		return $return;
	}

	protected function renderGroupedTable($data) {
		$content = "";
		foreach ($data as $key => $rows) {
			// We know for sure there is at least one entry in the rows
			// since we created a group from it.
			$content .= $this->renderGroupHeader($rows[0]);
			$content .= $this->renderUngroupedTable($rows);
		}
		return $content;
	}

	protected function renderGroupHeader($data) {
		$tpl = new ilTemplate( $this->object->deliverTable()->group_head_template_filename
							 , true, true
							 , $this->object->deliverTable()->group_head_template_module
							 );

		foreach ($this->object->deliverTable()->_group_by as $key => $conf) {
			$tpl->setVariable("VAL_".strtoupper($key), $data[$key]);
			$tpl->setVariable("TITLE_".strtoupper($key)
							 , $conf[2] ? $conf[1] : $this->lng->txt($conf[1]));
		}
		return $tpl->get();
	}

	protected function getExcelWriter() {
		require_once 'Customizing/global/plugins/Services/Cron/CronHook/ReportMaster/classes/ReportBase/class.spoutXLSXWriter.php';
		$workbook = new spoutXLSXWriter();
		return $workbook;
	}

	/**
	 * provide xlsx version of report for download.
	 */
	protected function exportExcel() {
		$this->object->prepareReport();

		$workbook = $this->getExcelWriter();

		$sheet_name = "report";
		$workbook
			->addSheet($sheet_name)
			->setRowFormatBold();

		$header = array();
		foreach ($this->object->deliverTable()->all_columns as $col) {
			if ($col[4]) {
				continue;
			}
			$in_header = $col[2] ? $col[1] : $this->lng->txt($col[1]);
			$header[] = str_replace('&shy;', '', strip_tags(htmlspecialchars_decode($in_header)));
		}
		$workbook
			->writeRow($header)
			->setRowFormatWrap();
		$callback = get_class($this).'::transformResultRowXLSX';
		foreach ($this->object->deliverData($callback) as $entry) {
			$row = array();
			foreach ($this->object->deliverTable()->all_columns as $col) {
				if ($col[4]) {
					continue;
				}
				$row[] = $entry[$col[0]];
			}
			$workbook->writeRow($row);
		}

		$workbook->offerDownload("report.xlsx");
	}

	/**
	* to be used in ilObjReport<>::getData
	*/
	public static function transformResultRow($a_rec) {
		$a_rec = self::replaceEmpty($a_rec);
		return $a_rec;
	}

	public static function transformResultRowXLSX($a_rec) {
		$a_rec = self::replaceEmpty($a_rec);
		return $a_rec;
	}

	final protected static function replaceEmpty($a_rec) {
		global $lng;
		foreach ($a_rec as $key => $value) {
			if ($a_rec[$key] == "-empty-" || $a_rec[$key] == "0000-00-00" || $a_rec[$key] === null) {
				$a_rec[$key] = $lng->txt("gev_table_no_entry");
			}
		}
		return $a_rec;
	}

	/**
	* housekeeping the get parameters passed to ctrl
	*/
	final protected function enableRelevantParametersCtrl() {
		foreach ($this->object->getRelevantParameters() as $get_parameter => $get_value) {
			$this->gCtrl->setParameter($this, $get_parameter, $get_value);
		}
	}

	final protected function disableRelevantParametersCtrl() {
		foreach ($this->object->getRelevantParameters() as $get_parameter => $get_value) {
			$this->gCtrl->setParameter($this, $get_parameter, null);
		}
	}

	protected function setFilterAction($cmd) {
		$this->enableRelevantParametersCtrl();
		$this->object->setFilterAction($this->gCtrl->getLinkTarget($this,$cmd));
		$this->disableRelevantParametersCtrl();
	}


	/**
	* Settings menu of the report. Note that any setting query will be performed inside ilObjBaseReport.
	* Allways call parent methods in final plugin-classmethod static::settingFrom, static::getSettingsData and static::saveSettingsData.
	*/
	protected function renderSettings() {
		$settings_form = $this->fillSettingsFormFromDatabase($this->settingsForm());
		$this->gTpl->setContent($settings_form->getHtml());
	}

	protected function fillSettingsFormFromDatabase($settings_form) {
		$data = $this->object->settings;
		$title = $this->object->getTitle();
		$desc = $this->object->getDescription();

		$settings_form->getItemByPostVar('title')->setValue($title);
		$settings_form->getItemByPostVar('description')->setValue($desc);

		$settings_form = $this->settings_form_handler->insertValues($data, $settings_form, $this->object->global_report_settings);
		$settings_form = $this->settings_form_handler->insertValues($data, $settings_form, $this->object->local_report_settings);
		return $settings_form;
	}

	protected function saveSettings() {
		$settings_form = $this->settingsForm();
		$settings_form->setValuesByPost();
		if($settings_form->checkInput()) {
			$this->saveSettingsData($settings_form);
			$red = $this->gCtrl->getLinkTarget($this, "settings", "", false, false);
			ilUtil::redirect($red);
		}
		$this->gTpl->setContent($settings_form->getHtml());
	}

	protected function saveSettingsData($settings_form) {
		$this->object->setTitle($settings_form->getItemByPostVar('title')->getValue());
		$this->object->setDescription($settings_form->getItemByPostVar('description')->getValue());

		$settings = array_merge($this->settings_form_handler->extractValues($settings_form,$this->object->global_report_settings)
								,$this->settings_form_handler->extractValues($settings_form,$this->object->local_report_settings));
		$this->object->setSettingsData($settings);

		$this->object->doUpdate();
		$this->object->update();
	}

	protected function settingsForm() {
		$settings_form = new ilPropertyFormGUI();
		$settings_form->setFormAction($this->gCtrl->getFormAction($this));
		$settings_form->addCommandButton("saveSettings", $this->gLng->txt("save"));

		$title = new ilTextInputGUI($this->gLng->txt('title'),'title');
		$title->setRequired(true);
		$settings_form->addItem($title);

		$description = new ilTextAreaInputGUI($this->gLng->txt('description'),'description');
		$settings_form->addItem($description);

		$this->settings_form_handler->addToForm($settings_form, $this->object->global_report_settings);
		$this->settings_form_handler->addToForm($settings_form, $this->object->local_report_settings);
		return $settings_form;
	}
}