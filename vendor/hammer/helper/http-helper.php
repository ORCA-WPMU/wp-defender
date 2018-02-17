<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Helper;

class HTTP_Helper {
	/**
	 * @param $key
	 * @param null $default
	 *
	 * @return null
	 */
	public static function retrieve_get( $key, $default = null ) {
		return Array_Helper::getValue( $_GET, $key, $default );
	}

	/**
	 * @param $key
	 * @param null $default
	 *
	 * @return null
	 */
	public static function retrieve_post( $key, $default = null ) {
		return Array_Helper::getValue( $_POST, $key, $default );
	}
}