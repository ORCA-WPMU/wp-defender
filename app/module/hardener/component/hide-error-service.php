<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\Array_Helper;
use Hammer\Helper\WP_Helper;
use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Rule_Service;

class Hide_Error_Service extends Rule_Service implements IRule_Service {

	/**
	 * @return bool
	 */
	public function check() {
		//we will start a request to see if it is showing error
		$altCache = WP_Helper::getArrayCache();
		$cached = $altCache->get( 'Hide_Error_Service', null );
		if ( $cached === null ) {
			//tmp turn off error log
			$isLog = ini_get( 'log_errors' );
			if ( $isLog == 1 ) {
				ini_set( 'log_errors', 0 );
			}
			$url      = site_url( 'wp-includes/theme-compat/embed.php', array(
				'user-agent' => 'Defender self check'
			) );
			$response = wp_remote_get( $url );
			$body     = wp_remote_retrieve_body( $response );
			if ( $isLog == 1 ) {
				ini_set( 'log_errors', 1 );
			}
			if ( strpos( $body, ABSPATH . 'wp-includes/theme-compat/embed.php' ) !== false ||
					WP_DEBUG == true && ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY != false )) {
				$altCache->set( 'Hide_Error_Service', 0 );

				return false;
			}
			$altCache->set( 'Hide_Error_Service', 1 );

			return true;
		} else {
			return $cached;
		}
	}

	/**
	 * Process to fix the wp-config base on scenario
	 *
	 * @return bool|\WP_Error
	 */
	public function process() {
		$config_path = $this->retrieveWPConfigPath();
		//check if can write
		if ( ! is_writeable( $config_path ) ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $config_path ) );
		}
		$config = file( $config_path );
		if ( ( $info = $this->findWPDebugLine( $config ) ) === - 1 ) {
			//this is a rare case, wpdebug is not defined
			if ( constant( 'WP_DEBUG' ) == null ) {
				//nowhere, find the hook line, hook after $prefix
				$hookline = $this->findDefaultHookLine( $config );
				if ( $hookline === false ) {
					return new \WP_Error( Error_Code::UNKNOWN_WPCONFIG, __( "Defender can't recognize your wp-config.php, please revert it to original state for further process.", wp_defender()->domain ) );
				}
				$config = Array_Helper::injectLine( $config, $hookline + 1, PHP_EOL . "define( 'WP_DEBUG', false );" . PHP_EOL );
				file_put_contents( $config_path, implode( null, $config ), LOCK_EX );

				return true;
			} else {
				//already somewhere
				return new \WP_Error( Error_Code::UNKNOWN_WPCONFIG, __( "Defender can't recognize your wp-config.php, please revert it to original state for further process.", wp_defender()->domain ) );
			}
		}

		list( $value, $line ) = $info;
		if ( $value == 1 ) {
			if ( constant( 'WP_DEBUG_LOG' ) == true ) {
				//debug need to be on for logging, so we just hide it
				$display_line = $this->findDebugDisplayLine( $config );
				if ( $display_line === false ) {
					$config = Array_Helper::injectLine( $config, $line + 1, "define( 'WP_DEBUG_DISPLAY', false );" . PHP_EOL );
				} else {
					$config[ $display_line ] = "define( 'WP_DEBUG_DISPLAY', false );" . PHP_EOL;
				}
			} else {
				//change wpdebug to off
				$config[ $line ] = 'define( \'WP_DEBUG\', false );' . PHP_EOL;
			}
			file_put_contents( $config_path, implode( null, $config ), LOCK_EX );

			return true;

		} elseif ( $value == 0 ) {
			//debug already off
			//this is a rare case, debug is off, but error still showing up
			return new \WP_Error( 0, __( "WP_DEBUG get override somewhere, please check with your host provider", wp_defender()->domain ) );
		}
	}

	/**
	 * This will parse wpconfig lines and check if we do have any defined wp_debug
	 * result code
	 * 1. found and enable
	 * 0. found and disable
	 * -1. not found
	 *
	 * @param $config
	 *
	 * @return int|array
	 */
	private function findWPDebugLine( $config ) {
		$pattern = "/^define\(\s*['|\"]WP_DEBUG['|\"],(.*)\)/";
		foreach ( $config as $key => $line ) {
			$line = trim( $line );
			if ( preg_match( $pattern, $line, $matches ) ) {
				if ( trim( $matches[1] ) == true ) {
					return array( 1, $key );
				} else {
					return array( 0, $key );
				}
			}
		}

		return - 1;
	}

	/**
	 * @param $config
	 *
	 * @return bool|string
	 */
	private static function findDebugDisplayLine( $config ) {
		$pattern = "/^define\(\s*['|\"]WP_DEBUG_DISPLAY['|\"], (.*)\)/";
		foreach ( $config as $key => $line ) {
			$line = trim( $line );
			if ( preg_match( $pattern, $line, $matches ) ) {
				return $key;
			}
		}

		return false;
	}

	public function revert() {
		/**
		 * find the line we append by checking the comment
		 * remove whole block
		 */
	}

	public function listen() {

	}
}