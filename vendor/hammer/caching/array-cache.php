<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Caching;
/**
 * This use for caching data in page load, non-persistent
 *
 * Class ArrayCache
 * @package Hammer\Caching
 */
class Array_Cache extends Cache {
	private $stored = array();

	/**
	 * @param $key
	 * @param $value
	 * @param null $duration
	 *
	 * @return bool
	 */
	protected function addValue( $key, $value, $duration ) {
		$this->stored[ $key ] = $value;

		return true;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	protected function deleteValue( $key ) {
		unset( $this->stored[ $key ] );

		return true;
	}

	/**
	 * @param $key
	 *
	 * @return bool|mixed
	 */
	protected function getValue( $key ) {
		return isset( $this->stored[ $key ] ) ? $this->stored[ $key ] : null;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param null $duration
	 *
	 * @return bool
	 */
	protected function setValue( $key, $value, $duration ) {
		$this->stored[ $key ] = $value;

		return true;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	protected function valueExist( $key ) {
		return isset( $this->stored[ $key ] );
	}


	protected function increaseValue( $key, $offset ) {

	}

	protected function decreaseValue( $key, $offset ) {

	}
}