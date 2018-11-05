<?php
declare(strict_types=1);

use \ILIAS\TMS\Mailing;
use \ILIAS\TMS\CourseCreation\Request;

/* Copyright (c) 2017 Nils Haagen <nils.haagen@concepts-and-training.de> */

/**
 * Course-related placeholder-values
 */
class ilTMSMailContextCourseCreation implements Mailing\MailContext {
	private static $PLACEHOLDER = array(
		'COURSE_TITLE' => 'placeholder_desc_crs_title',
		'COURSE_START_DATE' => 'placeholder_desc_crs_startdate',
		'COURSE_END_DATE' => 'placeholder_desc_crs_enddate'
	);

	/**
	 * @var int
	 */
	protected $request;

	/**
	 * @var ilLanguage
	 */
	protected $g_lang;

	public function __construct(Request $request, \ilLanguage $lng)
	{
		$this->request = $request;
		$this->lng = $lng;
		$this->lng->loadLanguageModule("tms");
	}

	/**
	 * @inheritdoc
	 */
	public function valueFor($placeholder_id, $contexts = array())
	{
		switch ($placeholder_id) {
			case 'COURSE_TITLE':
				return $this->crsTitle();
			case 'COURSE_START_DATE':
				return $this->crsStartdate();
			case 'COURSE_END_DATE':
				return $this->crsEnddate();
			default:
				return null;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function placeholderIds()
	{
		return array_keys($this::$PLACEHOLDER);
	}

	/**
	 * @inheritdoc
	 */
	public function placeholderDescriptionForId($placeholder_id)
	{
		return $this->lng->txt(self::$PLACEHOLDER[$placeholder_id]);
	}

	protected function crsTitle(): string
	{
		$crs_ref_id = $this->request->getCourseRefId();
		$title = $this->getTitleFromRequest($crs_ref_id);
		if(is_null($title)) {
			$title = $this->getTitleFromTemplate($crs_ref_id);
		}

		return $title;
	}

	protected function getTitleFromTemplate(int $crs_ref_id): string
	{
		$crs = $this->getObjectByRefId($crs_ref_id);
		$title = $crs->getTitle();
		$matches = [];
		preg_match("/^(.*)\s-\s.*$/", $title, $matches);
		return $matches[1];
	}

	/**
	 * @return string|null
	 */
	protected function getTitleFromRequest(int $crs_ref_id)
	{
		$config = $this->getConfigFor($crs_ref_id);
		if(isset($config["title"])) {
			return $config["title"];
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	protected function crsStartdate()
	{
		return $this->getDateOf("start");
	}

	/**
	 * @return string|null
	 */
	protected function crsEnddate()
	{
		return $this->getDateOf("end");
	}

	/**
	 * @return string|null
	 */
	protected function getDateOf(string $val)
	{
		$crs_ref_id = $this->request->getCourseRefId();
		$config = $this->getConfigFor($crs_ref_id);
		if(!array_key_exists("course_period", $config)) {
			return null;
		}

		if(!array_key_exists($val, $config["course_period"])) {
			return null;
		}

		return $config["course_period"][$val];
	}

	protected function getObjectByRefId(int $ref_id): \ilObject
	{
		assert('is_int($ref_id)');
		$object = \ilObjectFactory::getInstanceByRefId($ref_id);
		assert('$object instanceof \ilObject');
		return $object;
	}

	/**
	 * @param string|int 	$sub
	 * @return mixed[]
	 */
	protected function getConfigFor($sub): array
	{
		global $DIC;
		$log = $DIC["ilLog"];
		if(is_null($this->config[$sub])) {
			$config = [];
			foreach($this->request->getConfigurationFor($sub) as $configs) {
				foreach($configs as $key => $vals) {
					$config[$key] = $vals;
				}
			}

			$this->config[$sub] = $config;
		}
		$log->dump($this->config[$sub]);
		return $this->config[$sub];
	}

	protected function txt(string $code): string
	{
		return $this->lng->txt($code);
	}
}
