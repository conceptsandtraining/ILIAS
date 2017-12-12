<?php

/**
 * cat-tms-patch start
 */

require_once("Services/TMS/TrainingSearch/classes/Helper.php");

/**
 * Table gui to present cokable courses
 *
 * @author Stefan Hecken 	<stefan.hecken@concepts-and-training.de>
 */
class ilTrainingSearchTableGUI {

	/**
	 * @var ilTrainingSearchGUI
	 */
	protected $parent;

	/**
	 * @var ilLanguage
	 */
	protected $g_lng;

	public function __construct(ilTrainingSearchGUI $parent, Helper $helper, $search_user_id) {
		$this->parent = $parent;

		global $DIC;
		$this->g_lng = $DIC->language();
		$this->g_ctrl = $DIC->ctrl();
		$this->search_user_id = $search_user_id;

		$this->helper = $helper;

		$this->g_lng->loadLanguageModule('tms');
	}

	/**
	 * Set data to show in table
	 *
	 * @param mixed[] 	$data
	 *
	 * @return void
	 */
	public function setData(array $data) {
		$this->data = $data;
	}

	/**
	 * Get data should me shown in table
	 *
	 * @return mixed[]
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Renders the presentation table
	 *
	 * @return string
	 */
	public function render($view_constrols) {
		global $DIC;
		$f = $DIC->ui()->factory();
		$renderer = $DIC->ui()->renderer();

		//build table
		$ptable = $f->table()->presentation(
			$this->g_lng->txt("header"), //title
			$view_constrols,
			function ($row, BookableCourse $record, $ui_factory, $environment) { //mapping-closure
				$buttons = array();
				$book_button = $record->getBookButton($this->g_lng->txt("book_course"), $this->parent->getBookingLink($record), $this->search_user_id);
				$request_button = $record->getRequestButton($this->g_lng->txt("request_book"));

				if(!is_null($book_button)) {
					$buttons[] = $book_button;
				}
				if(!is_null($request_button)) {
					$buttons[] = $request_button;
				}

				return $row
					->withTitle($record->getTitleValue())
					->withSubTitle($record->getSubTitleValue())
					->withImportantFields($record->getImportantFields())
					->withContent($ui_factory->listing()->descriptive($record->getDetailFields()))
					->withFurtherFields($record->getFurtherFields())
					->withButtons($buttons);
			}
		);

		$data = $this->getData();

		//apply data to table and render
		return $renderer->render($ptable->withData($data));
	}
}

/**
 * cat-tms-patch end
 */
