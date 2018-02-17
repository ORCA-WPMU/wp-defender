<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use WP_Defender\Module\Hardener\Rule;

class WP_Version extends Rule {
	static $slug = 'wp_version';
	static $service;

	function getDescription() {
		$this->renderPartial( 'rules/wp-version' );
	}

	/**
	 * @return bool
	 */
	function check() {
		return $this->getService()->check();
	}

	function revert() {
		// TODO: Implement revert() method.
	}

	public function getTitle() {
		return __( "Update WordPress to latest version", wp_defender()->domain );
	}

	function addHooks() {
		// TODO: Implement addHooks() method.
	}

	function process() {
		// TODO: Implement process() method.
	}

	/**
	 * @return WP_Version_Service
	 */
	function getService() {
		if ( static::$service == null ) {
			static::$service = new WP_Version_Service();
		}

		return static::$service;
	}
}