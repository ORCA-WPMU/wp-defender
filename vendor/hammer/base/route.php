<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Base;

class Route extends Component {

	/**
	 * Find the action of current request. This way we only need to init one time.
	 *
	 * @param $id
	 * @param $action
	 * @param Controller[] $maps
	 *
	 * @throws \Exception
	 */
	public static function listenRequest( $id, $action, $maps = array() ) {
		foreach ( $maps as $controller ) {
			if ( $controller::$id == $id ) {
				$action = 'action' . ucfirst( $action );
				if ( $controller->hasMethod( $action ) ) {
					//trigger this
					$controller->$action();
				} else {
					throw new \Exception( "Action $controller:$action not found!" );
				}
			}
		}
	}
}