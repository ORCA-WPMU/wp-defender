<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Base;

class Behavior extends Component {
	/**
	 * The component this behavior tied to
	 * @var object
	 */
	public $owner;

	/**
	 * Attach this behavior to a component
	 *
	 * @param $owner
	 */
	public function attach( Component $owner ) {
		$this->owner = $owner;
		$owner->attachBehavior( self::getClassName(), $this );
	}

	/**
	 * Detach behavior from a component
	 *
	 * @return $this|bool
	 */
	public function detach() {
		if ( ! $this->owner instanceof Component ) {
			return false;
		}
		$this->owner->detachBehavior( self::getClassName() );
		$this->owner = null;

		return $this;
	}
}