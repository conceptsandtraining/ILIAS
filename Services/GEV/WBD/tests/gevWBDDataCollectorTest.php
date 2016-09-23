<?php
require_once("Services/GEV/WBD/classes/class.gevWBDDataCollector.php");
require_once("Services/GEV/WBD/classes/Error/class.gevWBDError.php");
require_once("Services/GEV/WBD/classes/class.gevWBD.php");

class _gevWBDError extends gevWBDError {
	protected function findReason() {
		$this->reason = "hugo wars";
		$this->internal = 1;
	}
}

class _gevWBDDataCollector extends gevWBDDataCollector {
	public $called_error = 0;

	public function testable_createNewUserList($db) {
		return $this->_createNewUserList($db);
	}

	public function testable_createUpdateUserList($db) {
		return $this->_createUpdateUserList($db);
	}

	public function testable_createReleaseUserList($db) {
		return $this->_createReleaseUserList($db);
	}

	public function testable_createNewEduRecordList($db) {
		return $this->_createNewEduRecordList($db);
	}

	public function testable_createWPAbfrageRecordList($db) {
		return $this->_createWPAbfrageRecordList($db);
	}

	public function error(gevWBDError $error) {
		$this->called_error++;
	}

	public function testable_error(gevWBDError $error) {
		parent::error($error);
	}

	public function performPreliminaryChecks($checks_to_release, $wbd) {
		return $this->prem_errors;
	}

	public function setPreliminaryErrors(array $prem_errors = array()) {
		$this->prem_errors = $prem_errors;
	}

	public function setDB($db) {
		$this->gDB = $db;
	}

	protected function createHistUserCourseRow($case_id, $data) {
		$this->case_id = $case_id;
		$this->data = $data;
	}

	protected function getWBDInstance($user_id) {
		return mock_gevWBD::getInstanceByObjOrId($user_id);
	}

	public function getCurrentDate() {
		return date("Y-m-d");
	}
}

class mock_gevWBD extends gevWBD {
	const CRS_ID = 10;

	protected function __construct($a_user_id) {
		$this->usr_id = $a_user_id;
	}

	static public function getInstanceByObjOrId($a_user) {
		return new self($a_user);
	}

	public function getCrsIdByRowId($row_id) {
		return self::CRS_ID;
	}
}

class mock_db {
	public $called_query = 0;
	public $called_fetchAssoc = 0;
	public $called_executed = 0;
	public $query_string = null;

	public $data = null;

	public function __construct(array $data) {
		$this->data = $data;
	}

	public function query($string) {
		$this->called_query++;
		$this->query_string = $string;
		return $this->data;
	}

	public function fetchAssoc(&$res) {
		$this->called_fetchAssoc++;
		return array_shift($res);
	}

	public function execute($sql, $data) {
		$this->called_executed++;
	}

	public function numRows($res) {
		return count($res);
	}
}

class gevWBDDataCollectorTest extends PHPUnit_Framework_TestCase {
	protected $backupGlobals = FALSE;

	public function setUp() {
		error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
		PHPUnit_Framework_Error_Deprecated::$enabled = FALSE;

		include_once("./Services/PHPUnit/classes/class.ilUnitUtil.php");
		ilUnitUtil::performInitialisation();

		$this->data_collector = new _gevWBDDataCollector("/Library/WebServer/Documents/cat_ilias_kunden/44generali/");
	}

	public function test_isWBDDataCollector() {
		$this->assertInstanceOf("gevWBDDataCollector", $this->data_collector);
	}

	public function test_createNewUserList() {
		$data = $this->getNewUserData();

		$db = new mock_db($data);

		$this->data_collector->testable_createNewUserList($db);

		$this->assertEquals(1, $db->called_query);
		$this->assertEquals(count($data) + 1, $db->called_fetchAssoc);
		$this->assertTrue($this->data_collector->called_error === 0);
	}

	public function test_createNewUserListError() {
		$data = $this->getNewUserDataError();

		$db = new mock_db($data);

		$this->data_collector->testable_createNewUserList($db);

		$this->assertEquals(1, $db->called_query);
		$this->assertEquals(count($data) + 1, $db->called_fetchAssoc);
		$this->assertTrue($this->data_collector->called_error > 0);
	}

	public function test_createUpdateUserList() {
		$data = $this->getUpdateUserData();

		$db = new mock_db($data);

		$this->data_collector->testable_createUpdateUserList($db);

		$this->assertEquals(1, $db->called_query);
		$this->assertEquals(count($data) + 1, $db->called_fetchAssoc);
		$this->assertTrue($this->data_collector->called_error === 0);
	}

	public function test_createUpdateUserListError() {
		$data = $this->getUpdateUserDataError();

		$db = new mock_db($data);

		$this->data_collector->testable_createUpdateUserList($db);

		$this->assertEquals(1, $db->called_query);
		$this->assertEquals(count($data) + 1, $db->called_fetchAssoc);
		$this->assertTrue($this->data_collector->called_error > 0);
	}

	public function test_createReleaseUserList() {
		$data = $this->getRealeseUserData();
		$this->data_collector->setPreliminaryErrors();

		$db = new mock_db($data);

		$this->data_collector->testable_createReleaseUserList($db);

		$this->assertEquals(1, $db->called_query);
		$this->assertEquals(count($data) + 1, $db->called_fetchAssoc);
		$this->assertTrue($this->data_collector->called_error === 0);
	}

	public function test_createReleaseUserListError() {
		$data = $this->getRealeseUserDataError();
		$this->data_collector->setPreliminaryErrors(array(new _gevWBDError("mandatory field missing: gender","user", "NEW_USER", 1, 2, 3)));

		$db = new mock_db($data);

		$this->data_collector->testable_createReleaseUserList($db);

		$this->assertEquals(1, $db->called_query);
		$this->assertEquals(count($data) + 1, $db->called_fetchAssoc);
		$this->assertTrue($this->data_collector->called_error > 0);
	}

	public function test_createNewEduRecordList() {
		$data = $this->getNewEduRecordData();

		$db = new mock_db($data);

		$this->data_collector->testable_createNewEduRecordList($db);

		$this->assertEquals(1, $db->called_query);
		$this->assertEquals(count($data) + 1, $db->called_fetchAssoc);
		$this->assertTrue($this->data_collector->called_error === 0);
	}

	public function test_createNewEduRecordListError() {
		$data = $this->getNewEduRecordDataError();
		
		$db = new mock_db($data);

		$this->data_collector->testable_createNewEduRecordList($db);

		$this->assertEquals(1, $db->called_query);
		$this->assertEquals(count($data) + 1, $db->called_fetchAssoc);
		$this->assertTrue($this->data_collector->called_error > 0);
	}

	public function test_createWPAbrageRecordList() {
		$data = $this->getWPAbfrageRecordData();
		
		$db = new mock_db($data);

		$this->data_collector->testable_createWPAbfrageRecordList($db);

		$this->assertEquals(1, $db->called_query);
		$this->assertEquals(count($data) + 1, $db->called_fetchAssoc);
		$this->assertTrue($this->data_collector->called_error === 0);
	}

	/**
	* @dataProvider wbdErrorProvider
	*/
	public function test_writeErrorToDB($error) {
		$db = new mock_db(array());
		$this->data_collector->setDB($db);
		$this->data_collector->testable_error($error);

		$this->assertEquals(1, $db->called_executed);
	}

	public function test_stornoSuccess() {
		$this->row_id = 25;
		$success = new gevWBDSuccessWPStorno(simplexml_load_string('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope">'
												.'<soap:Body>'
													.'<ns1:putResponse xmlns:ns1="http://erstanlage.stammdaten.external.service.wbd.gdv.de/">'
														.'<WPStornoRueckgabewert>'
															.'<WeiterbildungsPunkteBuchungsId>2015-145-1654</WeiterbildungsPunkteBuchungsId>'
															.'<VermittlerId>20150728-100390-74</VermittlerId>'
															.'<InterneVermittlerId>21352</InterneVermittlerId>'
															.'<BeginnErstePeriode>2015-07-28T00:00:00+02:00</BeginnErstePeriode>'
														.'</WPStornoRueckgabewert>'
													.'</ns1:putResponse>'
												.'</soap:Body>'
											.'</soap:Envelope>'
									),$this->row_id);

		$this->data_collector->successStornoRecord($success);

		$needed_case_id = array('usr_id' => 21352
							  , 'crs_id' => 10);

		$needed_data = array("wbd_cancelled" => true
					, "last_wbd_report" => $this->data_collector->getCurrentDate()
				);

		$this->assertEquals($needed_case_id, $this->data_collector->case_id);
		$this->assertEquals($needed_data, $this->data_collector->data);
	}

	protected function getNewUserData() {
		return array(array("gender"=>"m"
					  ,"email"=>"shecken@cat06.de"
					  ,"mobile_phone_nr"=>"0162/9800608"
					  ,"birthday"=>"1981-06-19"
					  ,"country"=>"D"
					  ,"lastname"=>"Hecken"
					  ,"city"=>"Köln"
					  ,"zipcode"=>"50969"
					  ,"phone_nr"=>"0221/46757600"
					  ,"degree"=>"Dr"
					  ,"wbd_agent_status"=>"Makler"
					  ,"okz"=>"OKZ1"
					  ,"firstname"=>"Stefan"
					  ,"wbd_type"=>"3 - TP-Service"
					  ,"user_id"=>290
					  ,"street"=>"Vorgebirgstr. 338"
					  ,"row_id"=>35214
					),array("gender"=>"m"
					  ,"email"=>"shecken@cat06.de"
					  ,"mobile_phone_nr"=>"0162/9800608"
					  ,"birthday"=>"1981-06-19"
					  ,"country"=>"D"
					  ,"lastname"=>"Hecken"
					  ,"city"=>"Köln"
					  ,"zipcode"=>"50969"
					  ,"phone_nr"=>"0221/46757600"
					  ,"degree"=>"Dr"
					  ,"wbd_agent_status"=>"Makler"
					  ,"okz"=>"OKZ1"
					  ,"firstname"=>"Stefan"
					  ,"wbd_type"=>"3 - TP-Service"
					  ,"user_id"=>290
					  ,"street"=>"Vorgebirgstr. 338"
					  ,"row_id"=>35214
					));
	}

	protected function getNewUserDataError() {
		return array(array("gender"=>"m"
					  ,"email"=>"shecken@cat06.de"
					  ,"mobile_phone_nr"=>"0162/9800608"
					  ,"birthday"=>"1981-06-19"
					  ,"country"=>"D"
					  ,"lastname"=>"Hecken"
					  ,"city"=>"Köln"
					  ,"zipcode"=>"50969"
					  ,"phone_nr"=>"0221/46757600"
					  ,"degree"=>"Dr"
					  ,"wbd_agent_status"=>"Makler"
					  ,"okz"=>""
					  ,"firstname"=>"Stefan"
					  ,"wbd_type"=>"3 - TP-Service"
					  ,"user_id"=>290
					  ,"street"=>"Vorgebirgstr. 338"
					  ,"row_id"=>35214
					),array("gender"=>"m"
					  ,"email"=>"shecken@cat06.de"
					  ,"mobile_phone_nr"=>"0162/9800608"
					  ,"birthday"=>"1981-06-19"
					  ,"country"=>"D"
					  ,"lastname"=>"Hecken"
					  ,"city"=>"Köln"
					  ,"zipcode"=>"50969"
					  ,"phone_nr"=>"0221/46757600"
					  ,"degree"=>"Dr"
					  ,"wbd_agent_status"=>"Makler"
					  ,"okz"=>"OKZ1"
					  ,"firstname"=>"Stefan"
					  ,"wbd_type"=>""
					  ,"user_id"=>290
					  ,"street"=>"Vorgebirgstr. 338"
					  ,"row_id"=>35214
					));
	}

	public function successNewUser() {
		return array(array(simplexml_load_string('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope">'
					.'<soap:Body>'
						.'<ns1:putResponse xmlns:ns1="http://erstanlage.stammdaten.external.service.wbd.gdv.de/">'
							.'<ErstanlageRueckgabewert>'
								.'<TpInterneVermittlerId>7665</TpInterneVermittlerId>'
								.'<VermittlerId>20150728-100390-74</VermittlerId>'
								.'<AnlageDatum>2015-07-28T00:00:00+02:00</AnlageDatum>'
								.'<BeginnZertifizierungsPeriode>2015-07-28T00:00:00+02:00</BeginnZertifizierungsPeriode>'
							.'</ErstanlageRueckgabewert>'
						.'</ns1:putResponse>'
					.'</soap:Body>'
				.'</soap:Envelope>'
									))
					,array(simplexml_load_string('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope">'
						.'<soap:Body>'
						.'<ns1:putResponse xmlns:ns1="http://erstanlage.stammdaten.external.service.wbd.gdv.de/">'
							.'<ErstanlageRueckgabewert>'
								.'<TpInterneVermittlerId>7665</TpInterneVermittlerId>'
								.'<VermittlerId>20150728-100390-74</VermittlerId>'
								.'<AnlageDatum>2015-07-28T00:00:00+02:00</AnlageDatum>'
								.'<BeginnZertifizierungsPeriode>2015-07-28T00:00:00+02:00</BeginnZertifizierungsPeriode>'
							.'</ErstanlageRueckgabewert>'
						.'</ns1:putResponse>'
						.'</soap:Body>'
					.'</soap:Envelope>'
									))
					,array(simplexml_load_string('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope">'
						.'<soap:Body>'
						.'<ns1:putResponse xmlns:ns1="http://erstanlage.stammdaten.external.service.wbd.gdv.de/">'
							.'<ErstanlageRueckgabewert>'
								.'<TpInterneVermittlerId>7665</TpInterneVermittlerId>'
								.'<VermittlerId>20150728-100390-74</VermittlerId>'
								.'<AnlageDatum>2015-07-28T00:00:00+02:00</AnlageDatum>'
								.'<BeginnZertifizierungsPeriode>2015-07-28T00:00:00+02:00</BeginnZertifizierungsPeriode>'
							.'</ErstanlageRueckgabewert>'
						.'</ns1:putResponse>'
						.'</soap:Body>'
					.'</soap:Envelope>'
									))
			);
	}

	protected function getUpdateUserData() {
		return array(array("address_type"=>"geschäftlich"
					  ,"gender"=>"m"
					  ,"email"=>"shecken@cat06.de"
					  ,"mobile_phone_nr"=>"0162/9800608"
					  ,"info_via_mail"=>false
					  ,"birthday"=>"1981-06-19"
					  ,"country"=>"D"
					  ,"lastname"=>"Hecken"
					  ,"city"=>"Köln"
					  ,"zipcode"=>"50969"
					  ,"phone_nr"=>"0221/46757600"
					  ,"degree"=>"Dr"
					  ,"wbd_agent_status"=>"Makler"
					  ,"okz"=>"OKZ1"
					  ,"firstname"=>"Stefan"
					  ,"user_id"=>290
					  ,"street"=>"Vorgebirgstr. 338"
					  ,"row_id"=>35214
					  ,"address_info"=>"Der wohnt bei Mutti"
					  ,"bwv_id"=>"1212-2323-23-2323"
					));
	}

	protected function getUpdateUserDataError() {
		return array(array("address_type"=>"geschäftlich"
					  ,"gender"=>"m"
					  ,"email"=>"shecken@cat06.de"
					  ,"mobile_phone_nr"=>"0162/9800608"
					  ,"info_via_mail"=>false
					  ,"birthday"=>"1981-06-19"
					  ,"country"=>"D"
					  ,"lastname"=>""
					  ,"city"=>"Köln"
					  ,"zipcode"=>"50969"
					  ,"phone_nr"=>"0221/46757600"
					  ,"degree"=>"Dr"
					  ,"wbd_agent_status"=>"Makler"
					  ,"okz"=>"OKZ1"
					  ,"firstname"=>"Stefan"
					  ,"user_id"=>290
					  ,"street"=>"Vorgebirgstr. 338"
					  ,"row_id"=>35214
					  ,"address_info"=>"Der wohnt bei Mutti"
					  ,"bwv_id"=>"1212-2323-23-2323"
					));
	}

	protected function getRealeseUserData() {
		return array(array("email"=>"shecken@cat06.de"
					  ,"mobile_phone_nr"=>"0162/9800608"
					  ,"bwv_id"=>"1212-2323-23-2323"
					  ,"user_id"=>290
					  ,"row_id"=>35214
					));
	}

	protected function getRealeseUserDataError() {
		return array(array("email"=>"shecken@cat06.de"
					  ,"mobile_phone_nr"=>"0162/9800608"
					  ,"bwv_id"=>""
					  ,"user_id"=>290
					  ,"row_id"=>35214
					));
	}

	protected function getNewEduRecordData() {
		return array(array("title"=>"Berufsunfähigkeitsversicherung 2013"
					  ,"begin_date" => "2015-12-20"
					  ,"end_date" => "2015-12-20"
					  ,"credit_points" => 5
					  ,"type" => "Virtuelles Training"
					  ,"wbd_topic" => "Privat-Vorsorge-Lebens-/Rentenversicherung"
					  ,"row_id"=>35214
					  ,"user_id"=>290
					  ,"bwv_id" => "22332-565-321-65"
					));
	}

	protected function getNewEduRecordDataError() {
		return array(array("title"=>"Berufsunfähigkeitsversicherung 2013"
					  ,"begin_date" => "2015-12-20"
					  ,"end_date" => "2015-12-20"
					  ,"credit_points" => 5
					  ,"type" => ""
					  ,"wbd_topic" => "Privat-Vorsorge-Lebens-/Rentenversicherung"
					  ,"row_id"=>35214
					  ,"user_id"=>290
					  ,"bwv_id" => "22332-565-321-65"
					));
	}

	protected function getWPAbfrageRecordData() {
		return array(array("row_id"=>35214
					  ,"user_id"=>290
					  ,"bwv_id" => "22332-565-321-65"
					));
	}

	protected function getWPAbfrageRecordDataError() {
		return array(array("row_id"=>35214
					  ,"user_id"=>290
					  ,"bwv_id" => ""
					));
	}

	public function wbdErrorProvider() {
		return array(array(new _gevWBDError("mandatory field missing: gender","user", "NEW_USER", 1, 2, 3)),
						array(new _gevWBDError("mandatory field missing: gender","user", "NEW_USER", 1, 2, 3)),
						array(new _gevWBDError("mandatory field missing: gender","user", "NEW_USER", 1, 2, 3)),
						array(new _gevWBDError("mandatory field missing: gender","user", "NEW_USER", 1, 2, 3)),
						array(new _gevWBDError("mandatory field missing: gender","user", "NEW_USER", 1, 2, 3)),
						array(new _gevWBDError("mandatory field missing: gender","user", "NEW_USER", 1, 2, 3))
			);
	}
}
?>