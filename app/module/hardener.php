<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module;

use Hammer\Base\Container;
use Hammer\Base\Module;
use Hammer\Helper\HTTP_Helper;
use Hammer\Helper\WP_Helper;

use WP_Defender\Module\Hardener\Controller\Main;
use WP_Defender\Module\Hardener\Model\Settings;

class Hardener extends Module {
	const Settings = 'hardener_settings';

	public function __construct() {
		//init dependency
		$this->initRulesStats();
		//call the controller
		new Main();
	}

	/**
	 * Init rules status
	 */
	public function initRulesStats() {
		$settings = Settings::instance();
		/**
		 * now we have a list of rules, and lists of their status
		 */
		$interval = '+60 minutes';
		//only refresh if on admin, if not we just do the listening

		if ( ( ( is_admin() || is_network_admin() )
		       && ( $settings->last_status_check == null || strtotime( $interval, $settings->last_status_check ) < time() )
		     ) || HTTP_Helper::retrieve_get( 'page' ) == 'wdf-hardener'
		) {
			//this mean we dont have any data, or data is overdue need to refresh
			//refetch those list

			$settings->refreshStatus();
		}

		//we will need to add every hooks needed
		foreach ( $settings->getDefinedRules( true ) as $rule ) {
			$rule->addHooks();
		}
	}

}