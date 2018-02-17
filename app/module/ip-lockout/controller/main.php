<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\IP_Lockout\Controller;

use Hammer\Helper\HTTP_Helper;
use Hammer\Helper\Log_Helper;
use Hammer\Helper\WP_Helper;
use WP_Defender\Behavior\Utils;
use WP_Defender\Controller;
use WP_Defender\Module\Audit\Component\Audit_API;
use WP_Defender\Module\IP_Lockout\Behavior\IP_Lockout;
use WP_Defender\Module\IP_Lockout\Component\Login_Protection_Api;
use WP_Defender\Module\IP_Lockout\Component\Logs_Table;
use WP_Defender\Module\IP_Lockout\Model\IP_Model;
use WP_Defender\Module\IP_Lockout\Model\IP_Model_Legacy;
use WP_Defender\Module\IP_Lockout\Model\Log_Model;
use WP_Defender\Module\IP_Lockout\Model\Log_Model_Legacy;
use WP_Defender\Module\IP_Lockout\Model\Settings;
use WP_Defender\Vendor\Email_Search;

class Main extends Controller {
	protected $slug = 'wdf-ip-lockout';
	public $layout = 'layout';
	public $email_search;

	/**
	 * @return array
	 */
	public function behaviors() {
		$behaviors = array(
			'utils' => '\WP_Defender\Behavior\Utils',
		);
		if ( wp_defender()->isFree == false ) {
			$behaviors['pro'] = '\WP_Defender\Module\IP_Lockout\Behavior\Pro\Reporting';
		}

		return $behaviors;
	}

	public function __construct() {
		$this->maybeLockouts();

		if ( $this->is_network_activate( wp_defender()->plugin_slug ) ) {
			$this->add_action( 'network_admin_menu', 'adminMenu' );
		} else {
			$this->add_action( 'admin_menu', 'adminMenu' );
		}

		if ( $this->isInPage() || $this->isDashboard() ) {
			$this->add_action( 'defender_enqueue_assets', 'scripts', 11 );
		}

		$this->maybeExport();

		$this->add_ajax_action( 'saveLockoutSettings', 'saveLockoutSettings' );
		$this->add_ajax_action( 'wd_import_ips', 'importBWIPs' );
		$this->add_ajax_action( 'lockoutLoadLogs', 'lockoutLoadLogs' );
		$this->add_ajax_action( 'lockoutIPAction', 'lockoutIPAction' );
		$this->add_ajax_action( 'lockoutEmptyLogs', 'lockoutEmptyLogs' );
		$this->add_ajax_action( 'lockoutSummaryData', 'lockoutSummaryData' );
		$this->add_ajax_action( 'migrateData', 'movingDataToTable' );

		$this->handleIpAction();
		$this->handleUserSearch();
	}

	public function lockoutSummaryData() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( 'nonce' ), 'lockoutSummaryData' ) ) {
			return;
		}

		$lockouts = Log_Model::findAll( array(
			'type' => array(
				Log_Model::LOCKOUT_404,
				Log_Model::AUTH_LOCK
			),
			'date' => array(
				'compare' => '>=',
				'value'   => strtotime( '-30 days', current_time( 'timestamp' ) )
			)
		), 'id', 'DESC' );

		if ( count( $lockouts ) == 0 ) {
			$data = array(
				'lastLockout'          => __( "Never", wp_defender()->domain ),
				'lockoutToday'         => 0,
				'lockoutThisMonth'     => 0,
				'loginLockoutThisWeek' => 0,
				'lockout404ThisWeek'   => 0,
			);

			wp_send_json_success( $data );
		}

		//init params
		$lastLockout          = '';
		$lockoutToday         = 0;
		$lockoutThisMonth     = count( $lockouts );
		$loginLockoutThisWeek = 0;
		$lockout404ThisWeek   = 0;
		//time
		$todayMidnight = strtotime( '-24 hours', current_time( 'timestamp' ) );
		$firstThisWeek = strtotime( '-7 days', current_time( 'timestamp' ) );
		foreach ( $lockouts as $k => $log ) {
			//the other as DESC, so first will be last lockout
			if ( $k == 0 ) {
				$lastLockout = $this->formatDateTime( date( 'Y-m-d H:i:s', $log->date ) );
			}

			if ( $log->date > $todayMidnight ) {
				$lockoutToday ++;
			}

			if ( $log->type == Log_Model::AUTH_LOCK && $log->date > $firstThisWeek ) {
				$loginLockoutThisWeek ++;
			} elseif ( $log->type == Log_Model::LOCKOUT_404 && $log->date > $firstThisWeek ) {
				$lockout404ThisWeek ++;
			}
		}

		$data = array(
			'lastLockout'          => $lastLockout,
			'lockoutToday'         => $lockoutToday,
			'lockoutThisMonth'     => $lockoutThisMonth,
			'loginLockoutThisWeek' => $loginLockoutThisWeek,
			'lockout404ThisWeek'   => $lockout404ThisWeek,
		);

		wp_send_json_success( $data );
	}

	public function lockoutEmptyLogs() {
		if ( ! $this->checkPermission() ) {
			return;
		}
		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( 'nonce' ), 'lockoutEmptyLogs' ) ) {
			return;
		}

		$perPage = 500;
		$count   = Log_Model::deleteAll( array(), '0,' . $perPage );
		if ( $count == 0 ) {
			wp_send_json_success( array(
				'message' => __( "Your logs have been successfully deleted.", wp_defender()->domain )
			) );
		}

		wp_send_json_error( array() );
	}

	/**
	 * Ajax to add an IP to blacklist or whitelist
	 */
	public function lockoutIPAction() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( 'nonce' ), 'lockoutIPAction' ) ) {
			return;
		}

		$id   = HTTP_Helper::retrieve_post( 'id' );
		$ip   = HTTP_Helper::retrieve_post( 'ip' );
		$type = HTTP_Helper::retrieve_post( 'type' );
		if ( $id ) {
			$log = Log_Model::findByID( $id );
			$ip  = $log->ip;
		}
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			if ( $type == 'unwhitelist' || $type == 'unblacklist' ) {
				$type = substr( $type, 2 );
				Settings::instance()->removeIpFromList( $ip, $type );
			} else {
				Settings::instance()->addIpToList( $ip, $type );
			}

			if ( isset( $log ) && is_object( $log ) ) {
				wp_send_json_success( array(
					'message' => Login_Protection_Api::getLogsActionsText( $log )
				) );
			} else {
				wp_send_json_success( array(
					'message' => sprintf( __( "IP %s has been added to your blacklist. You can control your blacklist in <a href=\"%s\">IP Lockouts.</a>", wp_defender()->domain ), $ip, network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blacklist' ) )
				) );
			}
		} else {
			wp_send_json_error( array(
				'message' => __( "No record found", wp_defender()->domain )
			) );
		}
	}

	/**
	 * Determine if an ip get lockout or not
	 */
	public function maybeLockouts() {
		$settings = Settings::instance();
		$isTest   = HTTP_Helper::retrieve_get( 'def-lockout-demo', false ) == 1;
		if ( $isTest ) {
			$message = null;
			$type    = HTTP_Helper::retrieve_get( 'type' );
			switch ( $type ) {
				case 'login':
					$message = $settings->login_protection_lockout_message;
					break;
				case '404':
					$message = $settings->detect_404_lockout_message;
					break;
				case 'blacklist':
					$message = $settings->ip_lockout_message;
					break;
				default:
					$message = __( "Demo", wp_defender()->domain );
			}
			$this->renderPartial( 'locked', array(
				'message' => $message
			) );
			die;
		}

		$ip = $this->getUserIp();
		if ( $settings->isWhitelist( $ip ) ) {
			return;
		} elseif ( $settings->isBlacklist( $ip ) ) {
			$this->renderPartial( 'locked', array(
				'message' => $settings->ip_lockout_message
			) );
			die;
		} else {
			if ( is_user_logged_in() ) {
				//if current user can logged in, and no blacklisted we don't need to check the ip
				return;
			}

			$model = IP_Model::findOne( array(
				'ip' => $ip
			) );
			if ( is_object( $model ) && $model->is_locked() ) {
				header( 'HTTP/1.0 403 Forbidden' );
				header( 'Cache-Control: private' );
				$this->renderPartial( 'locked', array(
					'message' => $model->lockout_message
				) );
				die;
			}
		}
	}

	/**
	 * Prepare for Email_Search component
	 */
	private function handleUserSearch() {
		$view                         = HTTP_Helper::retrieve_get( 'view' );
		$id                           = HTTP_Helper::retrieve_post( 'id' );
		$this->email_search           = new Email_Search();
		$this->email_search->settings = Settings::instance();
		if ( $view == 'notification' || ( defined( 'DOING_AJAX' ) && $id == 'lockout-notification' ) ) {
			$this->email_search->eId = 'lockout-notification';
			$this->email_search->add_hooks();
		} elseif ( $view == 'reporting' || ( defined( 'DOING_AJAX' ) && $id == 'lockout-report' ) ) {
			$this->email_search->eId       = 'lockout-report';
			$this->email_search->attribute = 'report_receipts';
			$this->email_search->add_hooks();
		}
	}

	public function lockoutLoadLogs() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		$table = new Logs_Table();
		$table->prepare_items();
		ob_start();
		$table->display();
		$content = ob_get_clean();
		wp_send_json_success( array(
			'html' => $content
		) );
	}

	/**
	 * Handle the logic here
	 */
	private function handleIpAction() {
		if ( get_site_option( 'defenderLockoutNeedUpdateLog' ) == 1 ) {
			//we are migratng, so no record
			return;
		}

		if ( ! Login_Protection_Api::checkIfTableExists() ) {
			//no table logs, omething happen
			return;
		}

		$ip       = $this->getUserIp();
		$settings = Settings::instance();
		if ( $settings->report && $this->hasMethod( 'lockoutReportCron' ) ) {
			//report
			$this->add_action( 'lockoutReportCron', 'lockoutReportCron' );
		}

		//cron for cleanup
		$nextCleanup = wp_next_scheduled( 'cleanUpOldLog' );
		if ( $nextCleanup === false || $nextCleanup > strtotime( '+90 minutes' ) ) {
			wp_clear_scheduled_hook( 'cleanUpOldLog' );
			wp_schedule_event( time(), 'hourly', 'cleanUpOldLog' );
		}

		$this->add_action( 'cleanUpOldLog', 'cleanUpOldLog' );

		if ( $settings->isWhitelist( $ip ) ) {
			return;
		}

		$arr = apply_filters( 'ip_lockout_default_whitelist_ip', array(
			'192.241.148.185',
			'104.236.132.222',
			'192.241.140.159',
			'192.241.228.89',
			'198.199.88.192',
			'54.197.28.242',
			'54.221.174.186',
			'54.236.233.244',
			array_key_exists( 'SERVER_ADDR', $_SERVER ) ? $_SERVER['SERVER_ADDR'] : ( isset( $_SERVER['LOCAL_ADDR'] ) ? $_SERVER['LOCAL_ADDR'] : null )
		) );

		if ( in_array( $ip, $arr ) ) {
			return;
		}
		//now check if this from google
		if ( Login_Protection_Api::isGoogleUA() && Login_Protection_Api::isGoogleIP( $ip ) ) {
			return;
		}

		if ( $settings->login_protection ) {
			$this->add_action( 'wp_login_failed', 'recordFailLogin', 9999 );
			$this->add_filter( 'authenticate', 'showAttemptLeft', 9999, 3 );
			$this->add_action( 'wp_login', 'clearAttemptStats', 10, 2 );
		}

		if ( $settings->detect_404 ) {
			$this->add_action( 'template_redirect', 'record404' );
		}

		$this->add_action( 'wd_lockout_trigger', 'updateIpStats' );
		//sending email
		if ( $settings->login_lockout_notification ) {
			$this->add_action( 'wd_login_lockout', 'lockoutLoginNotification', 10, 3 );
		}
		if ( $settings->ip_lockout_notification ) {
			$this->add_action( 'wd_404_lockout', 'lockout404Notification', 10, 2 );
		}
	}

	/**
	 * cron for delete old log
	 */
	public function cleanUpOldLog() {
		$timestamp = Utils::instance()->localToUtc( apply_filters( 'ip_lockout_logs_store_backward', '-' . Settings::instance()->storage_days . ' days' ) );
		Log_Model::deleteAll( array(
			'date' => array(
				'compare' => '<=',
				'value'   => $timestamp
			),
		), '0,1000' );
	}

	/**
	 * sending email when any lockout triggerd
	 *
	 * @param $model
	 * @param $uri
	 */
	public function lockout404Notification( $model, $uri ) {
		$settings = Settings::instance();
		if ( ! Login_Protection_Api::maybeSendNotification( '404', $model, $settings ) ) {
			return;
		}
		foreach ( $settings->receipts as $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( is_object( $user ) ) {
				$content        = $this->renderPartial( 'emails/404-lockout', array(
					'admin' => $user->display_name,
					'ip'    => $model->ip,
					'uri'   => $uri
				), false );
				$no_reply_email = "noreply@" . parse_url( get_site_url(), PHP_URL_HOST );
				$no_reply_email = apply_filters( 'wd_lockout_noreply_email', $no_reply_email );
				$headers        = array(
					'From: Defender <' . $no_reply_email . '>',
					'Content-Type: text/html; charset=UTF-8'
				);
				wp_mail( $user->user_email, sprintf( __( "404 lockout alert for %s", wp_defender()->domain ), network_site_url() ), $content, $headers );
			}
		}
	}

	/**
	 * @param IP_Model $model
	 */
	public function lockoutLoginNotification( IP_Model $model, $force, $blacklist ) {
		$settings = Settings::instance();
		if ( ! Login_Protection_Api::maybeSendNotification( 'login', $model, $settings ) ) {
			return;
		}
		foreach ( $settings->receipts as $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( is_object( $user ) ) {
				$view           = ( $force && $blacklist ) ? 'emails/login-username-ban' : 'emails/login-lockout';
				$content        = $this->renderPartial( $view, array(
					'admin' => $user->display_name,
					'ip'    => $model->ip,
				), false );
				$no_reply_email = "noreply@" . parse_url( get_site_url(), PHP_URL_HOST );
				$no_reply_email = apply_filters( 'wd_lockout_noreply_email', $no_reply_email );
				$headers        = array(
					'From: Defender <' . $no_reply_email . '>',
					'Content-Type: text/html; charset=UTF-8'
				);
				wp_mail( $user->user_email, sprintf( __( "Login lockout alert for %s", wp_defender()->domain ), network_site_url() ), $content, $headers );
			}
		}
	}

	/**
	 * After each log recorded, we will check if the threshold is met for a lockout
	 *
	 * @param Log_Model $log
	 */
	public function updateIpStats( Log_Model $log ) {
		if ( $log->type == Log_Model::AUTH_FAIL ) {
			Login_Protection_Api::maybeLock( $log );
		} elseif ( $log->type == Log_Model::ERROR_404 ) {
			Login_Protection_Api::maybe404Lock( $log );
		}
	}

	/**
	 * Listen to 404 request and record it
	 */
	public function record404() {
		if ( is_404() ) {
			$settings = Settings::instance();
			if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
				//we wont track 404 error if user can login and not subscriber
				return;
			}

			if ( $settings->detect_404_logged == false && is_user_logged_in() ) {
				return;
			}

			$uri    = $_SERVER['REQUEST_URI'];
			$absUrl = parse_url( get_site_url(), PHP_URL_PATH );
			if ( strpos( $uri, $absUrl ) === 0 ) {
				$uri = substr( $uri, strlen( $absUrl ) );
			}
			$uri = rtrim( $uri, '/' );
			if ( in_array( $uri, $settings->get404Whitelist() ) ) {
				//it is white list, just return
				return;
			}

			$ext               = pathinfo( $uri, PATHINFO_EXTENSION );
			$ext               = trim( $ext );
			$model             = new Log_Model();
			$model->ip         = $this->getUserIp();
			$model->user_agent = $_SERVER['HTTP_USER_AGENT'];
			$model->log        = esc_url( $uri );
			$model->date       = time();
			if ( strlen( $ext ) > 0 && in_array( $ext, $settings->get404Ignorelist() ) ) {
				$model->type = Log_Model::ERROR_404_IGNORE;
			} else {
				$model->type = '404_error';
			}
			$model->save();

			//need to check if this is css,js or images 404 from missig link from a page
			$ref = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : "";
			if ( $ref && parse_url( $ref, PHP_URL_SCHEME ) . '://' . parse_url( $ref, PHP_URL_HOST ) == site_url() ) {
				//the only variable we allow is ver, bydefault of wordpress
				$args = parse_url( $uri, PHP_URL_QUERY );
				if ( ! empty( $args ) ) {
					//validate it
					if ( isset( $args['ver'] ) && is_numeric( $args['ver'] ) ) {
						unset( $args['ver'] );
					}
				}
				if ( count( $args ) == 0 ) {
					//check the extension is js, css, or image type
					$exts = apply_filters( 'wd_allow_ref_extensions', array(
						'js',
						'css',
						'jpg',
						'png',
						'gif'
					) );
					$ext  = pathinfo( $uri, PATHINFO_EXTENSION );
					$ext  = strtolower( $ext );
					if ( in_array( $ext, $exts ) ) {
						//log but no lock
						return;
					}
				}
			}

			do_action( 'wd_lockout_trigger', $model );
		}
	}

	/**
	 * @param $username
	 */
	public function recordFailLogin( $username ) {
		$model             = new Log_Model();
		$model->ip         = $this->getUserIp();
		$model->user_agent = $_SERVER['HTTP_USER_AGENT'];
		$model->log        = sprintf( esc_html__( "Failed login attempt with username %s", wp_defender()->domain ), $username );
		$model->date       = time();
		$model->type       = 'auth_fail';
		$model->tried      = $username;
		$model->save();

		$settings         = Settings::instance();
		$username         = strtolower( $username );
		$unameBlacklisted = $settings->getUsernameBlacklist();
		if ( in_array( $username, $unameBlacklisted ) ) {
			Login_Protection_Api::maybeLock( $model, true, true );

			return;
		}

		do_action( 'wd_lockout_trigger', $model );
	}

	/**
	 * When user get login successfully, we will reset the attempt count
	 */
	public function clearAttemptStats( $user_login, $user = '' ) {
		$ip    = $this->getUserIp();
		$model = IP_Model::findOne( array(
			'ip' => $ip
		) );
		if ( is_object( $model ) ) {
			$model->attempt   = 1;
			$model->lock_time = current_time( 'timestamp' );
			$model->save();
		}
	}

	/**
	 * @param $user
	 *
	 * @return mixed
	 */
	public function showAttemptLeft( $user, $username, $password ) {
		if ( is_wp_error( $user ) && $_SERVER['REQUEST_METHOD'] == 'POST' && ! in_array( $user->get_error_code(), array(
				'empty_username',
				'empty_password'
			) )
		) {
			$ip    = $this->getUserIp();
			$model = IP_Model::findOne( array(
				'ip' => $ip
			) );
			if ( is_object( $model ) ) {
				if ( $model->is_locked() ) {
					//redirect
					wp_redirect( get_site_url() );
					exit;
				}
				$settings = Settings::instance();
				$attempt  = $model->attempt + 1;
				if ( $settings->login_protection_login_attempt - $attempt == 0 ) {
					$user->add( 'def_warning', $settings->login_protection_lockout_message );
				} else {
					$unameBlacklisted = $settings->getUsernameBlacklist();
					if ( in_array( $username, $unameBlacklisted ) ) {
						$user->add( 'def_warning', esc_html__( "You have been locked out by the administrator for attempting to login with a banned username", wp_defender()->domain ) );
					} else {
						$user->add( 'def_warning', sprintf( esc_html__( "%d login attempts remaining", wp_defender()->domain ), $settings->login_protection_login_attempt - $attempt ) );
					}
				}
			} else {
				$settings         = Settings::instance();
				$unameBlacklisted = $settings->getUsernameBlacklist();
				if ( in_array( $username, $unameBlacklisted ) ) {
					$user->add( 'def_warning', esc_html__( "You have been locked out by the administrator for attempting to login with a banned username", wp_defender()->domain ) );
				} else {
					//becase authenticate hook fire before wp_login_fail, so at this state, we dont have any data, we will decrease by one
					$user->add( 'def_warning', sprintf( esc_html__( "%d login attempts remaining", wp_defender()->domain ), $settings->login_protection_login_attempt - 1 ) );
				}
			}
		}

		return $user;
	}

	/**
	 * listener to process export Ips request
	 */
	public function maybeExport() {
		if ( HTTP_Helper::retrieve_get( 'page' ) == 'wdf-ip-lockout' && HTTP_Helper::retrieve_get( 'view' ) == 'export' ) {
			if ( ! $this->checkPermission() ) {
				return;
			}

			if ( ! wp_verify_nonce( HTTP_Helper::retrieve_get( '_wpnonce' ), 'defipexport' ) ) {
				return;
			}
			$setting = Settings::instance();
			$data    = array();
			foreach ( $setting->getIpBlacklist() as $ip ) {
				$data[] = array(
					'ip'   => $ip,
					'type' => 'blacklist'
				);
			}
			foreach ( $setting->getIpWhitelist() as $ip ) {
				$data[] = array(
					'ip'   => $ip,
					'type' => 'whitelist'
				);
			}
			$fp = fopen( 'php://memory', 'w' );
			foreach ( $data as $fields ) {
				fputcsv( $fp, $fields );
			}
			$filename = 'wdf-ips-export-' . date( 'ymdHis' ) . '.csv';
			fseek( $fp, 0 );
			header( 'Content-Type: text/csv' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
			// make php send the generated csv lines to the browser
			fpassthru( $fp );
			exit();
		}
	}

	/**
	 * Ajax for saving settings
	 */
	public function saveLockoutSettings() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'saveLockoutSettings' ) ) {
			return;
		}

		$settings     = Settings::instance();
		$lastSettings = clone $settings;
		$data         = wp_unslash( $_POST );
		//check and sanitize before save
		$textarea = array(
			'username_blacklist',
			'login_protection_lockout_message',
			'detect_404_ignored_filetypes',
			'detect_404_lockout_message',
			'detect_404_whitelist',
			'ip_lockout_message',
			'ip_blacklist',
			'ip_whitelist',
			'ip_lockout_message'
		);
		foreach ( $data as $k => $v ) {
			if ( in_array( $k, $textarea ) ) {
				$data[ $k ] = wp_kses_post( $v );
			} else {
				$data[ $k ] = sanitize_text_field( $v );
			}
		}
		$settings->import( $data );
		if ( $settings->validate() ) {
			$settings->save();
			$faultIps = WP_Helper::getArrayCache()->get( 'faultIps', array() );
			$isBLSelf = WP_Helper::getArrayCache()->get( 'isBlacklistSelf', false );
			if ( $faultIps || $isBLSelf ) {
				$res = array(
					'message' => sprintf( __( "Your settings have been updated, however some IPs were removed because invalid format, or you blacklist yourself", wp_defender()->domain ), implode( ',', $faultIps ) ),
					'reload'  => 1
				);
			} else {
				$res = array( 'message' => __( "Your settings have been updated.", wp_defender()->domain ), );
			}
			if ( ( $lastSettings->login_protection != $settings->login_protection )
			     || ( $lastSettings->detect_404 != $settings->detect_404 )
			) {
				if ( isset( $data['login_protection'] ) ) {
					if ( $data['login_protection'] == 1 ) {
						$status = __( "Login Protection has been activated.", wp_defender()->domain );
					} else {
						$status = __( "Login Protection has been deactivated.", wp_defender()->domain );
					}
				}
				if ( isset( $data['detect_404'] ) ) {
					if ( $data['detect_404'] == 1 ) {
						$status = __( "404 Detection has been activated.", wp_defender()->domain );
					} else {
						$status = __( "404 Detection has been deactivated.", wp_defender()->domain );
					}
				}
				//mean enabled or disabled, reload
				$res['reload'] = 1;
				if ( isset( $status ) && strlen( $status ) ) {
					$res['message'] = $status;
				}
			}
			if ( $this->hasMethod( 'scheduleReport' ) ) {
				$this->scheduleReport();
			}
			Utils::instance()->submitStatsToDev();
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( array(
				'message' => implode( '<br/>', $settings->getErrors() )
			) );
		}
	}

	/**
	 * Add submit admin page
	 */
	public function adminMenu() {
		$cap    = is_multisite() ? 'manage_network_options' : 'manage_options';
		$action = "actionIndex";
		if ( get_site_option( 'defenderLockoutNeedUpdateLog' ) == 1 ) {
			$action = "actionMigration";
		}
		add_submenu_page( 'wp-defender', esc_html__( "IP Lockouts", wp_defender()->domain ), esc_html__( "IP Lockouts", wp_defender()->domain ), $cap, $this->slug, array(
			&$this,
			$action
		) );
	}

	/**
	 * Ajax function for handling importing IPs
	 */
	public function importBWIPs() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		$id = HTTP_Helper::retrieve_post( 'file' );
		if ( ! is_object( get_post( $id ) ) ) {
			wp_send_json_error( array(
				'message' => __( "Your file is invalid!", wp_defender()->domain )
			) );
		}
		$file = get_attached_file( $id );
		if ( ! is_file( $file ) ) {
			wp_send_json_error( array(
				'message' => __( "Your file is invalid!", wp_defender()->domain )
			) );
		}

		if ( ! ( $data = Login_Protection_Api::verifyImportFile( $file ) ) ) {
			wp_send_json_error( array(
				'message' => __( "Your file content is invalid!", wp_defender()->domain )
			) );
		}
		$settings = Settings::instance();
		//all good, start to import
		foreach ( $data as $line ) {
			$settings->addIpToList( $line[0], $line[1] );
		}
		wp_send_json_success( array(
			'message' => __( "Your whitelist/blacklist has been successfully imported.", wp_defender()->domain ),
			'reload'  => 1
		) );
	}

	/**
	 * queue scripts
	 */
	public function scripts() {
		if ( $this->isInPage() ) {
			\WDEV_Plugin_Ui::load( wp_defender()->getPluginUrl() . 'shared-ui/' );
			if ( HTTP_Helper::retrieve_get( 'view' ) == 'blacklist' ) {
				remove_filter( 'admin_body_class', array( 'WDEV_Plugin_Ui', 'admin_body_class' ) );
			}
			wp_enqueue_script( 'defender' );
			wp_enqueue_style( 'defender' );
			wp_enqueue_script( 'iplockout', wp_defender()->getPluginUrl() . 'app/module/ip-lockout/js/script.js' );
		} else {
			wp_enqueue_script( 'iplockout', wp_defender()->getPluginUrl() . 'app/module/ip-lockout/js/script.js' );
		}
	}

	/**
	 * Internal route
	 */
	public function actionIndex() {
		$view = HTTP_Helper::retrieve_get( 'view' );
		switch ( $view ) {
			case 'login':
			default:
				$this->_renderLoginProtection();
				break;
			case '404':
				$this->_render404Protection();
				break;
			case 'blacklist':
				$this->_renderBlacklist();
				break;
			case 'logs':
				$this->_renderLogs();
				break;
			case 'notification':
				$this->_renderNotification();
				break;
			case 'settings':
				$this->_renderSettings();
				break;
			case 'reporting':
				$this->_renderReport();
				break;
		}
	}

	/**
	 * Show the updating screen
	 */
	public function actionMigration() {
		$this->layout = null;
		$this->render( 'migration' );
	}

	private function _renderSettings() {
		$this->render( 'settings', array(
			'settings' => Settings::instance()
		) );
	}

	private function _renderLoginProtection() {
		if ( Settings::instance()->login_protection ) {
			$this->render( 'login-lockouts/enabled', array(
				'settings' => Settings::instance()
			) );
		} else {
			$this->render( 'login-lockouts/disabled' );
		}
	}

	private function _render404Protection() {
		if ( Settings::instance()->detect_404 ) {
			$this->render( 'detect-404/enabled', array(
				'settings' => Settings::instance()
			) );
		} else {
			$this->render( 'detect-404/disabled' );
		}
	}

	private function _renderBlacklist() {
		wp_enqueue_media();
		$this->render( 'blacklist/enabled', array(
			'settings' => Settings::instance()
		) );
	}

	private function _renderLogs() {
		$this->render( 'logging/enabled' );
	}

	private function _renderNotification() {
		$this->email_search->add_script();
		$this->render( 'notification/enabled', array(
			'settings'     => Settings::instance(),
			'email_search' => $this->email_search
		) );
	}

	private function _renderReport() {
		$this->email_search->add_script();
		$view = wp_defender()->isFree ? 'notification/report-free' : 'notification/report';
		$this->render( $view, array(
			'settings'     => Settings::instance(),
			'email_search' => $this->email_search
		) );
	}

	public function movingDataToTable() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		$totalItems = get_site_option( 'defenderLogsTotal' );
		$resetFlag  = get_site_option( 'defenderMigrateNeedReset' );
		if ( $totalItems !== false && $resetFlag === false ) {
			//reset it
			delete_site_option( 'defenderLogsTotal' );
			delete_site_option( 'defenderLogsMovedCount' );
			update_site_option( 'defenderMigrateNeedReset', 1 );
			wp_send_json_error( array(
				'progress' => 0
			) );
		}
		$params = array(
			'date' => array(
				'compare' => '>=',
				'value'   => strtotime( '-30 days' )
			)
		);
		if ( $totalItems === false ) {
			//get the total
			$totalLogs = Log_Model_Legacy::count( $params );
			$totalsIPs = IP_Model_Legacy::count();
			//get the 200 items and import each time
			update_site_option( 'defenderLogsTotal', $totalLogs + $totalsIPs );
			//prevent timeout so we end here at the first time
			wp_send_json_error( array(
				'progress' => 0
			) );
		}

		$logs          = Log_Model_Legacy::findAll( $params, 'id', 'DESC', '0,50' );
		$logs          = array_filter( $logs );
		$ips           = IP_Model_Legacy::findAll( array(), 'id', 'DESC', '0,50' );
		$ips           = array_filter( $ips );
		$internalCount = 0;
		if ( is_array( $logs ) && count( $logs ) ) {
			foreach ( $logs as $item ) {
				$model = new Log_Model();
				$data  = $item->export();
				unset( $data['id'] );
				$model->import( $data );
				$model->save();
				$item->delete();
			}
			$internalCount += count( $logs );
		}

		if ( is_array( $ips ) && count( $ips ) ) {
			foreach ( $ips as $item ) {
				$model = new IP_Model();
				$data  = $item->export();
				unset( $data['id'] );
				$model->import( $data );
				$model->save();
				$item->delete();
			}

			$internalCount += count( $ips );
		}

		if ( empty( $logs ) && empty( $ips ) ) {
			//all moved
			delete_site_option( 'defenderLogsTotal' );
			delete_site_option( 'defenderLogsMovedCount' );
			delete_site_option( 'defenderLockoutNeedUpdateLog' );
			wp_send_json_success( array(
				'message' => __( "Thanks for your patience. All set.", wp_defender()->domain )
			) );
		}

		$count = get_site_option( 'defenderLogsMovedCount', 0 );
		$count += $internalCount;
		update_site_option( 'defenderLogsMovedCount', $count );
		wp_send_json_error( array(
			'progress' => round( ( $count / $totalItems ) * 100, 2 ) > 100 ? 100 : round( ( $count / $totalItems ) * 100, 2 )
		) );
	}
}