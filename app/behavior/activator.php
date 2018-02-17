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

class Activator extends Behavior {
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
						$settings               = Settings::instance();
						$settings->notification = 1;
						$settings->time         = '4:0';
						$settings->day          = 'monday';
						$settings->frequency    = 7;
						$cronTime               = Utils::instance()->reportCronTimestamp( $settings->time, 'scanReportCron' );
						wp_schedule_event( $cronTime, 'daily', 'scanReportCron' );
						$settings->save();
						//start a new scan
						Scan_Api::createScan();
						$activated[] = $item;
						break;
					case 'activate_audit':
						$settings               = \WP_Defender\Module\Audit\Model\Settings::instance();
						$settings->enabled      = 1;
						$settings->notification = 1;
						$settings->time         = '4:0';
						$settings->day          = 'monday';
						$settings->frequency    = 7;
						$cronTime               = Utils::instance()->reportCronTimestamp( $settings->time, 'auditReportCron' );
						wp_schedule_event( $cronTime, 'daily', 'auditReportCron' );
						$activated[] = $item;
						$settings->save();
						break;
					case 'activate_blacklist':
						$this->owner->toggleStatus( - 1, false );
						$activated[] = $item;
						break;
					case 'activate_lockout':
						$settings                   = \WP_Defender\Module\IP_Lockout\Model\Settings::instance();
						$settings->detect_404       = 1;
						$settings->login_protection = 1;
						$settings->report           = 1;
						$settings->report_frequency = 7;
						$settings->report_day       = 'monday';
						$settings->report_time      = '4:0';
						$cronTime                   = Utils::instance()->reportCronTimestamp( $settings->report_time, 'lockoutReportCron' );
						wp_schedule_event( $cronTime, 'daily', 'lockoutReportCron' );
						$activated[] = $item;
						$settings->save();
						break;
				}
			}
		}

		$cache = WP_Helper::getCache();
		$cache->set( 'isActivated', 1, 0 );

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

		if ( $cache->get( 'isActivated', false ) == 1 ) {
			return false;
		}


		if ( $cache->get( 'wdf_isActivated', false ) == 1 ) {
			//this mean user just upgraded from the free
			return true;
		}

		//alread has data, just return
		if ( get_site_option( 'wp_defender' ) != false
		     || get_site_option( 'wd_scan_settings' ) != false
		     || get_site_option( 'wd_audit_settings' ) != false
		     || get_site_option( 'wd_lockdown_settings' ) != false
		) {
			return false;
		}

		return true;
	}
}