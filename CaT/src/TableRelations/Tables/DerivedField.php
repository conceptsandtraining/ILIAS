<?php
namespace CaT\TableRelations\Tables;

use CaT\Filter as Filters;

class DerivedField extends Filters\Predicates\Field implements AbstractDerivedField{

	protected $derived_from = array();
	public function __construct(Filters\PredicateFactory $f, $name, \Closure $postprocess, $fields = array()) {
		$this->derived_from = $fields;
		$this->postprocess = $postprocess;
		parent::__construct($f, $name);
	}

	/**
	 * Get all fields from which this field is derived.
	 *
	 * @return	AbstractTableField[]
	 */
	public function derivedFrom() {
		$return = array();
		foreach ($this->derived_from as $field) {
			if($field instanceof AbstractTableField) {
				$return[$field->name()] = $field;
			} elseif($field instanceof self) {
				$return = array_merge($return, $field->derived_from);
			} else {
				throw new TableExcepiton('unknown field type');
			}
		}
		return $return;
	}

	/**
	 * Get first order of fields from which this field is derived.
	 *
	 * @return	AbstractTableField[]
	 */
	public function derivedFromDirect() {
		$return = array();
		foreach ($this->derived_from as $field) {
			$return[] = $field;
		}
		return $return;
	}

	/**
	 * Get the postprocessing-function to be used by interpreter.
	 *
	 * @return	closure 
	 */
	public function postprocess() {
		return $this->postprocess;
	}

	/**
	 * In case of TableFields this function returns a composition of 
	 * field-name and table name for sake of uniqueness. This is not
	 * necessary here, name and name_simple are same.
	 *
	 * @return	string
	 */
	public function name_simple() {
		return $this->name();
	}
}