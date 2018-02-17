<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Base;
/**
 * Class Component
 *
 * Class should extend this if behavior and event required
 *
 * @package Hammer\Base
 */
class Component extends HObject {
	/**
	 * Contains array of behaviors
	 * @var array
	 */
	private $_behaviors = array();
	private $_events = array();

	/**
	 * Defined a list of events and handler
	 * @return array
	 */
	public function events() {
		return array();
	}

	/**
	 * Define a list of behaviors
	 *
	 * @return array
	 */
	public function behaviors() {
		return array();
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function hasEvents( $name ) {
		if ( isset( $this->_events[ $name ] ) && count( $this->_events[ $name ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Queue an event
	 *
	 * @param $name
	 * @param $handler
	 * @param null $data
	 */
	public function on( $name, $handler, $data = null ) {
		$this->_events[ $name ][] = array( $handler, $data );
	}

	/**
	 * Dequeue an event
	 *
	 * @param $name
	 * @param null $handler
	 *
	 * @return bool
	 */
	public function off( $name, $handler = null ) {
		if ( empty( $this->_events ) ) {
			return false;
		}

		if ( $handler == null ) {
			unset( $this->_events[ $name ] );

			return true;
		}

		$removed = false;

		foreach ( $this->_events[ $name ] as $i => $event ) {
			if ( $event[0] === $handler ) {
				unset( $this->_events[ $name ][ $i ] );
				$removed = true;
			}
		}

		if ( $removed == true ) {
			//reset order
			$this->_events[ $name ] = array_values( $this->_events[ $name ] );
		}

		return $removed;
	}

	/**
	 * Trigger an event
	 *
	 * @param $name
	 * @param null $event
	 *
	 * @return bool|mixed
	 */
	public function trigger( $name, $event = null ) {
		//merge with the info we get from events() function
		$events = array_merge( $this->_events, $this->events() );

		if ( empty( $events[ $name ] ) ) {
			return false;
		}

		if ( $event == null ) {
			$event = new Event();
		}

		$event->sender  = $this;
		$event->name    = $name;
		$event->handled = false;
		foreach ( $events[ $name ] as $e ) {
			if ( isset( $e[1] ) ) {
				$event->data = $e[1];
			}
			call_user_func( $e[0], $event );
			if ( $event->handled ) {
				return $event->result;
			}
		}

		return false;
	}

	/**
	 * @param $name
	 * @param Behavior $behavior
	 * @param bool $append
	 */
	public function attachBehavior( $name, Behavior $behavior, $append = false ) {
		$behavior->owner = $this;
		if ( $append == true ) {
			$this->_behaviors = array_merge( array( $name => $behavior ), $this->_behaviors );
		} else {
			$this->_behaviors[ $name ] = $behavior;
		}
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public function detachBehavior( $name ) {
		$behavior        = $this->_behaviors[ $name ];
		$behavior->owner = null;
		unset( $this->_behaviors[ $name ] );

		return $behavior;
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function hasBehavior( $name ) {
		return isset( $this->_behaviors[ $name ] );
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __get( $name ) {
		//priority to current class properties
		$refClass = new \ReflectionClass( $this );
		if ( $refClass->hasProperty( $name ) ) {
			return $refClass->getProperty( $name )->getValue();
		}

		//check if behaviors already have
		foreach ( $this->_behaviors as $key => $behavior ) {
			$refClass = new \ReflectionClass( $behavior );
			if ( $refClass->hasProperty( $name ) ) {
				return $refClass->getProperty( $name )->getValue();
			}
		}

		throw new \Exception( 'Getting unknown property: ' . get_class( $this ) . '::' . $name );
	}

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __call( $name, $arguments ) {
		$refClass = new \ReflectionClass( $this );
		if ( $refClass->hasMethod( $name ) ) {
			$refMethod = new \ReflectionMethod( $this, $name );

			return $refMethod->invokeArgs( $this, $arguments );
		}

		$this->ensureBehaviors();

		foreach ( $this->_behaviors as $key => $behavior ) {
			$refClass = new \ReflectionClass( $behavior );
			if ( $refClass->hasMethod( $name ) ) {
				$refMethod = new \ReflectionMethod( $behavior, $name );

				return $refMethod->invokeArgs( $behavior, $arguments );
			}
		}

		throw new \Exception( 'Getting unknown property: ' . get_class( $this ) . '::' . $name );
	}

	/**
	 * make sure all behavior get init
	 */
	private function ensureBehaviors() {
		foreach ( $this->_behaviors as $name => $class ) {
			if ( ! $class instanceof Behavior ) {
				$this->attachBehavior( $name, new $class() );
			}
		}

		foreach ( $this->behaviors() as $name => $class ) {
			//this is built in behaviors, if a name already defined, then we do nothing
			if ( isset( $this->_behaviors[ $name ] ) ) {
				continue;
			}
			if ( ! $class instanceof Behavior ) {
				//built in need to init from this
				$behavior = new $class();
			} else {
				$behavior = $class;
			}
			//built in should queue first
			$this->attachBehavior( $name, $behavior, true );
		}
	}

	/**
	 * @param $property
	 *
	 * @return bool
	 */
	public function hasProperty( $property ) {
		$ref = new \ReflectionClass( $this );

		if ( $ref->hasProperty( $property ) ) {
			return true;
		} else {
			$this->ensureBehaviors();
			foreach ( $this->_behaviors as $key => $behavior ) {
				$ref = new \ReflectionClass( $behavior );
				if ( $ref->hasProperty( $property ) ) {
					return true;
				}
			}

			return false;
		}
	}

	/**
	 * @param $method
	 *
	 * @return bool
	 */
	public function hasMethod( $method ) {
		$ref = new \ReflectionClass( $this );

		if ( $ref->hasMethod( $method ) ) {
			return true;
		} else {
			$this->ensureBehaviors();
			foreach ( $this->_behaviors as $key => $behavior ) {
				$ref = new \ReflectionClass( $behavior );

				if ( $ref->hasMethod( $method ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Do not call this directly, magic method for assign value to property, if property is not exist for this component, we will
	 * check its behavior
	 *
	 * @param $name
	 * @param $value
	 *
	 * @throws \Exception
	 */
	public function __set( $name, $value ) {
		$refClass = new \ReflectionClass( $this );
		if ( $refClass->hasProperty( $name ) ) {
			$refClass->getProperty( $name )->setValue( $value );

			return;
		}

		foreach ( $this->_behaviors as $key => $behavior ) {
			$refClass = new \ReflectionClass( $behavior );
			if ( $refClass->hasProperty( $name ) ) {
				$refClass->getProperty( $name )->setValue( $value );

				return;
			}
		}

		throw new \Exception( 'Setting unknown property: ' . get_class( $this ) . '::' . $name );
	}
}