<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\HTTP_Helper;
use WP_Defender\Module\Hardener\Model\Settings;
use WP_Defender\Module\Hardener\Rule;

class Disable_Trackback extends Rule {
	static $slug = 'disable_trackback';
	static $service;

	function getDescription() {
		$this->renderPartial( 'rules/disable-trackback' );
	}

	/**
	 * @return bool
	 */
	function check() {
		return $this->getService()->check();
	}

	public function getTitle() {
		return __( "Disable trackbacks and pingbacks", wp_defender()->domain );
	}

	function addHooks() {
		$this->add_action( 'processingHardener' . self::$slug, 'process' );
		$this->add_action( 'processRevert' . self::$slug, 'revert' );
		if ( in_array( self::$slug, Settings::instance()->fixed ) ) {
			$this->add_filter( 'wp_headers', 'removePingback' );
		}
	}

	/**
	 * @param $headers
	 *
	 * @return mixed
	 */
	public function removePingback( $headers ) {
		unset( $headers['X-Pingback'] );

		return $headers;
	}

	function revert() {
		if ( ! $this->verifyNonce() ) {
			return;
		}

		$ret = $this->getService()->revert();
		if ( ! is_wp_error( $ret ) ) {
			Settings::instance()->addToIssues( self::$slug );
		} else {
			wp_send_json_error( array(
				'message' => $ret->get_error_message()
			) );
		}
	}

	function process() {
		if ( ! $this->verifyNonce() ) {
			return;
		}
		$process_posts                      = HTTP_Helper::retrieve_post( 'updatePosts' );
		$this->getService()->process_posts 	= $process_posts;

		$ret = $this->getService()->process();
		if ( ! is_wp_error( $ret ) ) {
			Settings::instance()->addToResolved( self::$slug );
		} else {
			wp_send_json_error( array(
				'message' => $ret->get_error_message()
			) );
		}
	}

	/**
	 * @return Disable_Trackback_Service
	 */
	public function getService() {
		if ( self::$service == null ) {
			self::$service = new Disable_Trackback_Service();
		}

		return self::$service;
	}
}