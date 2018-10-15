<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Database abstraction for effectiveness analyis
 *
 * @author Stefan Hecken <stefan.hecken@cocepts-and-training.de>
 */
class gevEffectivenessAnalysisDB {
	const TABLE_DUED_EFF_ANA = "eff_analysis_due_date";
	const TABLE_FINISHED_EFF_ANA = "eff_analysis";

	const EMPTY_TEXT = "-empty-";
	const EMPTY_DATE = "0000-00-00";

	public function __construct($db) {
		$this->gDB = $db;
	}

	/**
	 * Create the effectiveness analysis result table
	 */
	public function createTable() {
		if( !$this->gDB->tableExists(self::TABLE_FINISHED_EFF_ANA) ) {
			$this->gDB->createTable(self::TABLE_FINISHED_EFF_ANA, array(
				'crs_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true
				),
				'user_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true
				),
				'result' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true
				),
				'info' => array(
					'type' => 'clob',
					'notnull' => false
				),
				'finish_date' => array(
					'type' => 'date',
					'notnull' => false
				)
			));

			$this->gDB->addPrimaryKey(self::TABLE_FINISHED_EFF_ANA, array('crs_id', 'user_id'));
		}
	}

	/**
	 * Get data for my effectiveness analysis view
	 *
	 * @param int[] 		$employees
	 * @param string[] 		$reason_for_eff_analysis
	 * @param mixed[]		$filter
	 * @param int 			$offset
	 * @param int 			$limit
	 * @param string 		$order
	 * @param string 		$order_direction
	 *
	 * @return array<mixed[]>
	 */
	public function getEffectivenessAnalysisData($employees, array $reason_for_eff_analysis, array $filter, $offset, $limit, $order, $order_direction) {
		$query = "SELECT husr.user_id, husr.lastname, husr.firstname, husr.email\n"
				.", GROUP_CONCAT(DISTINCT husrorgu.orgu_title SEPARATOR ', ') AS orgunit\n"
				.", hcrs.title, hcrs.type, hcrs.begin_date, hcrs.end_date, hcrs.language, hcrs.training_number\n"
				."    , hcrs.venue, hcrs.target_groups, hcrs.objectives_benefits, hcrs.training_topics, hcrs.crs_id\n"
				.", effa.finish_date, effa.result\n";
		$query .= $this->getSelectBase($employees, $reason_for_eff_analysis);
		$query .= $this->getWhereByFilter($filter);
		$query .= $this->getGroupBy();
		$query .= " ORDER BY ".$order." ".$order_direction;
		$query .= " LIMIT ".$offset.", ".$limit;

		$ret = array();
		$result = $this->gDB->query($query);
		while($row = $this->gDB->fetchAssoc($result)) {
			foreach($row as $key => $value) {
				if($value == self::EMPTY_DATE || $value == self::EMPTY_TEXT || $value === null) {
					$row[$key] = "-";
				}
			}

			$ret[] = $row;
		}

		return $ret;
	}

	/**
	 * Get number of possible effectiveness analysis without offset and limit
	 *
	 * @param int 		$user_id
	 * @param mixed[]	$filter
	 *
	 * @return int
	 */
	public function getCountEffectivenessAnalysisData($employees, array $reason_for_eff_analysis, array $filter) {
		$query = "SELECT count(hcrs.crs_id) AS cnt\n";
		$query .= $this->getSelectBase($employees, $reason_for_eff_analysis);
		$query .= $this->getWhereByFilter($filter);
		$query .= $this->getGroupBy();

		$res = $this->gDB->query($query);

		return $this->gDB->numRows($res);
	}

	/**
	 * Get data for the effectiveness analyis report
	 *
	 * @param int 		$user_id
	 * @param mixed[] 	$filter
	 * @param string 	$order
	 * @param string 	$order_direction
	 *
	 * @return mixed[]
	 */
	public function getEffectivenessAnalysisReportData($employees, array $reason_for_eff_analysis, array $filter, $order, $order_direction) {
		$query = "SELECT husr.user_id, GROUP_CONCAT(DISTINCT CONCAT_WS(', ', husr.lastname, husr.firstname)) AS member, husr.email\n"
				.", GROUP_CONCAT(DISTINCT husrorgu.orgu_title SEPARATOR ', ') AS orgunit\n"
				.", GROUP_CONCAT(DISTINCT IF(ISNULL(husr2.lastname),NULL,CONCAT_WS(', ', husr2.lastname, husr2.firstname)) SEPARATOR ', ') AS superior\n"
				.", hcrs.title, hcrs.type, hcrs.begin_date, hcrs.end_date, hcrs.language, hcrs.training_number\n"
				."    , hcrs.venue, hcrs.target_groups, hcrs.objectives_benefits, hcrs.training_topics, hcrs.crs_id\n"
				."    , hcrs.reason_for_training\n"
				.", dued_eff_ana.due_date AS scheduled\n"
				.", effa.finish_date, effa.result\n";
		$query .= $this->getSelectBase($employees, $reason_for_eff_analysis);
		$query .= $this->getWhereByFilter($filter);
		$query .= $this->getGroupBy();
		$query .= " ORDER BY ".$order." ".$order_direction;

		$res = $this->gDB->query($query);

		while($row = $this->gDB->fetchAssoc($res)) {
			foreach($row as $key => $value) {
				if($value == self::EMPTY_DATE || $value == self::EMPTY_TEXT || $value === null) {
					$row[$key] = "-";
				}
			}

			$ret[] = $row;
		}

		return $ret;
	}

	/**
	 * Get user id where superior should get first mail
	 *
	 * @param int[] 	$employees
	 * @param int 		$superior_id
	 *
	 * @return int[]
	 */
	public function getUserIdsForFirstMail($employees, $superior_id, $reason_for_eff_analysis) {
		$query = "SELECT eff_analysis_due_date.crs_id AS send_for_crs, eff_analysis_due_date.user_id,\n"
				." IF(ISNULL(MAX(eff_log_first.send)), true, false) AS send_first \n"
				." ,eff_analysis.crs_id\n"
				." FROM eff_analysis_due_date\n"
				." JOIN hist_course hcrs\n"
				."     ON eff_analysis_due_date.crs_id = hcrs.crs_id\n"
				."        AND hcrs.hist_historic = 0\n"
				." LEFT JOIN eff_analysis\n"
				."     ON eff_analysis.crs_id = eff_analysis_due_date.crs_id\n"
				."         AND eff_analysis.user_id = eff_analysis_due_date.user_id\n"
				." LEFT JOIN eff_analysis_maillog eff_log_first ON eff_log_first.user_id = eff_analysis_due_date.user_id\n"
				."     AND eff_log_first.crs_id = eff_analysis_due_date.crs_id\n"
				."     AND eff_log_first.superior_id = ".$this->gDB->quote($superior_id, "integer")."\n"
				."     AND eff_log_first.type = 'first'\n"
				." WHERE ".$this->gDB->in("eff_analysis_due_date.user_id", $employees, false, "integer")."\n"
				."     AND eff_analysis_due_date.due_date <= DATE_SUB(CURDATE(), INTERVAL 15 DAY)\n"
				."     AND eff_analysis_due_date.due_date != '0000-00-00'\n"
				."     AND hcrs.begin_date != '0000-00-00'\n"
				."     AND ".$this->gDB->in("hcrs.reason_for_training", $reason_for_eff_analysis, false, "text")."\n"
				." GROUP BY eff_analysis_due_date.user_id, eff_analysis_due_date.crs_id\n"
				." HAVING send_first = true AND eff_analysis.crs_id IS NULL\n";

		$res = $this->gDB->query($query);
		while($row = $this->gDB->fetchAssoc($res)) {
			$ret[$row["send_for_crs"]][] = $row["user_id"];
		}

		return $ret;
	}

	/**
	 * Get user id where superior should get second reminder
	 *
	 * @param int[] 	$employees
	 * @param int 		$superior_id
	 *
	 * @return int[]
	 */
	public function getUserIdsForReminder($employees, $superior_id, $reason_for_eff_analysis) {
		$query = "SELECT eff_analysis_due_date.crs_id as send_for_crs, eff_analysis_due_date.user_id,\n"
				." IF(ISNULL(MAX(eff_log_first.send)), false,\n"
				."     IF(ISNULL(MAX(eff_log_second.send)) AND MAX(eff_log_first.send) <= DATE_SUB(CURDATE(), INTERVAL 15 DAY), true,\n"
				."         IF(MAX(eff_log_second.send) <= DATE_SUB(CURDATE(), INTERVAL 2 DAY), true, false)\n"
				."     )\n"
				." ) AS send_second \n"
				." ,eff_analysis.crs_id\n"
				." FROM eff_analysis_due_date\n"
				." JOIN hist_course hcrs\n"
				."     ON eff_analysis_due_date.crs_id = hcrs.crs_id\n"
				."        AND hcrs.hist_historic = 0\n"
				." LEFT JOIN eff_analysis\n"
				."     ON eff_analysis.crs_id = eff_analysis_due_date.crs_id\n"
				."         AND eff_analysis.user_id = eff_analysis_due_date.user_id\n"
				." LEFT JOIN eff_analysis_maillog eff_log_first ON eff_log_first.user_id = eff_analysis_due_date.user_id\n"
				."     AND eff_log_first.crs_id = eff_analysis_due_date.crs_id\n"
				."     AND eff_log_first.superior_id = ".$this->gDB->quote($superior_id, "integer")."\n"
				."     AND eff_log_first.type = 'first'\n"
				." LEFT JOIN eff_analysis_maillog eff_log_second ON eff_log_second.user_id = eff_analysis_due_date.user_id\n"
				."     AND eff_log_second.crs_id = eff_analysis_due_date.crs_id\n"
				."     AND eff_log_second.superior_id = ".$this->gDB->quote($superior_id, "integer")."\n"
				."     AND eff_log_second.type = 'second'\n"
				." WHERE ".$this->gDB->in("eff_analysis_due_date.user_id", $employees, false, "integer")."\n"
				."     AND eff_analysis_due_date.due_date <= DATE_SUB(CURDATE(), INTERVAL 15 DAY)\n"
				."     AND eff_analysis_due_date.due_date != '0000-00-00'\n"
				."     AND hcrs.begin_date != '0000-00-00'\n"
				."     AND ".$this->gDB->in("hcrs.reason_for_training", $reason_for_eff_analysis, false, "text")."\n"
				." GROUP BY eff_analysis_due_date.user_id, eff_analysis_due_date.crs_id\n"
				." HAVING send_second = true AND eff_analysis.crs_id IS NULL\n";

		$res = $this->gDB->query($query);
		while($row = $this->gDB->fetchAssoc($res)) {
			$ret[$row["send_for_crs"]][] = $row["user_id"];
		}

		return $ret;
	}

	/**
	 * Save result for effectiveness analysis for each user
	 *
	 * @param int 		$crs_id
	 * @param int 		$user_id
	 * @param int 		$result
	 * @param string 	$result_info
	 */
	public function saveResult($crs_id, $user_id, $result, $result_info) {
		$values = array("crs_id" => array("integer", $crs_id)
					  , "user_id" => array("integer", $user_id)
					  , "result" => array("integer", $result)
					  , "info" => array("text", $result_info)
					  , "finish_date" => array("text", date('Y-m-d'))
			);

		$this->gDB->insert(self::TABLE_FINISHED_EFF_ANA, $values);
	}

	/**
	 * Get the base of select statement
	 *
	 * @return string
	 */
	protected function getSelectBase($employees, array $reason_for_eff_analysis) {
		$today_date = date('Y-m-d');
		$sql = " FROM ".self::TABLE_DUED_EFF_ANA." dued_eff_ana\n"
				." JOIN hist_course hcrs\n"
				."    ON hcrs.crs_id = dued_eff_ana.crs_id\n"
				."        AND hcrs.hist_historic = 0\n"
				." JOIN hist_user husr\n"
				."    ON husr.user_id = dued_eff_ana.user_id\n"
				."        AND husr.hist_historic = 0\n"
				." JOIN hist_userorgu husrorgu\n"
				."    ON husrorgu.usr_id = husr.user_id\n"
				."        AND husrorgu.hist_historic = 0\n"
				."        AND (husrorgu.action = 1 OR husrorgu.action = 0)\n"
				." LEFT JOIN hist_userorgu husrorgu2\n"
				."    ON husrorgu2.orgu_id = husrorgu.orgu_id\n"
				."        AND husrorgu2.hist_historic = 0\n"
				."        AND (husrorgu2.action = 1 OR husrorgu2.action = 0)\n"
				."        AND husrorgu2.rol_title = ".$this->gDB->quote("Vorgesetzter", "text")."\n"
				." LEFT JOIN hist_user husr2\n"
				."    ON husrorgu2.usr_id = husr2.user_id\n"
				."        AND husr2.hist_historic = 0\n"
				." LEFT JOIN ".self::TABLE_FINISHED_EFF_ANA." effa\n"
				."    ON effa.crs_id = dued_eff_ana.crs_id\n"
				."        AND effa.user_id = dued_eff_ana.user_id\n"
				." WHERE dued_eff_ana.due_date <= ".$this->gDB->quote($today_date, "text")."\n"
				."    AND ".$this->gDB->in("dued_eff_ana.user_id", $employees, false, "integer")."\n"
				." AND hcrs.reason_for_training IN ('"
				. join("', '", $reason_for_eff_analysis)
				."')\n"
				;
		return $sql;
	}

	/**
	 * Get the group by statement
	 *
	 * @return string
	 */
	protected function getGroupBy() {
		return " GROUP BY husr.user_id, husr.lastname, husr.firstname, husr.email, hcrs.title, hcrs.type, hcrs.begin_date\n"
				 .", hcrs.end_date, hcrs.language, hcrs.training_number, hcrs.venue, hcrs.target_groups, hcrs.objectives_benefits\n"
				 .", hcrs.training_topics, hcrs.crs_id, effa.finish_date, effa.result";
	}

	/**
	 * Get where by filter values
	 *
	 * @param mixed[]		$filter
	 *
	 * @return string
	 */
	protected function getWhereByFilter(array $filter) {
		require_once("Services/GEV/Desktop/classes/EffectivenessAnalysis/class.gevEffectivenessAnalysis.php");
		$where = "";

		if(!isset($filter[gevEffectivenessAnalysis::F_STATUS])
			&& isset($filter[gevEffectivenessAnalysis::F_FINISHED])
			&& $filter[gevEffectivenessAnalysis::F_FINISHED] == gevEffectivenessAnalysis::STATE_FILTER_OPEN)
		{
			$where .= "     AND effa.finish_date IS NULL\n";
		}

		if(isset($filter[gevEffectivenessAnalysis::F_PERIOD])) {
			$start = $filter[gevEffectivenessAnalysis::F_PERIOD]["start"]->get(IL_CAL_DATE);
			$end = $filter[gevEffectivenessAnalysis::F_PERIOD]["end"]->get(IL_CAL_DATE);

			$where .= "     AND hcrs.begin_date >= ".$this->gDB->quote($start, "text"). " AND hcrs.begin_date <= ".$this->gDB->quote($end, "text")."\n";
		}

		if(isset($filter[gevEffectivenessAnalysis::F_TITLE]) && $filter[gevEffectivenessAnalysis::F_TITLE] != "") {
			$search_string = '%' .$filter[gevEffectivenessAnalysis::F_TITLE] .'%';
			$where .= "     AND " .$this->gDB->like('hcrs.title','text', $search_string) ."\n";
		}

		if(isset($filter[gevEffectivenessAnalysis::F_RESULT]) && $filter[gevEffectivenessAnalysis::F_RESULT] != "") {
			$where .= "     AND (".$this->gDB->in("effa.result", $filter[gevEffectivenessAnalysis::F_RESULT], false,  "integer");

			$pending_result = in_array(0, $filter[gevEffectivenessAnalysis::F_RESULT]);
			if($pending_result) {
				$where .= 'OR isNULL (effa.result)';
			}

			$where .= ")\n";
		}

		if(isset($filter[gevEffectivenessAnalysis::F_STATUS]) && !empty($filter[gevEffectivenessAnalysis::F_STATUS])) {
			$status = $filter[gevEffectivenessAnalysis::F_STATUS];

			switch($status) {
				case gevEffectivenessAnalysis::STATE_FILTER_FINISHED:
					$where .= "     AND effa.finish_date IS NOT NULL\n";
					break;
				case gevEffectivenessAnalysis::STATE_FILTER_OPEN:
					$where .= "     AND effa.finish_date IS NULL\n";
					break;
				case gevEffectivenessAnalysis::STATE_FILTER_ALL:
					break;
				default;
					throw new Exception("gevEffectivenessAnalysisDB::getWhereByFilter: Wrong value for Filter ".gevEffectivenessAnalysis::F_STATUS);
			}
		}

		return $where;
	}

	/**
	 * Get result data for crs and user
	 *
	 * @param int 		$crs_id
	 * @param int 		$user_id
	 *
	 * @return string[]
	 */
	public function getResultData($crs_id, $user_id) {
		$query = "SELECT result, info\n"
				." FROM ".self::TABLE_FINISHED_EFF_ANA."\n"
				." WHERE crs_id = ".$this->gDB->quote($crs_id, "integer")."\n"
				."     AND user_id = ".$this->gDB->quote($user_id, "integer");

		$res = $this->gDB->query($query);
		$row = $this->gDB->fetchAssoc($res);

		return $row;
	}
}
