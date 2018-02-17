<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Queue;

use Hammer\Base\Component;
use Hammer\Base\Container;

class Queue extends Component implements \Iterator, \ArrayAccess, \Countable {
	const EVENT_ITEM_PROCESSED = 'itemProcessed', EVENT_ITEM_FAIlED = 'itemFailed';
	/**
	 * Queue slug
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Args if you want to pass to process function
	 * @var array
	 */
	public $args = array();
	/**
	 * @var int
	 */
	private $position = 0;

	/**
	 * @var
	 */
	public $continueable = false;

	/**
	 * @var array
	 */
	private $data = array();

	/**
	 * @var
	 */
	private $status;

	/**
	 * Queue constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data = array(), $slug = false, $continueable = false ) {
		$cache              = Container::instance()->get( 'cache' );
		$this->data         = $data;
		$this->slug         = $slug;
		$this->continueable = $continueable;
		if ( $this->continueable == true ) {
			//we will set the pos by last point
			$cache = $cache->get( 'queue_' . $this->slug, null );
			if ( is_null( $cache ) ) {
				$this->position = 0;
			} else {
				$this->position = $cache;
			}
		} else {
			$this->position = 0;
		}
	}

	/**
	 * @return mixed|null
	 */
	public function current() {
		if ( isset( $this->data[ $this->position ] ) ) {
			return $this->data[ $this->position ];
		}

		return null;
	}

	/**
	 * move to next pos
	 */
	public function next() {
		$this->position ++;
	}

	/**
	 * return current index
	 * @return int
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * @return bool
	 */
	public function valid() {
		return ( isset( $this->data[ $this->position ] ) );
	}

	/**
	 * reset pos
	 */
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		return isset( $this->data[ $offset ] );
	}

	/**
	 * @param mixed $offset
	 *
	 * @return mixed|null
	 */
	public function offsetGet( $offset ) {
		if ( $this->offsetExists( $offset ) ) {
			return $this->data[ $offset ];
		}

		return null;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->data[] = $value;
		} else {
			$this->data[ $offset ] = $value;
		}
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset( $offset ) {
		unset( $this->data[ $offset ] );
	}

	/**
	 * @return int
	 */
	public function count() {
		return count( $this->data );
	}

	/**
	 * @return mixed
	 */
	public function shift() {
		return array_shift( $this->data );
	}

	/**
	 * @param $value
	 */
	public function unshift( $value ) {
		array_unshift( $this->data, $value );
	}

	/**
	 * @return bool
	 */
	public function isEnd() {
		return $this->position > ( count( $this->data ) - 1 );
	}

	/**
	 * We will use this for pass the parameter to the behavior attached. Using this way instead of inherit, it easier to change the code
	 * later without modify other
	 */
	public function processItem( $callback = null ) {
		if ( $this->hasMethod( 'processItemInternal' ) ) {
			if ( $this->valid() ) {
				if ( $this->processItemInternal( $this->args, $this->current() ) ) {
					//process ok, just moving to next, if not we still keep the current pos
					$this->next();
					$this->trigger( self::EVENT_ITEM_PROCESSED );

					return true;
				} else {
					return false;
				}
			} else {
				$this->trigger( self::EVENT_ITEM_FAIlED );
			}
		} else {
			//this can be process by callback
			if ( is_callable( $callback ) ) {
				call_user_func( $callback, $this->args, $this->current() );
			}
		}
	}

	/**
	 * saving current process status
	 */
	public function saveProcess() {
		if ( $this->continueable == true ) {
			$cache = Container::instance()->get( 'cache' );
			$cache->set( 'queue_' . $this->slug, $this->position, 0 );
		}
	}

	/**
	 * clearing the queue status
	 */
	public function clearStatusData() {
		if ( $this->continueable == true ) {
			$cache = Container::instance()->get( 'cache' );
			$cache->delete( 'queue_' . $this->slug );
		}
	}
}