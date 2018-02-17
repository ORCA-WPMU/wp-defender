<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Helper;

class Array_Helper {

	/**
	 * @param $arr
	 * @param $keys
	 * @param null $default
	 *
	 * @return null
	 */
	public static function getValue( $arr, $keys, $default = null ) {
		if ( is_string( $keys ) ) {
			$keys = explode( '.', $keys );
		}

		$curr = $arr;
		foreach ( $keys as $key ) {
			if ( isset( $curr[ $key ] ) ) {
				$curr = $curr[ $key ];
			} else {
				return $default;
			}
		}

		return $curr;
	}

	/**
	 * @param $arr
	 * @param $keys
	 * @param null $default
	 *
	 * @return null
	 */
	public static function remove( &$arr, $keys, $default = null ) {
		if ( is_string( $keys ) ) {
			$keys = explode( '.', $keys );
		}
		$curr = &$arr;
		foreach ( $keys as $i => $key ) {
			if ( ! isset( $curr[ $key ] ) ) {
				return $default;
			} else {
				if ( $i == count( $keys ) - 1 ) {
					//last key
					$ret = $curr[ $key ];
					unset( $curr[ $key ] );

					return $ret;
				} else {
					$curr = &$curr[ $key ];
				}
			}
		}
	}

	/**
	 * @param $arr
	 * @param $col
	 * @param bool $keep_key
	 *
	 * @return array
	 */
	public static function getColumn( $arr, $col, $keep_key = false ) {
		$result = array();
		foreach ( $arr as $key => $value ) {
			if ( isset( $value[ $col ] ) ) {
				if ( $keep_key ) {
					$result[ $key ] = $value[ $col ];
				} else {
					$result[] = $value[ $col ];
				}
			}
		}

		return $result;
	}

	public static function filter( $arr, $keys ) {

	}

	/**
	 * @param $arr
	 * @param $line
	 * @param $value
	 *
	 * @return mixed
	 */
	public static function injectLine( $arr, $line, $value ) {
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		array_splice( $arr, $line, 0, $value );

		return $arr;
	}
}