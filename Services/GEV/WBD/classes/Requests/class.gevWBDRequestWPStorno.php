<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* implementation of GEV WBD Request for Service WPMeldung
*
* @author	Stefan Hecken <shecken@concepts-and-training.de>
* @version	$Id$
*
*/
require_once("Services/GEV/WBD/classes/Requests/trait.gevWBDRequest.php");
require_once("Services/GEV/WBD/classes/Success/class.gevWBDSuccessBildungszeitStorno.php");
require_once("Services/GEV/WBD/classes/Error/class.gevWBDError.php");

class gevWBDRequestBildungszeitStorno extends WBDRequestBildungszeitStorno {
	use gevWBDRequest;

	protected $error_group;

	protected function __construct($data) {
		$this->wbd_booking_id = new WBDData(
			"WBDBuchungsId",
			$data["wbd_booking_id"]
		);
		$this->bwv_id = new WBDData(
			"gutberatenId",
			$data["bwv_id"]
		);

		$this->user_id = $data["user_id"];
		$this->row_id = $data["row_id"];
		$this->error_group = gevWBDError::ERROR_GROUP_CRS;

		$errors = $this->checkData();

		if(!empty($errors)) {
			throw new myLogicException(
				"gevWBDRequestBildungszeitStorno::__construct:checkData failed",
				0,
				null,
				$errors
			);
		}
	}

	public static function getInstance(array $data) {
		try {
			return new gevWBDRequestBildungszeitStorno($data);
		}catch(myLogicException $e) {
			return $e->options();
		}
	}

	/**
	* checked all given data
	*
	* @throws LogicException
	* 
	* @return string
	*/
	protected function checkData() {
		return $this->checkSzenarios();
	}

	/**
	* creates the success object VvErstanlage
	*
	* @throws LogicException
	*/
	public function createWBDSuccess($response) {
		$this->wbd_success = new gevWBDSuccessBildungszeitStorno(
			$response,
			$this->row_id
		);
	}

	/**
	* gets the row_id
	*
	* @return integer
	*/
	public function rowId() {
		return $this->row_id;
	}

	/**
	* gets a new WBD Error
	*
	* @return integer
	*/
	public function createWBDError($message) {
		$reason = $this->parseReason($message);
		$this->wbd_error = self::createError(
			$reason,
			$this->error_group,
			$this->user_id,
			$this->row_id
		);
	}
}