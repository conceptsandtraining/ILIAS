<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* implementation of GEV WBD Request for Service VermittlerVerwaltung
* part: Vermittler transferfähig machen
*
* @author	Stefan Hecken <shecken@concepts-and-training.de>
* @version	$Id$
*
*/
require_once("Services/GEV/WBD/classes/Requests/trait.gevWBDRequest.php");
require_once("Services/GEV/WBD/classes/Success/class.gevWBDSuccessKontoAufnahme.php");
require_once("Services/GEV/WBD/classes/Error/class.gevWBDError.php");

class gevWBDRequestKontoAufnahme extends WBDRequestKontoAufnahme {
	use gevWBDRequest;

	protected $error_group;

	protected function __construct($data) {
		$this->auth_email = new WBDData(
			"AuthentifizierungsEmail",
			$data["email"]
		);
		$this->auth_mobile_phone_nr = new WBDData(
			"AuthentifizierungsTelefonnummer",
			$data["mobile_phone_nr"]
		);
		$this->agent_id = new WBDData(
			"gutberatenId",
			$data["bwv_id"]
		);
		$this->firstname = new WBDData(
			"VorName",
			$data["firstname"]
		);
		$this->lastname = new WBDData(
			"Name",
			$data["lastname"]
		);
		$this->birthday = new WBDData(
			"Geburtsdatum", $data["birthday"]);

		$this->user_id = $data["user_id"];
		$this->row_id = $data["row_id"];
		$this->error_group = gevWBDError::ERROR_GROUP_USER;

		$errors = $this->checkData();

		if(!empty($errors)) {
			throw new myLogicException(
				"gevWBDRequestKontoAufnahme::__construct:checkData failed",
				0,
				null,
				$errors
			);
		}
	}

	public static function getInstance(array $data) {
		$data = self::polishInternalData($data);

		try {
			return new gevWBDRequestKontoAufnahme($data);
		}catch(myLogicException $e) {
			return $e->options();
		}
	}

	/**
	* creates the success object KontoTransferfaehig
	*
	* @throws LogicException
	*/
	public function createWBDSuccess($response) {
		$this->wbd_success = new gevWBDSuccessKontoAufnahme(
			$this->user_id,
			$this->row_id
		);
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
	* gets the row_id
	*
	* @return integer
	*/
	public function rowId() {
		return $this->row_id;
	}

	/**
	* gets the user_id
	*
	* @return integer
	*/
	public function userId() {
		return $this->user_id;
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