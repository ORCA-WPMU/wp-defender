<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Advanced_Tools\Controller;

use Hammer\Helper\HTTP_Helper;
use Hammer\Helper\WP_Helper;
use WP_Defender\Behavior\Utils;
use WP_Defender\Controller;
use WP_Defender\Module\Advanced_Tools\Component\Auth_API;
use WP_Defender\Module\Advanced_Tools\Model\Auth_Settings;

class Main extends Controller {
	protected $slug = 'wdf-advanced-tools';
	protected $sessionToken;
	public $layout = 'layout';

	/**
	 * @return array
	 */
	public function behaviors() {
		return array(
			'utils' => '\WP_Defender\Behavior\Utils'
		);
	}

	public function __construct() {
		if ( $this->is_network_activate( wp_defender()->plugin_slug ) ) {
			$this->add_action( 'network_admin_menu', 'adminMenu' );
		} else {
			$this->add_action( 'admin_menu', 'adminMenu' );
		}

		if ( $this->isInPage() || $this->isDashboard() ) {
			$this->add_action( 'defender_enqueue_assets', 'scripts', 11 );
		}
		$this->add_ajax_action( 'saveAdvancedSettings', 'saveSettings' );
		$setting = Auth_Settings::instance();
		if ( $setting->enabled ) {
			$this->add_action( 'update_option_jetpack_active_modules', 'listenForJetpackOption', 10, 3 );
			//prepare for the login part
			$isJetpackSSO = Auth_API::isJetPackSSO();
			$isTML        = Auth_API::isTML();
			if ( ! defined( 'DOING_AJAX' ) && ! $isJetpackSSO && ! $isTML ) {
				/**
				 * hook into wordpress login, can't use authenticate hook as that badly conflict
				 */
				$this->add_action( 'wp_login', 'maybeShowOTPLogin', 50, 2 );
				$this->add_action( 'login_form_defenderVerifyOTP', 'defenderVerifyOTP' );
				$this->add_action( 'set_logged_in_cookie', 'storeSessionKey' );
				/**
				 * end
				 */
			} else {
				if ( $isJetpackSSO ) {
					wp_defender()->global['compatibility'][] = __( "You enabled Jetpack WordPress.com login, so Defender will disable the two factors login for avoiding conflict", wp_defender()->domain );
				}
				if ( $isTML ) {
					wp_defender()->global['compatibility'][] = __( "You enabled the plugin Theme My Login, so Defender will disable the two factors login for avoiding conflict", wp_defender()->domain );
				}
			}
			$this->add_filter( 'ms_shortcode_ajax_login', 'm2NoAjax' );
			$this->add_action( 'show_user_profile', 'showUsers2FactorActivation' );
			$this->add_action( 'profile_update', 'saveBackupEmail' );
			$this->add_ajax_action( 'defVerifyOTP', 'verifyConfigOTP' );
			$this->add_ajax_action( 'defDisableOTP', 'disableOTP' );
			$this->add_ajax_action( 'defRetrieveOTP', 'retrieveOTP', false, true );
			if ( Utils::instance()->isActivatedSingle() ) {
				$this->add_filter( 'manage_users_columns', 'alterUsersTable' );
				$this->add_filter( 'manage_users_custom_column', 'alterUsersTableRow', 10, 3 );
			} else {
				$this->add_filter( 'wpmu_users_columns', 'alterUsersTable' );
				$this->add_filter( 'manage_users_custom_column', 'alterUsersTableRow', 10, 3 );
			}
		}
	}

	/**
	 * We have some feature conflict with jetpack, so listen to know when Defender can on
	 *
	 * @param $old_value
	 * @param $value
	 * @param $option
	 */
	public function listenForJetpackOption( $old_value, $value, $option ) {
		$settings = Auth_Settings::instance();
		if ( array_search( 'sso', $value ) !== false ) {
			$settings->markAsConflict( 'jetpack/jetpack.php' );
		} else {
			$settings->markAsUnConflict( 'jetpack/jetpack.php' );
		}
	}

	/**
	 * Stop ajax login on membership 2
	 * @return bool
	 */
	public function m2NoAjax() {
		return false;
	}

	/**
	 * Return 2 factor auth status
	 *
	 * @param $val
	 * @param $column_name
	 * @param $user_id
	 *
	 * @return string
	 */
	public function alterUsersTableRow( $val, $column_name, $user_id ) {
		if ( $column_name != 'defAuth' ) {
			return $val;
		}

		if ( Auth_API::isUserEnableOTP( $user_id ) ) {
			return '<span class="def-oval oval-green"></span>';
		}

		return '<span class="def-oval"></span>';
	}

	/**
	 * Add the auth column inside users on single site
	 *
	 * @param $columns
	 *
	 * @return mixed
	 *
	 */
	public function alterUsersTable( $columns ) {
		$columns = array_slice( $columns, 0, count( $columns ) - 1 ) + array(
				'defAuth' => __( "Two Factor", wp_defender()->domain )
			) + array_slice( $columns, count( $columns ) - 1 );

		return $columns;
	}

	/**
	 * Generate an email for backup otp
	 */
	public function retrieveOTP() {
		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_get( 'nonce' ), 'defRetrieveOTP' ) ) {
			wp_send_json_error( array() );
		}

		$token = HTTP_Helper::retrieve_get( 'token' );
		$query = new \WP_User_Query( array(
			'meta_key'   => 'defOTPLoginToken',
			'meta_value' => $token
		) );
		$res   = $query->get_results();
		if ( empty( $res ) ) {
			//no user
			wp_send_json_error( array(
				'message' => __( "Your token is invalid", wp_defender()->domain )
			) );
		}

		$user = $res[0];
		//create a backup code for this user
		$code = Auth_API::createBackupCode( $user->ID );
		//send email
		$backupEmail = Auth_API::getBackupEmail( $user->ID );
		//send
		wp_mail( $backupEmail, 'Your OTP code', $code );
		wp_send_json_success( array(
			'message' => __( "Your code has been sent to your email.", wp_defender()->domain )
		) );
	}

	/**
	 * disable OTP feature
	 */
	public function disableOTP() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		update_user_meta( get_current_user_id(), 'defenderAuthOn', 0 );
		wp_send_json_success();
	}

	/**
	 * Saving backup email when profile saved
	 *
	 * @param $userID
	 */
	public function saveBackupEmail( $userID ) {
		$email = HTTP_Helper::retrieve_post( 'def_backup_email' );
		if ( $email && get_current_user_id() == $userID ) {
			update_user_meta( $userID, 'defenderAuthEmail', $email );
		}
	}

	/**
	 * An ajax function for verify the OTP user input when configuring the 2 factors
	 */
	public function verifyConfigOTP() {
		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( 'nonce' ), 'defVerifyOTP' ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$otp = HTTP_Helper::retrieve_post( 'otp' );
		$otp = trim( $otp );
		if ( strlen( $otp ) == 0 ) {
			wp_send_json_error( array(
				'message' => __( "Please input a valid OTP code", wp_defender()->domain )
			) );
		}

		$secret = Auth_API::getUserSecret();
		//at this stage, secret should have value, do not need to check
		$res = Auth_API::compare( $secret, $otp );
		if ( $res ) {
			//save it
			update_user_meta( get_current_user_id(), 'defenderAuthOn', 1 );
			wp_send_json_success();
		} else {
			//now need to check if the current user have backup otp
			wp_send_json_error( array(
				'message' => __( "Your OTP code is incorrect. Please try again.", wp_defender()->domain )
			) );
		}
	}

	/**
	 * Show an section inside my profile page for user can activate 2 factor login
	 *
	 * @param $profileuser
	 */
	public function showUsers2FactorActivation( $profileuser ) {
		if ( ! Auth_API::isEnableForCurrentRole() ) {
			return;
		}

		$isOn = get_user_meta( $profileuser->ID, 'defenderAuthOn', true );
		wp_enqueue_style( 'defAuth', wp_defender()->getPluginUrl() . 'app/module/advanced-tools/css/login-admin.css' );
		$secretKey = Auth_API::createSecretForCurrentUser();
		if ( $isOn && $isOn == 1 ) {
			$email = Auth_API::getBackupEmail( $profileuser->ID );
			$this->renderPartial( 'login/enabled', array(
				'email' => $email
			) );
		} else {
			//show the screen
			$this->renderPartial( 'login/disabled', array(
				'secretKey' => $secretKey
			) );
		}
	}

	/**
	 * We will check and show the OTP screen if user signon successfully
	 *
	 * @param $userLogin
	 * @param $user
	 */
	public function maybeShowOTPLogin( $userLogin, $user ) {
		if ( ! Auth_API::isUserEnableOTP( $user->ID ) ) {
			//no enable, then just return
			return;
		}

		//clean up session and auth cookies for preventing
		$token = $this->sessionToken;
		if ( $token ) {
			$sManager = \WP_Session_Tokens::get_instance( $user->ID );
			$sManager->destroy( $token );
		}
		wp_clear_auth_cookie();

		$this->showOTPScreen( $user );
	}

	/**
	 * verify OTP code which user input in order to login
	 */
	public function defenderVerifyOTP() {
		if ( ( $otp = HTTP_Helper::retrieve_post( 'otp', null ) ) != null ) {
			$params = array();
			if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'DefOtpCheck' ) ) {
				$params['error'] = new \WP_Error( 'security_fail', __( "Some error happen", wp_defender()->domain ) );
			}

			$login_token = HTTP_Helper::retrieve_post( 'login_token' );
			$query       = new \WP_User_Query( array(
				'meta_key'   => 'defOTPLoginToken',
				'meta_value' => $login_token,
				'blog_id'    => 0
			) );
			$res         = $query->get_results();
			if ( empty( $res ) ) {
				//no users, redirect to the login page immediatly
				wp_redirect( site_url( 'wp-login.php', 'login_post' ) );
				exit;
			} else {
				$user     = $res[0];
				$secret   = Auth_API::getUserSecret( $user->ID );
				$redirect = HTTP_Helper::retrieve_post( 'redirect_to', admin_url() );
				if ( Auth_API::compare( $secret, $otp ) ) {
					//sign in
					delete_user_meta( $user->ID, 'defOTPLoginToken' );
					wp_set_current_user( $user->ID, $user->user_login );
					wp_set_auth_cookie( $user->ID, true );
					$redirect = apply_filters( 'login_redirect', $redirect, isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '', $user );
					wp_redirect( $redirect );
					exit;
				} else {
					$backupCode = get_user_meta( $user->ID, 'defenderBackupCode', true );
					if ( $backupCode && $backupCode['code'] == $otp && strtotime( '+3 minutes', $backupCode['time'] ) > time() ) {
						delete_user_meta( $user->ID, 'defOTPLoginToken' );
						delete_user_meta( $user->ID, 'defenderBackupCode' );
						wp_set_current_user( $user->ID, $user->user_login );
						wp_set_auth_cookie( $user->ID, true );
						$redirect = apply_filters( 'login_redirect', $redirect, isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '', $user );
						wp_redirect( $redirect );
						exit;
					} else {
						$params['error'] = new \WP_Error( 'opt_fail', __( "Whoops, the passcode you entered was incorrect or expired.", wp_defender()->domain ) );
						$this->showOTPScreen( $user, $params );
					}
				}
			}
		}
	}

	/**
	 * Show the OTP screen
	 *
	 * @param $user
	 * @param $params
	 */
	private function showOTPScreen( $user, $params = array() ) {
		//now show the OTP screen
		$this->add_action( 'login_enqueue_scripts', 'includeAuthStyles' );
		wp_enqueue_script( 'jquery' );
		$params['loginToken']  = $this->createLoginToken( $user );
		$params['redirect_to'] = HTTP_Helper::retrieve_post( 'redirect_to' );
		if ( ! isset( $params['error'] ) ) {
			$params['error'] = null;
		}
		//if this goes here then the current user is ok, need to show the 2 auth
		$this->renderPartial( 'login/otp', $params );
		exit;
	}

	/**
	 * We will empty all auth cookies or session, so should not rely on wp_get_session_token
	 *
	 * @param $cookie
	 */
	public function storeSessionKey( $cookie ) {
		$cookie             = wp_parse_auth_cookie( $cookie, 'logged_in' );
		$this->sessionToken = ! empty( $cookie['token'] ) ? $cookie['token'] : '';
	}

	/**
	 * Create a unique token to retrieve user later
	 *
	 * @param $user
	 *
	 * @return string
	 */
	private function createLoginToken( $user ) {
		$tmp = uniqid();
		// create and store a login token so we can query this user again
		update_user_meta( $user->ID, 'defOTPLoginToken', $tmp );

		return $tmp;
	}

	/**
	 * add css for OTP page
	 */
	public function includeAuthStyles() {
		//enqueue css here
		wp_enqueue_style( 'defAuth', wp_defender()->getPluginUrl() . 'app/module/advanced-tools/css/login.css' );
	}

	/**
	 * Add submit admin page
	 */
	public function adminMenu() {
		$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
		add_submenu_page( 'wp-defender', esc_html__( "Advanced Tools", wp_defender()->domain ), esc_html__( "Advanced Tools", wp_defender()->domain ), $cap, $this->slug, array(
			&$this,
			'actionIndex'
		) );
	}

	/**
	 * a simple router
	 */
	public function actionIndex() {
		$view = HTTP_Helper::retrieve_get( 'view' );
		switch ( $view ) {
			default:
				$this->viewAuth();
				break;
		}
	}

	/**
	 * View the 2 factor main admin page
	 */
	public function viewAuth() {
		$settings = Auth_Settings::instance();
		if ( $settings->enabled == false ) {
			$this->render( 'disabled' );
		} else {
			$this->render( 'main', array(
				'settings' => $settings
			) );
		}
	}

	/**
	 * Enqueue scripts & styles
	 */
	public function scripts() {
		if ( $this->isInPage() || $this->isDashboard() ) {
			\WDEV_Plugin_Ui::load( wp_defender()->getPluginUrl() . 'shared-ui/' );
			wp_enqueue_script( 'defender' );
			wp_enqueue_style( 'defender' );
			wp_enqueue_script( 'adtools', wp_defender()->getPluginUrl() . 'app/module/advanced-tools/js/scripts.js' );
		}
	}

	/**
	 * Saving settings in admin area
	 */
	public function saveSettings() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'saveAdvancedSettings' ) ) {
			return;
		}

		$data = $_POST;
		if ( ! isset( $data['userRoles'] ) ) {
			$data['userRoles'] = array();
		}
		$setting = Auth_Settings::instance();
		$setting->import( $data );
		$setting->save();

		$res           = array(
			'message' => __( "Your settings have been updated.", wp_defender()->domain )
		);
		$res['reload'] = 1;

		wp_send_json_success( $res );
	}
}