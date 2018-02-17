<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Base;
/**
 * Every event class need to extend this
 *
 * Class Event
 * @package Hammer\Base
 */
class Event extends HObject {
	/**
	 * Event name
	 * @var string
	 */
	public $name;
	/**
	 * The object calling this event
	 *
	 * @var object
	 */
	public $sender;
	/**
	 * A flag to say if this event handled or not
	 * @var bool
	 */
	public $handled = false;

	/**
	 * Passed args for this event
	 * @var mixed
	 */
	public $data = null;

	/**
	 * Use for return event result
	 * @var null
	 */
	public $result = null;
}