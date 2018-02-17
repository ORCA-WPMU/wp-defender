<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Controller;

use Hammer\Helper\HTTP_Helper;
use WP_Defender\Controller;

class Debug extends Controller {
	protected $slug = 'wdf-debug';

	public function __construct() {
		if ( HTTP_Helper::retrieve_get( 'page' ) != 'wdf-debug' ) {
			return;
		}

		if ( $this->is_network_activate( wp_defender()->plugin_slug ) ) {
			$this->add_action( 'network_admin_menu', 'adminMenu' );
		} else {
			$this->add_action( 'admin_menu', 'adminMenu' );
		}
	}

	/**
	 * Add submit admin page
	 */
	public function adminMenu() {
		$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
		add_submenu_page( 'wp-defender', esc_html__( "Debug", wp_defender()->domain ), esc_html__( "Debug", wp_defender()->domain ), $cap, $this->slug, array(
			&$this,
			'actionIndex'
		) );
	}

	public function actionIndex() {
		$this->render( 'debug' );
	}

	/**
	 * @return array
	 */
	public function behaviors() {
		return array(
			'utils' => '\WP_Defender\Behavior\Utils'
		);
	}
}