<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module;

use Hammer\Base\Module;
use WP_Defender\Module\Audit\Controller\Main;
use WP_Defender\Module\Audit\Controller\Main_Free;

class Audit extends Module {
	public function __construct() {
		if ( file_exists( __DIR__ . '/audit/test' ) ) {
			@unlink( __DIR__ . '/audit/test' );
		}
		if ( wp_defender()->isFree ) {
			new Main_Free();
		} else {
			new Main();
		}
	}
}