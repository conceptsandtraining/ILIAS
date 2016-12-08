<?php

require_once 'Customizing/global/plugins/Services/Cron/CronHook/ReportMaster/classes/ReportBase/class.ilObjReportBase.php';
require_once 'Services/GEV/Utils/classes/class.gevAMDUtils.php';
require_once 'Services/GEV/Utils/classes/class.gevSettings.php';
require_once("Modules/OrgUnit/classes/class.ilObjOrgUnit.php");
require_once("Services/GEV/Utils/classes/class.gevObjectUtils.php");
require_once("Services/GEV/Utils/classes/class.gevOrgUnitUtils.php");

class ilObjReportCompanyGlobal extends ilObjReportBase {
	
	protected $online;
	protected $relevant_parameters = array();
	protected $query_class;
	protected static $participated = array('teilgenommen');
	protected static $columns_to_sum = array('book_book' => 'book_book','part_book' => 'part_book','wp_part' => 'wp_part');
	protected static $wbd_relevant = array('OKZ1','OKZ2','OKZ3');
	protected $types;
	protected $filter_orgus = array();
	protected $sql_filter_orgus = null;
	protected $template_ref_field_id;


	public function initType() {
		 $this->setType("xrcg");
		 $amd_utils = gevAMDUtils::getInstance();
		 $this->types = $amd_utils->getOptions(gevSettings::CRS_AMD_TYPE);
	}

	/**
	 * We can not use regular query logic here (since there is no outer-join in mysql and i would like to avoid a lot of subqueries)
	 * so lets take this opportunity to do some preparation work for the actual query construction in getTrainingTypeQuery at least.
	 *
	 * @inheritdoc
	 */
	protected function buildQuery($query) {
		return $this->prepareQueryComponents($query);
	}

	protected function getFilterSettings() {
		$filter = $this->filter();
		if($this->filter_settings) {
			$settings = call_user_func(array($filter, "content"), $this->filter_settings);
		}
		// var_dump($settings);exit;
		return $settings;
	}

	protected function createLocalReportSettings() {
		$this->local_report_settings =
			$this->s_f->reportSettings('rep_robj_rcg');
	}

	protected function prepareQueryComponents($query) {
		// this will be used later to invoke other query objects. A cloning of a "virgin" query object would be more formal, 
		// but since right now __clone is not defined for queries...
		$this->query_class = get_class($query);

		$set = $this->getFilterSettings();
		// var_dump($set);exit;

		// this is quite a hack, but once we have the new filter-api it can be fixed
		$filter_orgus = $this->orgu_filter->getSelection();

		if(count($filter_orgus) > 0) {
			$this->sql_filter_orgus = 
			"SELECT DISTINCT usr_id FROM hist_userorgu"
			."	WHERE ".$this->orgu_filter->deliverQuery()
			."	AND hist_historic = 0 AND action >= 0 ";
		}
		return null;
	}

	/**
	 * @inheritdoc
	 */
	protected function buildFilter($filter) {
		$this->orgu_filter = new recursiveOrguFilter('org_unit', 'orgu_id', true, true);
		$this->orgu_filter->setFilterOptionsAll();
		$this->crs_topics_filter = new courseTopicsFilter('crs_topics','hc.topic_set');

		$filter ->dateperiod( "period"
							 , $this->plugin->txt("period")
							 , $this->plugin->txt("until")
							 , "hucs.end_date"
							 , "hucs.end_date"
							 , date("Y")."-01-01"
							 , date("Y")."-12-31"
							 , false
							 , ""
							 , function ($start, $end) {
									global $ilDB;
									return
									"AND ( hc.type <> 'Selbstlernkurs'\n".
									"      OR ( (hucs.end_date = '0000-00-00' OR hucs.end_date = '-empty-')\n".
									"           AND hucs.begin_date >= ".$ilDB->quote($start, "date")."\n".
									"           AND hucs.begin_date <= ".$ilDB->quote($end, "date")."\n".
									"         )\n".
									"      OR ( hucs.end_date >= ".$ilDB->quote($start, "date")."\n".
									"           AND hucs.end_date <= ".$ilDB->quote($end, "date")."\n".
									"         )\n".
									"    )\n";
								}
							 );
		$this->orgu_filter->addToFilter($filter);
		$this->crs_topics_filter->addToFilter($filter);
		$filter->multiselect("edu_program"
							 , $this->plugin->txt("edu_program")
							 , "edu_program"
							 , gevCourseUtils::getEduProgramsFromHisto()
							 , array()
							 , ""
							 , 200
							 , 160	
							 )
				->multiselect("template_title"
							 , $this->plugin->txt("template_title")
							 , "template_title"
							 , gevCourseUtils::getTemplateTitleFromHisto()
							 , array()
							 , ""
							 , 200
							 , 160	
							)
				->multiselect_custom( "dct_type"
							 , $this->plugin->txt("course_type")
							 , array("hc.edu_program = ".$this->gIldb->quote("dezentrales Training","text")." AND hc.dct_type = ".$this->gIldb->quote("fixed","text") 
							 			=> $this->plugin->txt("dec_fixed")
									,"hc.edu_program = ".$this->gIldb->quote("dezentrales Training","text")." AND hc.dct_type = ".$this->gIldb->quote("flexible","text")
										=> $this->plugin->txt("dec_flexible")
									,"hc.edu_program != ".$this->gIldb->quote("dezentrales Training","text")
							 			=> $this->plugin->txt("non_dec"))
							 , array()
							 , ""
							 , 200
							 , 160
							 , "text"
							 , "desc"
							 )
				->multiselect_custom( 'wbd_relevant'
							 , $this->plugin->txt('wbd_relevant')
							 , array($this->gIldb->in('hucs.okz',self::$wbd_relevant,false,'text') 
							 			=> $this->plugin->txt('yes')
							 		,$this->gIldb->in('hucs.okz',self::$wbd_relevant,true,'text') 
							 			=> $this->plugin->txt('no'))
							 , array()
							 , ""
							 , 200
							 , 50
							 ,"text"
							 , "asc"
							 ,true
							 )
				->multiselect_custom( 'wb_points'
							 , $this->plugin->txt('edupoints')
							 , array( 
								' hc.max_credit_points > 0 OR hc.crs_id < 0'
									=> $this->plugin->txt('trainings_w_points') 
								,$this->gIldb->in("hc.max_credit_points ",array('0','-empty-') ,false,'text')." AND hc.crs_id > 0"
									=> $this->plugin->txt('trainings_wo_points'))
							 , array()
							 , ""
							 , 200
							 , 50
							 ,"text"
							 ,"none"
							 ,true
							 )
				->checkbox('no_wbd_imported'
							, $this->plugin->txt("filter_no_wbd_imported")
							," hucs.crs_id > 0"
							," TRUE "
							)
				->static_condition("hucs.hist_historic = 0")
				->static_condition("hc.hist_historic = 0")
				->static_condition($this->gIldb->in('hc.type', $this->types, false, 'text'))
				->static_condition("hucs.booking_status = ".$this->gIldb->quote('gebucht','text'))
				->action($this->filter_action)
				->compile()
				;
		return null;
	}

	public function filter() {
		require_once 'Services/GEV/Utils/classes/class.gevAMDUtils.php';
		require_once 'Services/GEV/Utils/classes/class.gevSettings.php';

		$db = $this->gIldb;
		$crs_topics_filter = new courseTopicsFilter('crs_topics','hc.topic_set');
		$pf = new \CaT\Filter\PredicateFactory();
		$tf = new \CaT\Filter\TypeFactory();
		$f = new \CaT\Filter\FilterFactory($pf, $tf);
		$txt = function($id) { return $this->plugin->txt($id); };

		return
		$f->sequence
		(

				$f->option
				(
					$txt("filter_no_wbd_imported")
					, ""
				)->map
					(
						function($types) { return (bool)$types[0]; }
						,$tf->bool()
					),

				$f->option
				(
					$txt("org_unit_recursive")
					, ""
				)->map
					(
						function($types) { return (bool)$types[0]; }
						,$tf->bool()
					),


			$f->sequence
			(


				/* BEGIN BLOCK - PERIOD */
				$f->dateperiod
				(
					$txt("period")
					, ""
				)->map
					(
						function($start,$end) use ($f)
						{
							$pc = $f->dateperiod_overlaps_predicate
							(
								"hucs.begin_date"
								,"hucs.begin_date"
							);
							return array ("date_period_predicate" => $pc($start,$end)
										 ,"start" => $start
										 ,"end" => $end);
						},
						$tf->dict
						(
							array
							(
								"date_period_predicate" => $tf->cls("CaT\\Filter\\Predicates\\Predicate")
								,"start" => $tf->cls("DateTime")
								,"end" => $tf->cls("DateTime")
							)
						)
					),
					/* END BLOCK - PERIOD */


				/* BEGIN BLOCK - ORG UNIT */
				$f->multiselectsearch
				(
					$txt("org_unit_short")
					, ""
					, $this->getAllOrguIds()
				)->map
					(
						function($types) { return $types; }
						,$tf->lst($tf->int())
					),
				/* END BLOCK - ORG UNIT */


				/* BEGIN BLOCK - COURSE TOPICS */
				$f->multiselectsearch
				(
					$txt("crs_filter_topics")
					, ""
					, gevAMDUtils::getInstance()->getOptions(gevSettings::CRS_AMD_TOPIC)
				)->map
					(
						function($types) { return $types; }
						,$tf->lst($tf->string())
					),
				/* END BLOCK - COURSE TOPICS */


				/* BEGIN BLOCK - EDU PROGRAMS */
				$f->multiselectsearch
				(
					$txt("edu_program")
					, ""
					, gevCourseUtils::getEduProgramsFromHisto()
				)->map
					(
						function($types) { return $types; }
						,$tf->lst($tf->string())
					),
				/* END BLOCK - EDU PROGRAMS */

				/* BEGIN BLOCK - TEMPLATE TITLE */
				$f->multiselectsearch
				(
					$txt("template_title")
					, ""
					, gevCourseUtils::getTemplateTitleFromHisto()
				)->map
					(
						function($types) { return $types; }
						,$tf->lst($tf->string())
					),
				/* END BLOCK - TEMPLATE TITLE */

				/* BEGIN BLOCK - COURSE TYPE */
				$f->multiselectsearch
				(
					$txt("course_type")
					, ""
					, array("hc.edu_program = ".$db->quote("dezentrales Training","text")." AND hc.dct_type = ".$db->quote("fixed","text")
										=> $txt("dec_fixed")
									,"hc.edu_program = ".$db->quote("dezentrales Training","text")." AND hc.dct_type = ".$db->quote("flexible","text")
										=> $txt("dec_flexible")
									,"hc.edu_program != ".$db->quote("dezentrales Training","text")
										=> $txt("non_dec"))
				)->map
					(
						function($types) { return $types; }
						,$tf->lst($tf->string())
					),
				/* END BLOCK - COURSE TYPE */


				/* BEGIN BLOCK - WBD RELEVANT */
				$f->multiselectsearch
				(
					$txt("wbd_relevant")
					, ""
					, array($db->in('hucs.okz',self::$wbd_relevant,false,'text')
										=> $txt('yes')
									,$db->in('hucs.okz',self::$wbd_relevant,true,'text')
										=> $txt('no'))
				)->map
					(
						function($types) { return $types; }
						,$tf->lst($tf->string())
					),
				/* END BLOCK - WBD RELEVANT */

				/* BEGIN BLOCK - WB POINTS */
				$f->multiselectsearch
				(
					$txt("edupoints")
					, ""
					,  array(
								' hc.max_credit_points > 0 OR hc.crs_id < 0'
									=> $txt('trainings_w_points')
								,$db->in("hc.max_credit_points ",array('0','-empty-') ,false,'text')." AND hc.crs_id > 0"
									=> $txt('trainings_wo_points'))
				)->map
					(
						function($types) { return $types; }
						,$tf->lst($tf->string())
					)
				/* END BLOCK - WB POINTS */

			)->map
				(
					function($date_period_predicate, $start, $end, $org_unit_short, $edu_program, $template_title, $course_type, $wbd_relevant, $edupoints)
					{
						return array("period_pred" => $date_period_predicate
									,"start" => $start
									,"end" => $end
									,"org_unit_short" => $org_unit_short
									,"edu_program" => $edu_program
									,"template_title" => $template_title
									,"course_type" => $course_type
									,"wbd_relevant" => $booking_over
									,"edupoints" => $edupoints);
					},
					$tf->dict
					(
						array("period_pred" => $tf->cls("CaT\\Filter\\Predicates\\Predicate")
							 ,"start" => $tf->cls("DateTime")
							 ,"end" => $tf->cls("DateTime")
							 ,"org_unit_short" => $tf->lst($tf->string())
							 ,"edu_program" => $tf->lst($tf->string())
							 ,"template_title" => $tf->lst($tf->string())
							 ,"course_type" => $tf->lst($tf->string())
							 ,"wbd_relevant" => $tf->lst($tf->string())
							 ,"edupoints" => $tf->lst($tf->string())
						)
					)
				)
		)->map
			(
				function($filter_no_wbd_imported, $org_unit_recursive)
				{
					return array("filter_no_wbd_imported" => $filter_no_wbd_imported
								,"org_unit_recursive" => $org_unit_recursive);
				},
				$tf->dict
				(
					array("filter_no_wbd_imported" => $tf->bool()
						 ,"org_unit_recursive" => $tf->bool()
					)
				)
			);
	}

	protected function fetchData(callable $callback){
		$data = $this->joinPartialDataSets(
				$this->fetchPartialDataSet($this->getPartialQuery(true))
				,$this->fetchPartialDataSet($this->getPartialQuery(false))
				);

		$sum_data = array();

		foreach($data as &$row) {
			$row = call_user_func($callback,$row);
			foreach (self::$columns_to_sum as $column) {
				if(!isset($sum_data[$column])) {
					$sum_data[$column] = 0;
				}
				$sum_data[$column] += $row[$column];
			}
		}

		$sum_data['type'] = $this->plugin->txt('sum');
		$sum_data['part_user'] = '--';
		$sum_data['book_user'] = '--';
		$data['sum'] = $sum_data;
		return $data;
	}

	protected function getRowTemplateTitle() {
		return 'tpl.cat_global_company_report_data_row.html';
	}

	/**
	 * @inheritdoc
	 */
	protected function buildTable($table) {
		$table  ->column('type',$this->plugin->txt('type'), true)
				->column('book_book',$this->plugin->txt('bookings'), true)
				->column('book_user',$this->plugin->txt('members'), true)
				->column('part_book',$this->plugin->txt('participations'), true)
				->column('wp_part',$this->plugin->txt('edu_points'), true)
				->column('part_user',$this->plugin->txt('members'), true);
		return parent::buildTable($table);
	}

	/**
	 * @inheritdoc
	 */
	protected function buildOrder($order) {
		return $order;
	}

	public function buildQueryStatement() {
		$a_query_part = $this->getPartialQuery(true);
		$a_query_book = $this->getPartialQuery(false);
		return $a_query_part->sql()."\n "
				. $this->queryWhere()."\n AND "
				. $a_query_part->getSqlWhere()."\n "
				. $a_query_part->sqlGroupBy()."\n "
				. $this->queryHaving()."\n "
				. $this->queryOrder();
	}

	protected function getPartialQuery($has_participated) {
		$prefix = $has_participated ? 'part' : 'book';

		$query = call_user_func($this->query_class.'::create');
		$query		->select('hc.type')
					->select_raw('COUNT(hucs.usr_id) '.self::$columns_to_sum[$prefix.'_book'])
					->select_raw('COUNT(DISTINCT hucs.usr_id) '.$prefix.'_user');
		if($has_participated) {
			$query	->select_raw('SUM( IF( hucs.credit_points IS NOT NULL AND hucs.credit_points > 0 AND '.$this->gIldb->in('hucs.okz', self::$wbd_relevant,false,'text')
					.', hucs.credit_points, 0) ) '.self::$columns_to_sum['wp_part']);
		}
		$query 		->from('hist_course hc')
					->join('hist_usercoursestatus hucs')
						->on($this->userCourseSelectorByStatus($has_participated));
		if($this->sql_filter_orgus) {
			$query	->raw_join(' JOIN ('.$this->sql_filter_orgus.') as orgu ON orgu.usr_id = hucs.usr_id ');
		}

		$this->crs_topics_filter->addToQuery($query);

		$query	->group_by('hc.type')
				->compile();

		return $query;
	}

	protected function userCourseSelectorByStatus($has_participated) {
		if($has_participated) {
			$return = 'hc.crs_id = hucs.crs_id'
				.'	AND hucs.participation_status = '.$this->gIldb->quote('teilgenommen','text');
		} else {
			$return = 'hc.crs_id = hucs.crs_id'
				.'	AND '.$this->gIldb->in('hucs.participation_status',array('nicht gesetzt','-empty-'),false,'text');
		}
		return $return;
	}

	protected function fetchPartialDataSet($a_query) {
		$query = $a_query->sql()."\n "
				. $this->queryWhere()."\n AND "
				. $a_query->getSqlWhere()."\n"
				. $a_query->sqlGroupBy()."\n"
				. $this->queryHaving()."\n"
				. $this->queryOrder();
		$res = $this->gIldb->query($query);
		$return = array();
		while($rec = $this->gIldb->fetchAssoc($res)) {
			$return[$rec["type"]] = $rec;
		}

		return $return;
	}

	protected function joinPartialDataSets(array $a_data, array $b_data) {
		$return = array();
		//seems like a nice usecase for linq
		foreach ($this->types as $type) {
			if(!isset($a_data[$type])) {
				$a_data[$type] = array('type' => $type);
			}
			if(!isset($b_data[$type])) {
				$b_data[$type] = array('type' => $type);
			}
			$return[$type] = array_merge($a_data[$type],$b_data[$type]);
		}
		return $return;
	}
 
	public function getRelevantParameters() {
		return $this->relevant_parameters;
	}

	protected function getAllOrguIds() {
		$query = "SELECT DISTINCT obj_id, title FROM object_data JOIN object_reference USING(obj_id)"
				."	WHERE type = 'orgu' AND deleted IS NULL";
		$res = $this->gIldb->query($query);
		$return = array();
		while($rec = $this->gIldb->fetchAssoc($res)) {
			$return[$rec["obj_id"]] = $rec["title"];
		}
		return $return;
	}

}