<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Advanced_Tools\Component;

use Hammer\Base\Component;
use WP_Defender\Behavior\Utils;
use WP_Defender\Module\Advanced_Tools\Model\Auth_Settings;

class Auth_API extends Component {
	/**
	 * @param int $length
	 *
	 * @return string
	 */
	public static function generateSecret( $length = 16 ) {
		$strings = "ABCDEFGHIJKLMNOPQRSTUVWXYS234567";
		$secret  = array();
		for ( $i = 0; $i < $length; $i ++ ) {
			$secret[] = $strings[ rand( 0, strlen( $strings ) - 1 ) ];
		}

		return implode( "", $secret );
	}

	/**
	 * @param $name
	 * @param $secret
	 * @param int $width
	 * @param int $height
	 * @param null $title
	 *
	 * @return string
	 */
	public static function generateQRCode( $name, $secret, $width = 200, $height = 200, $title = null ) {
		$chl = urlencode( 'otpauth://totp/' . $name . '?secret=' . $secret . '' );
		if ( ! is_null( $title ) ) {
			$chl .= urlencode( '&issuer=' . $title );
		}

		return "https://chart.googleapis.com/chart?cht=qr&chs={$width}x{$height}&chl=$chl&chld=M|0";
	}

	/**
	 * Calculate the TOTP code
	 *
	 * @param $secret
	 * @param $counter
	 *
	 * @return \string
	 *
	 * reference: https://tools.ietf.org/html/rfc4226#section-5.3
	 *            https://garbagecollected.org/2014/09/14/how-google-authenticator-works/
	 */
	public static function generateCode( $secret, $counter = null ) {
		//secret should be base 32, as GA want it
		include_once wp_defender()->getPluginPath() . 'vendor/binary-to-text-php/Base2n.php';
		$base32 = new \Base2n( 5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', false, true, true );
		$secret = $base32->decode( $secret );
		//timestep fixed at 30
		if ( is_null( $counter ) ) {
			$counter = time();
		}
		$input = floor( $counter / 30 );
		//according to https://tools.ietf.org/html/rfc4226#section-5.3, should be a 8 bytes value
		$time = chr( 0 ) . chr( 0 ) . chr( 0 ) . chr( 0 ) . pack( 'N*', $input );
		$hmac = hash_hmac( 'sha1', $time, $secret, true );
		//now we have 20 bytes sha1, need to short it down
		//getting last byte of the hmac
		$offset     = ord( substr( $hmac, - 1 ) ) & 0x0F;
		$four_bytes = substr( $hmac, $offset, 4 );
		//now convert it into INT
		$value = unpack( 'N', $four_bytes );
		$value = $value[1];
		//make sure it always act like 32 bits
		$value = $value & 0x7FFFFFFF;;
		//we so close
		$code = $value % pow( 10, 6 );
		//in some case we have the 0 before, so it become lesser than 6, make sure it always right
		$code = str_pad( $code, 6, '0', STR_PAD_LEFT );

		return $code;
	}

	/**
	 * @param $secret
	 * @param $userCode
	 * @param int $window
	 *
	 * @return bool
	 */
	public static function compare( $secret, $userCode, $window = 1 ) {
		if ( strlen( $userCode ) != 6 ) {
			return false;
		}

		/**
		 * window is 30 seconds, before and after
		 */
		for ( $i = - $window; $i <= $window; $i ++ ) {
			$counter = $i == 0 ? null : $i * 30 + time();
			$code    = self::generateCode( $secret, $counter );
			if ( self::hasEqual( $code, $userCode ) ) {
				return true;
			}
		}


		return false;
	}

	/**
	 * Timing attack safe string comparison, replacement of has_equals which only on 5.6+
	 *
	 * @param $known_string
	 * @param $user_string
	 *
	 * @return bool
	 * reference: http://php.net/manual/en/function.hash-equals.php#119576
	 */
	private static function hasEqual( $known_string, $user_string ) {
		if ( function_exists( 'hash_equals' ) ) {
			return hash_equals( $known_string, $user_string );
		}

		$ret = 0;

		if ( strlen( $known_string ) !== strlen( $user_string ) ) {
			$user_string = $known_string;
			$ret         = 1;
		}

		$res = $known_string ^ $user_string;

		for ( $i = strlen( $res ) - 1; $i >= 0; -- $i ) {
			$ret |= ord( $res[ $i ] );
		}

		return ! $ret;
	}

	/**
	 * @return bool
	 */
	public static function isEnableForCurrentRole( $user = null ) {
		if ( $user == null ) {
			$user = wp_get_current_user();
		}
		if ( ! $user instanceof \WP_User ) {
			return false;
		}
		$settings = Auth_Settings::instance();
		if ( 0 === count( $user->roles ) ) {
			return true;
		}

		if ( Utils::instance()->isActivatedSingle() ) {
			$allowedForThisRole = array_intersect( $settings->userRoles, $user->roles );

			return count( $allowedForThisRole ) > 0;
		} else {
			$blogs     = get_blogs_of_user( $user->ID );
			$userRoles = array();
			foreach ( $blogs as $blog ) {
				//get user roles for this blog
				$u         = new \WP_User( $user->ID, '', $blog->userblog_id );
				$userRoles = array_merge( $u->roles, $userRoles );
			}
			$allowedForThisRole = array_intersect( $settings->userRoles, $userRoles );

			return count( $allowedForThisRole ) > 0;
		}
	}

	/**
	 * @return bool|mixed|string
	 */
	public static function createSecretForCurrentUser() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$secret = get_user_meta( get_current_user_id(), 'defenderAuthSecret', true );
		if ( ! $secret ) {
			$secret = self::generateSecret();
			update_user_meta( get_current_user_id(), 'defenderAuthSecret', $secret );
		}

		return $secret;
	}

	/**
	 * @param null $userID
	 *
	 * @return bool|mixed
	 */
	public static function getUserSecret( $userID = null ) {
		if ( $userID == null ) {
			$userID = get_current_user_id();
		}
		$secret = get_user_meta( $userID, 'defenderAuthSecret', true );
		if ( ! $secret ) {
			return false;
		}

		return $secret;
	}

	/**
	 * @param $userID
	 *
	 * @return mixed
	 */
	public static function isUserEnableOTP( $userID ) {
		if ( $userID instanceof \WP_User ) {
			$user   = $userID;
			$userID = $user->ID;
		} else {
			$user = get_user_by( 'id', $userID );
		}
		if ( ! self::isEnableForCurrentRole( $user ) ) {
			return false;
		}

		$isOn = get_user_meta( $userID, 'defenderAuthOn', true );

		return $isOn;
	}

	/**
	 * @param $userID
	 *
	 * @return bool|mixed|string
	 */
	public static function getBackupEmail( $userID ) {
		$email = get_user_meta( $userID, 'defenderAuthEmail', true );
		if ( empty( $email ) ) {
			$user = get_user_by( 'id', $userID );
			if ( ! is_object( $user ) ) {
				return false;
			}
			$email = $user->user_email;
		}

		return $email;
	}

	/**
	 * Generate single code, use in case lost phone
	 *
	 * @param $userID
	 *
	 * @return string
	 */
	public static function createBackupCode( $userID ) {
		$code = wp_generate_password( 20, false );
		update_user_meta( $userID, 'defenderBackupCode', array(
			'code' => $code,
			'time' => time()
		) );

		return $code;
	}

	/**
	 * @return bool
	 */
	public static function isJetPackSSO() {
		if ( is_plugin_active_for_network( 'jetpack/jetpack.php' ) ) {
			//loop through all sites
			$settings   = Auth_Settings::instance();
			$isConflict = $settings->isConflict( 'jetpack/jetpack.php' );
			if ( $isConflict === 0 ) {
				//no data, init
				global $wpdb;
				$sql   = "SELECT blog_id FROM `{$wpdb->prefix}blogs`";
				$blogs = $wpdb->get_col( $sql );
				foreach ( $blogs as $id ) {
					$options = get_blog_option( $id, 'jetpack_active_modules', array() );
					if ( array_search( 'sso', $options ) ) {
						$settings->markAsConflict( 'jetpack/jetpack.php' );

						return true;
					}
				}
			} else {
				//get the data from cache
				return $isConflict;
			}

		} elseif ( is_plugin_active( 'jetpack/jetpack.php' ) ) {
			//ugly but faster
			$settings   = Auth_Settings::instance();
			$isConflict = $settings->isConflict( 'jetpack/jetpack.php' );
			if ( $isConflict === 0 ) {
				$options = get_option( 'jetpack_active_modules', array() );
				if ( array_search( 'sso', $options ) ) {
					$settings->markAsConflict( 'jetpack/jetpack.php' );

					return true;
				}
			} else {
				return $isConflict;
			}

		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function isTML() {
		if ( is_plugin_active( 'theme-my-login/theme-my-login.php' ) || is_plugin_active_for_network( 'theme-my-login/theme-my-login.php' ) ) {
			$settings = Auth_Settings::instance();
			$settings->markAsConflict( 'theme-my-login/theme-my-login.php' );

			return true;
		}

		return false;
	}
}