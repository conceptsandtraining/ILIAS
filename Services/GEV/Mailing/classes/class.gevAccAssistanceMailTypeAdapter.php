<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './Services/MailTemplates/classes/class.ilMailTypeAdapter.php';

/**
 * GEV mail placeholders for courses
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 */
class gevAccAssistanceMailTypeAdapter extends ilMailTypeAdapter {
	private $placeholders = null;
	
	public function getCategoryNameLocalized($category_name, $lng) {
		return 'Account-assistance';
	}

	public function getTemplateTypeLocalized($category_name, $template_type, $lng) {
		return 'Generisch';
	}

	protected function getPlaceholders() {
		if ($this->placeholders == null) {
			$this->placeholders = array(
				  array("PWASSIST_LINK",	"Password Modification Link")
				, array("EMAIL",			"E-Mail-Address of Requesting User")
				, array("USR_LOGINS",		"List of Logins of Requesting User")
				);
		}
	
		return $this->placeholders;
	}

	public function getPlaceholdersLocalized($category_name = '', $template_type = '', $lng = '') {
		$ret = array();

		foreach($this->getPlaceholders() as $item)
		{
			$ret[] = array(
				'placeholder_code'          => strtoupper($item[0]),
				'placeholder_name'          => $item[0],
				'placeholder_description'   => $item[1]
			);
		};

		return $ret;
	}

	public function getPlaceHolderPreviews($category_name = '', $template_type = '', $lng = '') {
		$ret = array();

		foreach($this->getPlaceholders() as $item)
		{
			$ret[] = array(
				'placeholder_code'			=> strtoupper($item[0]),
				'placeholder_content'       => $item[0]
			);
		}

		return $ret;
	}

	public function hasAttachmentsPreview() {
		return false;
	}

	public function getAttachmentsPreview() {

	}
}

?>