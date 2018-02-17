<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Caching;

use Hammer\Base\Component;
use Hammer\Helper\WP_Helper;

abstract class Cache extends Component {

	public function behaviors() {
		return array(
			'serialize' => '\Hammer\Caching\Behavior\Serialize_Behavior'
		);
	}

	/**
	 * Default duration to store cache, in seconds. Default is 1 week. 0 is forever
	 *
	 * @var int
	 */
	public $duration = 604800;
	/**
	 * Prefix for cache key
	 * @var string
	 */
	public $prefix = null;

	/**
	 * @param $key
	 *
	 * @return string
	 */
	public function buildKey( $key ) {
		if ( is_string( $key ) ) {
			return ( $this->prefix . $key );
		} else {
			return ( $this->prefix . json_encode( $key ) );
		}
	}

	/**
	 * @param $key
	 * @param $value
	 * @param null $duration
	 *
	 * @return bool
	 */
	public function add( $key, $value, $duration = null ) {
		$key = $this->buildKey( $key );

		if ( $this->exists( $key ) ) {
			return false;
		}

		$duration = $duration === null ? $this->duration : $duration;
		//serialize func should use from behavior
		$value = $this->serialize( $value );

		return $this->addValue( $key, $value, $duration );
	}

	/**
	 * @param $key
	 */
	public function delete( $key ) {
		$key = $this->buildKey( $key );
		$this->deleteValue( $key );
	}

	/**
	 * @param $key
	 * @param int $offset
	 */
	public function increase( $key, $offset = 1 ) {
		$this->increaseValue( $key, $offset );
	}

	/**
	 * @param $key
	 * @param int $offset
	 */
	public function decrease( $key, $offset = 1 ) {
		$this->decreaseValue( $key, $offset );
	}

	/**
	 * @param $key
	 * @param null $default
	 *
	 * @return false|mixed|null
	 */
	public function get( $key, $default = null ) {
		$key   = $this->buildKey( $key );
		$value = $this->getValue( $key );
		if ( $value === false ) {
			$value = $default;
		}
		$value = maybe_unserialize( $value );

		return $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param null $duration
	 */
	public function set( $key, $value, $duration = null ) {
		$key      = $this->buildKey( $key );
		$duration = $duration === null ? $this->duration : $duration;
		$value    = $this->serialize( $value );
		if ( ! $this->exists( $key ) ) {
			$this->addValue( $key, $value, $duration );
		} else {
			$this->setValue( $key, $value, $duration );
		}
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function exists( $key ) {
		$key = $this->buildKey( $key );

		return $this->valueExist( $key );
	}

	/**
	 * A helper for child class
	 */
	public function isActivatedSingle() {
		if ( WP_Helper::is_network_activate( wp_defender()->plugin_slug ) ) {
			return false;
		}
	}

	/**
	 * Add a cache, if cache already exist, then do nothing
	 *
	 * @param $key
	 * @param $value
	 * @param null $duration
	 *
	 * @return bool
	 */
	protected abstract function addValue( $key, $value, $duration );

	/**
	 * Delete a cache key
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	protected abstract function deleteValue( $key );

	/**
	 * Get the cache value
	 *
	 * @param $key
	 *
	 * @return mixed|false
	 */
	protected abstract function getValue( $key );

	/**
	 * Update a value to exising key, if key doesnt exist then it will create one
	 *
	 * @param $key
	 * @param $value
	 * @param null $duration
	 *
	 * @return bool
	 */
	protected abstract function setValue( $key, $value, $duration );

	/**
	 * Check if a value is existing
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	protected abstract function valueExist( $key );

	/**
	 * @param $key
	 * @param $offset
	 *
	 * @return mixed
	 */
	protected abstract function increaseValue( $key, $offset );

	/**
	 * @param $key
	 * @param $offset
	 *
	 * @return mixed
	 */
	protected abstract function decreaseValue( $key, $offset );

}