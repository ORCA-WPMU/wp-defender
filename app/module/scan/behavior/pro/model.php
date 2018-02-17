<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Scan\Behavior\Pro;

use Hammer\Base\Behavior;

/**
 * This is contains attributes
 * Class Model
 * @package WP_Defender\Module\Scan\Behavior\Pro
 */
class Model extends Behavior {
	/**
	 * Scan contents file for suspicious
	 * @var bool
	 */
	public $scan_content = true;

	/**
	 * Toggle automate scan
	 * @var bool
	 */
	public $automate_scan = true;

	/**
	 * Frequency of automate scan, params weekly|daily|monthly
	 * @var string
	 */
	public $frequency = 'weekly';

	/**
	 * Day schedule the scan, default is Sunday
	 * @var string
	 */
	public $day = 'Sunday';

	/**
	 * Time for the schedule scan, defalt is 3am
	 * @var string
	 */
	public $time = '03:00';
}