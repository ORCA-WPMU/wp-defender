<?php

/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\IP_Lockout\Model;

use Hammer\Base\DB_Model;

class IP_Model extends DB_Model {
	const STATUS_BLOCKED = 'blocked', STATUS_NORMAL = 'normal';

	protected static $tableName = 'defender_lockout';

	public $id;
	public $ip;
	public $status;
	public $lockout_message;
	public $release_time;
	public $lock_time;
	public $lock_time_404;
	public $attempt;
	public $attempt_404;
	public $meta;

	/**
	 * @return bool
	 */
	public function is_locked() {
		if ( $this->status == self::STATUS_BLOCKED ) {
			if ( $this->release_time < time() ) {
				//unlock it
				$this->attempt = 0;
				$this->status  = self::STATUS_NORMAL;
				$this->save();

				return false;
			} else {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $key
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public function getMeta( $key, $default = null ) {
		$meta = $this->meta;
		if ( ! is_array( $meta ) ) {
			$meta = maybe_unserialize( $meta );
		}

		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		if ( isset( $meta[ $key ] ) ) {
			return $meta[ $key ];
		}

		return $default;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function updateMeta( $key, $value ) {
		$meta = $this->meta;
		if ( ! is_array( $meta ) ) {
			$meta = maybe_unserialize( $meta );
		}

		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		$meta[ $key ] = $value;
		$this->meta   = serialize( $meta );
		$this->save();
	}

	public function events() {
		$that = $this;

		return array(
			self::EVENT_BEFORE_INSERT => array(
				array(
					function () use ( $that ) {
						if ( is_array( $that->meta ) ) {
							$that->meta = serialize( $that->meta );
						}
					}
				)
			),
			self::EVENT_BEFORE_UPDATE => array(
				array(
					function () use ( $that ) {
						if ( is_array( $that->meta ) ) {
							$that->meta = serialize( $that->meta );
						}
					}
				)
			)
		);
	}
}