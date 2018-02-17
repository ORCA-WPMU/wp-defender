<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\WP_Helper;
use WP_Defender\Behavior\Utils;
use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Rule_Service;

class Protect_Information_Service extends Rule_Service implements IRule_Service {

	/**
	 * @return bool
	 */
	public function check() {
		$cache = WP_Helper::getArrayCache()->get( 'Protect_Information_Service', null );
		if ( $cache === null ) {
			$url    	= wp_defender()->getPluginUrl() . 'changelog.txt';
			$ssl_verify = apply_filters( 'defender_ssl_verify', true ); //most hosts dont really have valid ssl or ssl still pending
			$status 	= wp_remote_head( $url, array( 'user-agent' => $_SERVER['HTTP_USER_AGENT'], 'sslverify' => $ssl_verify ) );
			if ( 200 == wp_remote_retrieve_response_code( $status ) ) {
				WP_Helper::getArrayCache()->set( 'Protect_Information_Service', false );
				return false;
			}
			WP_Helper::getArrayCache()->set( 'Protect_Information_Service', true );
			return true;
		} else {
			return $cache;
		}
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function process() {
		$htPath = ABSPATH . '.htaccess';
		if ( ! is_file( $htPath ) ) {
			file_put_contents( $htPath, '', LOCK_EX );
		}
		if ( ! is_writeable( $htPath ) ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
		}
		$htConfig       = file( $htPath );
		$rules    		= $this->apache_rule();
		$containsSearch = array_diff( $rules, $htConfig );
		if ( count( $containsSearch ) == 0 || ( count( $containsSearch ) == count( $rules ) ) ) {
			//append this
			$htConfig = array_merge( $htConfig, array( implode( '', $rules ) ) );
			file_put_contents( $htPath, implode( '', $htConfig ), LOCK_EX );
		}

		return true;
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function revert() {
		global $is_apache;
		if ( $is_apache ) {
			$htPath = ABSPATH . '.htaccess';
			if ( ! is_writeable( $htPath ) ) {
				return new \WP_Error( Error_Code::NOT_WRITEABLE,
					sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
			}
			$htConfig = file_get_contents( $htPath );
			$rules    = $this->apache_rule();

			preg_match_all('/## WP Defender(.*?)## WP Defender - End ##/s', $htConfig, $matches);
			if ( is_array( $matches ) && count( $matches ) > 0 ) {
				$htConfig = str_replace( implode( '', $matches[0] ), '', $htConfig );
			} else {
				$htConfig = str_replace( implode( '', $rules ), '', $htConfig );
			}
			$htConfig = trim( $htConfig );
			file_put_contents( $htPath, $htConfig, LOCK_EX );

			return true;
		} else {
			//Other servers we cant revert
			return new \WP_Error( Error_Code::INVALID, __( "Revert is not possible on your current server", wp_defender()->domain ) );
		}
	}

	/**
	 * Get Apache rule depending on the version
	 *
	 * @return array
	 */
	protected static function apache_rule() {
		$version = Utils::instance()->determineApacheVersion();
		if ( floatval( $version ) >= 2.4 ) {
			$rules    = array(
				PHP_EOL . '## WP Defender - Prevent information disclosure ##' . PHP_EOL,
				'<FilesMatch "\.(txt|md|exe|sh|bak|inc|pot|po|mo|log|sql)$">' . PHP_EOL .
				'Require all denied' . PHP_EOL .
				'</FilesMatch>' . PHP_EOL,
				'<Files robots.txt>' . PHP_EOL .
				'Require all granted' . PHP_EOL .
				'</Files>' . PHP_EOL,
				'<Files ads.txt>' . PHP_EOL .
				'Require all granted' . PHP_EOL .
				'</Files>' . PHP_EOL,
				'## WP Defender - End ##'
			);
		} else {
			$rules    = array(
				PHP_EOL . '## WP Defender - Prevent information disclosure ##' . PHP_EOL,
				'<FilesMatch "\.(txt|md|exe|sh|bak|inc|pot|po|mo|log|sql)$">' . PHP_EOL .
				'Order allow,deny' . PHP_EOL .
				'Deny from all' . PHP_EOL .
				'</FilesMatch>' . PHP_EOL,
				'<Files robots.txt>' . PHP_EOL .
				'Allow from all' . PHP_EOL .
				'</Files>' . PHP_EOL,
				'<Files ads.txt>' . PHP_EOL .
				'Allow from all' . PHP_EOL .
				'</Files>' . PHP_EOL,
				'## WP Defender - End ##'
			);
		}
		return $rules;
	}
}