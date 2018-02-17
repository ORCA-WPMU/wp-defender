<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Audit\Component;

use Hammer\Base\Component;
use Hammer\Helper\Log_Helper;
use Hammer\Helper\WP_Helper;
use WP_Defender\Behavior\Utils;
use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Audit\Model\Settings;

class Audit_API extends Component {
	const ACTION_ADDED = 'added', ACTION_UPDATED = 'updated', ACTION_DELETED = 'deleted', ACTION_TRASHED = 'trashed',
		ACTION_RESTORED = 'restored';
	public static $end_point = 'audit.wpmudev.org';

	/**
	 * @param array $filter
	 * @param string $order_by
	 * @param string $order
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	public static function pullLogs( $filter = array(), $order_by = 'timestamp', $order = 'desc', $nopaging = false ) {
		$data             = $filter;
		$data['site_url'] = network_site_url();
		$data['order_by'] = $order_by;
		$data['order']    = $order;
		$data['nopaging'] = $nopaging;
		$data['timezone'] = get_option( 'gmt_offset' );
		$response         = Utils::instance()->devCall( 'https://' . self::$end_point . '/logs', $data, array(
			'method'  => 'GET',
			'timeout' => 20,
			//'sslverify' => false,
			'headers' => array(
				'apikey' => Utils::instance()->getAPIKey()
			)
		), true );
		//todo need to remove in some next versions

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$body    = wp_remote_retrieve_body( $response );
			$results = json_decode( $body, true );
			if ( isset( $results['message'] ) ) {
				return new \WP_Error( Error_Code::API_ERROR, $results['message'] );
			}

			return $results;
		}

		return new \WP_Error( Error_Code::API_ERROR, sprintf( __( "Whoops, Defender had trouble loading up your event log. You can try a <a href='%s'class=''>​quick refresh</a>​ of this page or check back again later.", wp_defender()->domain ),
			network_admin_url( 'admin.php?page=wdf-logging' ) ) );
	}

	/**
	 * @param array $filter
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	public static function pullLogsSummary( $filter = array() ) {
		$data             = $filter;
		$data['site_url'] = network_site_url();
		$data['timezone'] = get_option( 'gmt_offset' );
		$response         = Utils::instance()->devCall( 'http://' . self::$end_point . '/logs/summary', $data, array(
			'method'  => 'GET',
			'timeout' => 20,
			//'sslverify' => false,
			'headers' => array(
				'apikey' => Utils::instance()->getAPIKey()
			)
		), true );

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$body    = wp_remote_retrieve_body( $response );
			$results = json_decode( $body, true );
			if ( isset( $results['message'] ) ) {
				return new \WP_Error( Error_Code::API_ERROR, $results['message'] );
			}

			return $results;
		}

		return new \WP_Error( Error_Code::API_ERROR, sprintf( __( "Whoops, Defender had trouble loading up your event log. You can try a <a href='%s'class=''>​quick refresh</a>​ of this page or check back again later.", wp_defender()->domain ),
			network_admin_url( 'admin.php?page=wdf-logging' ) ) );
	}

	/**
	 * Open a socket to DEV api
	 */
	public static function openSocket() {
		if ( ! isset( wp_defender()->global['sockets'] ) ) {
			$fp = @stream_socket_client( 'ssl://' . self::$end_point . ':443', $errno, $errstr,
				3, // timeout should be ignored when ASYNC
				STREAM_CLIENT_ASYNC_CONNECT );

			if ( is_resource( $fp ) ) {
				//socket_set_nonblock( $fp );
				wp_defender()->global['sockets'][] = $fp;
			}
		}
	}

	/**
	 * @return array
	 */
	public static function dictionary() {
		return array(
			self::ACTION_TRASHED  => esc_html__( "trashed", wp_defender()->domain ),
			self::ACTION_UPDATED  => esc_html__( "updated", wp_defender()->domain ),
			self::ACTION_DELETED  => esc_html__( "deleted", wp_defender()->domain ),
			self::ACTION_ADDED    => esc_html__( "created", wp_defender()->domain ),
			self::ACTION_RESTORED => esc_html__( "restored", wp_defender()->domain ),
		);
	}

	public static function liveable_audit_log( $text ) {
		//first need to get the site id
		$site_id = 1;
		//rip out any html if any
		$text = esc_html( $text );

		$text = str_replace( '; ', '<br/>', $text );

		/**
		 * we continue to check anything with ID, usually it will be
		 * comment ID
		 * file URL
		 */

		return $text;
		//we got the site ID.
	}

	public static function get_event_type() {
		return WP_Helper::getArrayCache()->get( 'event_types', array() );
	}

	/**
	 * @param $slug
	 *
	 * @return mixed
	 */
	public static function get_action_text( $slug ) {
		$dic = WP_Helper::getArrayCache()->get( 'dictionary', array() );

		return isset( $dic[ $slug ] ) ? $dic[ $slug ] : $slug;
	}

	public static function time_since( $since ) {
		$since = time() - $since;
		if ( $since < 0 ) {
			$since = 0;
		}
		$chunks = array(
			array( 60 * 60 * 24 * 365, esc_html__( "year" ) ),
			array( 60 * 60 * 24 * 30, esc_html__( "month" ) ),
			array( 60 * 60 * 24 * 7, esc_html__( "week" ) ),
			array( 60 * 60 * 24, esc_html__( 'day' ) ),
			array( 60 * 60, esc_html__( "hour" ) ),
			array( 60, esc_html__( "minute" ) ),
			array( 1, esc_html__( "second" ) )
		);

		for ( $i = 0, $j = count( $chunks ); $i < $j; $i ++ ) {
			$seconds = $chunks[ $i ][0];
			$name    = $chunks[ $i ][1];
			if ( ( $count = floor( $since / $seconds ) ) != 0 ) {
				break;
			}
		}

		$print = ( $count == 1 ) ? '1 ' . $name : "$count {$name}s";

		return $print;
	}

	/**
	 * Queue event data prepare for submitting
	 *
	 * @param $data
	 * since 1.1
	 */
	public static function queueEventsData( $data ) {
		$events   = WP_Helper::getArrayCache()->get( 'events_queue', array() );
		$events[] = $data;
		WP_Helper::getArrayCache()->set( 'events_queue', $events );
	}

	/**
	 * @param $data
	 */
	public static function curlToAPI( $data ) {
		Utils::instance()->devCall( 'http://' . self::$end_point . '/logs/add_multiple', $data, array(
			'method'  => 'POST',
			//'sslverify' => false,
			'timeout' => 3,
			'headers' => array(
				'apikey' => Utils::instance()->getAPIKey()
			)
		), true );
	}

	/**
	 * @return bool
	 */
	public static function isActive() {
		return Settings::instance()->enabled;
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	public static function socketToAPI( $data ) {
		//$sockets = WP_Helper::getArrayCache()->get( 'sockets', array() );
		$sockets = isset( wp_defender()->global['sockets'] ) ? wp_defender()->global['sockets'] : array();
		//we will need to wait a little bit
		if ( count( $sockets ) == 0 ) {
			//fall back
			return false;
		}
		$start_time = microtime( true );
		$sks        = $sockets;
		$r          = null;
		$e          = null;
		if ( ( $socket_ready = stream_select( $r, $sks, $e, 1 ) ) === false ) {
			//this case error happen

			return false;
		}

		$fp = array_shift( $sockets );

		$uri  = '/logs/add_multiple';
		$vars = http_build_query( $data );

		fwrite( $fp, "POST " . $uri . "  HTTP/1.1\r\n" );
		fwrite( $fp, "Host: " . self::$end_point . "\r\n" );
		fwrite( $fp, "Content-Type: application/x-www-form-urlencoded\r\n" );
		fwrite( $fp, "Content-Length: " . strlen( $vars ) . "\r\n" );
		fwrite( $fp, "apikey:" . Utils::instance()->getAPIKey() . "\r\n" );
		fwrite( $fp, "Connection: close\r\n" );
		fwrite( $fp, "\r\n" );
		fwrite( $fp, $vars );
		socket_set_timeout( $fp, 5 );
		$res = '';
		while ( ! feof( $fp ) ) {
			$res      .= fgets( $fp, 1024 );
			$end_time = microtime( true );
			if ( $end_time - $start_time > 3 ) {
				fclose( $fp );
				break;
			}
			//Log_Helper::logger( fgets( $fp, 1024 ) );
		}

		return true;
	}

	/**
	 * @param bool $clearCron
	 *
	 * @return false|int
	 * @deprecated
	 */
	public static function getReportTime( $clearCron = true ) {
		if ( $clearCron ) {
			wp_clear_scheduled_hook( 'auditReportCron' );
		}
		$settings = Settings::instance();
		switch ( $settings->frequency ) {
			case '1':
				//check if the time is over or not, then send the date
				$timeString     = date( 'Y-m-d' ) . ' ' . $settings->time . ':00';
				$nextTimeString = date( 'Y-m-d', strtotime( 'tomorrow' ) ) . ' ' . $settings->time . ':00';
				break;
			case '7':
			default:
				$timeString     = date( 'Y-m-d', strtotime( $settings->day . ' this week' ) ) . ' ' . $settings->time . ':00';
				$nextTimeString = date( 'Y-m-d', strtotime( $settings->day . ' next week' ) ) . ' ' . $settings->time . ':00';
				break;
			case '30':
				$timeString     = date( 'Y-m-d', strtotime( $settings->day . ' this month' ) ) . ' ' . $settings->time . ':00';
				$nextTimeString = date( 'Y-m-d', strtotime( $settings->day . ' next month' ) ) . ' ' . $settings->time . ':00';
				break;
		}

		$toUTC = Utils::instance()->localToUtc( $timeString );
		if ( $toUTC <= time() ) {
			//already passed
			return Utils::instance()->localToUtc( $nextTimeString );
		} else {
			return $toUTC;
		}
	}

	/**
	 * We get all the hooks from internal component and add it to wp hook system on wp_load time
	 */
	public static function setupEvents() {
		//we only queue for
		if ( defined( 'DOING_CRON' ) && constant( 'DOING_CRON' ) == true ) {
			//this is cron, we only queue the core audit to catch auto update
			$events_class = array(
				new Core_Audit()
			);
		} else {
			$events_class = array(
				new Comment_Audit(),
				new Core_Audit(),
				new Media_Audit(),
				new Options_Audit(),
				new Post_Audit(),
				new Users_Audit()
			);
		}

		//we will build up the dictionary here
		$dictionary  = self::dictionary();
		$event_types = array();

		foreach ( $events_class as $class ) {
			$hooks      = $class->get_hooks();
			$dictionary = array_merge( $class->dictionary(), $dictionary );
			foreach ( $hooks as $key => $hook ) {
				$func = function () use ( $key, $hook, $class ) {
					//this is argurements of the hook
					$args = func_get_args();
					//this is hook data, defined in each events class
					$class->build_log_data( $key, $args, $hook );
				};
				add_action( $key, $func, 11, count( $hook['args'] ) );
				$event_types[] = $hook['event_type'];
			}
		}

		WP_Helper::getArrayCache()->set( 'event_types', array_unique( $event_types ) );
		WP_Helper::getArrayCache()->set( 'dictionary', $dictionary );
	}

	/**
	 * Store all events to WPMUDEV cloud
	 */
	public static function onCloud( $data ) {
		if ( count( $data ) ) {
			self::openSocket();
			/**
			 * if data is more than one, means various event happened at once, we will need to group each by type
			 * and submit at bulk
			 */
			if ( count( $data ) > 1 ) {
				$groups     = array();
				$socket_log = array();
				foreach ( $data as $k => $val ) {
					if ( ! isset( $groups[ $val['event_type'] ] ) ) {
						$groups[ $val['event_type'] ] = array();
					}
					$groups[ $val['event_type'] ][] = $val;
				}
				//now regroup, and start to submit
				$new_data = array();
				foreach ( $groups as $k => $val ) {
					$tmp = array();
					foreach ( $val as $v ) {
						$tmp[] = $v['msg'];
					}
					$first        = array_shift( $val );
					$first['msg'] = implode( '; ', $tmp );
					$socket_log[] = $first['msg'];
					$new_data[]   = $first;
				}

				$data = $new_data;
			} elseif ( count( $data ) == 1 ) {
				$socket_log[] = $data[0]['msg'];
			}

			if ( self::socketToAPI( $data ) == false ) {
				self::curlToAPI( $data );
			}
		}
	}
}