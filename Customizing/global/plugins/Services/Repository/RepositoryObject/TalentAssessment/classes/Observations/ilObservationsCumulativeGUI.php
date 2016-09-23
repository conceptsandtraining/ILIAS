<?php

namespace CaT\Plugins\TalentAssessment\Observations;

require_once("Services/CaTUIComponents/classes/class.catHSpacerGUI.php");
require_once("Services/UICore/classes/class.ilTemplate.php");

class ilObservationsCumulativeGUI {
	public function __construct($parent_obj) {
		global $tpl;

		$this->gTpl = $tpl;
		$this->parent_obj = $parent_obj;
		$this->gTpl->addCSS("Customizing/global/plugins/Services/Repository/RepositoryObject/TalentAssessment/templates/css/talent_assessment_observation_cummulative_table.css");
	}

	public function render() {
		$obj_id = $this->parent_obj->getObjId();
		$actions = $this->parent_obj->getActions();

		$observator = $actions->getAssignedUser($obj_id, $actions->getAssignedUser($obj_id));
		$obs = $actions->getObservationsCumulative($obj_id);
		$req_res = $actions->getRequestresultCumulative(array_keys($obs));
		$col_span = count($observator);

		$tpl = new \ilTemplate("tpl.talent_assessment_observations_cumulative.html", true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/TalentAssessment");

		foreach($obs as $key => $title) {
			$tpl->setCurrentBlock("observations");
			$tpl->setVariable("COL_SPAN_HEAD", $col_span);
			$tpl->setVariable("OBS_TITLE", $title);
			$tpl->parseCurrentBlock();
		}

		for($i = 0; $i < count($obs); $i++) {
			foreach ($observator as $key => $value) {
				$tpl->setCurrentBlock("observator");
				$tpl->setVariable("OBSERVATOR_NAME", $value["lastname"].", ".$value["firstname"]);
				$tpl->parseCurrentBlock();
			}
		}

		$html = "";
		$printed = array();
		$i = 0;
		foreach($req_res as $req_title => $req) {
			if($printed[$req_title]) {
				continue;
			}
			$pts_tpl = new \ilTemplate("tpl.talent_assessment_observations_cumulative_pts.html", true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/TalentAssessment");
			$pts_tpl->setCurrentBlock("tr");
			$pts_tpl->setVariable("CSS_ROW", "row_norm");
			if ($i % 2 == 0) {
			  $pts_tpl->setVariable("CSS_ROW", "row_grey");
			}
			$i++;
			foreach($obs as $obs_key => $title) {
			    foreach ($observator as $usr) {
					$pts = $this->getPointsFor($req_res, $req_title, $obs_key, $usr["usr_id"]);

					$pts_tpl->setCurrentBlock("pts");
					$pts_tpl->setVariable("POINTS", $pts);
					$pts_tpl->parseCurrentBlock();
					}

				$pts_tpl->setVariable("REQ_TITLE", $req_title);
				$pts_tpl->setVariable("POINTS_MIDDLE", round($req["middle"],1));
			}
			$html .= $pts_tpl->get();

			$tpl->setCurrentBlock("tr_requirement");
			$tpl->setVariable("PTS_ROW", $html);
			$tpl->parseCurrentBlock();

			$printed[$req_title] = true;
		}

		$middle_total = 0;
		foreach($obs as $key => $title) {
			$middle = $this->getObservationMiddle($req_res, $key);
			$middle_total += $middle;

			$tpl->setCurrentBlock("bla");
			$tpl->setVariable("COL_SPAN_FOOTER", $col_span);
			$tpl->setVariable("PTS", round($middle,1));
			$tpl->parseCurrentBlock();
		}

		$tpl->setVariable("MIDDLE_TOTAL", round(($middle_total / count($obs)),1));

		return $tpl->get();
	}

	protected function getPointsFor($req, $title, $obs_id, $usr_id) {
		$observator = $req[$title][$obs_id]["observator"];

		if(array_key_exists($usr_id, $observator)) {
			return $observator[$usr_id];
		}

		return "-";
	}

	protected function getObservationMiddle($req_res, $obs_id) {
		$sum = 0;
		$counter = 0;

		foreach ($req_res as $key => $req) {
			if(array_key_exists($obs_id, $req)) {
				foreach ($req[$obs_id]["observator"] as $value) {
					$sum += $value;
				}
			$counter++;
			}
		}

		return $sum / $counter;
	}
}