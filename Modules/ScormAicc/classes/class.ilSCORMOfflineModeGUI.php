<?php/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE *//*** GUI class ilSCORMOfflineModeGUI** GUI class for scorm offline player connection** @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>* @version $Id: class.ilSCORMOfflineModeGUI.php  $***/class ilSCORMOfflineModeGUI{	function ilSCORMOfflineModeGUI($type) {		global $ilias, $tpl, $lng, $ilCtrl;		$this->ilias =& $ilias;		$this->tpl =& $tpl;		$this->lng =& $lng;		$this->ctrl =& $ilCtrl;				$this->ctrl->saveParameter($this, "ref_id");	}	function executeCommand()	{		global $tpl;		// Fill meta header tags		$tpl->setCurrentBlock('mh_meta_item');		$tpl->setVariable('MH_META_NAME','require-sop-version');		$tpl->setVariable('MH_META_CONTENT',"0.1");		$tpl->addJavascript('./Modules/ScormAicc/scripts/sopConnector.js');		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.scorm_offline_mode.html", "Modules/ScormAicc");		$this->tpl->setVariable("BLABLA","Hallo Stefan");		$tpl->parseCurrentBlock();//		include_once('Services/MetaData/classes/class.ilMDUtils.php');//		ilMDUtils::_fillHTMLMetaTags($this->object->getId(),$this->object->getId(),'crs');		global $ilCtrl;		$next_class = $ilCtrl->getNextClass($this);		echo $next_class;		$cmd = $this->ctrl->getCmd();	}    }?>