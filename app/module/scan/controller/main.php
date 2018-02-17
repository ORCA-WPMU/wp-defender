<?php

namespace WP_Defender\Module\Scan\Controller;

use Hammer\Helper\HTTP_Helper;
use Hammer\Helper\Log_Helper;
use WP_Defender\Module\Scan;
use WP_Defender\Module\Scan\Component\Scan_Api;
use WP_Defender\Module\Scan\Model\Settings;
use WP_Defender\Module\Scan\Model\Result_Item;
use WP_Defender\Vendor\Email_Search;
use WP_Defender\Behavior\Utils;

/**
 * Author: Hoang Ngo
 */
class Main extends \WP_Defender\Controller {
	protected $slug = 'wdf-scan';
	public $layout = 'layout';
	private $email_search;

	/**
	 * @return array
	 */
	public function behaviors() {
		$behaviors = array( 'utils' => '\WP_Defender\Behavior\Utils' );
		if ( wp_defender()->isFree == false ) {
			$behaviors['pro'] = '\WP_Defender\Module\Scan\Behavior\Pro\Reporting';
		}

		return $behaviors;
	}

	/**
	 * Main constructor.
	 */
	public function __construct() {
		if ( $this->is_network_activate( wp_defender()->plugin_slug ) ) {
			$this->add_action( 'network_admin_menu', 'adminMenu' );
		} else {
			$this->add_action( 'admin_menu', 'adminMenu' );
		}

		if ( $this->isInPage() || $this->isDashboard() ) {
			$this->add_action( 'defender_enqueue_assets', 'scripts', 11 );
		}

		/**
		 * ajax actions
		 */
		$this->add_ajax_action( 'startAScan', 'startAScan' );
		$this->add_ajax_action( 'processScan', 'processScan' );
		$this->add_ajax_action( 'ignoreItem', 'ignoreItem' );
		$this->add_ajax_action( 'unIgnoreItem', 'unIgnoreItem' );
		$this->add_ajax_action( 'deleteItem', 'deleteItem' );
		$this->add_ajax_action( 'resolveItem', 'resolveItem' );
		$this->add_ajax_action( 'saveScanSettings', 'saveScanSettings' );
		$this->add_ajax_action( 'scanBulkAction', 'scanBulkAction' );
		$this->add_ajax_action( 'pullSrcFile', 'pullSrcFile' );
		$this->add_ajax_action( 'cancelScan', 'cancelScan' );

		//init receiption
		$this->email_search           = new Email_Search();
		$this->email_search->eId      = 'scan_receipts';
		$this->email_search->settings = Settings::instance();
		$this->email_search->add_hooks();
		//process scan in background
		$this->add_action( 'processScanCron', 'processScanCron' );
		//scan as schedule
		$this->add_action( 'scanReportCron', 'scanReportCron' );
	}

	/**
	 * @return bool|void
	 */
	public function scanReportCron() {
		if ( wp_defender()->isFree ) {
			return;
		}

		$settings       = Settings::instance();
		$lastReportSent = $settings->lastReportSent;
		if ( $lastReportSent == null ) {
			$model = Scan_Api::getLastScan();
			if ( is_object( $model ) ) {
				$lastReportSent           = $model->dateFinished;
				$settings->lastReportSent = $lastReportSent;
				//init
				$settings->save();
			} else {
				//no sent, so just assume last 30 days, as this only for monthly
				$lastReportSent = strtotime( '-31 days', current_time( 'timestamp' ) );
			}
		}

		if ( ! $this->isReportTime( $settings->frequency, $settings->day, $lastReportSent ) ) {
			return false;
		}

		//need to check if we already have a scan in progress
		$activeScan = Scan_Api::getActiveScan();
		if ( ! is_object( $activeScan ) ) {
			$model       = Scan_Api::createScan();
			$model->logs = 'report';
			wp_clear_scheduled_hook( 'processScanCron' );
			wp_schedule_single_event( strtotime( '+1 minutes' ), 'processScanCron' );
		}

	}

	public function cancelScan() {
		if ( ! $this->checkPermission() ) {
			return;
		}
		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'cancelScan' ) ) {
			return;
		}

		$activeScan = Scan_Api::getActiveScan();
		if ( is_object( $activeScan ) ) {
			//remove it and it minions
			$activeScan->delete();
			Scan_Api::flushCache();
			wp_send_json_success( array(
				'url' => network_admin_url( 'admin.php?page=wdf-scan' )
			) );
		}

		wp_send_json_error( array(
			'message' => ''
		) );
	}

	public function pullSrcFile() {
		if ( ! $this->checkPermission() ) {
			return;
		}
		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'pullSrcFile' ) ) {
			return;
		}

		$id    = HTTP_Helper::retrieve_post( 'id' );
		$model = Result_Item::findByID( $id );
		if ( is_object( $model ) ) {
			wp_send_json_success( array(
				'html' => $model->getSrcCode()
			) );
		}
	}

	/**
	 *
	 */
	public function scanBulkAction() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'scanBulkAction' ) ) {
			return;
		}

		$items = HTTP_Helper::retrieve_post( 'items' );
		if ( ! is_array( $items ) ) {
			$items = array();
		}
		$bulk = HTTP_Helper::retrieve_post( 'bulk' );
		switch ( $bulk ) {
			case 'ignore':
				foreach ( $items as $id ) {
					$item = Result_Item::findByID( $id );
					if ( is_object( $item ) ) {
						$item->ignore();
					}
				}
				$this->submitStatsToDev();
				wp_send_json_success( array(
					'message' => _n( "The suspicious file has been successfully ignored.",
						"The suspicious files have been successfully ignored.",
						count( $items ),
						wp_defender()->domain )
				) );
				break;
			case 'unignore':
				foreach ( $items as $id ) {
					$item = Result_Item::findByID( $id );
					if ( is_object( $item ) ) {
						$item->unignore();
					}
				}
				$this->submitStatsToDev();
				wp_send_json_success( array(
					'message' => _n( "The suspicious file has been successfully restored.",
						"The suspicious files have been successfully restored.",
						count( $items ),
						wp_defender()->domain )
				) );
				break;
			case 'delete':
				$ids = array();
				foreach ( $items as $id ) {
					$item = Result_Item::findByID( $id );
					if ( is_object( $item ) ) {
						if ( $item->hasMethod( 'purge' ) && $item->purge() === true ) {
							$ids[] = $id;
						}
					}
				}
				if ( $ids ) {
					$this->submitStatsToDev();
					wp_send_json_success( array(
						'message' => _n( "The suspicious files has been successfully deleted.",
							"The suspicious files have been successfully deleted.",
							count( $items ), wp_defender()->domain )
					) );
				} else {
					wp_send_json_error( array(
						'message' => __( "No item has been deleted", wp_defender()->domain )
					) );
				}
				break;
			case 'resolve':
				$ids = array();
				foreach ( $items as $id ) {
					$item = Result_Item::findByID( $id );
					if ( is_object( $item ) ) {
						if ( $item->hasMethod( 'resolve' ) && $item->resolve() === true ) {
							$ids[] = $id;
						}
					}
				}
				if ( $ids ) {
					$this->submitStatsToDev();
					wp_send_json_success( array(
						'message' => _n( "The suspicious files has been successfully resolved.",
							"The suspicious files have been successfully resolved.",
							count( $items ), wp_defender()->domain )
					) );
				} else {
					wp_send_json_error( array(
						'message' => __( "No item has been resolved", wp_defender()->domain )
					) );
				}
				break;
		}
	}

	/**
	 * Process a scan via cronjob, only use to process in background, not create a new scan
	 */
	public function processScanCron() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX == true ) {
			//we dont process if ajax, this only for active scan
			return;
		}
		//sometime the scan get stuck, queue it first
		wp_schedule_single_event( strtotime( '+1 minutes' ), 'processScanCron' );

		$activeScan = Scan_Api::getActiveScan();
		if ( ! is_object( $activeScan ) ) {
			//no scan created, return
			return;
		}

		$ret = Scan_Api::processActiveScan();

		if ( $ret == true ) {
			//completed
			$this->sendEmailReport();
			$this->submitStatsToDev();
			//scan done, remove the background cron
			wp_clear_scheduled_hook( 'processScanCron' );
		}
	}

	/**
	 * process scan settings
	 */
	public function saveScanSettings() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'saveScanSettings' ) ) {
			return;
		}

		$settings = Settings::instance();
		$data     = $_POST;
		foreach ( $data as $key => $val ) {
			if ( in_array( $key, array( 'email_all_ok', 'email_has_issue' ) ) ) {
				$data[ $key ] = wp_kses_post( $val );
			} else {
				$data[ $key ] = sanitize_text_field( $val );
			}
		}
		$settings->import( $data );
		$settings->email_all_ok    = stripslashes( $settings->email_all_ok );
		$settings->email_has_issue = stripslashes( $settings->email_has_issue );
		$settings->save();
		if ( $this->hasMethod( 'scheduleReportTime' ) ) {
			$this->scheduleReportTime( $settings );
			$this->submitStatsToDev();
		}
		wp_send_json_success( array(
			'message' => __( "Your settings have been updated.", wp_defender()->domain )
		) );
	}

	/**
	 * Resolve an item
	 */
	public function resolveItem() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'resolveItem' ) ) {
			return;
		}

		$id = HTTP_Helper::retrieve_post( 'id', false );

		$model = Result_Item::findByID( $id );
		if ( is_object( $model ) ) {
			$ret = $model->resolve();
			if ( is_wp_error( $ret ) ) {
				wp_send_json_error( array(
					'message' => $ret->get_error_message()
				) );
			} else {
				if ( $ret === true ) {
					$this->submitStatsToDev();
					wp_send_json_success( array(
						'mid'     => 'mid-' . $model->id,
						'message' => __( "This item has been resolved.", wp_defender()->domain )
					) );
				} elseif ( $ret === false ) {
					wp_send_json_error( array(
						'message' => __( "Please try again!", wp_defender()->domain )
					) );
				} elseif ( is_string( $ret ) ) {
					$this->submitStatsToDev();
					wp_send_json_success( array(
						'url' => $ret
					) );
				}
			}
		} else {
			wp_send_json_error( array(
				'message' => __( "The item doesn't exist!", wp_defender()->domain )
			) );
		}
	}

	/**
	 * Ajax process to remove an item
	 */
	public function deleteItem() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'deleteItem' ) ) {
			return;
		}

		$id    = HTTP_Helper::retrieve_post( 'id', false );
		$model = Result_Item::findByID( $id );
		if ( is_object( $model ) ) {
			$ret = $model->purge();
			$this->submitStatsToDev();
			if ( is_wp_error( $ret ) ) {
				wp_send_json_error( array(
					'message' => $ret->get_error_message()
				) );

			} else {
				wp_send_json_success( array(
					'mid'     => 'mid-' . $model->id,
					'message' => __( "This item has been permanently removed", wp_defender()->domain ),
					'counts'  => $this->getIssuesAndIgnoredCounts( $model->parentId )
				) );
			}
		} else {
			wp_send_json_error( array(
				'message' => __( "The item doesn't exist!", wp_defender()->domain )
			) );
		}
	}

	/**
	 *
	 */
	public function unIgnoreItem() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'unIgnoreItem' ) ) {
			return;
		}

		$id    = HTTP_Helper::retrieve_post( 'id', false );
		$model = Result_Item::findByID( $id );
		if ( is_object( $model ) ) {
			$model->unignore();
			$this->submitStatsToDev();
			wp_send_json_success( array(
				'mid'     => 'mid-' . $model->id,
				'message' => __( "The suspicious file has been successfully restored.", wp_defender()->domain ),
				'counts'  => $this->getIssuesAndIgnoredCounts( $model->parentId )
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( "The item doesn't exist!", wp_defender()->domain )
			) );
		}
	}

	/**
	 * Ajax function for ignoring scan result item
	 */
	public function ignoreItem() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'ignoreItem' ) ) {
			return;
		}

		$id    = HTTP_Helper::retrieve_post( 'id', false );
		$model = Result_Item::findByID( $id );
		if ( is_object( $model ) ) {
			$model->ignore();
			$this->submitStatsToDev();
			wp_send_json_success( array(
				'mid'     => 'mid-' . $model->id,
				'message' => __( "The suspicious file has been successfully ignored.", wp_defender()->domain ),
				'counts'  => $this->getIssuesAndIgnoredCounts( $model->parentId )
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( "The item doesn't exist!", wp_defender()->domain )
			) );
		}
	}

	/**
	 * Ajax action for processing a scan on page
	 */
	public function processScan() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'processScan' ) ) {
			return;
		}

		//clean all qron
		wp_clear_scheduled_hook( 'processScanCron' );

		$ret   = Scan_Api::processActiveScan();
		$model = Scan_Api::getActiveScan();
		$data  = array(
			'percent'    => Scan_Api::getScanProgress(),
			'statusText' => is_object( $model ) ? $model->statusText : null
		);
		if ( $ret == true ) {
			$data['url'] = network_admin_url( 'admin.php?page=wdf-scan' );
			$referrer    = HTTP_Helper::retrieve_post( '_wp_http_referer' );
			parse_str( parse_url( $referrer, PHP_URL_QUERY ), $urlComp );
			if ( isset( $urlComp['page'] ) && $urlComp['page'] == 'wp-defender' ) {
				//from dashboard
				$data['url'] = network_admin_url( 'admin.php?page=wp-defender' );
			}
			$this->sendEmailReport( true );
			$this->submitStatsToDev();
			wp_send_json_success( $data );
		} else {
			//not completed
			//we will schedule a cron here in case user close tthe page
			wp_schedule_single_event( strtotime( '+1 minutes' ), 'processScanCron' );
			wp_send_json_error( $data );
		}
	}

	/**
	 * Ajax action, to start a new scan
	 */
	public function startAScan() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'startAScan' ) ) {
			return;
		}

		$ret = Scan_Api::createScan();
		if ( ! is_wp_error( $ret ) ) {
			wp_send_json_success( array(
					'url' => network_admin_url( 'admin.php?page=wdf-scan' )
				)
			);
		}

		wp_send_json_error( array(
			'message' => $ret->get_error_message(),
		) );
	}

	/**
	 * Add submit admin page
	 */
	public function adminMenu() {
		$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
		add_submenu_page( 'wp-defender', esc_html__( "File Scanning", wp_defender()->domain ), esc_html__( "File Scanning", wp_defender()->domain ), $cap, $this->slug, array(
			&$this,
			'actionIndex'
		) );
	}

	/**
	 * Enqueue scripts & styles
	 */
	public function scripts() {
		$data = array(
			'scanning_title' => __( "Scan In Progress", wp_defender()->domain ) . '<form class="scan-frm float-r"><input type="hidden" name="action" value="cancelScan"/>' . wp_nonce_field( 'cancelScan', '_wpnonce', true, false ) . '<button type="submit" class="button button-small button-secondary">' . __( "Cancel", wp_defender()->domain ) . '</button></form>',
			'no_issues'      => __( "Your code is currently clean! There were no issues found during the last scan, though you can always perform a new scan anytime.", wp_defender()->domain )
		);
		if ( $this->isInPage() ) {
			\WDEV_Plugin_Ui::load( wp_defender()->getPluginUrl() . 'shared-ui/' );
			wp_enqueue_script( 'defender' );
			wp_enqueue_script( 'scan', wp_defender()->getPluginUrl() . 'app/module/scan/js/script.js' );
			wp_enqueue_script( 'highlight.js', wp_defender()->getPluginUrl() . 'app/module/scan/js/highlight.pack.js' );
			wp_enqueue_script( 'highlight-linenumbers.js', wp_defender()->getPluginUrl() . 'app/module/scan/js/highlightjs-line-numbers.js' );
			wp_enqueue_style( 'defender' );
			wp_localize_script( 'scan', 'scan', $data );
		} else {
			wp_enqueue_script( 'scan', wp_defender()->getPluginUrl() . 'app/module/scan/js/script.js' );
		}
	}

	/**
	 * Internal route for this module
	 */
	public function actionIndex() {
		$view = HTTP_Helper::retrieve_get( 'view' );
		switch ( $view ) {
			case 'issue':
			default:
				$this->viewResult();
				break;
			case 'ignored':
				$this->viewIgnore();
				break;
			case 'cleaned':
				$this->viewCleaned();
				break;
			case 'settings':
				$this->viewSettings();
				break;
			case 'reporting':
				$this->viewAutomation();
				break;
		}
	}

	/**
	 * Render view when first start
	 */
	private function viewBrandNew() {
		$this->renderPartial( 'new' );
	}

	/**
	 * Render scanning page, this will be move to behavior later due to free vs pro
	 */
	private function viewScanning() {
		$model   = Scan_Api::getActiveScan();
		$percent = Scan_Api::getScanProgress();
		$this->renderPartial( 'scanning', array(
			'lastScanDate' => $this->getLastScanDate(),
			'model'        => $model,
			'percent'      => $percent
		) );
	}

	/**
	 * View all issues
	 */
	private function viewResult() {
		$activeScan = Scan_Api::getActiveScan();
		$lastScan   = Scan_Api::getLastScan();
		//no scan done, force to show new scan page
		if ( ! is_object( $activeScan ) && ! is_object( $lastScan ) ) {
			$this->viewBrandNew();
		} elseif ( is_object( $activeScan ) && $activeScan->status != Scan\Model\Scan::STATUS_ERROR ) {
			$this->viewScanning();
		} elseif ( is_object( $activeScan ) && $activeScan->status == Scan\Model\Scan::STATUS_ERROR ) {
			$this->viewError( $activeScan );
		} else {
			$this->render( 'issues', array(
				'lastScanDate' => $this->getLastScanDate(),
				'model'        => $lastScan
			) );
		}
	}

	/**
	 * @param Scan\Model\Scan $model
	 */
	private function viewError( Scan\Model\Scan $model ) {
		//auto restart
		$model->status = Scan\Model\Scan::STATUS_PROCESS;
		$model->save();
		$this->viewScanning();
	}

	/**
	 * View all ignored items
	 */
	private function viewIgnore() {
		$activeScan = Scan_Api::getActiveScan();
		$lastScan   = Scan_Api::getLastScan();
		//no scan done, force to show new scan page
		if ( ! is_object( $activeScan ) && ! is_object( $lastScan ) ) {
			$this->viewBrandNew();
		} elseif ( is_object( $activeScan ) && $activeScan->status != Scan\Model\Scan::STATUS_ERROR ) {
			$this->viewScanning();
		} elseif ( is_object( $activeScan ) && $activeScan->status == Scan\Model\Scan::STATUS_ERROR ) {
			$this->viewError( $activeScan );
		} else {
			$this->render( 'ignored', array(
				'lastScanDate' => $this->getLastScanDate(),
				'model'        => $lastScan
			) );
		}
	}

	/**
	 * View fixed item
	 */
	private function viewCleaned() {
		$activeScan = Scan_Api::getActiveScan();
		$lastScan   = Scan_Api::getLastScan();
		//no scan done, force to show new scan page
		if ( ! is_object( $activeScan ) && ! is_object( $lastScan ) ) {
			$this->viewBrandNew();
		} elseif ( is_object( $activeScan ) && $activeScan->status != Scan\Model\Scan::STATUS_ERROR ) {
			$this->viewScanning();
		} elseif ( is_object( $activeScan ) && $activeScan->status == Scan\Model\Scan::STATUS_ERROR ) {
			$this->viewError( $activeScan );
		} else {
			$this->render( 'cleaned', array(
				'lastScanDate' => $this->getLastScanDate(),
				'model'        => $lastScan
			) );
		}
	}

	/**
	 *
	 */
	private function viewSettings() {
		$model = Scan_Api::getLastScan();
		if ( ! is_object( $model ) ) {
			return $this->viewBrandNew();
		}
		$activeScan = Scan_Api::getActiveScan();
		if ( is_object( $activeScan ) && $activeScan->status != Scan\Model\Scan::STATUS_ERROR ) {
			$this->viewScanning();
		} else {
			$setting = Scan\Model\Settings::instance();
			$view    = wp_defender()->isFree ? 'setting-free' : 'setting';
			$this->render( $view, array(
				'setting'      => $setting,
				'model'        => $model,
				'lastScanDate' => $this->getLastScanDate(),
				'email'        => $this->email_search
			) );
		}
	}

	private function viewAutomation() {
		$model   = Scan_Api::getLastScan();
		$setting = Scan\Model\Settings::instance();
		if ( ! is_object( $model ) ) {
			return $this->viewBrandNew();
		}
		$activeScan = Scan_Api::getActiveScan();
		if ( is_object( $activeScan ) && $activeScan->status != Scan\Model\Scan::STATUS_ERROR ) {
			$this->viewScanning();
		} else {
			$this->email_search->add_script();
			$view = wp_defender()->isFree ? 'automation-free' : 'automation';
			$this->render( $view, array(
				'setting'      => $setting,
				'model'        => $model,
				'lastScanDate' => $this->getLastScanDate(),
				'email'        => $this->email_search
			) );
		}
	}

	/**
	 * Get last scan date and format it with WP date time format
	 * @return string|null
	 */
	private function getLastScanDate() {
		$model = Scan_Api::getLastScan();
		if ( is_object( $model ) ) {
			return $this->formatDateTime( $model->dateFinished );
		}

		return null;
	}

	public function sendEmailReport( $force = false ) {
		$settings = Settings::instance();
		if ( $settings->notification == false && $force != true ) {
			return false;
		}

		$model = Scan_Api::getLastScan();
		if ( ! is_object( $model ) ) {
			return;
		}
		$count = $model->countAll( Result_Item::STATUS_ISSUE );

		//Check one instead of validating both conditions
		if ( $settings->always_send == false && $count == 0 ) {
			return;
		}

		$recipients = $settings->receipts;

		if ( empty( $recipients ) ) {
			return;
		}

		foreach ( $recipients as $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( ! is_object( $user ) ) {
				continue;
			}
			//prepare the parameters
			$email   = $user->user_email;
			$params  = array(
				'USER_NAME'      => $this->getDisplayName( $user_id ),
				'ISSUES_COUNT'   => $count,
				'SCAN_PAGE_LINK' => network_admin_url( 'admin.php?page=wdf-scan' ),
				'ISSUES_LIST'    => $this->issues_list_html( $model ),
				'SITE_URL'       => network_site_url(),
			);
			$params  = apply_filters( 'wd_notification_email_params', $params );
			$subject = apply_filters( 'wd_notification_email_subject', $settings->email_subject );
			$subject = stripslashes( $subject );
			if ( $count == 0 ) {
				$email_content = $settings->email_all_ok;
			} else {
				$email_content = $settings->email_has_issue;
			}
			$email_content = apply_filters( 'wd_notification_email_content_before', $email_content, $model );
			foreach ( $params as $key => $val ) {
				$email_content = str_replace( '{' . $key . '}', $val, $email_content );
				$subject       = str_replace( '{' . $key . '}', $val, $subject );
			}
			//change nl to br
			$email_content = wpautop( stripslashes( $email_content ) );
			$email_content = apply_filters( 'wd_notification_email_content_after', $email_content, $model );

			$email_template = $this->renderPartial( 'email-template', array(
				'subject' => $subject,
				'message' => $email_content
			), false );
			$no_reply_email = "noreply@" . parse_url( get_site_url(), PHP_URL_HOST );
			$no_reply_email = apply_filters( 'wd_scan_noreply_email', $no_reply_email );
			$headers        = array(
				'From: Defender <' . $no_reply_email . '>',
				'Content-Type: text/html; charset=UTF-8'
			);
			wp_mail( $email, $subject, $email_template, $headers );
		}
	}

	/**
	 * Build issues html table
	 *
	 * @param $model
	 *
	 * @return string
	 * @access private
	 * @since 1.0
	 */
	private function issues_list_html( Scan\Model\Scan $model ) {
		ob_start();
		?>
        <table class="results-list"
               style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top;">
            <thead class="results-list-header" style="border-bottom: 2px solid #ff5c28;">
            <tr style="padding: 0; text-align: left; vertical-align: top;">
                <th class="result-list-label-title"
                    style="Margin: 0; color: #ff5c28; font-family: Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; line-height: 48px; margin: 0; padding: 0; text-align: left; width: 35%;"><?php esc_html_e( "File", wp_defender()->domain ) ?></th>
                <th class="result-list-data-title"
                    style="Margin: 0; color: #ff5c28; font-family: Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; line-height: 48px; margin: 0; padding: 0; text-align: left;"><?php esc_html_e( "Issue", wp_defender()->domain ) ?></th>
            </tr>
            </thead>
            <tbody class="results-list-content">
			<?php foreach ( $model->getItems() as $k => $item ): ?>
				<?php if ( $k == 0 ): ?>
                    <tr style="padding: 0; text-align: left; vertical-align: top;">
                        <td class="result-list-label"
                            style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;"><?php echo $item->getTitle() ?>
                            <span
                                    style="display: inline-block; font-weight: 400; width: 100%;"><?php echo $item->getSubTitle() ?></span>
                        </td>
                        <td class="result-list-data"
                            style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;"><?php echo $item->getIssueDetail() ?></td>
                    </tr>
				<?php else: ?>
                    <tr style="padding: 0; text-align: left; vertical-align: top;">
                        <td class="result-list-label <?php echo $k > 0 ? " bordered" : null ?>"
                            style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; border-top: 2px solid #ff5c28; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;"><?php echo $item->getTitle() ?>
                            <span
                                    style="display: inline-block; font-weight: 400; width: 100%;"><?php echo $item->getSubTitle() ?></span>
                        </td>
                        <td class="result-list-data <?php echo $k > 0 ? " bordered" : null ?>"
                            style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; border-top: 2px solid #ff5c28; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;"><?php echo $item->getIssueDetail() ?></td>
                    </tr>
				<?php endif; ?>
			<?php endforeach; ?>
            </tbody>
            <tfoot class="results-list-footer">
            <tr style="padding: 0; text-align: left; vertical-align: top;">
                <td colspan="2"
                    style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 10px 0 0; text-align: left; vertical-align: top; word-wrap: break-word;">
                    <p style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0 0 24px; text-align: left;">
                        <a class="plugin-brand" href="<?php echo network_admin_url( 'admin.php?page=wdf-scan' ) ?>"
                           style="Margin: 0; color: #ff5c28; display: inline-block; font: inherit; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none;"><?php esc_html_e( "Let’s get your site patched up.", wp_defender()->domain ) ?>
                            <img class="icon-arrow-right"
                                 src="<?php echo wp_defender()->getPluginUrl() ?>assets/email-images/icon-arrow-right-defender.png"
                                 alt="Arrow"
                                 style="-ms-interpolation-mode: bicubic; border: none; clear: both; display: inline-block; margin: -2px 0 0 5px; max-width: 100%; outline: none; text-decoration: none; vertical-align: middle; width: auto;"></a>
                    </p>
                </td>
            </tr>
            </tfoot>
        </table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Counts of issues and ignored items
	 *
	 * @param Integer $parent_id - PArent id of the model
	 *
	 * @return Array
	 */
	public function getIssuesAndIgnoredCounts( $parent_id ) {
		$total_issues  = 0;
		$total_ignored = 0;

		$issues_wp  = $this->countStatus( $parent_id, Result_Item::STATUS_ISSUE );
		$ignored_wp = $this->countStatus( $parent_id, Result_Item::STATUS_IGNORED );

		$total_issues  = $issues_wp;
		$total_ignored = $ignored_wp;

		$premium_counts = array();
		if ( wp_defender()->isFree == false ) {
			$issues_vuln     = $this->countStatus( $parent_id, Result_Item::STATUS_ISSUE, 'vuln' );
			$issues_content  = $this->countStatus( $parent_id, Result_Item::STATUS_ISSUE, 'content' );
			$ignored_vuln    = $this->countStatus( $parent_id, Result_Item::STATUS_IGNORED, 'vuln' );
			$ignored_content = $this->countStatus( $parent_id, Result_Item::STATUS_IGNORED, 'content' );

			$total_issues  += $issues_vuln;
			$total_issues  += $issues_content;
			$total_ignored += $ignored_vuln;
			$total_ignored += $ignored_content;

			$premium_counts = array( 'vuln_issues' => $issues_vuln, 'content_issues' => $issues_content );
		}

		$counts = array( 'issues' => $total_issues, 'issues_wp' => $issues_wp, 'ignored' => $total_ignored );

		$counts = array_merge( $counts, $premium_counts );

		return $counts;
	}


	/**
	 * Count status based on the parent id and type
	 *
	 * @param Integer $parent_id - Parent id of the model
	 * @param String $status - Status
	 * @param String $type - Issue Type. Defaults to core
	 *
	 * @return Integer
	 */
	private function countStatus( $parent_id, $status, $type = 'core' ) {
		$counts = Result_Item::count( array(
			'status'   => $status,
			'parentId' => $parent_id,
			'type'     => $type
		) );

		return $counts;
	}
}