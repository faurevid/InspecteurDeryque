<?php
/* This file is released under the CeCILL-B V1 licence.*/
/**
 * Boolean representation
 */
class DBoolean extends DefaultData
{
	const name = 'Boolean';

	const n_boolean = 'Boolean';
	public $boolean;

	const display_prefs = 'boolean';

	public function filterData() {
		DefaultData::filterData();
		$this->value = $this->value == true;
	}
}
?>
