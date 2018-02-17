<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Rule_Service;

class PHP_Version_Service extends Rule_Service implements IRule_Service {

	/**
	 * @return bool
	 */
	public function check() {
		if ( version_compare( $this->getPHPVersion(), '5.6', '<=' ) ) {
			return false;
		}

		return true;
	}

	public function process() {

	}

	public function revert() {

	}

	public function listen() {

	}
}