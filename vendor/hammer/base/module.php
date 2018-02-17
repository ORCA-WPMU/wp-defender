<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Base;

class Module extends Component {
	public static $id;

	protected $_controllers = array();

	/**
	 * @param $id
	 * @param $controller
	 */
	public function addController( $id, $controller ) {
		$this->_controllers[ $id ] = $controller;
	}

	/**
	 * @param $id
	 */
	public function removeController( $id ) {
		unset( $this->_controllers[ $id ] );
	}

	/**
	 * @return array
	 */
	public function controllerMap() {
		return $this->_controllers;
	}
}