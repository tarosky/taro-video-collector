<?php

namespace Tarosky\VideoCollector\Model;

/**
 * API Result object.
 *
 * @package tsvc
 */
class ApiResult {

	/**
	 * @var \WP_Error Error object.
	 */
	private $errors = null;

	/**
	 * @var array Result object.
	 */
	private $result = [];

	/**
	 * Has error?
	 *
	 * @return bool
	 */
	public function has_error() {
		return ! is_null( $this->errors ) && $this->errors->get_error_messages();
	}

	/**
	 * Error messages.
	 *
	 * @return string[]
	 */
	public function get_error_messages() {
		return is_null( $this->errors ) ? [] : $this->errors->get_error_messages();
	}

	/**
	 * Add new error.
	 *
	 * @param \WP_Error $error Error object.
	 *
	 * @return void
	 */
	public function add_error( $error ) {
		if ( is_null( $this->errors ) ) {
			$this->errors = new \WP_Error();
		}
		$this->errors->add( $error->get_error_code(), $error->get_error_message() );
	}

	/**
	 * Result object.
	 *
	 * @return array
	 */
	public function get_result() {
		return $this->result;
	}

	/**
	 * Add item.
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function add_results( $value ) {
		$this->result[] = $value;
	}
}
