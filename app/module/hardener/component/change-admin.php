<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\HTTP_Helper;
use WP_Defender\Module\Hardener\Model\Settings;
use WP_Defender\Module\Hardener\Rule;

class Change_Admin extends Rule {
	static $slug = 'change_admin';
	static $service;

	public function getDescription() {
		$this->renderPartial( 'rules/change-admin' );
	}

	public function check() {
		return $this->getService()->check();
	}

	public function addHooks() {
		$this->add_action( 'processingHardener' . self::$slug, 'process' );
	}

	public function revert() {
		// TODO: Implement revert() method.
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return __( "Change default admin user account", wp_defender()->domain );
	}

	/**
	 *
	 */
	public function process() {
		if ( ! $this->verifyNonce() ) {
			return;
		}
		$username = HTTP_Helper::retrieve_post( 'username' );
		$this->getService()->setUsername( $username );
		$ret = $this->getService()->process();
		if ( is_wp_error( $ret ) ) {
			wp_send_json_error( array(
				'message' => $ret->get_error_message()
			) );
		} else {
			Settings::instance()->addToResolved( self::$slug );
			wp_send_json_success( array(
				'message' => sprintf( __( "Your admin name has changed. You will need to <a href='" . wp_login_url() . "'><strong>%s</strong></a>.<br/>This will auto reload after <span class='hardener-timer'>10</span> seconds.", wp_defender()->domain ), "re-login" ),
				'reload'  => 10
			) );
		}
	}

	/**
	 * @return Change_Admin_Service
	 */
	public function getService() {
		if ( self::$service == null ) {
			self::$service = new Change_Admin_Service();
		}

		return self::$service;
	}
}