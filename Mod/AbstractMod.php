<?php
/* This file is released under the CeCILL-B V1 licence.*/

/** Abstract class governing types embedded into a specific directory tree. */
abstract class AbstractMod
{
	public $class; /** Class name of the mod.*/
	public $parent_class; /** Parent class name of the mod.*/
	public $folder; /** Folder in which is located the class.*/

	private $cache_variables = null;/**< Cache because introspection is slow */

    /** The constructor.
     * @param $class The class of the Object
     */
	public function AbstractMod($class, $folder) {
		$this->class = $class;
		$this->folder = $folder;
		$this->parent_class = get_parent_class($class);
	}
    /**
     * Clean the folder name.
     * @param $folder The folder path to clean.
     */
	public static function secureFolder($folder) {
		return preg_replace('/(\.\.)|\\\'/', '', $folder);
	}
    /**
     * Returns Object pointed by the current AbstractMod class.
     */
	public function initialize() {
		$class = $this->class;
		return new $class();
	}

    /**
     * Get the internal structure of the data.
     *
     * It use introspection, for the beauty and slowness.
     */
	public function getVariables() {
		// Cache power !
		if ($this->cache_variables !== null)
			return $this->cache_variables;

		// PHP power !!
		$class = $this->class;

		$vars = get_class_vars($class);

		// Array with activated vars and her names only
		$n_vars = [];

		foreach ($vars as $var => $value) {
			$var_name = 'n_'.$var;
			$constant = constant($class.'::'.$var_name);

			// Only add activated vars (we can disable a
			// var with a constant set to false
			if ($constant)
				$n_vars[$var] = $constant;
		}

		$this->cache_variables = $n_vars;

		// Return power !!!
		return $n_vars;
	}
}
?>
