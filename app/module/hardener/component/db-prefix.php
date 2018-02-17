<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\HTTP_Helper;
use WP_Defender\Module\Hardener\Model\Settings;
use WP_Defender\Module\Hardener\Rule;

class DB_Prefix extends Rule {
	static $slug = 'db_prefix';
	static $service;

	function getDescription() {
		$this->renderPartial( 'rules/db-prefix' );
	}

	function check() {
		return $this->getService()->check();
	}

	function addHooks() {
		$this->add_action( 'processingHardener' . self::$slug, 'process' );
	}

	function revert() {
		// TODO: Implement revert() method.
	}

	public function getTitle() {
		return __( "Change default database prefix", wp_defender()->domain );
	}

	function process() {
		if ( ! $this->verifyNonce() ) {
			return;
		}
		$dbprefix                       = HTTP_Helper::retrieve_post( 'dbprefix' );
		$this->getService()->new_prefix = $dbprefix;
		$ret                            = $this->getService()->process();

		if ( ! is_wp_error( $ret ) ) {
			Settings::instance()->addToResolved( self::$slug );
		} else {
			wp_send_json_error( array(
				'message' => $ret->get_error_message()
			) );
		}
	}

	/**
	 * @return DB_Prefix_Service
	 */
	function getService() {
		if ( static::$service == null ) {
			static::$service = new DB_Prefix_Service();
		}

		return static::$service;
	}
}