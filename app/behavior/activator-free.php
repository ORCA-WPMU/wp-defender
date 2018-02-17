<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Behavior;

use Hammer\Base\Behavior;
use Hammer\Helper\HTTP_Helper;
use Hammer\Helper\WP_Helper;
use WP_Defender\Module\Scan\Component\Scan_Api;
use WP_Defender\Module\Scan\Model\Settings;

class Activator_Free extends Behavior{
	public function activateModule() {
		if ( ! Utils::instance()->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'activateModule' ) ) {
			return;
		}

		$activator = HTTP_Helper::retrieve_post( 'activator' );
		$activated = array();
		if ( count( $activator ) ) {
			foreach ( $activator as $item ) {
				switch ( $item ) {
					case 'activate_scan':
						//start a new scan
						Scan_Api::createScan();
						$activated[] = $item;
						break;
					case 'activate_lockout':
						$settings                   = \WP_Defender\Module\IP_Lockout\Model\Settings::instance();
						$settings->detect_404       = 1;
						$settings->login_protection = 1;
						$activated[] = $item;
						$settings->save();
						break;
				}
			}
		}

		$cache = WP_Helper::getCache();
		$cache->set( 'wdf_isActivated', 1, 0 );

		wp_send_json_success( array(
			'activated' => $activated
		) );
	}

	/**
	 * Check if we should show activator screen
	 * @return bool
	 */
	public function isShowActivator() {
		$cache = WP_Helper::getCache();
		if ( $cache->get( 'wdf_isActivated', false ) == 1 ) {
			return false;
		}
		//alread has data, just return
		if ( get_site_option( 'wp_defender' ) != false
		     || get_site_option( 'wd_scan_settings' ) != false
		     || get_site_option( 'wd_lockdown_settings' ) != false
		) {
			return false;
		}

		return true;
	}
}