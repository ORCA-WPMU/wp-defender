<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener;

/**
 * Interface IRule_Service
 * @package WP_Defender\Module\Hardener
 */
interface IRule_Service {
	/**
	 * Check if current rule fixed or not
	 * @return bool
	 */
	public function check();

	/**
	 * Process the rule
	 * @return bool|\WP_Error
	 */
	public function process();

	/**
	 * Revert if able
	 * @return bool|\WP_Error
	 */
	public function revert();
}