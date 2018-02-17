<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\WP_Helper;
use Hammer\Helper\Log_Helper;
use WP_Defender\Behavior\Utils;
use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Rule_Service;

class Prevent_PHP_Service extends Rule_Service implements IRule_Service {

	/**
	 * @return bool
	 */
	public function check() {
		$cache = WP_Helper::getArrayCache()->get( 'Prevent_PHP_Service', null );
		if ( $cache === null ) {
			//init upload dir and a php file
			Utils::instance()->getDefUploadDir();
			$url    	= WP_Helper::getUploadUrl();
			$url    	= $url . '/wp-defender/index.php';
			$ssl_verify = apply_filters( 'defender_ssl_verify', true ); //most hosts dont really have valid ssl or ssl still pending
			$status 	= wp_remote_head( $url, array( 'user-agent' => $_SERVER['HTTP_USER_AGENT'], 'timeout' => 10, 'sslverify' => $ssl_verify ) );
			if ( is_wp_error( $status ) ) {
				//General error
				return false;
			} else {
				if ( 200 == wp_remote_retrieve_response_code( $status ) ) {
					WP_Helper::getArrayCache()->set( 'Prevent_PHP_Service', false );

					return false;
				}
				WP_Helper::getArrayCache()->set( 'Prevent_PHP_Service', true );

				return true;
			}
		} else {
			return $cache;
		}
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function process() {
		return new \WP_Error( Error_Code::INVALID, __( "Process is not possible on your current server", wp_defender()->domain ) );
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function revert() {
		return new \WP_Error( Error_Code::INVALID, __( "Revert is not possible on your current server", wp_defender()->domain ) );
	}
}