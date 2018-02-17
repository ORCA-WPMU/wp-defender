<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Caching\Behavior;

use Hammer\Base\Behavior;

class Serialize_Behavior extends Behavior {
	/**
	 * @param $value
	 *
	 * @return mixed
	 */
	public function serialize( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return serialize( $value );
		}

		return $value;
	}

	/**
	 * @param $value
	 *
	 * @return mixed
	 */
	public function unserialize( $value ) {
		return maybe_unserialize( $value );
	}
}