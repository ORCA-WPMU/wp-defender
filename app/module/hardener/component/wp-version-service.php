<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Rule_Service;

class WP_Version_Service extends Rule_Service implements IRule_Service {

	/**
	 * @return bool
	 */
	public function check() {
		global $wp_version;
		if ( version_compare( $wp_version, $this->getLatestVersion(), '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function getLatestVersion() {
		if ( ! function_exists( 'get_core_updates' ) ) {
			include_once ABSPATH . 'wp-admin/includes/update.php';
		}
		$update_data = get_core_updates();

		if ( $update_data === false ) {
			wp_version_check( array(), true );
			$update_data = get_core_updates();
		}

		if ( isset( $update_data[0] ) && is_object( $update_data[0] ) ) {
			$latest = $update_data[0];

			return $latest->version;
		}

		return false;
	}

	public function process() {

	}

	public function revert() {

	}

	public function listen() {

	}
}