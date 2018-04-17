<?php

namespace ILIAS\TMS\TableRelations\Tables\DerivedFields;
use ILIAS\TMS\TableRelations\Tables as T;
use ILIAS\TMS\Filter as Filters;

/**
 * Calculate the average over a field.
 */
class ConstString extends T\DerivedField  {

	protected $value;

	public function __construct(Filters\PredicateFactory $f, $name, $value = '') {
		assert('is_string($name)');
		assert('is_string($value)');
		$this->value = $value;
		parent::__construct($f, $name);
	}

	/**
	 * Get the value this field represents.
	 *
	 * @return	string
	 */
	public function value()
	{
		return $this->value;
	}
}
