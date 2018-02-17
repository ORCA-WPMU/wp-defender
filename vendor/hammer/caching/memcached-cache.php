<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Caching;
/**
 * Only use this engine if memory cached setup properly
 *
 * Class Memcached_Cache
 * @package Hammer\Caching
 */
class Memcached_Cache extends Cache {

	/**
	 * @param $key
	 * @param $value
	 * @param null $duration
	 *
	 * @return bool
	 */
	protected function addValue( $key, $value, $duration ) {
		return wp_cache_add( $key, $value, null, $duration );
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	protected function deleteValue( $key ) {
		return wp_cache_delete( $key );
	}

	/**
	 * @param $key
	 *
	 * @return bool|mixed
	 */
	protected function getValue( $key ) {
		return wp_cache_get( $key );
	}

	/**
	 * @param $key
	 * @param $offset
	 */
	protected function increaseValue( $key, $offset ) {
		wp_cache_incr( $key, $offset );
	}

	/**
	 * @param $key
	 * @param $offset
	 */
	protected function decreaseValue( $key, $offset ) {
		wp_cache_decr( $key, $offset );
	}

	/**
	 * @param $key
	 * @param $value
	 * @param null $duration
	 *
	 * @return bool
	 */
	protected function setValue( $key, $value, $duration ) {
		return wp_cache_set( $key, $value, null, $duration );
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	protected function valueExist( $key ) {
		if ( wp_cache_get( $key ) === false ) {
			return false;
		}

		return true;
	}
}