<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Audit\Model;

use Hammer\Helper\WP_Helper;

class Settings extends \Hammer\WP\Settings {

	private static $_instance;

	/**
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * @var string
	 */
	public $frequency = '7';
	/**
	 * @var string
	 */
	public $day = 'sunday';
	/**
	 * @var string
	 */

	public $time = '0:00';
	/**
	 * Toggle notification on or off
	 * @var bool
	 */
	public $notification = true;

	/**
	 * @var array
	 */
	public $receipts = array();

	public $dummy = array();
	/**
	 * @var
	 */
	public $lastReportSent;

	/**
	 * @return array
	 */
	public function behaviors() {
		return array(
			'utils' => '\WP_Defender\Behavior\Utils'
		);
	}

	public function __construct( $id, $isMulti ) {
		if ( is_admin() || is_network_admin() && current_user_can( 'manage_options' ) ) {
			$this->receipts[] = get_current_user_id();
		}

		parent::__construct( $id, $isMulti );
	}

	/**
	 * @return Settings
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			$class           = new Settings( 'wd_audit_settings', WP_Helper::is_network_activate( wp_defender()->plugin_slug ) );
			self::$_instance = $class;
		}

		return self::$_instance;
	}

}