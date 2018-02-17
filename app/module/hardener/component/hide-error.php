<?php
/**
 * Author: Hoang Ngo
 */
namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\WP_Helper;
use WP_Defender\Module\Hardener\Model\Settings;
use WP_Defender\Module\Hardener\Rule;

class Hide_Error extends Rule {
	static $slug = 'hide_error';
	static $service;

	function getDescription() {
		$this->renderPartial( 'rules/hide-error' );
	}

	/**
	 * @return bool|false|mixed|null
	 */
	function check() {
		$stat = WP_Helper::getArrayCache()->get( self::$slug . 'stat', null );
		if ( $stat === null ) {
			$stat = $this->getService()->check();
			WP_Helper::getArrayCache()->set( self::$slug . 'stat', $stat );
		}

		return $stat;
	}

	public function getTitle() {
		return __( "Hide error reporting", wp_defender()->domain );
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

	function addHooks() {
		$this->add_action( 'processingHardener' . self::$slug, 'process' );
		$this->add_action( 'processRevert' . self::$slug, 'revert' );
	}

	function process() {
		if ( ! $this->verifyNonce() ) {
			return;
		}
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
	 * @return Hide_Error_Service
	 */
	public function getService() {
		if ( self::$service == null ) {
			self::$service = new Hide_Error_Service();
		}

		return self::$service;
	}
}