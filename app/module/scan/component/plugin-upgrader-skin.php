<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Scan\Component;
if ( ! class_exists( '\Plugin_Upgrader_Skin' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php';
}

class Plugin_Upgrader_Skin extends \Plugin_Upgrader_Skin {
	/**
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$defaults = array( 'url' => '', 'theme' => '', 'nonce' => '', 'title' => esc_html__( 'Update Plugin' ) );
		$args     = wp_parse_args( $args, $defaults );

		$this->plugin = $args['theme'];

		parent::__construct( $args );
	}

	public function after() {

	}

	public function error( $errors ) {

	}

	public function feedback( $string ) {

	}

	public function header() {

	}

	public function footer() {

	}
}