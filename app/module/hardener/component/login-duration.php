<?php
/**
 * @author Paul Kevin
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\WP_Helper;
use Hammer\Helper\HTTP_Helper;
use WP_Defender\Module\Hardener\Model\Settings;
use WP_Defender\Module\Hardener\Rule;
use WP_Defender\Behavior\Utils;

class Login_Duration extends Rule {

    static $slug = 'login-duration';

    static $service;

    /**
	 * @return Login_Duration_Service
	 */
	public function getService() {
		if ( self::$service == null ) {
			self::$service = new Login_Duration_Service();
		}
		return self::$service;
	}

    function getDescription() {
		$this->renderPartial( 'rules/login-duration' );
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return __( "Manage Login Duration", wp_defender()->domain );
	}

    /**
	 * @return bool
	 */
	function check() {
		return $this->getService()->check();
	}

	function addHooks() {
		$this->add_action( 'processingHardener' . self::$slug, 'process' );
		$this->add_action( 'processRevert' . self::$slug, 'revert' );
		$this->add_action( 'wp_login', 'login_action_handler', 10, 2 );
		if ( $this->check() ) {
			$this->add_filter( 'auth_cookie_expiration', 'cookie_duration', 10, 3 );
			$this->add_filter( 'login_message', 'login_message' );
			$this->add_action( 'wp_loaded', 'check_login' );
		}

	}

    function revert() {
		if ( ! $this->verifyNonce() ) {
			return;
		}
		$settings 	= Settings::instance();
		$service 	= $this->getService();
		$ret = $service->revert();
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
		$service 	= $this->getService();
		$duration 	= HTTP_Helper::retrieve_post( 'duration' );
		if ( is_numeric( $duration ) && intval( $duration ) > 0 ) {
			$service->setDuration( $duration );
			$ret = $service->process();
			if ( ! is_wp_error( $ret ) ) {
				Settings::instance()->addToResolved( self::$slug );
			} else {
				wp_send_json_error( array(
					'message' => $ret->get_error_message()
				) );
			}
		} else {
			wp_send_json_error( array(
				'message' => __( 'Duration can only be a number and greater than 0', wp_defender()->domain )
			) );
		}
	}

	/**
	 * Set the last login user meta
	 */
	function login_action_handler( $user_login, $user = '' ) {
		if ( $user == '' ){
			$user = get_user_by( 'login', $user_login );
		}
		if ( !$user ){
			return;
		}
		$last_login_time = current_time( 'mysql' );
        update_user_meta( $user->ID, 'last_login_time', $last_login_time );
	}

	/**
	 * Check login of users
	 */
	function check_login() {
		$defender_logout = HTTP_Helper::retrieve_get( 'defender_logout', false );
		if( is_user_logged_in() ) {
			$current_user 	= wp_get_current_user();
			$user_id 		= $current_user->ID;
			if ( !$defender_logout ) {
				$current_time 		= current_time( 'mysql' );
				$last_login_time 	= get_user_meta( $user_id, 'last_login_time', true );
				$login_period 		= $this->getService()->getDuration( true );
				if ( $last_login_time ) {
					$current_time 		= strtotime( $current_time );
					$last_login_time 	= strtotime( $last_login_time );
					$diff 				= $current_time - $last_login_time ;
					//Check if the current and login times are not the same
					//so we dont kick out someone who set it to 0
					if( ( $current_time != $last_login_time ) && $diff > $login_period ) {
						$current_url = Utils::instance()->currentPageURL();
						$after_logout_payload = array( 'redirect_to' => $current_url, 'msg'=>'session_expired' );
						if ( is_multisite() ) {
							set_site_transient( 'defender_logout_payload', $after_logout_payload, 30 * 60 );
						}
						set_transient( 'defender_logout_payload', $after_logout_payload, 30 * 60 );
						$logout_url = add_query_arg( 'defender_logout', '1', site_url() );
						wp_redirect( $logout_url );
						exit;
					}
				} else {
					//Incase the user already was logged in
					$last_login_time = current_time( 'mysql' );
					update_user_meta( $user_id, 'last_login_time', $last_login_time );
				}
			} else{
				delete_user_meta( $user_id, 'last_login_time' );
				wp_logout();
				$after_logout = HTTP_Helper::retrieve_get( 'after_logout', false );
				if ( $after_logout ) {
					$after_logout_url = esc_url( $after_logout );
					wp_redirect( $after_logout_url );
					exit;
				}
				$login_url = wp_login_url();
				$logout_payload = ( is_multisite() ? get_site_transient( 'defender_logout_payload' ) : get_transient( 'defender_logout_payload' ) );

				$login_url = add_query_arg( array(
								'redirect_to' 				=> $logout_payload['redirect_to'],
								'defender_login_message' 	=> $logout_payload['msg'],
							), $login_url );
				wp_redirect( $login_url );
				exit;
			}
		} else if ( $defender_logout ) {
			$after_logout = HTTP_Helper::retrieve_get( 'after_logout', false );
			if ( $after_logout ) {
				$after_logout_url = esc_url( $after_logout );
				wp_redirect( $after_logout_url );
			}
			$login_url = wp_login_url();
			$logout_payload = ( is_multisite() ? get_site_transient( 'defender_logout_payload' ) : get_transient( 'defender_logout_payload' ) );

			$login_url = add_query_arg( array(
							'redirect_to' 				=> $logout_payload['redirect_to'],
							'defender_login_message' 	=> $logout_payload['msg'],
						), $login_url );
			wp_redirect( $login_url );
			exit;
		}
	}


	/**
	 * Handle the custom login message
	 *
	 */
	function login_message( $message = '' ) {
		$login_msg = HTTP_Helper::retrieve_get( 'defender_login_message', false );
		if( $login_msg ) {
			$logout_msg = strip_tags( $login_msg );
			if ( $logout_msg == 'session_expired' ) {
				$duration = $this->getService()->getDuration( false );
				$msg = sprintf( __( 'Your session has expired because it has been over %d days since your last login. Please log back in to continue.', wp_defender()->domain ), $duration );
				$msg = htmlspecialchars( $msg, ENT_QUOTES, 'UTF-8' );
           	 	$message .= '<p class="login message">'. $msg . '</p>';
			}
		}
		return $message;
	}

	/**
	 * Cookie duration in days in seconds
	 *
	 * @param Integer $duration - default duration
	 * @param Integer $user_id - current user id
	 * @param Boolean $remember - remember me login
	 *
	 * @return Integer $duration
	 */
	function cookie_duration( $duration, $user_id, $remember ) {
		if ( $remember ) {
			$duration = $this->getService()->getDuration( true );
		}
		return $duration;
	}

}

?>