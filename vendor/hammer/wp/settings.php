<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\WP;

use Hammer\Base\Model;
use Hammer\Helper\Log_Helper;

/**
 * All settings class should extend ths
 *
 * Class Settings
 * @package Hammer\WP
 */
class Settings extends Model {
	private static $_instance;

	const EVENT_BEFORE_SAVE = 'beforeSave', EVENT_AFTER_SAVE = 'afterSave', EVENT_BEFORE_DELETE = 'beforeDelete', EVENT_AFTER_DELETED = 'afterDeleted';
	/**
	 * Required, this will be the option name for storing
	 * @var string
	 */
	protected $id;
	protected $is_multi;

	public function __construct( $id, $is_multi ) {
		$this->id       = $id;
		$this->is_multi = $is_multi;
		if ( $this->is_multi ) {
			$data = get_site_option( $this->id, false );
		} else {
			$data = get_option( $this->id, false );
		}

		if ( is_array( $data ) && count( $data ) ) {
			$this->import( $data );
		}
	}

	/**
	 * Saving current settings to database
	 *
	 * @return bool
	 */
	public function save() {
		$this->trigger( self::EVENT_BEFORE_SAVE );
		$data = $this->export();
		if ( $this->is_multi ) {
			$ret = update_site_option( $this->id, $data );
		} else {
			$ret = update_option( $this->id, $data, false );
		}

		$this->trigger( self::EVENT_AFTER_SAVE );

		return $ret;
	}

	public function delete() {
		$this->trigger( self::EVENT_BEFORE_DELETE );
		$ret = delete_option( $this->id );
		$ret = delete_site_option( $this->id );
		$this->trigger( self::EVENT_AFTER_DELETED );
	}
}