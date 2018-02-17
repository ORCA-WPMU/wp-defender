<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module;

use Hammer\Base\Module;
use WP_Defender\Module\Advanced_Tools\Controller\Main;

class Advanced_Tools extends Module {
	public function __construct() {
		$main = new Main();
	}
}