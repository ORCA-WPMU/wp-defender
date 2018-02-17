<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\WP_Helper;
use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Model\Settings;
use WP_Defender\Module\Hardener\Rule_Service;

class Security_Key_Service extends Rule_Service implements IRule_Service {
	const CACHE_KEY = 'security_key';
	const DEFAULT_DAYS = '60 days';

	/**
	 * @return bool
	 */
	public function check() {
		$last = Settings::instance()->getDValues( self::CACHE_KEY );
		$reminder = Settings::instance()->getDValues( 'securityReminderDate' );
		if ( $last ) {
			if ( $reminder == null ) {
				$reminder = strtotime( '+' . self::DEFAULT_DAYS, $last );
			}
			if ( $reminder < time() ) {
				return false;
			}

			return true;
		} elseif ( $reminder != null && $reminder < time() ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function process() {
		$config_path = $this->retrieveWPConfigPath();
		//check if can write
		if ( ! is_writeable( $config_path ) ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $config_path ) );
		}

		return $this->generateSalt( $config_path );

	}

	public function revert() {
		Settings::instance()->setDValues( self::CACHE_KEY, null );
		Settings::instance()->setDValues( 'securityReminderDate', null );
	}

	/**
	 * This function will check & generate new salt if needed
	 * Cover case
	 * All salt provided in wp-config
	 * No salt in wp-config
	 * Partial salt (missing some) in wp-config
	 *
	 * @param null $path
	 *
	 * @return bool|\WP_Error
	 */
	private function generateSalt( $path ) {
		$const  = array(
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		);
		$config = file( $path );
		//we need a place where we can inject the define in case wp config missing
		$hook_line = false;
		$missing   = array();
		foreach ( $const as $key ) {
			//generate salt
			$salt = wp_generate_password( 64, true, true );
			//replace it to the wp config
			if ( defined( $key ) ) {
				$old_salt = constant( $key );
				//replace
				foreach ( $config as $index => $line ) {
					$line = trim( $line );

					$pattern = '/^define\(\s*(\'|\")' . $key . '(\'|\")\s*,\s*(\'|\")' . preg_quote( $old_salt, '/' ) . '(\'|\")\s*\)/';

					if ( preg_match( $pattern, $line ) === 1 ) {
						//match
						$new_line         = "define( '$key', '$salt' );" . PHP_EOL;
						$config[ $index ] = $new_line;
						if ( $hook_line === false ) {
							$hook_line = $index;
						}
						//break out of the config line loop
						break;
					}
				}
			} else {
				//we don't have any key like this, so we will inject
				$missing[] = $key;
			}
		}
		//now check the missing
		if ( count( $missing ) ) {
			//for any reason we missing a security key, this mean wp-config altered by 3rd party, halt
			return new \WP_Error( Error_Code::UNKNOWN_WPCONFIG, __( "Defender can't recognize your wp-config.php, please revert it to original state for further process.", wp_defender()->domain ) );
		}

		//we already check for perm above, no need to check again
		//lock the file
		file_put_contents( $path, implode( '', $config ), LOCK_EX );
		Settings::instance()->setDValues( self::CACHE_KEY, time() );

		return true;
	}
}