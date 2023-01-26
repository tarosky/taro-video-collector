<?php

namespace Tarosky\VideoCollector\Pattern;

/**
 * Singleton pattern.
 */
abstract class SingletonPattern {

	/**
	 * @var static[] Instance store.
	 */
	static private $instances = [];

	/**
	 * Constructor.
	 */
	final protected function __construct() {
		$this->init();
	}

	/**
	 * Executed once in constructor.
	 *
	 * @return void
	 */
	protected function init() {
		// Do something.
	}

	/**
	 * Get constructor.
	 *
	 * @return static
	 */
	static public function get_instance() {
		$class_name = get_called_class();
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new $class_name();
		}
		return self::$instances[ $class_name ];
	}
}
