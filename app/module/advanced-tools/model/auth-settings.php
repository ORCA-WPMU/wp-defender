<?php

/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Advanced_Tools\Model;

use Hammer\Helper\WP_Helper;

class Auth_Settings extends \Hammer\WP\Settings {
	private static $_instance;
	public $enabled = false;
	public $lostPhone = true;
	public $userRoles = array();
	public $isConflict = array();

	public function __construct( $id, $is_multi ) {
		//fetch the userRoles
		if ( ! function_exists( 'get_editable_roles' ) ) {
			include_once ABSPATH . 'wp-admin/includes/user.php';
		}
		$this->userRoles = array_keys( get_editable_roles() );
		//remove subscriber from the list
		unset( $this->userRoles[ array_search( 'subscriber', $this->userRoles ) ] );
		parent::__construct( $id, $is_multi );
	}

	/**
	 * @return Auth_Settings
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			$class           = new Auth_Settings( 'wd_2auth_settings', WP_Helper::is_network_activate( wp_defender()->plugin_slug ) );
			self::$_instance = $class;
		}

		return self::$_instance;
	}

	/**
	 * @param $plugin
	 *
	 * @return bool|int
	 */
	public function isConflict( $plugin ) {
		if ( in_array( $plugin, $this->isConflict ) ) {
			return true;
		} elseif ( in_array( '!' . $plugin, $this->isConflict ) ) {
			return false;
		}

		return 0;
	}

	/**
	 * @param $plugin
	 */
	public function markAsConflict( $plugin ) {
		if ( ! in_array( $plugin, $this->isConflict ) ) {
			$this->isConflict [] = $plugin;
			$this->save();
		}
	}

	/**
	 * @param $plugin
	 */
	public function markAsUnConflict( $plugin ) {
		if ( ( $i = array_search( $plugin, $this->isConflict ) ) !== false ) {
			unset( $this->isConflict[ $i ] );
		}
		if ( ! in_array( '!' . $plugin, $this->isConflict ) ) {
			$this->isConflict [] = '!' . $plugin;
		}
		$this->save();
	}

	public function events() {
		$that = $this;

		return array(
			self::EVENT_AFTER_DELETED => array(
				array(
					function () use ( $that ) {
						global $wpdb;
						$sql = "DELETE from " . $wpdb->usermeta . " WHERE meta_key IN ('defOTPLoginToken','defenderBackupCode','defenderAuthSecret','defenderAuthOn','defenderAuthEmail')";
						$wpdb->query( $sql );
					}
				)
			)
		);
	}
}