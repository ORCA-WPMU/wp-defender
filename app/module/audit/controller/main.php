<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Audit\Controller;

use Hammer\Helper\HTTP_Helper;
use Hammer\Helper\Log_Helper;
use Hammer\Helper\WP_Helper;
use WP_Defender\Behavior\Utils;
use WP_Defender\Module\Audit\Behavior\Audit;
use WP_Defender\Module\Audit\Component\Audit_API;
use WP_Defender\Module\Audit\Component\Audit_Table;
use WP_Defender\Module\Audit\Model\Settings;
use WP_Defender\Vendor\Email_Search;

class Main extends \WP_Defender\Controller {
	protected $slug = 'wdf-logging';
	public $layout = 'layout';
	public $email_search;

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

		$this->add_ajax_action( 'activeAudit', 'activeAudit' );
		$this->add_ajax_action( 'auditLoadLogs', 'auditLoadLogs' );
		$this->add_ajax_action( 'saveAuditSettings', 'saveAuditSettings' );
		$this->add_ajax_action( 'auditOnCloud', 'auditOnCloud', true, true );
		$this->add_ajax_action( 'dashboardSummary', 'dashboardSummary' );
		$this->add_ajax_action( 'exportAsCvs', 'exportAsCvs' );

		if ( Settings::instance()->enabled == 1 ) {
			$this->add_action( 'wp_loaded', 'setupEvents', 1 );
			$this->add_action( 'shutdown', 'triggerEventSubmit' );
		}
		$this->email_search = new Email_Search();
		if ( ( HTTP_Helper::retrieve_get( 'view' ) == ''
		       && HTTP_Helper::retrieve_get( 'page' ) == 'wdf-logging' )
		     || ( ( defined( 'DOING_AJAX' ) && DOING_AJAX == true )
		          && HTTP_Helper::retrieve_post( 'id' ) == 'audit_lite' )
		) {
			//load the lite version of user search on main page & when using ajax, for using the
			//ajax hooks
			$this->email_search->lite      = true;
			$this->email_search->eId       = 'audit_lite';
			$this->email_search->noExclude = true;
		} else {
			$this->email_search->eId = 'audit';
		}
		$this->email_search->settings = Settings::instance();
		$this->email_search->add_hooks();
		//report cron
		$this->add_action( 'auditReportCron', 'auditReportCron' );
	}

	public function exportAsCvs() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		$params  = $this->prepareAuditParams();
		$data    = Audit_API::pullLogs( $params, 'timestamp', 'desc', true );
		$logs    = $data['data'];
		$fp      = fopen( 'php://memory', 'w' );
		$headers = array(
			__( "Summary", wp_defender()->domain ),
			__( "Date / Time", wp_defender()->domain ),
			__( "Context", wp_defender()->domain ),
			__( "Type", wp_defender()->domain ),
			__( "IP address", wp_defender()->domain ),
			__( "User", wp_defender()->domain )
		);
		fputcsv( $fp, $headers );
		foreach ( $logs as $fields ) {
			$vars = array(
				$fields['msg'],
				is_array( $fields['timestamp'] )
					? $this->formatDateTime( date( 'Y-m-d H:i:s', $fields['timestamp'][0] ) )
					: $this->formatDateTime( date( 'Y-m-d H:i:s', $fields['timestamp'] ) ),
				ucwords( Audit_API::get_action_text( $fields['context'] ) ),
				ucwords( Audit_API::get_action_text( $fields['action_type'] ) ),
				$fields['ip'],
				$this->getDisplayName( $fields['user_id'] )
			);
			fputcsv( $fp, $vars );
		}
		$filename = 'wdf-audit-logs-export-' . date( 'ymdHis' ) . '.csv';
		fseek( $fp, 0 );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		// make php send the generated csv lines to the browser
		fpassthru( $fp );
		exit();
	}

	public function dashboardSummary() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'dashboardSummary' ) ) {
			return;
		}

		if ( HTTP_Helper::retrieve_post( 'weekly' ) == '1' ) {
			$weekCount = Audit_API::pullLogs( array(
				'date_from' => date( 'Y-m-d', strtotime( '-7 days' ) ) . ' 00:00:00',
				'date_to'   => date( 'Y-m-d' ) . ' 23:59:59'
			) );
			wp_send_json_success( array(
				'eventWeek' => is_wp_error( $weekCount ) ? '-' : $weekCount['total_items']
			) );
		}

		$eventsInMonth = Audit_API::pullLogs( array(
			'date_from' => date( 'Y-m-d', strtotime( 'first day of this month', current_time( 'timestamp' ) ) ) . ' 00:00:00',
			'date_to'   => date( 'Y-m-d' ) . ' 23:59:59'
		) );

		if ( is_wp_error( $eventsInMonth ) ) {
			wp_send_json_error( array(
				'message' => $eventsInMonth->get_error_message()
			) );
		}

		$lastEventDate   = __( "Never", wp_defender()->domain );
		$dailyEventCount = 0;

		if ( $eventsInMonth['total_items'] > 0 ) {
			$request = Audit_API::pullLogsSummary();
			if ( is_wp_error( $request ) ) {
				wp_send_json_error( array(
					'message' => $request->get_error_message()
				) );
			}
			$dailyEventCount = $request['count'];
			$lastEventDate   = $eventsInMonth['data'][0]['timestamp'];
			if ( is_array( $lastEventDate ) ) {
				$lastEventDate = $lastEventDate[0];
			}
			$lastEventDate = $this->formatDateTime( date( 'Y-m-d H:i:s', $lastEventDate ) );
		}
		$content = $this->renderPartial( 'widget', array(
			'eventMonth' => $eventsInMonth['total_items'],
			'eventDay'   => $dailyEventCount,
			'lastEvent'  => $lastEventDate
		), false );

		wp_send_json_success( array(
			'html' => $content
		) );
	}

	public function sort_email_data( $a, $b ) {
		return $a['count'] < $b['count'];
	}

	/**
	 * process scan settings
	 */
	public function saveAuditSettings() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'saveAuditSettings' ) ) {
			return;
		}

		$settings = Settings::instance();
		$data     = array_map( 'sanitize_text_field', $_POST );
		$settings->import( $data );
		$settings->save();
		$cronTime = $this->reportCronTimestamp( $settings->time, 'auditReportCron' );
		if ( $settings->notification == true ) {
			wp_schedule_event( $cronTime, 'daily', 'auditReportCron' );
		}
		$res = array(
			'message' => __( "Your settings have been updated.", wp_defender()->domain )
		);

		if ( $settings->notification == true ) {
			$res['notification'] = 1;
			$res['frequency']    = ucfirst( \WP_Defender\Behavior\Utils::instance()->frequencyToText( $settings->frequency ) );
			if ( $settings->frequency == 1 ) {
				$res['schedule'] = sprintf( __( "at %s", wp_defender()->domain ), strftime( '%I:%M %p', strtotime( $settings->time ) ) );
			} else {
				$res['schedule'] = sprintf( __( "%s at %s", wp_defender()->domain ), ucfirst( $settings->day ), strftime( '%I:%M %p', strtotime( $settings->time ) ) );
			}
		} else {
			$res['notification'] = 0;
			$res['text']         = '-';
		}
		if ( $settings->enabled == 0 ) {
			$res['reload'] = 1;
		}
		Utils::instance()->submitStatsToDev();
		wp_send_json_success( $res );
	}

	/**
	 * Ajax for loading audit table html
	 */
	public function auditLoadLogs() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		$lite = HTTP_Helper::retrieve_get( 'lite', false );
		if ( $lite == 1 ) {

		} else {
			$params = $this->prepareAuditParams();
			$data   = Audit_API::pullLogs( $params );
			$table  = $this->_renderTable( $data );
			wp_send_json_success( array(
				'html'  => $table,
				'count' => is_array( $data ) ? $data['total_items'] : 0
			) );
		}
	}

	/**
	 * hook all the action for listening on events
	 */
	public function setupEvents() {
		Audit_API::setupEvents();
	}

	public function triggerEventSubmit() {
		$data = WP_Helper::getArrayCache()->get( 'events_queue', array() );
		if ( is_array( $data ) && count( $data ) ) {
			Audit_API::onCloud( $data );
		}
	}

	/**
	 * Sending report email by cron
	 */
	public function auditReportCron() {
		if ( wp_defender()->isFree ) {
			return;
		}

		$settings = Settings::instance();

		if ( $settings->notification == false ) {
			return;
		}

		$lastReportSent = $settings->lastReportSent;
		if ( $lastReportSent == null ) {
			//no sent, so just assume last 30 days, as this only for monthly
			$lastReportSent = strtotime( '-31 days', current_time( 'timestamp' ) );
		}

		if ( ! $this->isReportTime( $settings->frequency, $settings->day, $lastReportSent ) ) {
			return false;
		}

		switch ( $settings->frequency ) {
			case 1:
				$date_from = strtotime( '-24 hours' );
				$date_to   = time();
				break;
			case 7:
				$date_from = strtotime( '-7 days' );
				$date_to   = time();
				break;
			case 30:
				$date_from = strtotime( '-30 days' );
				$date_to   = time();
				break;
		}

		if ( ! isset( $date_from ) && ! isset( $date_to ) ) {
			//something wrong
			return;
		}

		$date_from = date( 'Y-m-d', $date_from );
		$date_to   = date( 'Y-m-d', $date_to );

		$logs = Audit_API::pullLogs( array(
			'date_from' => $date_from . ' 0:00:00',
			'date_to'   => $date_to . ' 23:59:59',
			//no paging
			'paged'     => - 1,
			//'no_group_item' => 1
		) );

		$data       = $logs['data'];
		$email_data = array();
		foreach ( $data as $row => $val ) {
			if ( ! isset( $email_data[ $val['event_type'] ] ) ) {
				$email_data[ $val['event_type'] ] = array(
					'count' => 0
				);
			}

			if ( ! isset( $email_data[ $val['event_type'] ][ $val['action_type'] ] ) ) {
				$email_data[ $val['event_type'] ][ $val['action_type'] ] = 1;
			} else {
				$email_data[ $val['event_type'] ][ $val['action_type'] ] += 1;
			}
			$email_data[ $val['event_type'] ]['count'] += 1;
		}

		uasort( $email_data, array( &$this, 'sort_email_data' ) );

		//now we create a table
		if ( count( $email_data ) ) {
			ob_start();
			?>
            <table class="wrapper main" align="center"
                   style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
                <tbody>
                <tr style="padding: 0; text-align: left; vertical-align: top;">
                    <td class="wrapper-inner main-inner"
                        style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 40px; text-align: left; vertical-align: top; word-wrap: break-word;">

                        <table class="main-intro"
                               style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top;">
                            <tbody>
                            <tr style="padding: 0; text-align: left; vertical-align: top;">
                                <td class="main-intro-content"
                                    style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
                                    <h3 style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 32px; font-weight: normal; line-height: 32px; margin: 0; margin-bottom: 0; padding: 0 0 28px; text-align: left; word-wrap: normal;"><?php _e( "Hi {USER_NAME},", wp_defender()->domain ) ?></h3>
                                    <p style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0 0 24px; text-align: left;">
										<?php printf( __( "It’s WP Defender here, reporting from the frontline with a quick update on what’s been happening at <a href=\"%s\">%s</a>.", wp_defender()->domain ), site_url(), site_url() ) ?></p>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <table class="results-list"
                               style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top;">
                            <thead class="results-list-header" style="border-bottom: 2px solid #ff5c28;">
                            <tr style="padding: 0; text-align: left; vertical-align: top;">
                                <th class="result-list-label-title"
                                    style="Margin: 0; color: #ff5c28; font-family: Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; line-height: 48px; margin: 0; padding: 0; text-align: left; width: 35%;">
									<?php _e( "Event Type", wp_defender()->domain ) ?>
                                </th>
                                <th class="result-list-data-title"
                                    style="Margin: 0; color: #ff5c28; font-family: Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; line-height: 48px; margin: 0; padding: 0; text-align: left;">
									<?php _e( "Action Summaries", wp_defender()->domain ) ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody class="results-list-content">
							<?php $count = 0; ?>
							<?php foreach ( $email_data as $key => $row ): ?>
                                <tr style="padding: 0; text-align: left; vertical-align: top;">
									<?php if ( $count == 0 ) {
										$style = '-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;';
									} else {
										$style = '-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; border-top: 2px solid #ff5c28; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;';
									} ?>
                                    <td class="result-list-label bordered"
                                        style="<?php echo $style ?>">
										<?php echo ucfirst( Audit_API::get_action_text( strtolower( $key ) ) ) ?>
                                    </td>
                                    <td class="result-list-data bordered"
                                        style="<?php echo $style ?>">
										<?php foreach ( $row as $i => $v ): ?>
											<?php if ( $i == 'count' ) {
												continue;
											} ?>
                                            <span
                                                    style="display: inline-block; font-weight: 400; width: 100%;">
												<?php echo ucwords( Audit_API::get_action_text( strtolower( $i ) ) ) ?>
                                                : <?php echo $v ?>
											</span>
										<?php endforeach; ?>
                                    </td>
                                </tr>
								<?php $count ++; ?>
							<?php endforeach; ?>
                            </tbody>
                            <tfoot class="results-list-footer">
                            <tr style="padding: 0; text-align: left; vertical-align: top;">
                                <td colspan="2"
                                    style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 10px 0 0; text-align: left; vertical-align: top; word-wrap: break-word;">
                                    <p style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0 0 24px; text-align: left;">
                                        <a class="plugin-brand"
                                           href="<?php echo network_admin_url( 'admin.php?page=wdf-logging&date_from=' . date( 'm/d/Y', strtotime( $date_from ) ) . '&date_to=' . date( 'm/d/Y', strtotime( $date_to ) ) ) ?>"
                                           style="Margin: 0; color: #ff5c28; display: inline-block; font: inherit; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none;"><?php _e( "You can view the full audit report for your site here.", wp_defender()->domain ) ?>
                                            <img
                                                    class="icon-arrow-right"
                                                    src="<?php echo wp_defender()->getPluginUrl() ?>assets/email-images/icon-arrow-right-defender.png"
                                                    alt="Arrow"
                                                    style="-ms-interpolation-mode: bicubic; border: none; clear: both; display: inline-block; margin: -2px 0 0 5px; max-width: 100%; outline: none; text-decoration: none; vertical-align: middle; width: auto;"></a>
                                    </p>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                        <table class="main-signature"
                               style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top;">
                            <tbody>
                            <tr style="padding: 0; text-align: left; vertical-align: top;">
                                <td class="main-signature-content"
                                    style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
                                    <p style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0 0 24px; text-align: left;">
                                        Stay safe,</p>
                                    <p class="last-item"
                                       style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0; text-align: left;">
                                        WP Defender <br><strong>WPMU DEV Security Hero</strong></p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
			<?php
			$table = ob_get_clean();
		} else {
			$table = '<p>' . sprintf( esc_html__( "There were no events logged for %s", wp_defender()->domain ), network_site_url() ) . '</p>';
		}

		$template = $this->renderPartial( 'email_template', array(
			'message' => $table,
			'subject' => sprintf( esc_html__( "Here’s what’s been happening at %s", wp_defender()->domain ), network_site_url() )
		), false );


		foreach ( Settings::instance()->receipts as $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( ! is_object( $user ) ) {
				continue;
			}
			//prepare the parameters
			$email = $user->user_email;

			$no_reply_email = "noreply@" . parse_url( get_site_url(), PHP_URL_HOST );
			$no_reply_email = apply_filters( 'wd_audit_noreply_email', $no_reply_email );
			$headers        = array(
				'From: Defender <' . $no_reply_email . '>',
				'Content-Type: text/html; charset=UTF-8'
			);
			$params         = array(
				'USER_NAME' => $this->getDisplayName( $user_id ),
				'SITE_URL'  => network_site_url(),
			);
			$email_content  = $template;
			foreach ( $params as $key => $val ) {
				$email_content = str_replace( '{' . $key . '}', $val, $email_content );
			}
			wp_mail( $email, sprintf( esc_html__( "Here’s what’s been happening at %s", wp_defender()->domain ), network_site_url() ), $email_content, $headers );
		}

		$settings->lastReportSent = time();
		$settings->save();
	}

	/**
	 * activate audit
	 */
	public function activeAudit() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		if ( ! wp_verify_nonce( HTTP_Helper::retrieve_post( '_wpnonce' ), 'activeAudit' ) ) {
			return;
		}

		$settings = Settings::instance();
		if ( $settings->enabled == true ) {
			$settings->enabled = false;
		} else {
			$settings->enabled = true;
		}
		$settings->save();
		Utils::instance()->submitStatsToDev();
		wp_send_json_success( array(
			'url' => network_admin_url( 'admin.php?page=wdf-logging' )
		) );
	}

	/**
	 * Add submit admin page
	 */
	public function adminMenu() {
		$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
		add_submenu_page( 'wp-defender', esc_html__( "Audit Logging", wp_defender()->domain ), esc_html__( "Audit Logging", wp_defender()->domain ), $cap, $this->slug, array(
			&$this,
			'actionIndex'
		) );
	}

	public function scripts() {
		if ( $this->isInPage() ) {
			\WDEV_Plugin_Ui::load( wp_defender()->getPluginUrl() . 'shared-ui/' );
			wp_enqueue_script( 'defender' );
			wp_enqueue_style( 'defender' );
			wp_enqueue_script( 'audit', wp_defender()->getPluginUrl() . 'app/module/audit/js/script.js', array(
				'jquery-effects-core'
			) );
			wp_enqueue_script( 'audit-momentjs', wp_defender()->getPluginUrl() . 'app/module/audit/js/moment/moment.min.js' );
			wp_enqueue_style( 'audit-daterangepicker', wp_defender()->getPluginUrl() . 'app/module/audit/js/daterangepicker/daterangepicker.css' );
			wp_enqueue_script( 'audit-daterangepicker', wp_defender()->getPluginUrl() . 'app/module/audit/js/daterangepicker/daterangepicker.js' );
		} else {
			wp_enqueue_script( 'audit', wp_defender()->getPluginUrl() . 'app/module/audit/js/script.js' );
		}
	}

	public function actionIndex() {
		$view = HTTP_Helper::retrieve_get( 'view' );
		switch ( $view ) {
			case 'audit':
			default:
				$this->_renderAudit();
				break;
			case 'report':
				$this->_renderReport();
				break;
			case'settings':
				$this->_renderSettings();
				break;
		}
	}

	private function _renderSettings() {
		$settings = Settings::instance();
		if ( $settings->enabled ) {
			$this->render( 'settings', array(
				'settings' => $settings
			) );
		} else {
			$this->render( 'new' );
		}
	}

	/**
	 *
	 */
	private function _renderAudit() {
		if ( Settings::instance()->enabled ) {
			$date_format = 'm/d/Y';
			$this->email_search->add_script();
			$this->email_search->placeholder = __( "Type a user’s name", wp_defender()->domain );
			$this->email_search->empty_msg   = __( "We did not find an user with this name...", wp_defender()->domain );
			$from                            = Http_Helper::retrieve_get( 'date_from', date( $date_format, strtotime( 'today midnight', strtotime( '-7 days', current_time( 'timestamp' ) ) ) ) );
			$to                              = Http_Helper::retrieve_get( 'date_to', date( $date_format, current_time( 'timestamp' ) ) );
			$this->render( 'main', array(
				'email_search' => $this->email_search,
				'from'         => $from,
				'to'           => $to,
				//'table'        => $this->_renderTable( $data )
			) );
		} else {
			$this->render( 'new' );
		}
	}

	/**
	 * @return array
	 */
	private function prepareAuditParams() {
		$date_format = 'm/d/Y';
		$attributes  = array(
			'date_from'   => date( $date_format, strtotime( '-7 days', current_time( 'timestamp' ) ) ),
			'date_to'     => date( $date_format, current_time( 'timestamp' ) ),
			'user_id'     => '',
			'event_type'  => '',
			'ip'          => '',
			'context'     => '',
			'action_type' => '',
			'blog_id'     => 1,
			'date_range'  => HTTP_Helper::retrieve_get( 'date_range', null ),
			'paged'       => HTTP_Helper::retrieve_get( 'paged', 1 )
		);
		$params      = array();
		foreach ( $attributes as $att => $value ) {
			$params[ $att ] = HTTP_Helper::retrieve_get( $att, $value );
			if ( $att == 'date_from' || $att == 'date_to' ) {
				$df_object = \DateTime::createFromFormat( $date_format, $params[ $att ] );
				//check if the date string is right, if not, we use default
				if ( is_object( $df_object ) ) {
					$params[ $att ] = $df_object->format( 'Y-m-d' );
				}
			} elseif ( $att == 'user_id' ) {
				$params['user_id'] = HTTP_Helper::retrieve_get( 'term' );
			} elseif ( $att == 'date_range' && in_array( $value, array( 1, 7, 30 ) ) ) {
				$params['date_from'] = date( 'Y-m-d', strtotime( '-' . $value . ' days', current_time( 'timestamp' ) ) );
			}
		}

		$params['date_to']   = trim( $params['date_to'] . ' 23:59:59' );
		$params['date_from'] = trim( $params['date_from'] . ' 00:00:00' );
		if ( ! empty( $params['user_id'] ) ) {
			if ( ! filter_var( $params['user_id'], FILTER_VALIDATE_INT ) ) {
				$user_id = username_exists( $params['user_id'] );
				if ( $user_id == false ) {
					$params['user_id'] = null;
				} else {
					$params['user_id'] = $user_id;
				}
			}
		}

		return $params;
	}

	/**
	 * @return bool|string
	 */
	public function _renderTable( $data ) {
		return $this->renderPartial( 'table', array(
			'data'       => $data,
			'pagination' => is_wp_error( $data ) ? '' : $this->pagination( $data['total_items'], $data['total_pages'] )
		), false );
	}

	protected function pagination( $total_items, $total_pages ) {
		if ( $total_items == 0 ) {
			return;
		}

		if ( $total_pages < 2 ) {
			return;
		}

		$links        = array();
		$current_page = absint( HTTP_Helper::retrieve_get( 'paged', 1 ) );
		/**
		 * if pages less than 7, display all
		 * if larger than 7 we will get 3 previous page of current, current, and .., and, and previous, next, first, last links
		 */
		$current_url = set_url_scheme( 'http://' . parse_url( get_site_url(), PHP_URL_HOST ) . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );
		$current_url = esc_url( $current_url );

		$radius = 2;
		if ( $current_page > 1 && $total_pages > $radius ) {
			$links['first'] = sprintf( '<a class="button button-light" data-paged="%s" href="%s">%s</a>',
				1, add_query_arg( 'paged', 1, $current_url ), '&laquo;' );
			$links['prev']  = sprintf( '<a class="button button-light" data-paged="%s" href="%s">%s</a>',
				$current_page - 1, add_query_arg( 'paged', $current_page - 1, $current_url ), '&lsaquo;' );
		}

		for ( $i = 1; $i <= $total_pages; $i ++ ) {
			if ( ( $i >= 1 && $i <= $radius ) || ( $i > $current_page - 2 && $i < $current_page + 2 ) || ( $i <= $total_pages && $i > $total_pages - $radius ) ) {
				if ( $i == $current_page ) {
					$links[ $i ] = sprintf( '<a href="#" class="button button-light" data-paged="%s" disabled="">%s</a>', $i, $i );
				} else {
					$links[ $i ] = sprintf( '<a class="button button-light" data-paged="%s" href="%s">%s</a>',
						$i, add_query_arg( 'paged', $i, $current_url ), $i );
				}
			} elseif ( $i == $current_page - $radius || $i == $current_page + $radius ) {
				$links[ $i ] = '<a href="#" class="button button-light" disabled="">...</a>';
			}
		}

		if ( $current_page < $total_pages && $total_pages > $radius ) {
			$links['next'] = sprintf( '<a class="button button-light" data-paged="%s" href="%s">%s</a>',
				$current_page + 1, add_query_arg( 'paged', $current_page + 1, $current_url ), '&rsaquo;' );
			$links['last'] = sprintf( '<a class="button button-light" data-paged="%s" href="%s">%s</a>',
				$total_pages, add_query_arg( 'paged', $total_pages, $current_url ), '&raquo;' );
		}
		$output = join( "\n", $links );

		return $output;
	}

	public function buildFilterUrl( $type, $value ) {
		/**
		 * when click on a filter link, we will havet o include the current date range, and from
		 * we will need to keep the current get too
		 */
		$allowed     = array(
			'event_type',
			'term',
			'date_from',
			'date_to'
		);
		$http_params = array();
		foreach ( $_GET as $key => $val ) {
			if ( in_array( $key, $allowed ) && ! empty( $val ) ) {
				$http_params[ $key ] = $val;
			}
		}

		$http_params[ $type ] = $value;

		return '#' . http_build_query( $http_params );
	}

	private function _renderReport() {
		if ( Settings::instance()->enabled ) {
			$this->email_search->lite = false;
			$this->email_search->add_script();
			$this->render( 'report', array(
				'email'   => $this->email_search,
				'setting' => Settings::instance()
			) );
		} else {
			$this->render( 'new' );
		}
	}
}