<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\IP_Lockout\Behavior\Pro;

use Hammer\Base\Behavior;
use WP_Defender\Behavior\Utils;
use WP_Defender\Module\IP_Lockout\Component\Login_Protection_Api;
use WP_Defender\Module\IP_Lockout\Model\Settings;

class Reporting extends Behavior {
	/**
	 * Sending report email
	 */
	public function lockoutReportCron() {
		if ( wp_defender()->isFree ) {
			return;
		}
		$settings = Settings::instance();

		if ( $settings->report == false ) {
			return false;
		}

		$lastReportSent = $settings->lastReportSent;
		if ( $lastReportSent == null ) {
			//no sent, so just assume last 30 days, as this only for monthly
			$lastReportSent = strtotime( '-31 days', current_time( 'timestamp' ) );
		}

		if ( ! Utils::instance()->isReportTime( $settings->report_frequency, $settings->report_day, $lastReportSent ) ) {
			return false;
		}

		$after_time = '';
		$time_unit  = '';
		switch ( $settings->report_frequency ) {
			case '1':
				$after_time = 'yesterday midnight';
				$time_unit  = __( "In the past 24 hours", wp_defender()->domain );
				break;
			case '7':
				$after_time = '-7 days';
				$time_unit  = __( "In the past week", wp_defender()->domain );
				break;
			case '30':
				$after_time = '-30 days';
				$time_unit  = __( "In the month", wp_defender()->domain );
				break;
		}
		$after_time = strtotime( $after_time, current_time( 'timestamp' ) );
		$userIds    = $settings->report_receipts;
		$userIds    = array_unique( $userIds );
		foreach ( $userIds as $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( is_object( $user ) ) {
				$content        = $this->owner->renderPartial( 'emails/report', array(
					'admin'         => $user->display_name,
					'count_total'   => Login_Protection_Api::getAllLockouts( $after_time ),
					'last_lockout'  => Login_Protection_Api::getLastLockout(),
					'lockout_404'   => Login_Protection_Api::get404Lockouts( $after_time ),
					'lockout_login' => Login_Protection_Api::getLoginLockouts( $after_time ),
					'time_unit'     => $time_unit
				), false );
				$no_reply_email = "noreply@" . parse_url( get_site_url(), PHP_URL_HOST );
				$no_reply_email = apply_filters( 'wd_lockout_noreply_email', $no_reply_email );
				$headers        = array(
					'From: Defender <' . $no_reply_email . '>',
					'Content-Type: text/html; charset=UTF-8'
				);
				wp_mail( $user->user_email, sprintf( __( "Defender Lockouts Report for %s", wp_defender()->domain ), network_site_url() ), $content, $headers );
			}
		}
		$settings->lastReportSent = time();
		$settings->save();
	}

	public function scheduleReport() {
		$settings = Settings::instance();
		$cronTime = Utils::instance()->reportCronTimestamp( $settings->report_time, 'lockoutReportCron' );
		if ( $settings->report == true ) {
			wp_schedule_event( $cronTime, 'daily', 'lockoutReportCron' );
		}
	}
}