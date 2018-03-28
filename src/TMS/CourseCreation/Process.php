<?php

/* Copyright (c) 2018 Richard Klees <richard.klees@concepts-and-training.de> */

namespace ILIAS\TMS\CourseCreation;

/**
 * Creates courses based on templates.
 */
class Process {
	const WAIT_FOR_DB_TO_INCORPORATE_CHANGES_IN_S = 2;
	const SOAP_TIMEOUT = 30;

	/**
	 * @var	\ilTree
	 */
	protected $tree;

	/**
	 * @var	\ilDBInterface
	 */
	protected $db;

	public function __construct(\ilTree $tree, \ilDBInterface $db) {
		$this->tree = $tree;
		$this->db = $db;
	}

	/**
	 * Run the course creation process for a given course.
	 *
	 * @return Request
	 */
	public function run(Request $request) {
		$ref_id = $this->cloneAllObject($request);

		$request = $request->withTargetRefIdAndFinishedTS((int)$ref_id, new \DateTime());

		sleep(self::WAIT_FOR_DB_TO_INCORPORATE_CHANGES_IN_S);

		$this->adjustCourseTitle($request);
		$this->setCourseOnline($request);
		$this->configureCopiedObjects($request);

		return $request;
	}

	/**
	 * Get copy options for the ilCopyWizard from the request.
	 *
	 * @param Request	$request
	 * @return	array
	 */
	protected function getCopyWizardOptions(Request $request) {
		$sub_nodes = $this->tree->getSubTreeIds($request->getCourseRefId());
		$options = [];
		foreach ($sub_nodes as $sub) {
			$options[(int)$sub] = ["type" => $request->getCopyOptionFor((int)$sub)];
		}
		return $options;
	}

	/**
	 * Remove the residues from the copy process in the title.
	 *
	 * @param	Request		$request
	 * @return void
	 */
	protected function adjustCourseTitle($request) {
		$crs_ref_id = $request->getTargetRefId();
		$crs = $this->getObjectByRefId($crs_ref_id);
		$title = $crs->getTitle();
		$matches = [];
		preg_match("/^(.*)\s-\s.*$/", $title, $matches);
		$crs->setTitle($matches[1]);
		$crs->update();
	}

	/**
	 * Set course online.
	 *
	 * @param	Request		$request
	 * @return void
	 */
	protected function setCourseOnline($request) {
		$crs_ref_id = $request->getTargetRefId();
		$crs = $this->getObjectByRefId($crs_ref_id);
		$crs->setOfflineStatus(false);
		$crs->update();
	}

	/**
	 * Configure copied objects.
	 *
	 * @param	Request $request
	 * @return	null
	 */
	protected function configureCopiedObjects(Request $request) {
		$target_ref_id = $request->getTargetRefId();
		assert('!is_null($target_ref_id)');

		$sub_nodes = array_merge(
			[$target_ref_id],
			$this->tree->getSubTreeIds($target_ref_id)
		);
		$mappings = $this->getCopyMappings($sub_nodes);
		foreach ($sub_nodes as $sub) {
			$configs = $request->getConfigurationFor($mappings[$sub]);
			if ($configs === null) {
				continue;
			}
			$object = $this->getObjectByRefId((int)$sub);
			assert('method_exists($object, "afterCourseCreation")');
			foreach($configs as $config) {
				$object->afterCourseCreation($config);
			}
		}
	}

	/**
	 * Get copy mappings for ref_ids, where target => source.
	 *
	 * @param	int[]	$ref_ids
	 * @return	array<int,int>
	 */
	protected function getCopyMappings(array $ref_ids) {
		$res = $this->db->query(
			"SELECT tgt.ref_id tgt_ref, src.ref_id src_ref ".
			"FROM object_reference tgt ".
			"JOIN copy_mappings mp ON tgt.obj_id = mp.obj_id ".
			"JOIN object_reference src ON mp.source_id = src.obj_id ".
			"WHERE ".$this->db->in("tgt.ref_id", $ref_ids, false, "integer")
		);
		$mappings = [];
		while ($row = $this->db->fetchAssoc($res)) {
			$mappings[(int)$row["tgt_ref"]] = (int)$row["src_ref"];
		}
		return $mappings;
	}

	/**
	 * Get an object for the given ref.
	 *
	 * @param	int		$ref_id
	 * @return	\ilObject
	 */
	protected function getObjectByRefId($ref_id) {
		assert('is_int($ref_id)');
		$object = \ilObjectFactory::getInstanceByRefId($ref_id);
		assert('$object instanceof \ilObject');
		return $object;
	}

	/**
	 * Our custom version of ilContainer::cloneAllObject.
	 *
	 * Allows us to mess with modalities of creation via SOAP.
	 *
	 * @param	Request $request
	 * @return	int ref_id of clone
	 */
	protected function cloneAllObject(Request $request)
	{
		global $ilLog, $ilAccess,$ilErr,$rbacsystem,$tree;

		include_once('./Services/Link/classes/class.ilLink.php');
		include_once('Services/CopyWizard/classes/class.ilCopyWizardOptions.php');

		$session_id = $request->getSessionId();
		$client_id = CLIENT_ID;
		$new_type = "crs";
		$ref_id = $this->tree->getParentId($request->getCourseRefId());
		$clone_source = $request->getCourseRefId();
		$options = $this->getCopyWizardOptions($request);
		$a_submode = 1;

		// Save wizard options
		$copy_id = \ilCopyWizardOptions::_allocateCopyId();
		$wizard_options = \ilCopyWizardOptions::_getInstance($copy_id);
		$wizard_options->saveOwner($request->getUserId());
		$wizard_options->saveRoot($clone_source);

		// add entry for source container
		$wizard_options->initContainer($clone_source, $ref_id);

		foreach($options as $source_id => $option)
		{
			$wizard_options->addEntry($source_id,$option);
		}
		$wizard_options->read();
		$wizard_options->storeTree($clone_source);

		// Duplicate session to avoid logout problems with backgrounded SOAP calls
		// Start cloning process using soap call
		include_once 'Services/WebServices/SOAP/classes/class.ilSoapClient.php';

		$soap_client = new \ilSoapClient();
		$soap_client->setResponseTimeout(self::SOAP_TIMEOUT);
		$soap_client->enableWSDL(true);

		$ilLog->write(__METHOD__.': Trying to call Soap client...');
		if(!$soap_client->init()) {
			throw new \RuntimeException("Could not init SOAP client.");
		}

		\ilLoggerFactory::getLogger('obj')->info('Calling soap clone method');
		$res = $soap_client->call('ilClone',array($session_id.'::'.$client_id, $copy_id));

		if ($res === false || !is_numeric($res)) {
			throw new \RuntimeException("Could not clone course via SOAP.");
		}

		return (int)$res;
	}
}

