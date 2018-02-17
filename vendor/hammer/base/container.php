<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Base;

class Container extends Component {
	/**
	 * Singleton for this class
	 *
	 * @var Controller
	 */
	private static $_instance;

	/** store class instance if singleton
	 * @var array
	 */
	private $_cache = array();

	/** Store class name, defination and params
	 * @var array
	 */
	private $_pools = array();

	private function __construct() {

	}

	/**
	 * @return Container
	 */
	public static function instance() {
		if ( ! is_object( self::$_instance ) ) {
			self::$_instance = new Container();
		}

		return self::$_instance;
	}

	/**
	 * Store the object and it configs to array cache
	 *
	 * Alias of the class
	 *
	 * @param $name string
	 * // class name, or an object
	 * @param $class
	 * //config for the class, wll be applied when call @get()
	 * @param array $config
	 * // params for the consutrctor if any
	 * @param array $params
	 */
	public function set( $name, $class, $config = array(), $params = array() ) {
		$this->_pools[ $name ] = array(
			$class,
			$config,
			$params,
			false
		);
	}

	/**
	 * Get the object
	 *
	 * @param $name
	 *
	 * @return bool|object
	 */
	public function get( $name ) {
		$cache = isset( $this->_pools[ $name ] ) ? $this->_pools[ $name ] : null;
		if ( ! is_array( $cache ) ) {
			return false;
		}

		list( $class, $configs, $params, $is_singleton ) = $cache;

		if ( is_string( $class ) && ! class_exists( $class ) ) {
			return $class;
		}

		if ( $is_singleton && isset( $this->_cache[ $name ] ) ) {
			$object = $this->_cache[ $name ];
		} elseif ( is_object( $class ) ) {
			$object = $class;
		} else {
			$ref    = new \ReflectionClass( $class );
			$object = $ref->newInstanceArgs( $params );
		}

		foreach ( $configs as $key => $val ) {
			if ( $object->hasProperty( $key ) ) {
				$object->$key = $val;
			}
		}

		if ( $is_singleton ) {
			$this->_cache[ $name ] = $object;
		}

		return $object;
	}

	/**
	 * @param $name
	 * @param $class
	 * @param array $config
	 * @param array $params
	 */
	public function setSingleton( $name, $class, $config = array(), $params = array() ) {
		$this->_pools[ $name ] = array(
			$class,
			$config,
			$params,
			true
		);
	}
}