<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Controller;

use Hammer\Helper\HTTP_Helper;
use Hammer\Helper\WP_Helper;
use WP_Defender\Behavior\Utils;
use WP_Defender\Controller;
use WP_Defender\Module\Audit\Component\Audit_API;
use WP_Defender\Module\IP_Lockout\Component\Login_Protection_Api;
use WP_Defender\Module\Scan\Component\Scan_Api;
use WP_Defender\Module\Scan\Model\Result_Item;
use WP_Defender\Module\Scan\Model\Settings;

class Dashboard extends Controller {
	protected $slug = 'wp-defender';

	public function __construct() {
		if ( $this->is_network_activate( wp_defender()->plugin_slug ) ) {
			$this->add_action( 'network_admin_menu', 'admin_menu' );
		} else {
			$this->add_action( 'admin_menu', 'admin_menu' );
		}

		if ( $this->isInPage() ) {
			$this->add_action( 'defender_enqueue_assets', 'scripts', 11 );
		}

		$this->add_ajax_action( 'blacklistWidgetStatus', 'blacklistWidgetStatus' );
		$this->add_ajax_action( 'toggleBlacklistWidget', 'toggleBlacklistWidget' );
		$this->add_ajax_action( 'activateModule', 'activateModule' );
		$this->add_ajax_action( 'skipActivator', 'skipActivator' );
		$this->add_action( 'defenderSubmitStats', 'defenderSubmitStats' );
		$this->add_filter( 'wdp_register_hub_action', 'addMyEndpoint' );
		add_filter( 'custom_menu_order', '__return_true' );
		$this->add_filter( 'menu_order', 'menuOrder' );
	}

	public function skipActivator() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'skipActivator' ) ) {
			return;
		}
		$cache = WP_Helper::getCache();
		$cache->set( wp_defender()->isFree ? 'wdf_isActivated' : 'isActivated', 1, 0 );
		wp_send_json_success();
	}

	public function menuOrder( $menu_order ) {
		global $submenu;
		if ( isset( $submenu['wp-defender'] ) ) {
			$defender_menu = $submenu['wp-defender'];
			//$defender_menu[6][4] = 'wd-menu-hide';
			$defender_menu[0][0]    = esc_html__( "Dashboard", wp_defender()->domain );
			$defender_menu          = array_values( $defender_menu );
			$submenu['wp-defender'] = $defender_menu;
		}

		global $menu;
		$count     = $this->countTotalIssues();
		$indicator = $count > 0 ? ' <span class="update-plugins wd-issue-indicator-sidebar"><span class="plugin-count">' . $count . '</span></span>' : null;
		foreach ( $menu as $k => $item ) {
			if ( $item[2] == 'wp-defender' ) {
				$menu[ $k ][0] .= $indicator;
			}
		}

		return $menu_order;
	}

	public function defenderSubmitStats() {
		if ( $this->hasMethod( '_submitStatsToDev' ) ) {
			$this->_submitStatsToDev();
		}
	}

	/**
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function addMyEndpoint( $actions ) {
		$actions['defender_new_scan']          = array( &$this, 'new_scan' );
		$actions['defender_schedule_scan']     = array( &$this, 'schedule_scan' );
		$actions['defender_manage_audit_log']  = array( &$this, 'manage_audit_log' );
		$actions['defender_manage_lockout']    = array( &$this, 'manage_lockout' );
		$actions['defender_whitelist_ip']      = array( &$this, 'whitelist_ip' );
		$actions['defender_blacklist_ip']      = array( &$this, 'blacklist_ip' );
		$actions['defender_get_stats']         = array( &$this, 'get_stats' );
		$actions['defender_get_scan_progress'] = array( &$this, 'get_scan_progress' );

		return $actions;
	}

	public function get_scan_progress() {
		$ret = Scan_Api::processActiveScan();
		if ( is_wp_error( $ret ) ) {
			wp_send_json_error( array(
				'message' => $ret->get_error_message()
			) );
		} else {
			$percent = Scan_Api::getScanProgress();
			if ( $ret == true ) {
				$percent = 100;
			}
			wp_send_json_success( array(
				'progress' => $percent
			) );
		}
	}

	/**
	 * @param $params
	 * @param $action
	 */
	public function new_scan( $params, $action ) {
		$ret = Scan_Api::createScan();
		if ( is_wp_error( $ret ) ) {
			wp_send_json_error( array(
				'message' => $ret->get_error_message()
			) );
		}

		wp_send_json_success();
	}

	/**
	 * @param $params
	 * @param $action
	 */
	public function schedule_scan( $params, $action ) {
		$frequency   = $params['frequency'];
		$day         = $params['day'];
		$time        = $params['time'];
		$allowedFreq = array( 1, 7, 30 );
		if ( ! in_array( $frequency, $allowedFreq ) || ! in_array( $day, Utils::instance()->getDaysOfWeek() ) || ! in_array( $time, Utils::instance()->getTimes() ) ) {
			wp_send_json_error();
		}
		$settings            = Settings::instance();
		$settings->frequency = $frequency;
		$settings->day       = $day;
		$settings->time      = $time;

		wp_send_json_success();
	}

	/**
	 * Hub Audit log endpoint
	 *
	 * @param $params
	 * @param $action
	 */
	public function manage_audit_log( $params, $action ) {
		$response = null;
		if ( class_exists( '\WP_Defender\Module\Audit\Model\Settings' ) ) {
			$response = array();
			$settings = \WP_Defender\Module\Audit\Model\Settings::instance();
			if ( $settings->enabled == true ) {
				$settings->enabled   = false;
				$response['enabled'] = false;
			} else {
				$settings->enabled   = true;
				$response['enabled'] = true;
			}
			$settings->save();
		}
		wp_send_json_success( $response );
	}

	/**
	 * Hub Lockouts endpoint
	 *
	 * @param $params
	 * @param $action
	 */
	public function manage_lockout( $params, $action ) {
		$type     = $params['type'];
		$settings = \WP_Defender\Module\IP_Lockout\Model\Settings::instance();
		$response = array();
		if ( $type == 'login' ) {
			if ( $settings->login_protection ) {
				$settings->login_protection = 0;
				$response[ $type ]          = 'disabled';
			} else {
				$settings->login_protection = 1;
				$response[ $type ]          = 'enabled';
			}
			$settings->save();
		} else if ( $type == '404' ) {
			if ( $settings->detect_404 ) {
				$settings->detect_404 = 0;
				$response[ $type ]    = 'disabled';
			} else {
				$settings->detect_404 = 1;
				$response[ $type ]    = 'enabled';
			}
			$settings->save();
		} else {
			$response[ $type ] = 'invalid';
		}
		wp_send_json_success();
	}

	/**
	 * Hub Whitelist IP endpoint
	 *
	 * @param $params
	 * @param $action
	 */
	public function whitelist_ip( $params, $action ) {
		$settings = \WP_Defender\Module\IP_Lockout\Model\Settings::instance();
		$ip       = $params['ip'];
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$settings->removeIpFromList( $ip, 'blacklist' );
			$settings->addIpToList( $ip, 'whitelist' );
		} else {
			wp_send_json_error();
		}
		wp_send_json_success();
	}

	/**
	 * Hub Blacklist IP endpoint
	 *
	 * @param $params
	 * @param $action
	 */
	public function blacklist_ip( $params, $action ) {
		$settings = \WP_Defender\Module\IP_Lockout\Model\Settings::instance();
		$ip       = $params['ip'];
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$settings->removeIpFromList( $ip, 'whitelist' );
			$settings->addIpToList( $ip, 'blacklist' );
		} else {
			wp_send_json_error();
		}
		wp_send_json_success();
	}

	/**
	 * Hub Stats endpoint
	 *
	 * @param $params
	 * @param $action
	 */
	public function get_stats( $params, $action ) {
		$stats = Utils::instance()->generateStats();
		wp_send_json_success(
			array(
				'stats' => $stats
			)
		);
	}

	public function actionIndex() {
		$this->render( 'dashboard' );
	}

	public function blacklistWidgetStatus() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'blacklistWidgetStatus' ) ) {
			return;
		}

		if ( $this->hasMethod( 'pullBlacklistStatus' ) ) {
			$this->pullBlacklistStatus();
		}

		exit;
	}

	public function toggleBlacklistWidget() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'toggleBlacklistWidget' ) ) {
			return;
		}

		if ( $this->hasMethod( 'toggleStatus' ) ) {
			$this->toggleStatus();
		}

		exit;
	}

	/**
	 * @param bool $detail
	 *
	 * @return array|int|null|string
	 */
	public function countTotalIssues( $detail = false ) {
		$hardenerCount = count( \WP_Defender\Module\Hardener\Model\Settings::instance()->issues );
		$scan          = Scan_Api::getLastScan();
		$total         = $hardenerCount;
		$scanCount     = 0;
		if ( is_object( $scan ) ) {
			$scanCount = $scan->countAll( Result_Item::STATUS_ISSUE );

			$total += $scanCount;
		}
		if ( $detail == false ) {
			return $total;
		}

		return array( $hardenerCount, $scanCount );
	}

	/**
	 *
	 */
	public function admin_menu() {
		$cap        = is_multisite() ? 'manage_network_options' : 'manage_options';
		$menu_title = wp_defender()->isFree ? esc_html__( "Defender", wp_defender()->domain ) : esc_html__( "Defender Pro", wp_defender()->domain );
		//$menu_title = sprintf( $menu_title, $indicator );
		add_menu_page( esc_html__( "Defender Pro", wp_defender()->domain ), $menu_title, $cap, 'wp-defender', array(
			&$this,
			'actionIndex'
		), $this->get_menu_icon() );
	}

	/**
	 * Return svg image
	 * @return string
	 */
	private function get_menu_icon() {
		ob_start();
		?>
        <svg width="17px" height="18px" viewBox="10 397 17 18" version="1.1" xmlns="http://www.w3.org/2000/svg"
             xmlns:xlink="http://www.w3.org/1999/xlink">
            <!-- Generator: Sketch 3.8.3 (29802) - http://www.bohemiancoding.com/sketch -->
            <desc>Created with Sketch.</desc>
            <defs></defs>
            <path
                    d="M24.8009393,403.7962 L23.7971393,410.1724 C23.7395393,410.5372 23.5313393,410.8528 23.2229393,411.0532 L18.4001393,413.6428 L13.5767393,411.0532 C13.2683393,410.8528 13.0601393,410.5372 13.0019393,410.1724 L11.9993393,403.7962 L11.6153393,401.3566 C12.5321393,402.9514 14.4893393,405.5518 18.4001393,408.082 C22.3115393,405.5518 24.2675393,402.9514 25.1855393,401.3566 L24.8009393,403.7962 Z M26.5985393,398.0644 C25.7435393,397.87 22.6919393,397.2106 19.9571393,397 L19.9571393,403.4374 L18.4037393,404.5558 L16.8431393,403.4374 L16.8431393,397 C14.1077393,397.2106 11.0561393,397.87 10.2011393,398.0644 C10.0685393,398.0938 9.98213933,398.221 10.0031393,398.3536 L10.8875393,403.969 L11.8913393,410.3446 C12.0071393,411.0796 12.4559393,411.7192 13.1105393,412.0798 L16.8431393,414.1402 L18.4001393,415 L19.9571393,414.1402 L23.6891393,412.0798 C24.3431393,411.7192 24.7925393,411.0796 24.9083393,410.3446 L25.9121393,403.969 L26.7965393,398.3536 C26.8175393,398.221 26.7311393,398.0938 26.5985393,398.0644 L26.5985393,398.0644 Z"
                    id="Defender-Icon" stroke="none" fill="#FFFFFF" fill-rule="evenodd"></path>
        </svg>
		<?php
		$svg = ob_get_clean();

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	public function scripts() {
		\WDEV_Plugin_Ui::load( wp_defender()->getPluginUrl() . 'shared-ui/' );
		wp_enqueue_script( 'defender' );
		$data = array(
			'activator_title'    => __( "QUICK SETUP", wp_defender()->domain ) . '<form method="post" class="skip-activator float-r"><input type="hidden" name="action" value="skipActivator"/>' . wp_nonce_field( 'skipActivator', '_wpnonce', true, false ) . '<button type="submit" class="button button-small button-secondary">' . __( "Skip", wp_defender()->domain ) . '</button></form>',
			'activate_scan'      => __( "Activating File Scanning...", wp_defender()->domain ),
			'activate_audit'     => __( "Activating Audit Module...", wp_defender()->domain ),
			'activate_lockout'   => __( "Activating IP Lockouts Module...", wp_defender()->domain ),
			'activate_blacklist' => __( "Activating Blacklist Monitoring...", wp_defender()->domain )
		);
		wp_enqueue_style( 'defender' );
		wp_localize_script( 'defender', 'dashboard', $data );
	}

	/**
	 * @return array
	 */
	public function behaviors() {
		return array(
			'utils'     => '\WP_Defender\Behavior\Utils',
			'activator' => wp_defender()->isFree ? '\WP_Defender\Behavior\Activator_Free' : '\WP_Defender\Behavior\Activator',
			'hardener'  => '\WP_Defender\Module\Hardener\Behavior\Widget',
			'scan'      => '\WP_Defender\Module\Scan\Behavior\Scan',
			'lockout'   => '\WP_Defender\Module\IP_Lockout\Behavior\Widget',
			'audit'     => wp_defender()->isFree ? '\WP_Defender\Module\Audit\Behavior\Audit_Free' : '\WP_Defender\Module\Audit\Behavior\Audit',
			'blacklist' => wp_defender()->isFree ? '\WP_Defender\Behavior\Blacklist_Free' : '\WP_Defender\Behavior\Blacklist',
			'report'    => wp_defender()->isFree ? '\WP_Defender\Behavior\Report_Free' : '\WP_Defender\Behavior\Report',
			'at'        => '\WP_Defender\Module\Advanced_Tools\Behavior\AT_Widget'
		);
	}
}