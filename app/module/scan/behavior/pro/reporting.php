<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Scan\Behavior\Pro;

use Hammer\Base\Behavior;
use WP_Defender\Behavior\Utils;

class Reporting extends Behavior {
	public function scheduleReportTime( $settings ) {
		if ( $settings->notification ) {
			$cronTime = Utils::instance()->reportCronTimestamp( $settings->time, 'scanReportCron' );
			wp_schedule_event( $cronTime, 'daily', 'scanReportCron' );
		} else {
			wp_clear_scheduled_hook( 'processScanCron' );
		}

	}
}